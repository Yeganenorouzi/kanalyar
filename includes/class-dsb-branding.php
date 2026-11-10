<?php
/**
 * برندینگ اختیاری افزونه.
 *
 * @package DailyBalePost
 */

defined( 'ABSPATH' ) || exit;

/**
 * برندینگ به‌صورت پیکربندی، نه کد.
 *
 * افزونه به‌صورت پیش‌فرض هیچ برندی ندارد. یک آژانس یا فروشنده می‌تواند
 * بدون دست‌زدن به کد، نام و لوگوی خودش را فعال کند. به این ترتیب
 * نسخه‌ی تمیز و نسخه‌ی برنددار یک کدِ واحد دارند و لازم نیست
 * دو نسخه‌ی موازی نگه‌داری و با هم هماهنگ شوند.
 *
 * ترتیب اولویت:
 *   ۱. فیلتر dsb_branding
 *   ۲. ثابت DSB_BRANDING در wp-config.php (نام پروفایل)
 *   ۳. فایل branding/active.php که اسکریپت build می‌سازد
 *   ۴. هیچ‌کدام → بدون برند
 */
class DSB_Branding {

	/**
	 * پوشه‌ی پروفایل‌های برندینگ.
	 */
	const DIR = 'branding';

	/**
	 * فایلی که پروفایل فعال را اعلام می‌کند (خروجی اسکریپت build).
	 */
	const ACTIVE_FILE = 'branding/active.php';

	/**
	 * کش درون‌درخواستی.
	 *
	 * @var array|null|false false یعنی هنوز محاسبه نشده.
	 */
	private static $cache = false;

	/**
	 * پروفایل برندینگ فعال.
	 *
	 * @return array|null آرایه‌ی برندینگ، یا null اگر برندی فعال نباشد.
	 */
	public static function profile() {
		if ( false !== self::$cache ) {
			return self::$cache;
		}

		self::$cache = self::resolve();

		return self::$cache;
	}

	/**
	 * آیا برندینگی فعال است.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		$profile = self::profile();

		return is_array( $profile ) && ! empty( $profile['name'] );
	}

	/**
	 * یک مقدار از پروفایل.
	 *
	 * @param string $key     کلید.
	 * @param string $default مقدار جایگزین.
	 * @return string
	 */
	public static function get( $key, $default = '' ) {
		$profile = self::profile();

		if ( ! is_array( $profile ) || empty( $profile[ $key ] ) ) {
			return $default;
		}

		return (string) $profile[ $key ];
	}

	/**
	 * نشانی لوگو برای نمایش در پنل.
	 *
	 * @return string
	 */
	public static function logo_url() {
		$profile = self::profile();

		if ( ! is_array( $profile ) || empty( $profile['logo'] ) ) {
			return '';
		}

		return self::asset_url( $profile, $profile['logo'] );
	}

	/**
	 * آیکون منوی پیشخوان به‌صورت data URI.
	 *
	 * @return string رشته‌ی خالی اگر آیکونی تعریف نشده باشد.
	 */
	public static function menu_icon() {
		$profile = self::profile();

		if ( ! is_array( $profile ) || empty( $profile['icon'] ) ) {
			return '';
		}

		$path = self::asset_path( $profile, $profile['icon'] );

		if ( ! $path || ! is_readable( $path ) ) {
			return '';
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		return 'data:image/svg+xml;base64,' . base64_encode( file_get_contents( $path ) );
	}

	/**
	 * تشخیص پروفایل فعال.
	 *
	 * @return array|null
	 */
	private static function resolve() {
		/**
		 * بازنویسی کامل برندینگ از طریق کد.
		 *
		 * @param array|null $branding آرایه‌ی برندینگ یا null.
		 */
		$filtered = apply_filters( 'dsb_branding', null );

		if ( is_array( $filtered ) ) {
			return self::normalize( $filtered, '' );
		}

		$slug = '';

		if ( defined( 'DSB_BRANDING' ) && DSB_BRANDING ) {
			$slug = (string) DSB_BRANDING;
		} else {
			$active = DSB_PATH . self::ACTIVE_FILE;

			if ( is_readable( $active ) ) {
				$slug = (string) include $active;
			}
		}

		$slug = sanitize_key( $slug );

		if ( ! $slug ) {
			return null;
		}

		$file = DSB_PATH . self::DIR . '/' . $slug . '/profile.php';

		if ( ! is_readable( $file ) ) {
			return null;
		}

		$profile = include $file;

		if ( ! is_array( $profile ) ) {
			return null;
		}

		return self::normalize( $profile, $slug );
	}

	/**
	 * پاک‌سازی و تکمیل آرایه‌ی پروفایل.
	 *
	 * @param array  $profile پروفایل خام.
	 * @param string $slug    نام پوشه‌ی پروفایل.
	 * @return array
	 */
	private static function normalize( array $profile, $slug ) {
		return array(
			'slug'  => $slug,
			'name'  => isset( $profile['name'] ) ? sanitize_text_field( $profile['name'] ) : '',
			'phone' => isset( $profile['phone'] ) ? sanitize_text_field( $profile['phone'] ) : '',
			'site'  => isset( $profile['site'] ) ? esc_url_raw( $profile['site'] ) : '',
			'label' => isset( $profile['label'] ) ? sanitize_text_field( $profile['label'] ) : '',
			'logo'  => isset( $profile['logo'] ) ? $profile['logo'] : '',
			'icon'  => isset( $profile['icon'] ) ? $profile['icon'] : '',
		);
	}

	/**
	 * نشانی یک فایل پروفایل.
	 *
	 * @param array  $profile پروفایل.
	 * @param string $file    نام فایل یا نشانی کامل.
	 * @return string
	 */
	private static function asset_url( array $profile, $file ) {
		if ( preg_match( '#^https?://#i', $file ) ) {
			return $file;
		}

		if ( empty( $profile['slug'] ) ) {
			return '';
		}

		return DSB_URL . self::DIR . '/' . $profile['slug'] . '/' . ltrim( $file, '/' );
	}

	/**
	 * مسیر فایل یک پروفایل روی دیسک.
	 *
	 * @param array  $profile پروفایل.
	 * @param string $file    نام فایل.
	 * @return string
	 */
	private static function asset_path( array $profile, $file ) {
		if ( preg_match( '#^https?://#i', $file ) || empty( $profile['slug'] ) ) {
			return '';
		}

		return DSB_PATH . self::DIR . '/' . $profile['slug'] . '/' . ltrim( $file, '/' );
	}
}
