<?php
/**
 * اجرای یک قانون انتشار.
 *
 * @package Kanalyar
 */

defined( 'ABSPATH' ) || exit;

/**
 * انتخاب محتوا، ساخت پیام و ارسال به همه‌ی مقصدهای یک قانون.
 */
class KY_Publisher {

	/**
	 * تنظیمات سراسری.
	 *
	 * @var KY_Settings
	 */
	private $settings;

	/**
	 * تاریخچه.
	 *
	 * @var KY_Logger
	 */
	private $logger;

	/**
	 * مقصدها.
	 *
	 * @var KY_Destinations
	 */
	private $destinations;

	/**
	 * انتخاب‌گر محتوا.
	 *
	 * @var KY_Content_Selector
	 */
	private $selector;

	/**
	 * سازنده‌ی پیام.
	 *
	 * @var KY_Message
	 */
	private $message;

	/**
	 * سازنده.
	 *
	 * @param KY_Settings     $settings     تنظیمات.
	 * @param KY_Logger       $logger       تاریخچه.
	 * @param KY_Destinations $destinations مقصدها.
	 */
	public function __construct( KY_Settings $settings, KY_Logger $logger, KY_Destinations $destinations ) {
		$this->settings     = $settings;
		$this->logger       = $logger;
		$this->destinations = $destinations;
		$this->selector     = new KY_Content_Selector();
		$this->message      = new KY_Message( $settings );
	}

	/**
	 * اجرای یک قانون.
	 *
	 * اگر حداقل یک مقصد موفق باشد، نتیجه موفق است و مطلب در
	 * تاریخچه‌ی قانون ثبت می‌شود تا دوباره انتخاب نشود.
	 *
	 * @param array        $rule   قانون.
	 * @param string       $source منبع اجرا: auto | manual | publish.
	 * @param WP_Post|null $post   مطلب مشخص؛ خالی یعنی خودِ قانون انتخاب کند.
	 * @return array{ok:bool,message:string,title:string,results:array}
	 */
	public function publish( array $rule, $source = 'auto', $post = null ) {
		$targets = $this->destinations->pick( (array) $rule['destinations'] );

		if ( ! $targets ) {
			return $this->fail(
				$rule,
				'—',
				__( 'هیچ مقصد فعالی برای این قانون تنظیم نشده است.', 'kanalyar' ),
				$source
			);
		}

		if ( ! $post instanceof WP_Post ) {
			$post = $this->selector->pick( $rule );
		}

		if ( is_wp_error( $post ) ) {
			return $this->fail( $rule, '—', $post->get_error_message(), $source );
		}

		$parts   = $this->message->build( $post, $rule );
		$retries = (int) $this->settings->get( 'retries' );

		$results = array();
		$any_ok  = false;

		foreach ( $targets as $destination ) {
			$messenger = $this->messenger( $destination, $retries );
			$result    = $this->send( $messenger, $parts, $rule );
			$ok        = ! empty( $result['ok'] );
			$any_ok    = $any_ok || $ok;

			$results[] = array(
				'destination' => $destination['label'],
				'platform'    => $destination['platform'],
				'ok'          => $ok,
				'message'     => $ok ? __( 'ارسال شد', 'kanalyar' ) : KY_Messenger::error_message( $result ),
				'raw'         => $result,
			);

			$this->logger->add(
				$ok ? KY_Logger::STATE_OK : KY_Logger::STATE_FAIL,
				$parts['title'],
				$ok ? __( 'ارسال شد', 'kanalyar' ) : KY_Messenger::error_message( $result ),
				$source,
				$rule['label'],
				$destination['label']
			);
		}

		if ( $any_ok ) {
			$this->selector->remember( $rule, $post->ID );
		}

		return array(
			'ok'      => $any_ok,
			'message' => $this->summary( $results ),
			'title'   => $parts['title'],
			'results' => $results,
		);
	}

	/**
	 * ساخت کلاینت یک مقصد. برای تست قابل بازنویسی است.
	 *
	 * @param array $destination مقصد.
	 * @param int   $retries     تلاش مجدد.
	 * @return KY_Messenger
	 */
	protected function messenger( array $destination, $retries ) {
		return KY_Destinations::messenger( $destination, $retries );
	}

	/**
	 * خلاصه‌ی نتیجه‌ی همه‌ی مقصدها.
	 *
	 * @param array $results نتایج.
	 * @return string
	 */
	private function summary( array $results ) {
		$ok = 0;

		foreach ( $results as $row ) {
			if ( $row['ok'] ) {
				$ok++;
			}
		}

		$total = count( $results );

		if ( $ok === $total ) {
			/* translators: %d: تعداد مقصد. */
			return sprintf( _n( 'به %d مقصد ارسال شد', 'به %d مقصد ارسال شد', $total, 'kanalyar' ), $total );
		}

		if ( 0 === $ok ) {
			return __( 'ارسال به هیچ مقصدی موفق نبود.', 'kanalyar' );
		}

		return sprintf(
			/* translators: ۱: تعداد موفق، ۲: تعداد کل. */
			__( 'به %1$d مقصد از %2$d مقصد ارسال شد.', 'kanalyar' ),
			$ok,
			$total
		);
	}

	/**
	 * ارسال پیام با زنجیره‌ی جایگزین برای تصویر.
	 *
	 * ترتیب در حالت خودکار: لینک تصویر ← آپلود مستقیم فایل ← فقط متن.
	 * این زنجیره خطای «failed to get HTTP URL content» را خنثی می‌کند.
	 *
	 * @param KY_Messenger $api   کلاینت.
	 * @param array         $parts اجزای پیام.
	 * @param array         $rule  قانون.
	 * @return array
	 */
	private function send( KY_Messenger $api, array $parts, array $rule ) {
		$mode = (string) $rule['photo_mode'];

		// هر پیام‌رسان قالب‌بندی و دکمه را پشتیبانی نمی‌کند؛ ایتا هیچ‌کدام را.
		$text   = $api->supports_markdown() ? $parts['text'] : $parts['plain'];
		$markup = $api->supports_buttons() ? $parts['reply_markup'] : null;

		$api->with(
			array(
				'silent' => ! empty( $rule['silent'] ),
				'pin'    => ! empty( $rule['pin'] ),
			)
		);

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

		// حالت خودکار: سرور نتوانست تصویر را از سایت بگیرد، خودمان آپلودش می‌کنیم.
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
	 * @param array  $rule    قانون.
	 * @param string $title   عنوان مطلب.
	 * @param string $message پیام خطا.
	 * @param string $source  منبع اجرا.
	 * @return array
	 */
	private function fail( array $rule, $title, $message, $source ) {
		$this->logger->add( KY_Logger::STATE_FAIL, $title, $message, $source, $rule['label'], '—' );

		return array(
			'ok'      => false,
			'message' => $message,
			'title'   => $title,
			'results' => array(),
		);
	}

	/**
	 * دسترسی به انتخاب‌گر محتوا.
	 *
	 * @return KY_Content_Selector
	 */
	public function selector() {
		return $this->selector;
	}
}
