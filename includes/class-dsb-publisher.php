<?php
/**
 * هماهنگ‌کننده‌ی انتخاب محتوا، ساخت پیام و ارسال.
 *
 * @package DailyBalePost
 */

defined( 'ABSPATH' ) || exit;

/**
 * انتشار یک مطلب در کانال‌های فعال (بله و تلگرام)، هر کدام با تنظیمات خودش.
 */
class DSB_Publisher {

	/**
	 * تنظیمات.
	 *
	 * @var DSB_Settings
	 */
	private $settings;

	/**
	 * تاریخچه.
	 *
	 * @var DSB_Logger
	 */
	private $logger;

	/**
	 * انتخاب‌گر محتوا.
	 *
	 * @var DSB_Content_Selector
	 */
	private $selector;

	/**
	 * سازنده‌ی پیام.
	 *
	 * @var DSB_Message
	 */
	private $message;

	/**
	 * سازنده.
	 *
	 * @param DSB_Settings $settings تنظیمات.
	 * @param DSB_Logger   $logger   تاریخچه.
	 */
	public function __construct( DSB_Settings $settings, DSB_Logger $logger ) {
		$this->settings = $settings;
		$this->logger   = $logger;
		$this->selector = new DSB_Content_Selector( $settings );
		$this->message  = new DSB_Message( $settings );
	}

	/**
	 * کلاینت بله با تنظیمات فعلی.
	 *
	 * @deprecated 4.1.0 به‌جای آن از messenger() استفاده کنید.
	 *
	 * @return DSB_Messenger
	 */
	public function api() {
		return $this->messenger( 'bale' );
	}

	/**
	 * ساخت کلاینت یک پیام‌رسان با تنظیمات فعلی.
	 *
	 * @param string $platform bale یا telegram.
	 * @return DSB_Messenger
	 */
	public function messenger( $platform ) {
		$destination = $this->settings->destination( $platform );

		if ( $destination ) {
			return $this->build( $destination );
		}

		return $this->build(
			array(
				'platform' => $platform,
				'token'    => '',
				'channel'  => '',
				'endpoint' => '',
			)
		);
	}

	/**
	 * کلاینت مقصدهایی که هم فعال‌اند و هم توکن و کانال دارند.
	 *
	 * @return DSB_Messenger[]
	 */
	public function messengers() {
		return wp_list_pluck( $this->ready_targets(), 'messenger' );
	}

	/**
	 * وضعیت هر کانال، برای نمایش در پنل.
	 *
	 * @return array<int,array{platform:string,label:string,enabled:bool,configured:bool,ready:bool,endpoint:string}>
	 */
	public function status() {
		$rows = array();

		foreach ( $this->settings->destinations() as $destination ) {
			$messenger  = $this->build( $destination );
			$configured = $messenger->is_configured();

			$rows[] = array(
				'platform'   => $destination['platform'],
				'label'      => $messenger->label(),
				'enabled'    => ! empty( $destination['enabled'] ),
				'configured' => $configured,
				'ready'      => ! empty( $destination['enabled'] ) && $configured,
				'endpoint'   => $messenger->endpoint(),
			);
		}

		return $rows;
	}

	/**
	 * مقصدهای آماده‌ی ارسال.
	 *
	 * برای کانال خاموش هیچ کلاینتی ساخته نمی‌شود، پس هیچ درخواستی هم نمی‌رود.
	 *
	 * @param string $only فقط این پلتفرم (خالی = همه).
	 * @return array<int,array{platform:string,messenger:DSB_Messenger,options:array}>
	 */
	private function ready_targets( $only = '' ) {
		$ready = array();

		foreach ( $this->settings->destinations() as $destination ) {
			if ( $only && $destination['platform'] !== $only ) {
				continue;
			}

			if ( empty( $destination['enabled'] ) ) {
				continue;
			}

			$messenger = $this->build( $destination );

			if ( ! $messenger->is_configured() ) {
				continue;
			}

			$ready[] = array(
				'platform'  => $destination['platform'],
				'messenger' => $messenger,
				'options'   => (array) ( $destination['options'] ?? array() ),
			);
		}

		return $ready;
	}

	/**
	 * ساخت کلاینت از پیکربندی یک مقصد.
	 *
	 * @param array $destination پیکربندی مقصد.
	 * @return DSB_Messenger
	 */
	private function build( array $destination ) {
		$messenger = DSB_Messenger::make(
			$destination['platform'],
			$destination['token'],
			$destination['channel'],
			(int) $this->settings->get( 'retries' )
		);

		if ( ! empty( $destination['endpoint'] ) ) {
			$messenger->with_endpoint( $destination['endpoint'] );
		}

		return $messenger;
	}

