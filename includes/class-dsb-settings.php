<?php
/**
 * مدیریت تنظیمات افزونه.
 *
 * @package DailyBalePost
 */

defined( 'ABSPATH' ) || exit;

/**
 * خواندن، اعتبارسنجی و ذخیره‌ی تنظیمات.
 */
class DSB_Settings {

	/**
	 * نام آپشنی که همه‌ی تنظیمات در آن ذخیره می‌شود.
	 */
	const OPTION = 'dsb_settings';

	/**
	 * منطقه‌ی زمانی مرجع زمان‌بندی.
	 */
	const TIMEZONE = 'Asia/Tehran';

	/**
	 * نگاشت آپشن‌های تک‌تکِ نسخه‌ی ۳ به کلیدهای جدید.
	 *
	 * @var array<string,string>
	 */
	private static $legacy_map = array(
		'dsb_token'      => 'token',
		'dsb_channel'    => 'channel',
		'dsb_phone'      => 'phone',
		'dsb_hour'       => 'hour',
		'dsb_minute'     => 'minute',
		'dsb_enabled'    => 'enabled',
		'dsb_categories' => 'categories',
		'dsb_avoid'      => 'avoid',
		'dsb_instock'    => 'instock',
		'dsb_days'       => 'days',
		'dsb_show_price' => 'show_price',
	);

	/**
	 * کش درون‌درخواستی تنظیمات.
	 *
	 * @var array|null
	 */
	private $cache = null;

	/**
	 * مقادیر پیش‌فرض.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'enabled'      => 1,
			'token'        => '',
			'channel'      => '',
			'phone'        => '',
			'hour'         => 10,
			'minute'       => 45,
			'categories'   => array(),
			'avoid'        => 10,
			'instock'      => 1,
			'show_price'   => 1,
			'days'         => array( 0, 1, 2, 3, 4, 5, 6 ),
			'photo_mode'   => 'auto',
			'retries'      => 2,
			'max_attempts' => 3,
			'alerts'       => 1,
			'alert_email'  => '',
		);
	}

	/**
	 * حالت‌های مجاز ارسال تصویر.
	 *
	 * @return array<string,string>
	 */
	public static function photo_modes() {
		return array(
			'auto'   => __( 'خودکار — اول لینک، اگر نشد آپلود مستقیم (پیشنهادی)', 'daily-bale-post' ),
			'upload' => __( 'همیشه آپلود مستقیم فایل', 'daily-bale-post' ),
			'url'    => __( 'فقط ارسال لینک تصویر', 'daily-bale-post' ),
			'none'   => __( 'بدون تصویر (فقط متن)', 'daily-bale-post' ),
		);
	}

	/**
	 * همه‌ی تنظیمات.
	 *
	 * @return array
	 */
	public function all() {
		if ( null === $this->cache ) {
			$stored      = get_option( self::OPTION, array() );
			$stored      = is_array( $stored ) ? $stored : array();
			$this->cache = wp_parse_args( $stored, self::defaults() );
		}
		return $this->cache;
	}

	/**
	 * یک تنظیم.
	 *
	 * @param string $key     کلید.
	 * @param mixed  $default مقدار جایگزین اگر کلید وجود نداشت.
	 * @return mixed
	 */
	public function get( $key, $default = null ) {
		$all = $this->all();

		if ( array_key_exists( $key, $all ) ) {
			return $all[ $key ];
		}

		return $default;
	}

	/**
	 * ذخیره‌ی تنظیمات (ادغام با مقادیر فعلی).
	 *
	 * @param array $values مقادیر پاک‌سازی‌شده.
	 * @return void
	 */
	public function save( array $values ) {
		$merged = array_merge( $this->all(), $values );
		update_option( self::OPTION, $merged, true );
		$this->cache = $merged;
	}

	/**
	 * نشانی ایمیل مقصدِ هشدارها.
	 *
	 * @return string
	 */
	public function alert_email() {
		$email = trim( (string) $this->get( 'alert_email' ) );

		if ( ! $email || ! is_email( $email ) ) {
			$email = get_option( 'admin_email' );
		}

		return $email;
	}

	/**
	 * آبجکت زمان «الان» به وقت تهران.
	 *
	 * @return DateTimeImmutable
	 */
	public static function now() {
		return new DateTimeImmutable( 'now', new DateTimeZone( self::TIMEZONE ) );
	}

