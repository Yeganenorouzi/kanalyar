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
	public function endpoint() {
		return 'https://tapi.bale.ai/bot';
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