	/**
	 * علت آماده‌نبودن یک کانال، به زبان آدمیزاد.
	 *
	 * @param string $platform پلتفرم.
	 * @return string متن خطا، یا خالی اگر کانال آماده باشد.
	 */
	public function blocker( $platform ) {
		$destination = $this->settings->destination( $platform );

		if ( ! $destination ) {
			return __( 'این کانال شناخته نشد.', 'daily-bale-post' );
		}

		$label = $destination['label'];

		if ( empty( $destination['enabled'] ) ) {
			return sprintf(
				/* translators: %s: نام کانال. */
				__( 'ارسال به %s خاموش است؛ اول تیک فعال‌سازی همان کانال را بزنید.', 'daily-bale-post' ),
				$label
			);
		}

		if ( ! $this->build( $destination )->is_configured() ) {
			return sprintf(
				/* translators: %s: نام کانال. */
				__( 'توکن یا آیدی کانال %s تنظیم نشده است.', 'daily-bale-post' ),
				$label
			);
		}

		return '';
	}

	/**
	 * بررسی اتصال یک کانال، بدون ارسال پست.
	 *
	 * @param string $platform پلتفرم.
	 * @return array{ok:bool,message:string,raw:array}
	 */
	public function check( $platform ) {
		$blocker = $this->blocker( $platform );

		if ( $blocker ) {
			return array(
				'ok'      => false,
				'message' => $blocker,
				'raw'     => array(),
			);
		}

		$messenger = $this->messenger( $platform );
		$result    = $messenger->check();

		if ( empty( $result['ok'] ) ) {
			return array(
				'ok'      => false,
				'message' => DSB_Messenger::error_message( $result ),
				'raw'     => $result,
			);
		}

		$bot = $result['result']['username'] ?? ( $result['result']['first_name'] ?? '' );

		$message = $bot
			? sprintf(
				/* translators: 1: نام کانال، 2: نام بات. */
				__( 'اتصال %1$s سالم است؛ بات: %2$s', 'daily-bale-post' ),
				$messenger->label(),
				'@' . ltrim( (string) $bot, '@' )
			)
			: sprintf(
				/* translators: %s: نام کانال. */
				__( 'اتصال %s سالم است.', 'daily-bale-post' ),
				$messenger->label()
			);

		return array(
			'ok'      => true,
			'message' => $message,
			'raw'     => $result,
		);
	}

	/**
	 * پیش‌نمایش متن پیام یک کانال، بدون ارسال.
	 *
	 * @param string $platform پلتفرم.
	 * @return array{ok:bool,message:string,text:string,product:string}
	 */
	public function preview( $platform ) {
		$destination = $this->settings->destination( $platform );

		if ( ! $destination ) {
			return array(
				'ok'      => false,
				'message' => __( 'این کانال شناخته نشد.', 'daily-bale-post' ),
				'text'    => '',
				'product' => '—',
			);
		}

		$item = $this->selector->pick();

		if ( is_wp_error( $item ) ) {
			return array(
				'ok'      => false,
				'message' => $item->get_error_message(),
				'text'    => '',
				'product' => '—',
			);
		}

		$parts = $this->message->build( $item, (array) ( $destination['options'] ?? array() ) );

		return array(
			'ok'      => true,
			'message' => sprintf(
				/* translators: 1: نام کانال، 2: نام مطلب. */
				__( 'پیش‌نمایش پیام %1$s با مطلب «%2$s»', 'daily-bale-post' ),
				$destination['label'],
				get_the_title( $item )
			),
			'text'    => $parts['text'],
			'product' => get_the_title( $item ),
		);
	}

	/**
	 * انتشار یک مطلب در کانال‌های فعال.
	 *
	 * اگر حتی یک کانال موفق شود، ارسال امروز انجام‌شده حساب می‌شود؛
	 * خطای کانال‌های دیگر جداگانه در تاریخچه ثبت می‌شود.
	 *
	 * @param string $source منبع اجرا: auto | manual.
	 * @param string $only   فقط این پلتفرم (برای ارسال تستی یک کانال).
	 * @return array{ok:bool,message:string,product:string,raw:array,sent:array,failed:array}
	 *         کلید product نام مطلب ارسال‌شده است (نام قدیمی، برای سازگاری).
	 */
	public function publish( $source = 'auto', $only = '' ) {
		$targets = $this->ready_targets( $only );

		if ( ! $targets ) {
			$message = $only
				? $this->blocker( $only )
				: __( 'هیچ کانالی فعال و تنظیم‌شده نیست؛ توکن و آیدی کانال بله یا تلگرام را وارد کنید.', 'daily-bale-post' );

			if ( '' === $message ) {
				$message = __( 'کانالی برای ارسال پیدا نشد.', 'daily-bale-post' );
			}

			return $this->fail( '—', $message, $source );
		}

		$item = $this->selector->pick();

		if ( is_wp_error( $item ) ) {
			return $this->fail( '—', $item->get_error_message(), $source );
		}

		$context = $this->message->prepare( $item );
		$name    = get_the_title( $item );
		$raw     = array();
		$sent    = array();
		$failed  = array();

		foreach ( $targets as $target ) {
			$messenger = $target['messenger'];
			$platform  = $target['platform'];
			$label     = $messenger->label();
			$parts     = $this->message->compose( $context, $target['options'] );
			$result    = $this->send( $messenger, $parts, $target['options'] );

			$raw[ $platform ] = $result;

			if ( ! empty( $result['ok'] ) ) {
				$sent[ $platform ] = $label;

				$this->logger->add(
					DSB_Logger::STATE_OK,
					$name,
					$this->note( $label, $source ),
					$source
				);

				continue;
			}

			$error = DSB_Messenger::error_message( $result );

			$failed[ $platform ] = $label . ': ' . $error;

			$this->logger->add(
				DSB_Logger::STATE_FAIL,
				$name,
				sprintf(
					/* translators: 1: نام پیام‌رسان، 2: پیام خطا. */
					__( '%1$s — %2$s', 'daily-bale-post' ),
					$label,
					$error
				),
				$source
			);
		}

		if ( ! $sent ) {
			return array(
				'ok'      => false,
				'message' => implode( ' | ', $failed ),
				'product' => $name,
				'raw'     => $raw,
				'sent'    => array(),
				'failed'  => $failed,
			);
		}

		$this->selector->remember( $item->ID );

		$message = $this->note( implode( '، ', $sent ), $source );

		if ( $failed ) {
			$message .= ' — ' . sprintf(
				/* translators: %s: فهرست خطاهای کانال‌های ناموفق. */
				__( 'ناموفق: %s', 'daily-bale-post' ),
				implode( ' | ', $failed )
			);
		}

		return array(
			'ok'      => true,
			'message' => $message,
			'product' => $name,
			'raw'     => $raw,
			'sent'    => $sent,
			'failed'  => $failed,
		);
	}