	/**
	 * پاک‌سازی ورودی فرم تنظیمات.
	 *
	 * @param array $raw داده‌ی خام POST.
	 * @return array
	 */
	public function sanitize( array $raw ) {
		$defaults = self::defaults();
		$clean    = array();

		$clean['enabled']    = empty( $raw['enabled'] ) ? 0 : 1;
		$clean['instock']    = empty( $raw['instock'] ) ? 0 : 1;
		$clean['show_price'] = empty( $raw['show_price'] ) ? 0 : 1;
		$clean['alerts']     = empty( $raw['alerts'] ) ? 0 : 1;

		$clean['token']   = sanitize_text_field( wp_unslash( $raw['token'] ?? '' ) );
		$clean['channel'] = sanitize_text_field( wp_unslash( $raw['channel'] ?? '' ) );
		$clean['phone']   = sanitize_text_field( wp_unslash( $raw['phone'] ?? '' ) );

		$email                = sanitize_email( wp_unslash( $raw['alert_email'] ?? '' ) );
		$clean['alert_email'] = is_email( $email ) ? $email : '';

		$clean['hour']   = max( 0, min( 23, (int) ( $raw['hour'] ?? $defaults['hour'] ) ) );
		$clean['minute'] = max( 0, min( 59, (int) ( $raw['minute'] ?? $defaults['minute'] ) ) );
		$clean['avoid']  = max( 0, min( 500, (int) ( $raw['avoid'] ?? $defaults['avoid'] ) ) );

		$clean['retries']      = max( 0, min( 5, (int) ( $raw['retries'] ?? $defaults['retries'] ) ) );
		$clean['max_attempts'] = max( 1, min( 10, (int) ( $raw['max_attempts'] ?? $defaults['max_attempts'] ) ) );

		$mode                = sanitize_key( $raw['photo_mode'] ?? $defaults['photo_mode'] );
		$clean['photo_mode'] = array_key_exists( $mode, self::photo_modes() ) ? $mode : $defaults['photo_mode'];

		$cats                = isset( $raw['categories'] ) ? (array) $raw['categories'] : array();
		$clean['categories'] = array_values( array_filter( array_map( 'absint', $cats ) ) );

		$days          = isset( $raw['days'] ) ? (array) $raw['days'] : array();
		$days          = array_map( 'intval', $days );
		$days          = array_values( array_unique( array_filter( $days, array( __CLASS__, 'is_weekday' ) ) ) );
		sort( $days );
		$clean['days'] = $days;

		return $clean;
	}

	/**
	 * آیا عدد یک روز هفته‌ی معتبر است (۰ تا ۶).
	 *
	 * @param int $day شماره‌ی روز.
	 * @return bool
	 */
	public static function is_weekday( $day ) {
		return $day >= 0 && $day <= 6;
	}

	/**
	 * نصب: ساخت آپشن و انتقال تنظیمات نسخه‌های قبلی.
	 *
	 * @return void
	 */
	public function install() {
		if ( ! get_option( DSB_Health::INSTALLED_OPTION, 0 ) ) {
			update_option( DSB_Health::INSTALLED_OPTION, time(), false );
		}

		$stored = get_option( self::OPTION, null );

		if ( is_array( $stored ) && ! empty( $stored ) ) {
			// آپشن جدید وجود دارد؛ فقط کلیدهای تازه را اضافه کن.
			$this->save( wp_parse_args( $stored, self::defaults() ) );
			return;
		}

		$this->save( $this->collect_legacy() );
	}

	/**
	 * خواندن تنظیمات نسخه‌ی ۳ از آپشن‌های جداگانه.
	 *
	 * @return array
	 */
	private function collect_legacy() {
		$values = self::defaults();

		foreach ( self::$legacy_map as $old_key => $new_key ) {
			$legacy = get_option( $old_key, null );

			if ( null === $legacy || false === $legacy ) {
				continue;
			}

			if ( is_array( $values[ $new_key ] ) ) {
				$values[ $new_key ] = array_values( array_filter( array_map( 'intval', (array) $legacy ), array( __CLASS__, 'is_weekday' ) ) );

				if ( 'categories' === $new_key ) {
					$values[ $new_key ] = array_values( array_filter( array_map( 'absint', (array) $legacy ) ) );
				}
			} elseif ( is_int( $values[ $new_key ] ) ) {
				$values[ $new_key ] = (int) $legacy;
			} else {
				$values[ $new_key ] = is_scalar( $legacy ) ? (string) $legacy : '';
			}
		}

		return $values;
	}
}
