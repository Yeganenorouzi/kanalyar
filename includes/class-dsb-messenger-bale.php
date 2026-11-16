<?php
/**
 * کلاینت پیام‌رسان بله.
 *
 * @package DailyBalePost
 */

defined( 'ABSPATH' ) || exit;

/**
 * بله.
 */
class DSB_Messenger_Bale extends DSB_Messenger {

	/**
	 * ریشه‌ی API بله.
	 *
	 * @return string
	 */
	public function default_endpoint() {
		/**
		 * بازنویسی نشانی API بله.
		 *
		 * @param string $endpoint نشانی پیش‌فرض.
		 */
		return (string) apply_filters( 'dsb_bale_endpoint', 'https://tapi.bale.ai/bot' );
	}

	/**
	 * شناسه‌ی ماشینی.
	 *
	 * @return string
	 */
	public function platform() {
		return 'bale';
	}

	/**
	 * نام نمایشی.
	 *
	 * @return string
	 */
	public function label() {
		return __( 'بله', 'daily-bale-post' );
	}
}
