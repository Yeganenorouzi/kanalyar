<?php
/**
 * قالب متن پیام و جایگزینی شورت‌کدها.
 *
 * @package DailyBalePost
 */

defined( 'ABSPATH' ) || exit;

/**
 * رندر قالب متن پیام.
 *
 * قالب را مدیر سایت می‌نویسد، پس مارک‌داونِ خودِ قالب دست‌نخورده می‌ماند و
 * فقط مقادیر جایگزین‌شده خنثی‌سازی می‌شوند.
 */
class DSB_Template {

	/**
	 * بیشینه‌ی طول قالب.
	 */
	const MAX_LENGTH = 4000;

	/**
	 * قالب پیش‌فرض؛ همان ساختار پست‌های نسخه‌های قبل.
	 *
	 * @return string
	 */
	public static function default_template() {
		$default = "🔹 *{title}*\n\n{excerpt}\n\n{price_block}\n🔗 {url}";

		/**
		 * تغییر قالب پیش‌فرض متن پیام.
		 *
		 * @param string $default قالب پیش‌فرض.
		 */
		return (string) apply_filters( 'dsb_default_template', $default );
	}

	/**
	 * متن دکمه‌ی پیش‌فرض.
	 *
	 * @param bool $is_product آیا مطلب محصول است.
	 * @return string
	 */
	public static function default_button( $is_product ) {
		return $is_product
			? __( '🛒 مشاهده محصول', 'daily-bale-post' )
			: __( '📖 مشاهده مطلب', 'daily-bale-post' );
	}

	/**
	 * شورت‌کدهای قابل استفاده در قالب، برای نمایش در پنل.
	 *
	 * @return array<string,string> نام شورت‌کد => توضیح.
	 */
	public static function placeholders() {
		return array(
			'title'         => __( 'عنوان مطلب', 'daily-bale-post' ),
			'excerpt'       => __( 'توضیح کوتاه (محصول) یا چکیده/ابتدای متن', 'daily-bale-post' ),
			'url'           => __( 'نشانی مطلب', 'daily-bale-post' ),
			'price_block'   => __( 'بلوک کامل قیمت و تخفیف (فقط محصول)', 'daily-bale-post' ),
			'price'         => __( 'قیمت فعلی با واحد', 'daily-bale-post' ),
			'price_regular' => __( 'قیمت بدون تخفیف', 'daily-bale-post' ),
			'price_sale'    => __( 'قیمت با تخفیف', 'daily-bale-post' ),
			'discount'      => __( 'درصد تخفیف', 'daily-bale-post' ),
			'stock'         => __( 'وضعیت موجودی (فقط محصول)', 'daily-bale-post' ),
			'sku'           => __( 'کد محصول', 'daily-bale-post' ),
			'categories'    => __( 'دسته‌بندی‌های مطلب', 'daily-bale-post' ),
			'type'          => __( 'نام نوع محتوا', 'daily-bale-post' ),
			'phone'         => __( 'شماره تماس تنظیمات', 'daily-bale-post' ),
			'site'          => __( 'نام سایت', 'daily-bale-post' ),
			'date'          => __( 'تاریخ امروز', 'daily-bale-post' ),
		);
	}

	/**
	 * رندر قالب با مقادیر.
	 *
	 * خط‌هایی که فقط شورت‌کد داشتند و مقدارشان خالی بوده حذف می‌شوند، تا
	 * پست برای محتوای بدون قیمت یا بدون چکیده، خط و برچسبِ خالی نگیرد.
	 *
	 * @param string $template قالب.
	 * @param array  $tokens   نگاشت نام شورت‌کد به مقدار آماده.
	 * @return string
	 */
	public static function render( $template, array $tokens ) {
		$template = (string) $template;

		if ( '' === trim( $template ) ) {
			$template = self::default_template();
		}

		$template = self::resolve_conditions( $template, $tokens );

		$lines  = explode( "\n", str_replace( array( "\r\n", "\r" ), "\n", $template ) );
		$output = array();

		foreach ( $lines as $line ) {
			$has_token = (bool) preg_match( '/\{[a-z_]+\}/', $line );
			$rendered  = self::replace( $line, $tokens );

			// خطی که فقط شورت‌کدِ خالی داشته، کلاً حذف می‌شود.
			if ( $has_token && '' === trim( $rendered ) ) {
				continue;
			}

			$output[] = rtrim( $rendered );
		}

		$text = implode( "\n", $output );
		$text = preg_replace( "/\n{3,}/", "\n\n", $text );

		return trim( $text );
	}

	/**
	 * اجرای بخش‌های شرطی {if:name}...{/if:name} .
	 *
	 * @param string $template قالب.
	 * @param array  $tokens   مقادیر.
	 * @return string
	 */
	private static function resolve_conditions( $template, array $tokens ) {
		return (string) preg_replace_callback(
			'/\{if:([a-z_]+)\}(.*?)\{\/if:\1\}/s',
			static function ( $match ) use ( $tokens ) {
				$value = isset( $tokens[ $match[1] ] ) ? trim( (string) $tokens[ $match[1] ] ) : '';

				return '' !== $value ? $match[2] : '';
			},
			$template
		);
	}

	/**
	 * جایگزینی شورت‌کدها در یک متن.
	 *
	 * شورت‌کد ناشناس حذف می‌شود تا در پست نهایی {foo} دیده نشود.
	 *
	 * @param string $text   متن.
	 * @param array  $tokens مقادیر.
	 * @return string
	 */
	public static function replace( $text, array $tokens ) {
		$text = (string) $text;

		foreach ( $tokens as $name => $value ) {
			$text = str_replace( '{' . $name . '}', (string) $value, $text );
		}

		$text = preg_replace( '/\{\/?if:[a-z_]+\}/', '', $text );

		return (string) preg_replace( '/\{[a-z_]+\}/', '', $text );
	}
}
