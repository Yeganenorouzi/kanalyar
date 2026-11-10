<?php
/**
 * تاریخچه‌ی ارسال.
 *
 * @package DailyBalePost
 */

defined( 'ABSPATH' ) || exit;

/**
 * نگه‌داری آخرین رویدادهای ارسال.
 */
class DSB_Logger {

	/**
	 * نام آپشن تاریخچه.
	 */
	const OPTION = 'dsb_log';

	/**
	 * بیشینه‌ی ردیف‌های نگه‌داشته‌شده.
	 */
	const LIMIT = 60;

	/**
	 * وضعیت‌های ممکن.
	 */
	const STATE_OK   = 'ok';
	const STATE_FAIL = 'fail';
	const STATE_SKIP = 'skip';

	/**
	 * افزودن یک ردیف.
	 *
	 * @param string $state  یکی از ثابت‌های STATE_*.
	 * @param string $name   نام محصول یا توضیح کوتاه.
	 * @param string $msg    پیام وضعیت.
	 * @param string $source منبع اجرا: auto | manual | repair.
	 * @return void
	 */
	public function add( $state, $name, $msg, $source = 'auto' ) {
		$log = $this->all();

		$log[] = array(
			'time'   => time(),
			'state'  => $state,
			'name'   => (string) $name,
			'msg'    => (string) $msg,
			'source' => (string) $source,
		);

		update_option( self::OPTION, array_slice( $log, -self::LIMIT ), false );
	}

	/**
	 * همه‌ی ردیف‌ها، از قدیم به جدید.
	 *
	 * @return array
	 */
	public function all() {
		$log = get_option( self::OPTION, array() );
		return is_array( $log ) ? $log : array();
	}

	/**
	 * ردیف‌ها از جدید به قدیم.
	 *
	 * @return array
	 */
	public function recent() {
		return array_reverse( $this->all() );
	}

	/**
	 * آخرین ارسال موفق.
	 *
	 * @return array|null
	 */
	public function last_success() {
		foreach ( $this->recent() as $row ) {
			if ( self::STATE_OK === ( $row['state'] ?? '' ) ) {
				return $row;
			}
		}
		return null;
	}

	/**
	 * آخرین ردیف ثبت‌شده با هر وضعیتی.
	 *
	 * @return array|null
	 */
	public function last() {
		$log = $this->all();
		return $log ? end( $log ) : null;
	}

	/**
	 * پاک‌کردن تاریخچه.
	 *
	 * @return void
	 */
	public function clear() {
		delete_option( self::OPTION );
	}
}
