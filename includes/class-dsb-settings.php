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
			'enabled'              => 1,

			// بله.
			'bale_enabled'         => 1,
			'token'                => '',
			'channel'              => '',
			'bale_template'        => '',
			'bale_photo_mode'      => 'auto',
			'bale_button'          => 1,
			'bale_button_text'     => '',

			// تلگرام.
			'telegram_enabled'     => 0,
			'telegram_token'       => '',
			'telegram_channel'     => '',
			'telegram_endpoint'    => '',
			'telegram_template'    => '',
			'telegram_photo_mode'  => 'auto',
			'telegram_button'      => 1,
			'telegram_button_text' => '',

			'phone'                => '',
			'hour'                 => 10,
			'minute'               => 45,
			'post_types'           => array( 'product' ),
			'terms'                => array(),
			'categories'           => array(),
			'avoid'                => 10,
			'instock'              => 1,
			'show_price'           => 1,
			'days'                 => array( 0, 1, 2, 3, 4, 5, 6 ),
			'photo_mode'           => 'auto',
			'retries'              => 2,
			'max_attempts'         => 3,
			'alerts'               => 1,
			'alert_email'          => '',
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

		$clean['bale_enabled']     = empty( $raw['bale_enabled'] ) ? 0 : 1;
		$clean['telegram_enabled'] = empty( $raw['telegram_enabled'] ) ? 0 : 1;

		$clean['bale_button']     = empty( $raw['bale_button'] ) ? 0 : 1;
		$clean['telegram_button'] = empty( $raw['telegram_button'] ) ? 0 : 1;

		$clean['token']   = sanitize_text_field( wp_unslash( $raw['token'] ?? '' ) );
		$clean['channel'] = sanitize_text_field( wp_unslash( $raw['channel'] ?? '' ) );
		$clean['phone']   = sanitize_text_field( wp_unslash( $raw['phone'] ?? '' ) );

		$clean['telegram_token']   = sanitize_text_field( wp_unslash( $raw['telegram_token'] ?? '' ) );
		$clean['telegram_channel'] = sanitize_text_field( wp_unslash( $raw['telegram_channel'] ?? '' ) );

		$clean['telegram_endpoint'] = self::clean_endpoint( $raw['telegram_endpoint'] ?? '' );

		$clean['bale_template']     = self::clean_template( $raw['bale_template'] ?? '' );
		$clean['telegram_template'] = self::clean_template( $raw['telegram_template'] ?? '' );

		$clean['bale_button_text']     = sanitize_text_field( wp_unslash( $raw['bale_button_text'] ?? '' ) );
		$clean['telegram_button_text'] = sanitize_text_field( wp_unslash( $raw['telegram_button_text'] ?? '' ) );

		$clean['bale_photo_mode']     = self::clean_photo_mode( $raw['bale_photo_mode'] ?? '' );
		$clean['telegram_photo_mode'] = self::clean_photo_mode( $raw['telegram_photo_mode'] ?? '' );

		$email                = sanitize_email( wp_unslash( $raw['alert_email'] ?? '' ) );
		$clean['alert_email'] = is_email( $email ) ? $email : '';

		$clean['hour']   = max( 0, min( 23, (int) ( $raw['hour'] ?? $defaults['hour'] ) ) );
		$clean['minute'] = max( 0, min( 59, (int) ( $raw['minute'] ?? $defaults['minute'] ) ) );
		$clean['avoid']  = max( 0, min( 500, (int) ( $raw['avoid'] ?? $defaults['avoid'] ) ) );

		$clean['retries']      = max( 0, min( 5, (int) ( $raw['retries'] ?? $defaults['retries'] ) ) );
		$clean['max_attempts'] = max( 1, min( 10, (int) ( $raw['max_attempts'] ?? $defaults['max_attempts'] ) ) );

		$types = isset( $raw['post_types'] ) ? (array) $raw['post_types'] : array();
		$types = array_map( 'sanitize_key', $types );

		$clean['post_types'] = array_values( array_unique( array_filter( $types ) ) );

		$terms = isset( $raw['terms'] ) ? (array) $raw['terms'] : array();

		$clean['terms'] = self::clean_terms( $terms );

		$days          = isset( $raw['days'] ) ? (array) $raw['days'] : array();
		$days          = array_map( 'intval', $days );
		$days          = array_values( array_unique( array_filter( $days, array( __CLASS__, 'is_weekday' ) ) ) );
		sort( $days );
		$clean['days'] = $days;

		return $clean;
	}

	/**
	 * پاک‌سازی حالت ارسال تصویر.
	 *
	 * @param string $raw مقدار خام.
	 * @return string
	 */
	public static function clean_photo_mode( $raw ) {
		$mode = sanitize_key( (string) $raw );

		return array_key_exists( $mode, self::photo_modes() )
			? $mode
			: self::defaults()['photo_mode'];
	}

	/**
	 * پاک‌سازی قالب متن پیام.
	 *
	 * قالب متن ساده است (مارک‌داونِ بله و تلگرام)، پس تگ HTML حذف می‌شود
	 * ولی خط‌های جدید و شورت‌کدها دست‌نخورده می‌مانند.
	 *
	 * @param string $raw مقدار خام.
	 * @return string
	 */
	public static function clean_template( $raw ) {
		$text = (string) wp_unslash( $raw );
		$text = str_replace( array( "\r\n", "\r" ), "\n", $text );
		$text = sanitize_textarea_field( $text );
		$text = trim( $text );

		if ( mb_strlen( $text ) > DSB_Template::MAX_LENGTH ) {
			$text = mb_substr( $text, 0, DSB_Template::MAX_LENGTH );
		}

		// قالبی که با پیش‌فرض یکی است، خالی ذخیره می‌شود تا با
		// به‌روزرسانی‌های بعدیِ پیش‌فرض هم‌گام بماند.
		return DSB_Template::default_template() === $text ? '' : $text;
	}

	/**
	 * پاک‌سازی فهرست ترم‌های انتخاب‌شده.
	 *
	 * هر مقدار به شکل «طبقه‌بندی:شناسه‌ی ترم» است، مثل product_cat:12 .
	 *
	 * @param array $raw فهرست خام.
	 * @return string[]
	 */
	public static function clean_terms( array $raw ) {
		$clean = array();

		foreach ( $raw as $value ) {
			$value = sanitize_text_field( wp_unslash( $value ) );

			if ( ! preg_match( '/^([a-z0-9_\-]+):(\d+)$/i', $value, $match ) ) {
				continue;
			}

			$term_id = absint( $match[2] );

			if ( ! $term_id ) {
				continue;
			}

			$clean[] = sanitize_key( $match[1] ) . ':' . $term_id;
		}

		return array_values( array_unique( $clean ) );
	}

	/**
	 * ترم‌های انتخاب‌شده، گروه‌بندی‌شده بر اساس طبقه‌بندی.
	 *
	 * @return array<string,int[]>
	 */
	public function term_map() {
		$map = array();

		foreach ( (array) $this->get( 'terms' ) as $value ) {
			$parts = explode( ':', (string) $value );

			if ( 2 !== count( $parts ) ) {
				continue;
			}

			$taxonomy = sanitize_key( $parts[0] );
			$term_id  = absint( $parts[1] );

			if ( ! $taxonomy || ! $term_id ) {
				continue;
			}

			$map[ $taxonomy ][] = $term_id;
		}

		foreach ( $map as $taxonomy => $ids ) {
			$map[ $taxonomy ] = array_values( array_unique( $ids ) );
		}

		return $map;
	}

	/**
	 * پاک‌سازی نشانی پراکسی API.
	 *
	 * نشانی باید با http یا https شروع شود. اگر کاربر فقط دامنه را
	 * وارد کند، ‎/bot به آن اضافه می‌شود تا با قرارداد Bot API بخواند.
	 *
	 * @param string $raw نشانی خام.
	 * @return string
	 */
	public static function clean_endpoint( $raw ) {
		$url = trim( (string) wp_unslash( $raw ) );

		if ( '' === $url ) {
			return '';
		}

		$url = esc_url_raw( $url );

		if ( '' === $url || ! preg_match( '#^https?://#i', $url ) ) {
			return '';
		}

		$url = rtrim( $url, '/' );

		if ( ! preg_match( '#bot$#i', $url ) ) {
			$url .= '/bot';
		}

		return $url;
	}

	/**
	 * پیکربندی مقصدهای ارسال (فقط داده، بدون ساخت کلاینت).
	 *
	 * @return array<int,array{platform:string,enabled:bool,token:string,channel:string,endpoint:string}>
	 */
	public function destinations() {
		return array(
			array(
				'platform' => 'bale',
				'label'    => DSB_Messenger::platform_label( 'bale' ),
				'enabled'  => (bool) $this->get( 'bale_enabled' ),
				'token'    => (string) $this->get( 'token' ),
				'channel'  => (string) $this->get( 'channel' ),
				'endpoint' => '',
				'options'  => array(
					'template'    => (string) $this->get( 'bale_template' ),
					'photo_mode'  => self::clean_photo_mode( $this->get( 'bale_photo_mode' ) ),
					'button'      => (bool) $this->get( 'bale_button' ),
					'button_text' => (string) $this->get( 'bale_button_text' ),
				),
			),
			array(
				'platform' => 'telegram',
				'label'    => DSB_Messenger::platform_label( 'telegram' ),
				'enabled'  => (bool) $this->get( 'telegram_enabled' ),
				'token'    => (string) $this->get( 'telegram_token' ),
				'channel'  => (string) $this->get( 'telegram_channel' ),
				'endpoint' => (string) $this->get( 'telegram_endpoint' ),
				'options'  => array(
					'template'    => (string) $this->get( 'telegram_template' ),
					'photo_mode'  => self::clean_photo_mode( $this->get( 'telegram_photo_mode' ) ),
					'button'      => (bool) $this->get( 'telegram_button' ),
					'button_text' => (string) $this->get( 'telegram_button_text' ),
				),
			),
		);
	}

	/**
	 * پیکربندی یک مقصد با نام پلتفرم.
	 *
	 * @param string $platform bale یا telegram.
	 * @return array|null
	 */
	public function destination( $platform ) {
		foreach ( $this->destinations() as $destination ) {
			if ( $destination['platform'] === $platform ) {
				return $destination;
			}
		}

		return null;
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
			$values = $this->migrate_channels( wp_parse_args( $stored, self::defaults() ), $stored );

			$this->save( $this->migrate_terms( $values ) );
			return;
		}

		$this->save( $this->migrate_terms( $this->collect_legacy() ) );
	}

	/**
	 * انتقال حالت ارسال تصویرِ عمومی نسخه‌های پیش از ۴.۳ به هر دو کانال.
	 *
	 * @param array $values تنظیمات کامل‌شده.
	 * @param array $stored تنظیمات خامِ ذخیره‌شده.
	 * @return array
	 */
	private function migrate_channels( array $values, array $stored ) {
		$legacy_mode = isset( $stored['photo_mode'] ) ? self::clean_photo_mode( $stored['photo_mode'] ) : '';

		if ( '' === $legacy_mode ) {
			return $values;
		}

		foreach ( array( 'bale_photo_mode', 'telegram_photo_mode' ) as $key ) {
			if ( ! isset( $stored[ $key ] ) ) {
				$values[ $key ] = $legacy_mode;
			}
		}

		return $values;
	}

	/**
	 * انتقال «دسته‌بندی محصولات» نسخه‌های پیش از ۴.۲ به فهرست ترم‌ها.
	 *
	 * کلید categories دست نمی‌خورد تا اگر کاربر به نسخه‌ی قبل برگشت،
	 * تنظیمش را از دست نداده باشد.
	 *
	 * @param array $values تنظیمات.
	 * @return array
	 */
	private function migrate_terms( array $values ) {
		if ( ! empty( $values['terms'] ) ) {
			return $values;
		}

		$categories = array_filter( array_map( 'absint', (array) ( $values['categories'] ?? array() ) ) );

		if ( ! $categories ) {
			return $values;
		}

		foreach ( $categories as $term_id ) {
			$values['terms'][] = 'product_cat:' . $term_id;
		}

		return $values;
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
