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
	const LIMIT = 120;

	/**
	 * وضعیت‌های ممکن.
	 */
	const STATE_OK   = 'ok';
	const STATE_FAIL = 'fail';
	const STATE_SKIP = 'skip';

	/**
	 * افزودن یک ردیف.
	 *
	 * @param string $state       یکی از ثابت‌های STATE_*.
	 * @param string $title       عنوان مطلب یا توضیح کوتاه.
	 * @param string $msg         پیام وضعیت.
	 * @param string $source      منبع اجرا: auto | manual.
	 * @param string $rule        نام قانون.
	 * @param string $destination نام مقصد.
	 * @return void
	 */
	public function add( $state, $title, $msg, $source = 'auto', $rule = '', $destination = '' ) {
		$log = $this->all();

		$log[] = array(
			'time'        => time(),
			'state'       => $state,
			'name'        => (string) $title,
			'msg'         => (string) $msg,
			'source'      => (string) $source,
			'rule'        => (string) $rule,
			'destination' => (string) $destination,
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
	 * @param int $limit حداکثر تعداد.
	 * @return array
	 */
	public function recent( $limit = 0 ) {
		$rows = array_reverse( $this->all() );

		return $limit > 0 ? array_slice( $rows, 0, $limit ) : $rows;
	}

	/**
	 * آخرین ارسال موفق، به‌صورت کلی یا برای یک قانون.
	 *
	 * @param string $rule نام قانون؛ خالی یعنی همه.
	 * @return array|null
	 */
	public function last_success( $rule = '' ) {
		foreach ( $this->recent() as $row ) {
			if ( self::STATE_OK !== ( $row['state'] ?? '' ) ) {
				continue;
			}

			if ( '' !== $rule && ( $row['rule'] ?? '' ) !== $rule ) {
				continue;
			}

			return $row;
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
