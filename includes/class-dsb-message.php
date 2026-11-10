<?php
/**
 * ساخت متن و دکمه‌های پست.
 *
 * @package DailyBalePost
 */

defined( 'ABSPATH' ) || exit;

/**
 * تبدیل یک محصول ووکامرس به پیام آماده‌ی ارسال.
 */
class DSB_Message {

	/**
	 * تنظیمات.
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
	 * ساخت پیام.
	 *
	 * @param WC_Product $product محصول.
	 * @return array{text:string,reply_markup:array,photo_url:string,photo_path:string}
	 */
	public function build( WC_Product $product ) {
		$product_id = $product->get_id();
		$name       = $product->get_name();
		$url        = get_permalink( $product_id );
		$phone      = trim( (string) $this->settings->get( 'phone' ) );
		$show_price = (bool) $this->settings->get( 'show_price' );

		$price   = $product->get_price();
		$regular = (float) $product->get_regular_price();
		$sale    = (float) $product->get_sale_price();

		$has_price = $show_price && '' !== $price && (float) $price > 0;

		$text  = '🔹 *' . self::escape( $name ) . '*' . "\n\n";
		$specs = $this->specs( $product->get_short_description() );

		if ( $specs ) {
			$text .= $specs . "\n\n";
		}

		if ( $show_price && $product->is_on_sale() && $sale > 0 && $regular > $sale ) {
			$percent = (int) round( ( ( $regular - $sale ) / $regular ) * 100 );

			$text .= '💰 قیمت: ' . self::money( $regular ) . ' تومان' . "\n";
			$text .= '🔥 با ' . $percent . '٪ تخفیف: *' . self::money( $sale ) . '* تومان' . "\n\n";
		} elseif ( $has_price ) {
			$text .= '💰 قیمت: *' . self::money( (float) $price ) . '* تومان' . "\n\n";
		} elseif ( $phone ) {
			$text .= '📞 استعلام قیمت: ' . self::escape( $phone ) . "\n\n";
		}

		$text .= $this->link_line( $url );

		// نکته: بله مثل تلگرام فقط لینک http/https را در دکمه می‌پذیرد،
		// پس دکمه‌ی tel: ساخته نمی‌شود؛ شماره در خود متن پست می‌آید.
		$buttons = array();

		$buttons[] = array(
			array(
				'text' => '🛒 مشاهده محصول',
				'url'  => $url,
			),
		);

		return array(
			'text'         => $text,
			'reply_markup' => array( 'inline_keyboard' => $buttons ),
			'photo_url'    => $this->photo_url( $product ),
			'photo_path'   => $this->photo_path( $product ),
		);
	}

	/**
	 * خط لینک محصول، به شکلی که مارک‌داون را نشکند.
	 *
	 * نشانی خام می‌تواند _ یا * داشته باشد و کل پست را با خطای
	 * «can't parse entities» از بین ببرد؛ داخل پرانتزِ لینکِ مارک‌داون
	 * هیچ کاراکتری به‌عنوان نشانه تفسیر نمی‌شود.
	 *
	 * @param string $url نشانی محصول.
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
			return '🔗 [' . __( 'مشاهده صفحه‌ی محصول', 'daily-bale-post' ) . '](' . $url . ')';
		}

		return '🔗 ' . self::escape( $url );
	}

	/**
	 * نشانی تصویر شاخص در اندازه‌ی مناسب ارسال.
	 *
	 * @param WC_Product $product محصول.
	 * @return string
	 */
	private function photo_url( WC_Product $product ) {
		$image_id = $product->get_image_id();

		if ( ! $image_id ) {
			return '';
		}

		$url = wp_get_attachment_image_url( $image_id, 'large' );

		return $url ? $url : (string) wp_get_attachment_url( $image_id );
	}

	/**
	 * مسیر فایل تصویر روی سرور، برای آپلود مستقیم.
	 *
	 * @param WC_Product $product محصول.
	 * @return string
	 */
	private function photo_path( WC_Product $product ) {
		$image_id = $product->get_image_id();

		if ( ! $image_id ) {
			return '';
		}

		$path = get_attached_file( $image_id );

		return ( $path && is_readable( $path ) ) ? $path : '';
	}

	/**
	 * تبدیل توضیح کوتاه HTML به متن ساده.
	 *
	 * @param string $html توضیح کوتاه محصول.
	 * @return string
	 */
	private function specs( $html ) {
		if ( empty( $html ) ) {
			return '';
		}

		$text = preg_replace( '/<li[^>]*>/i', '• ', $html );
		$text = preg_replace( '#</li>#i', "\n", $text );
		$text = preg_replace( '#<br\s*/?>#i', "\n", $text );
		$text = preg_replace( '#</p>#i', "\n", $text );
		$text = wp_strip_all_tags( $text );
		$text = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );
		$text = preg_replace( "/\n{3,}/", "\n\n", $text );
		$text = trim( $text );

		if ( '' === $text ) {
			return '';
		}

		// کپشن بله حداکثر ۱۰۲۴ کاراکتر است؛ مشخصات را محدود نگه می‌داریم.
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
	 * بدون این کار، نام محصولی که _ یا * دارد باعث خطای
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
