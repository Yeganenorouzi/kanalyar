<?php
/**
 * تنظیمات سراسری افزونه.
 *
 * @package Kanalyar
 */

defined( 'ABSPATH' ) || exit;

/**
 * تنظیماتی که به یک قانون خاص مربوط نیستند.
 *
 * محتوا، مقصد و زمان‌بندی در KY_Rules و KY_Destinations نگه‌داری می‌شوند.
 */
class KY_Settings {

	/**
	 * نام آپشنی که تنظیمات سراسری در آن ذخیره می‌شود.
	 */
	const OPTION = 'dsb_settings';

	/**
	 * منطقه‌ی زمانی مرجع زمان‌بندی.
	 */
	const TIMEZONE = 'Asia/Tehran';

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
			'enabled'     => 1,
			'phone'       => '',
			'retries'     => 2,
			'alerts'      => 1,
			'alert_email' => '',
		);
	}

	/**
	 * حالت‌های مجاز ارسال تصویر.
	 *
	 * @return array<string,string>
	 */
	public static function photo_modes() {
		return array(
			'auto'   => __( 'خودکار — اول لینک، اگر نشد آپلود مستقیم (پیشنهادی)', 'kanalyar' ),
			'upload' => __( 'همیشه آپلود مستقیم فایل', 'kanalyar' ),
			'url'    => __( 'فقط ارسال لینک تصویر', 'kanalyar' ),
			'none'   => __( 'بدون تصویر (فقط متن)', 'kanalyar' ),
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

		return array_key_exists( $key, $all ) ? $all[ $key ] : $default;
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
	 * آیا عدد یک روز هفته‌ی معتبر است (۰ تا ۶).
	 *
	 * @param int $day شماره‌ی روز.
	 * @return bool
	 */
	public static function is_weekday( $day ) {
		return $day >= 0 && $day <= 6;
	}

	/**
	 * پاک‌سازی ورودی فرم تنظیمات سراسری.
	 *
	 * @param array $raw داده‌ی خام POST.
	 * @return array
	 */
	public function sanitize( array $raw ) {
		$defaults = self::defaults();

		$email = sanitize_email( $raw['alert_email'] ?? '' );

		return array(
			'enabled'     => empty( $raw['enabled'] ) ? 0 : 1,
			'alerts'      => empty( $raw['alerts'] ) ? 0 : 1,
			'phone'       => sanitize_text_field( $raw['phone'] ?? '' ),
			'alert_email' => is_email( $email ) ? $email : '',
			'retries'     => max( 0, min( 5, (int) ( $raw['retries'] ?? $defaults['retries'] ) ) ),
		);
	}

	/**
	 * نصب و مهاجرت.
	 *
	 * @param KY_Destinations $destinations مقصدها.
	 * @param KY_Rules        $rules        قوانین.
	 * @return void
	 */
	public function install( KY_Destinations $destinations, KY_Rules $rules ) {
		if ( ! get_option( KY_Health::INSTALLED_OPTION, 0 ) ) {
			update_option( KY_Health::INSTALLED_OPTION, time(), false );
		}

		$legacy = $this->collect_legacy();

		$this->save( wp_parse_args( $this->global_part( $legacy ), self::defaults() ) );

		// اگر هنوز مقصد و قانونی نساخته‌ایم، از تنظیمات قدیمی بسازشان.
		if ( ! $destinations->all() && ! $rules->all() ) {
			$this->seed_from_legacy( $legacy, $destinations, $rules );
		}
	}

	/**
	 * جداکردن بخش سراسریِ تنظیمات قدیمی.
	 *
	 * @param array $legacy تنظیمات قدیمی.
	 * @return array
	 */
	private function global_part( array $legacy ) {
		return array(
			'enabled'     => isset( $legacy['enabled'] ) ? (int) $legacy['enabled'] : 1,
			'phone'       => $legacy['phone'] ?? '',
			'retries'     => isset( $legacy['retries'] ) ? (int) $legacy['retries'] : 2,
			'alerts'      => isset( $legacy['alerts'] ) ? (int) $legacy['alerts'] : 1,
			'alert_email' => $legacy['alert_email'] ?? '',
		);
	}

	/**
	 * ساخت اولین مقصد و قانون از تنظیمات نسخه‌های قبل.
	 *
	 * @param array            $legacy       تنظیمات قدیمی.
	 * @param KY_Destinations $destinations مقصدها.
	 * @param KY_Rules        $rules        قوانین.
	 * @return void
	 */
	private function seed_from_legacy( array $legacy, KY_Destinations $destinations, KY_Rules $rules ) {
		$token   = (string) ( $legacy['token'] ?? '' );
		$channel = (string) ( $legacy['channel'] ?? '' );

		// نصب تازه: یک قانون و مقصد خالی بساز تا کاربر فقط پرشان کند.
		$destination_id = KY_Destinations::new_id();

		$destinations->save(
			array(
				array(
					'id'       => $destination_id,
					'label'    => '' !== $channel ? $channel : __( 'کانال بله', 'kanalyar' ),
					'platform' => 'bale',
					'token'    => $token,
					'chat_id'  => $channel,
					'enabled'  => 1,
				),
			)
		);

		$has_products = post_type_exists( 'product' );

		$rules->save(
			array(
				array_merge(
					KY_Rules::blank(),
					array(
						'id'           => KY_Rules::new_id(),
						'label'        => $has_products
							? __( 'محصولات روزانه', 'kanalyar' )
							: __( 'نوشته‌های روزانه', 'kanalyar' ),
						'enabled'      => 1,
						'post_types'   => $has_products ? array( 'product' ) : array( 'post' ),
						'terms'        => $this->legacy_terms( $legacy, $has_products ),
						'strategy'     => $has_products ? 'random' : 'newest',
						'instock_only' => isset( $legacy['instock'] ) ? (int) $legacy['instock'] : 1,
						'show_price'   => isset( $legacy['show_price'] ) ? (int) $legacy['show_price'] : 1,
						'show_excerpt' => 1,
						'avoid'        => isset( $legacy['avoid'] ) ? (int) $legacy['avoid'] : 20,
						'photo_mode'   => $legacy['photo_mode'] ?? 'auto',
						'destinations' => array( $destination_id ),
						'days'         => isset( $legacy['days'] ) ? (array) $legacy['days'] : range( 0, 6 ),
						'hour'         => isset( $legacy['hour'] ) ? (int) $legacy['hour'] : 10,
						'minute'       => isset( $legacy['minute'] ) ? (int) $legacy['minute'] : 45,
						'max_attempts' => isset( $legacy['max_attempts'] ) ? (int) $legacy['max_attempts'] : 3,
					)
				),
			)
		);
	}

	/**
	 * تبدیل فیلتر دسته‌بندی قدیمی به ساختار جدید.
	 *
	 * @param array $legacy       تنظیمات قدیمی.
	 * @param bool  $has_products آیا ووکامرس هست.
	 * @return array
	 */
	private function legacy_terms( array $legacy, $has_products ) {
		$cats = array_values( array_filter( array_map( 'absint', (array) ( $legacy['categories'] ?? array() ) ) ) );

		if ( ! $cats || ! $has_products ) {
			return array();
		}

		return array( 'product_cat' => $cats );
	}

	/**
	 * خواندن تنظیمات نسخه‌های قبلی، از هر دو قالب ۳.x و ۴.x.
	 *
	 * @return array
	 */
	private function collect_legacy() {
		$stored = get_option( self::OPTION, array() );

		if ( is_array( $stored ) && ! empty( $stored ) ) {
			return $stored;
		}

		$map = array(
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

		$out = array();

		foreach ( $map as $old => $new ) {
			$value = get_option( $old, null );

			if ( null === $value || false === $value ) {
				continue;
			}

			if ( in_array( $new, array( 'categories', 'days' ), true ) ) {
				$out[ $new ] = array_values( array_map( 'intval', (array) $value ) );
			} elseif ( in_array( $new, array( 'hour', 'minute', 'avoid', 'enabled', 'instock', 'show_price' ), true ) ) {
				$out[ $new ] = (int) $value;
			} else {
				$out[ $new ] = is_scalar( $value ) ? (string) $value : '';
			}
		}

		return $out;
	}
}