	/**
	 * متن وضعیت ارسال موفق.
	 *
	 * @param string $labels نام کانال یا کانال‌ها.
	 * @param string $source منبع اجرا.
	 * @return string
	 */
	private function note( $labels, $source ) {
		return 'manual' === $source
			? sprintf(
				/* translators: %s: نام کانال‌ها. */
				__( 'ارسال شد به %s (دستی)', 'daily-bale-post' ),
				$labels
			)
			: sprintf(
				/* translators: %s: نام کانال‌ها. */
				__( 'ارسال شد به %s', 'daily-bale-post' ),
				$labels
			);
	}

	/**
	 * ارسال پیام با زنجیره‌ی جایگزین برای تصویر.
	 *
	 * ترتیب در حالت خودکار: لینک تصویر ← آپلود مستقیم فایل ← فقط متن.
	 * این زنجیره خطای «failed to get HTTP URL content» را خنثی می‌کند.
	 *
	 * @param DSB_Messenger $api     کلاینت.
	 * @param array         $parts   اجزای پیام.
	 * @param array         $options تنظیمات کانال.
	 * @return array
	 */
	private function send( DSB_Messenger $api, array $parts, array $options = array() ) {
		$mode   = DSB_Settings::clean_photo_mode( $options['photo_mode'] ?? '' );
		$markup = $parts['reply_markup'];
		$text   = $parts['text'];

		if ( 'none' === $mode || ( '' === $parts['photo_url'] && '' === $parts['photo_path'] ) ) {
			return $api->send_message( $text, $markup );
		}

		if ( 'upload' === $mode ) {
			$result = $api->send_photo_upload( $parts['photo_path'], $text, $markup );
			return ! empty( $result['ok'] ) ? $result : $api->send_message( $text, $markup );
		}

		$result = '' !== $parts['photo_url']
			? $api->send_photo_by_url( $parts['photo_url'], $text, $markup )
			: array( 'ok' => false );

		if ( ! empty( $result['ok'] ) || 'url' === $mode ) {
			return $result;
		}

		// حالت خودکار: پیام‌رسان نتوانست تصویر را از سایت بگیرد، خودمان آپلودش می‌کنیم.
		if ( '' !== $parts['photo_path'] ) {
			$uploaded = $api->send_photo_upload( $parts['photo_path'], $text, $markup );

			if ( ! empty( $uploaded['ok'] ) ) {
				return $uploaded;
			}
		}

		// آخرین تلاش: پست بدون تصویر بهتر از پستِ نرفته است.
		return $api->send_message( $text, $markup );
	}

	/**
	 * ثبت شکست در تاریخچه و ساخت پاسخ.
	 *
	 * @param string $name    نام مطلب.
	 * @param string $message پیام خطا.
	 * @param string $source  منبع اجرا.
	 * @param array  $raw     پاسخ خام.
	 * @return array
	 */
	private function fail( $name, $message, $source, $raw = array() ) {
		$this->logger->add( DSB_Logger::STATE_FAIL, $name, $message, $source );

		return array(
			'ok'      => false,
			'message' => $message,
			'product' => $name,
			'raw'     => $raw,
			'sent'    => array(),
			'failed'  => array(),
		);
	}

	/**
	 * دسترسی به انتخاب‌گر محتوا.
	 *
	 * @return DSB_Content_Selector
	 */
	public function selector() {
		return $this->selector;
	}
}
