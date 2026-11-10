<?php
/**
 * هماهنگ‌کننده‌ی انتخاب محصول، ساخت پیام و ارسال.
 *
 * @package DailyBalePost
 */

defined( 'ABSPATH' ) || exit;

/**
 * انتشار یک محصول در کانال بله.
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
	 * انتخاب‌گر محصول.
	 *
	 * @var DSB_Product_Selector
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
		$this->selector = new DSB_Product_Selector( $settings );
		$this->message  = new DSB_Message( $settings );
	}

	/**
	 * ساخت کلاینت API با تنظیمات فعلی.
	 *
	 * @return DSB_Bale_API
	 */
	public function api() {
		return new DSB_Bale_API(
			$this->settings->get( 'token' ),
			$this->settings->get( 'channel' ),
			(int) $this->settings->get( 'retries' )
		);
	}

	/**
	 * انتشار یک محصول.
	 *
	 * @param string $source منبع اجرا: auto | manual.
	 * @return array{ok:bool,message:string,product:string,raw:array}
	 */
	public function publish( $source = 'auto' ) {
		$api = $this->api();

		if ( ! $api->is_configured() ) {
			return $this->fail( '—', __( 'توکن یا آیدی کانال تنظیم نشده است.', 'daily-bale-post' ), $source );
		}

		$product = $this->selector->pick();

		if ( is_wp_error( $product ) ) {
			return $this->fail( '—', $product->get_error_message(), $source );
		}

		$parts  = $this->message->build( $product );
		$result = $this->send( $api, $parts );

		if ( empty( $result['ok'] ) ) {
			return $this->fail(
				$product->get_name(),
				DSB_Bale_API::error_message( $result ),
				$source,
				$result
			);
		}

		$this->selector->remember( $product->get_id() );

		$note = 'manual' === $source
			? __( 'ارسال شد (دستی)', 'daily-bale-post' )
			: __( 'ارسال شد', 'daily-bale-post' );

		$this->logger->add( DSB_Logger::STATE_OK, $product->get_name(), $note, $source );

		return array(
			'ok'      => true,
			'message' => $note,
			'product' => $product->get_name(),
			'raw'     => $result,
		);
	}

	/**
	 * ارسال پیام با زنجیره‌ی جایگزین برای تصویر.
	 *
	 * ترتیب در حالت خودکار: لینک تصویر ← آپلود مستقیم فایل ← فقط متن.
	 * این زنجیره خطای «failed to get HTTP URL content» را خنثی می‌کند.
	 *
	 * @param DSB_Bale_API $api   کلاینت.
	 * @param array        $parts اجزای پیام.
	 * @return array
	 */
	private function send( DSB_Bale_API $api, array $parts ) {
		$mode   = (string) $this->settings->get( 'photo_mode' );
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

		// حالت خودکار: بله نتوانست تصویر را از سایت بگیرد، خودمان آپلودش می‌کنیم.
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
	 * @param string $name    نام محصول.
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
		);
	}

	/**
	 * دسترسی به انتخاب‌گر محصول.
	 *
	 * @return DSB_Product_Selector
	 */
	public function selector() {
		return $this->selector;
	}
}
