<?php
/**
 * کلاینت پیام‌رسان تلگرام.
 *
 * @package Kanalyar
 */

defined( 'ABSPATH' ) || exit;

/**
 * تلگرام.
 *
 * نکته: روی سرورهای داخل ایران معمولاً دسترسی مستقیم به
 * api.telegram.org بسته است. در این حالت خطای «قطعی شبکه» در
 * تاریخچه ثبت می‌شود و باید پراکسی سمت سرور تنظیم شود.
 */
class KY_Messenger_Telegram extends KY_Messenger {

	/**
	 * ریشه‌ی API تلگرام.
	 *
	 * با فیلتر ky_telegram_endpoint می‌توان آن را به یک پراکسی تغییر داد.
	 *
	 * @return string
	 */
	public function endpoint() {
		/**
		 * بازنویسی نشانی API تلگرام، مثلاً برای عبور از پراکسی.
		 *
		 * @param string $endpoint نشانی پیش‌فرض.
		 */
		$endpoint = apply_filters( 'ky_telegram_endpoint', 'https://api.telegram.org/bot' );

		// نام فیلتر در نسخه‌های پیش از ۶.
		return (string) apply_filters( 'dsb_telegram_endpoint', $endpoint );
	}

	/**
	 * شناسه‌ی ماشینی.
	 *
	 * @return string
	 */
	public function platform() {
		return 'telegram';
	}

	/**
	 * نام نمایشی.
	 *
	 * @return string
	 */
	public function label() {
		return __( 'تلگرام', 'kanalyar' );
	}
}
