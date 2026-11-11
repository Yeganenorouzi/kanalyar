<?php
/**
 * ساخت متن و دکمه‌های پست.
 *
 * @package DailyBalePost
 */

defined( 'ABSPATH' ) || exit;

/**
 * تبدیل یک مطلب وردپرس به پیام آماده‌ی ارسال.
 *
 * برای محصولات ووکامرس قیمت و تخفیف هم اضافه می‌شود؛
 * برای بقیه‌ی نوع‌های محتوا خلاصه‌ی مطلب می‌آید.
 */
class DSB_Message {

	/**
	 * تنظیمات سراسری.
	 *
	 * @var DSB_Settings
	 */
	private $settings;

	/**
	 * سازنده.
	 *
	 * @param DSB_Settings $settings تنظیمات.
	 */
	public function __construct( DSB_Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * ساخت پیام از یک مطلب.
	 *
	 * @param WP_Post $post مطلب.
	 * @param array   $rule قانون انتشار.
	 * @return array{text:string,reply_markup:array,photo_url:string,photo_path:string,title:string}
	 */
	public function build( WP_Post $post, array $rule ) {
		$title = get_the_title( $post );
		$url   = get_permalink( $post );

		$text = '🔹 *' . self::escape( $title ) . '*' . "\n\n";

		$body = $this->body( $post, $rule );

		if ( $body ) {
			$text .= $body . "\n\n";
		}

		$text .= $this->commerce( $post, $rule );
		$text .= $this->link_line( $url );

		$buttons = array();

		$cta = trim( (string) $rule['cta'] );

		$buttons[] = array(
			array(
				'text' => '' !== $cta ? $cta : $this->default_cta( $post ),
				'url'  => $url,
			),
		);

		return array(
			'title'        => $title,
			'text'         => $text,
			'reply_markup' => array( 'inline_keyboard' => $buttons ),
			'photo_url'    => $this->photo_url( $post ),
			'photo_path'   => $this->photo_path( $post ),
		);
	}

	/**
	 * متن دکمه بر اساس نوع محتوا.
	 *
	 * @param WP_Post $post مطلب.
	 * @return string
	 */
	private function default_cta( WP_Post $post ) {
		if ( $this->is_product( $post ) ) {
			return '🛒 مشاهده محصول';
		}

		return '📖 مطالعه‌ی کامل';
	}

	/**
	 * بدنه‌ی متن: توضیح کوتاه محصول یا خلاصه‌ی مطلب.
	 *
	 * @param WP_Post $post مطلب.
	 * @param array   $rule قانون.
	 * @return string
	 */
	private function body( WP_Post $post, array $rule ) {
		if ( empty( $rule['show_excerpt'] ) ) {
			return '';
		}

		$raw = '';

		if ( $this->is_product( $post ) ) {
			$product = wc_get_product( $post->ID );

			if ( $product ) {
				$raw = $product->get_short_description();
			}
		}

		if ( '' === $raw ) {
			$raw = $post->post_excerpt;
		}

		if ( '' === $raw ) {
			$raw = wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), 55, '…' );
		}

