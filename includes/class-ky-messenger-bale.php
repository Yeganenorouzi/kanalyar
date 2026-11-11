<?php
/**
 * کلاینت پیام‌رسان بله.
 *
 * @package Kanalyar
 */

defined( 'ABSPATH' ) || exit;

/**
 * بله.
 */
class KY_Messenger_Bale extends KY_Messenger {

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
		return __( 'بله', 'kanalyar' );
	}
}