		return $this->to_plain_text( $raw );
	}

	/**
	 * بخش قیمت، فقط برای محصولات ووکامرس.
	 *
	 * @param WP_Post $post مطلب.
	 * @param array   $rule قانون.
	 * @return string
	 */
	private function commerce( WP_Post $post, array $rule ) {
		if ( ! $this->is_product( $post ) ) {
			return '';
		}

		$product = wc_get_product( $post->ID );

		if ( ! $product ) {
			return '';
		}

		$phone      = trim( (string) $this->settings->get( 'phone' ) );
		$show_price = ! empty( $rule['show_price'] );

		$price   = $product->get_price();
		$regular = (float) $product->get_regular_price();
		$sale    = (float) $product->get_sale_price();

		$has_price = $show_price && '' !== $price && (float) $price > 0;

		if ( $show_price && $product->is_on_sale() && $sale > 0 && $regular > $sale ) {
			$percent = (int) round( ( ( $regular - $sale ) / $regular ) * 100 );

			return '💰 قیمت: ' . self::money( $regular ) . ' تومان' . "\n"
				. '🔥 با ' . $percent . '٪ تخفیف: *' . self::money( $sale ) . '* تومان' . "\n\n";
		}

		if ( $has_price ) {
			return '💰 قیمت: *' . self::money( (float) $price ) . '* تومان' . "\n\n";
		}

		if ( $phone ) {
			return '📞 استعلام قیمت: ' . self::escape( $phone ) . "\n\n";
		}

		return '';
	}

	/**
	 * آیا این مطلب یک محصول ووکامرس است.
	 *
	 * @param WP_Post $post مطلب.
	 * @return bool
	 */
	private function is_product( WP_Post $post ) {
		return 'product' === $post->post_type && function_exists( 'wc_get_product' );
	}

	/**
	 * خط لینک مطلب، به شکلی که مارک‌داون را نشکند.
	 *
	 * نشانی خام می‌تواند _ یا * داشته باشد و کل پست را با خطای
	 * «can't parse entities» از بین ببرد؛ داخل پرانتزِ لینکِ مارک‌داون
	 * هیچ کاراکتری به‌عنوان نشانه تفسیر نمی‌شود.
	 *
	 * @param string $url نشانی مطلب.
	 * @return string
	 */
	private function link_line( $url ) {
		$url = (string) $url;

		if ( '' === $url ) {
			return '';
		}

		// نشانی تمیز را خام نشان می‌دهیم تا ظاهر پست‌ها تغییر نکند.
		if ( ! preg_match( '/[*_`\[\]\\\\]/', $url ) ) {
			return '🔗 ' . $url;
		}

		// نشانی مشکل‌دار: پرانتزِ لینک مارک‌داون محتوایش را تفسیر نمی‌کند.
		if ( false === strpos( $url, '(' ) && false === strpos( $url, ')' ) ) {
			return '🔗 [' . __( 'مشاهده در سایت', 'daily-bale-post' ) . '](' . $url . ')';
		}

		return '🔗 ' . self::escape( $url );
	}

	/**
	 * نشانی تصویر شاخص در اندازه‌ی مناسب ارسال.
	 *
	 * @param WP_Post $post مطلب.
	 * @return string
	 */
	private function photo_url( WP_Post $post ) {
		$image_id = get_post_thumbnail_id( $post );

		if ( ! $image_id ) {
			return '';
		}

		$url = wp_get_attachment_image_url( $image_id, 'large' );

		return $url ? $url : (string) wp_get_attachment_url( $image_id );
	}

	/**
	 * مسیر فایل تصویر روی سرور، برای آپلود مستقیم.
	 *
	 * @param WP_Post $post مطلب.
	 * @return string
	 */
	private function photo_path( WP_Post $post ) {
		$image_id = get_post_thumbnail_id( $post );

		if ( ! $image_id ) {
			return '';
		}

		$path = get_attached_file( $image_id );

		return ( $path && is_readable( $path ) ) ? $path : '';
	}

	/**
	 * تبدیل HTML به متن ساده و کوتاه.
	 *
	 * @param string $html متن خام.
	 * @return string
	 */
	private function to_plain_text( $html ) {
		if ( empty( $html ) ) {
			return '';
		}

		$text = preg_replace( '/<li[^>]*>/i', '• ', $html );
		$text = preg_replace( '#</li>#i', "\n", $text );
		$text = preg_replace( '#<br\s*/?>#i', "\n", $text );
		$text = preg_replace( '#</p>#i', "\n", $text );
		$text = strip_shortcodes( $text );
		$text = wp_strip_all_tags( $text );
		$text = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );
		$text = preg_replace( "/\n{3,}/", "\n\n", $text );
		$text = trim( $text );

		if ( '' === $text ) {
			return '';
		}

		// کپشن حداکثر ۱۰۲۴ کاراکتر است؛ بدنه را محدود نگه می‌داریم.
		if ( mb_strlen( $text ) > 600 ) {
			$text = rtrim( mb_substr( $text, 0, 599 ) ) . '…';
		}

		return self::escape( $text );
	}

	/**
	 * قالب‌بندی عدد قیمت.
	 *
	 * @param float $amount مبلغ.
	 * @return string
	 */
	private static function money( $amount ) {
		return number_format( (float) $amount, 0, '.', ',' );
	}

	/**
	 * خنثی‌کردن کاراکترهای مارک‌داون در متن پویا.
	 *
	 * بدون این کار، عنوانی که _ یا * دارد باعث خطای
	 * «can't parse entities» و ازدست‌رفتن کل پست می‌شود.
	 *
	 * @param string $text متن.
	 * @return string
	 */
	public static function escape( $text ) {
		return str_replace(
			array( '\\', '*', '_', '`', '[', ']' ),
			array( '\\\\', '\*', '\_', '\`', '\[', '\]' ),
			(string) $text
		);
	}
}
