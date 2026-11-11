<?php
/**
 * ساخت متن و دکمه‌های پست از روی قالب.
 *
 * @package Kanalyar
 */

defined( 'ABSPATH' ) || exit;

/**
 * تبدیل یک مطلب وردپرس به پیام آماده‌ی ارسال.
 *
 * متن از روی یک قالب با جای‌نشان ساخته می‌شود، و دو نسخه تولید می‌شود:
 * یکی با مارک‌داون برای بله و تلگرام، و یکی ساده برای پیام‌رسان‌هایی
 * مثل ایتا که قالب‌بندی را پشتیبانی نمی‌کنند.
 */
class KY_Message {

	/**
	 * قالب پیش‌فرض؛ همان ظاهری که نسخه‌های قبل تولید می‌کردند.
	 */
	const DEFAULT_TEMPLATE = "🔹 *{title}*\n\n{excerpt}\n\n{price}\n🔗 {link}\n\n{hashtags}";

	/**
	 * تنظیمات سراسری.
	 *
	 * @var KY_Settings
	 */
	private $settings;

	/**
	 * سازنده.
	 *
	 * @param KY_Settings $settings تنظیمات.
	 */
	public function __construct( KY_Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * فهرست جای‌نشان‌های قابل استفاده در قالب.
	 *
	 * @return array<string,string>
	 */
	public static function placeholders() {
		return array(
			'{title}'          => __( 'عنوان مطلب', 'kanalyar' ),
			'{excerpt}'        => __( 'خلاصه یا توضیح کوتاه', 'kanalyar' ),
			'{link}'           => __( 'نشانی مطلب', 'kanalyar' ),
			'{price}'          => __( 'بخش کامل قیمت و تخفیف (فقط محصول)', 'kanalyar' ),
			'{price_amount}'   => __( 'فقط عدد قیمت فعلی', 'kanalyar' ),
			'{regular_price}'  => __( 'قیمت بدون تخفیف', 'kanalyar' ),
			'{sale_price}'     => __( 'قیمت با تخفیف', 'kanalyar' ),
			'{discount}'       => __( 'درصد تخفیف', 'kanalyar' ),
			'{sku}'            => __( 'کد محصول', 'kanalyar' ),
			'{stock}'          => __( 'وضعیت موجودی', 'kanalyar' ),
			'{categories}'     => __( 'دسته‌بندی‌ها با ویرگول', 'kanalyar' ),
			'{tags}'           => __( 'برچسب‌ها با ویرگول', 'kanalyar' ),
			'{hashtags}'       => __( 'برچسب‌ها و دسته‌ها به شکل هشتگ', 'kanalyar' ),
			'{author}'         => __( 'نویسنده', 'kanalyar' ),
			'{date}'           => __( 'تاریخ انتشار', 'kanalyar' ),
			'{site}'           => __( 'نام سایت', 'kanalyar' ),
			'{phone}'          => __( 'شماره تماس از تنظیمات کلی', 'kanalyar' ),
		);
	}

	/**
	 * ساخت پیام از یک مطلب.
	 *
	 * @param WP_Post $post مطلب.
	 * @param array   $rule قانون انتشار.
	 * @return array{title:string,text:string,plain:string,reply_markup:array,photo_url:string,photo_path:string}
	 */
	public function build( WP_Post $post, array $rule ) {
		$title    = get_the_title( $post );
		$url      = get_permalink( $post );
		$template = trim( (string) ( $rule['template'] ?? '' ) );

		if ( '' === $template ) {
			$template = self::DEFAULT_TEMPLATE;
		}

		$vars = $this->vars( $post, $rule, $title, $url );

		$buttons = array();
		$cta     = trim( (string) ( $rule['cta'] ?? '' ) );

		$buttons[] = array(
			array(
				'text' => '' !== $cta ? $cta : $this->default_cta( $post ),
				'url'  => $url,
			),
		);

		return array(
			'title'        => $title,
			'text'         => $this->render( $template, $vars, true ),
			'plain'        => $this->render( $template, $vars, false ),
			'reply_markup' => array( 'inline_keyboard' => $buttons ),
			'photo_url'    => $this->photo_url( $post ),
			'photo_path'   => $this->photo_path( $post ),
		);
	}

	/**
	 * جای‌گذاری جای‌نشان‌ها در قالب.
	 *
	 * @param string $template قالب.
	 * @param array  $vars     مقادیر، هر کدام با دو نسخه‌ی md و plain.
	 * @param bool   $markdown آیا خروجی مارک‌داون باشد.
	 * @return string
	 */
	private function render( $template, array $vars, $markdown ) {
		$sentinels = array();
		$values    = array();
		$index     = 0;

		/*
		 * مقادیر ابتدا با یک نشانه‌ی میانی جایگزین می‌شوند تا پاک‌سازیِ
		 * نشانه‌های مارک‌داونِ قالب به محتوای خودِ مقادیر دست نزند.
		 * بدون این کار، زیرخطِ داخل هشتگ‌ها هم حذف می‌شد.
		 */
		foreach ( $vars as $key => $pair ) {
			$token = "\x00" . $index . "\x00";

			$sentinels[ $key ] = $token;
			$values[ $token ]  = $markdown ? $pair['md'] : $pair['plain'];
			$index++;
		}

		$text = strtr( $template, $sentinels );

		if ( ! $markdown ) {
			// نشانه‌های مارک‌داونِ خودِ قالب در حالت ساده معنی ندارند.
			$text = preg_replace( '/[*_`]/u', '', $text );
		}

		$text = strtr( $text, $values );

		// جای‌نشان‌های خالی نباید خط سفید جا بگذارند.
		$text = preg_replace( "/[ \t]+\n/", "\n", $text );
		$text = preg_replace( "/\n{3,}/", "\n\n", $text );

		return trim( $text );
	}

	/**
	 * ساخت مقادیر جای‌نشان‌ها.
	 *
	 * @param WP_Post $post  مطلب.
	 * @param array   $rule  قانون.
	 * @param string  $title عنوان.
	 * @param string  $url   نشانی.
	 * @return array
	 */
	private function vars( WP_Post $post, array $rule, $title, $url ) {
		$vars = array();

		$vars['{title}'] = self::pair( $title );
		$vars['{link}']  = array(
			'md'    => $this->link_value( $url ),
			'plain' => $url,
		);

		$vars['{excerpt}'] = self::pair( $this->body( $post, $rule ) );
		$vars['{site}']    = self::pair( wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
		$vars['{phone}']   = self::pair( trim( (string) $this->settings->get( 'phone' ) ) );
		$vars['{author}']  = self::pair( get_the_author_meta( 'display_name', $post->post_author ) );
		$vars['{date}']    = self::pair( get_the_date( '', $post ) );

		$terms = $this->terms( $post );

		$vars['{categories}'] = self::pair( implode( '، ', $terms['categories'] ) );
		$vars['{tags}']       = self::pair( implode( '، ', $terms['tags'] ) );
		$vars['{hashtags}']   = self::pair( $this->hashtags( $terms, $rule ) );

		return array_merge( $vars, $this->commerce_vars( $post, $rule ) );
	}

	/**
	 * مقادیر مربوط به محصول ووکامرس.
	 *
	 * @param WP_Post $post مطلب.
	 * @param array   $rule قانون.
	 * @return array
	 */
	private function commerce_vars( WP_Post $post, array $rule ) {
		$empty = array(
			'{price}'         => self::pair( '' ),
			'{price_amount}'  => self::pair( '' ),
			'{regular_price}' => self::pair( '' ),
			'{sale_price}'    => self::pair( '' ),
			'{discount}'      => self::pair( '' ),
			'{sku}'           => self::pair( '' ),
			'{stock}'         => self::pair( '' ),
		);

		if ( ! $this->is_product( $post ) ) {
			return $empty;
		}

		$product = wc_get_product( $post->ID );

		if ( ! $product ) {
			return $empty;
		}

		$show  = ! empty( $rule['show_price'] );
		$phone = trim( (string) $this->settings->get( 'phone' ) );

		$empty['{sku}']   = self::pair( (string) $product->get_sku() );
		$empty['{stock}'] = self::pair(
			$product->is_in_stock()
				? __( 'موجود', 'kanalyar' )
				: __( 'ناموجود', 'kanalyar' )
		);

		// محصول متغیر: بازه‌ی قیمت به‌جای یک عدد.
		if ( $product->is_type( 'variable' ) ) {
			$min = (float) $product->get_variation_price( 'min', true );
			$max = (float) $product->get_variation_price( 'max', true );

			if ( $show && $min > 0 ) {
				if ( $max > $min ) {
					$line = '💰 قیمت: از ' . self::money( $min ) . ' تا ' . self::money( $max ) . ' تومان';
					$empty['{price}'] = self::raw( $line, $line );
				} else {
					$empty['{price}'] = self::raw(
						'💰 قیمت: *' . self::money( $min ) . '* تومان',
						'💰 قیمت: ' . self::money( $min ) . ' تومان'
					);
				}

				$empty['{price_amount}'] = self::pair( self::money( $min ) );
			} elseif ( $phone ) {
				$empty['{price}'] = self::raw(
					'📞 استعلام قیمت: ' . self::escape( $phone ),
					'📞 استعلام قیمت: ' . $phone
				);
			}

			return $empty;
		}

		$price   = $product->get_price();
		$regular = (float) $product->get_regular_price();
		$sale    = (float) $product->get_sale_price();

		$empty['{regular_price}'] = self::pair( $regular > 0 ? self::money( $regular ) : '' );
		$empty['{sale_price}']    = self::pair( $sale > 0 ? self::money( $sale ) : '' );

		if ( $show && $product->is_on_sale() && $sale > 0 && $regular > $sale ) {
			$percent = (int) round( ( ( $regular - $sale ) / $regular ) * 100 );

			$empty['{discount}']     = self::pair( (string) $percent );
			$empty['{price_amount}'] = self::pair( self::money( $sale ) );
			$empty['{price}']        = self::raw(
				'💰 قیمت: ' . self::money( $regular ) . ' تومان' . "\n"
				. '🔥 با ' . $percent . '٪ تخفیف: *' . self::money( $sale ) . '* تومان'
			);

			return $empty;
		}

		if ( $show && '' !== $price && (float) $price > 0 ) {
			$empty['{price_amount}'] = self::pair( self::money( (float) $price ) );
			$empty['{price}']        = self::raw(
				'💰 قیمت: *' . self::money( (float) $price ) . '* تومان',
				'💰 قیمت: ' . self::money( (float) $price ) . ' تومان'
			);

			return $empty;
		}

		if ( $phone ) {
			$empty['{price}'] = self::raw(
				'📞 استعلام قیمت: ' . self::escape( $phone ),
				'📞 استعلام قیمت: ' . $phone
			);
		}

		return $empty;
	}

	/**
	 * دسته‌ها و برچسب‌های یک مطلب.
	 *
	 * @param WP_Post $post مطلب.
	 * @return array{categories:string[],tags:string[]}
	 */
	private function terms( WP_Post $post ) {
		$categories = array();
		$tags       = array();

		foreach ( get_object_taxonomies( $post->post_type, 'objects' ) as $slug => $taxonomy ) {
			if ( ! $taxonomy->public ) {
				continue;
			}

			$names = wp_get_post_terms( $post->ID, $slug, array( 'fields' => 'names' ) );

			if ( is_wp_error( $names ) || ! $names ) {
				continue;
			}

			if ( $taxonomy->hierarchical ) {
				$categories = array_merge( $categories, $names );
			} else {
				$tags = array_merge( $tags, $names );
			}
		}

		return array(
			'categories' => array_values( array_unique( $categories ) ),
			'tags'       => array_values( array_unique( $tags ) ),
		);
	}

	/**
	 * ساخت رشته‌ی هشتگ‌ها.
	 *
	 * @param array $terms دسته‌ها و برچسب‌ها.
	 * @param array $rule  قانون.
	 * @return string
	 */
	private function hashtags( array $terms, array $rule ) {
		$source = (string) ( $rule['hashtags'] ?? 'tags' );

		if ( 'none' === $source ) {
			return '';
		}

		$names = array();

		if ( in_array( $source, array( 'tags', 'both' ), true ) ) {
			$names = array_merge( $names, $terms['tags'] );
		}

		if ( in_array( $source, array( 'categories', 'both' ), true ) ) {
			$names = array_merge( $names, $terms['categories'] );
		}

		$tags = array();

		foreach ( array_unique( $names ) as $name ) {
			$tag = self::hashtagify( $name );

			if ( '' !== $tag ) {
				$tags[] = '#' . $tag;
			}
		}

		return implode( ' ', array_slice( $tags, 0, 12 ) );
	}

	/**
	 * تبدیل یک نام به هشتگ معتبر.
	 *
	 * فاصله‌ها به زیرخط تبدیل می‌شوند و کاراکترهای غیرمجاز حذف.
	 *
	 * @param string $name نام دسته یا برچسب.
	 * @return string
	 */
	public static function hashtagify( $name ) {
		$tag = trim( (string) $name );
		$tag = preg_replace( '/[\s\x{200c}]+/u', '_', $tag );
		$tag = preg_replace( '/[^\p{L}\p{N}_]+/u', '', $tag );

		return trim( (string) $tag, '_' );
	}

	/**
	 * متن دکمه بر اساس نوع محتوا.
	 *
	 * @param WP_Post $post مطلب.
	 * @return string
	 */
	private function default_cta( WP_Post $post ) {
		return $this->is_product( $post ) ? '🛒 مشاهده محصول' : '📖 مطالعه‌ی کامل';
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
	 * آیا این مطلب یک محصول ووکامرس است.
	 *
	 * @param WP_Post $post مطلب.
	 * @return bool
	 */
	private function is_product( WP_Post $post ) {
		return 'product' === $post->post_type && function_exists( 'wc_get_product' );
	}

	/**
	 * مقدار نشانی مطلب، به شکلی که مارک‌داون را نشکند.
	 *
	 * نشانی خام می‌تواند _ یا * داشته باشد و کل پست را با خطای
	 * «can't parse entities» از بین ببرد؛ داخل پرانتزِ لینکِ مارک‌داون
	 * هیچ کاراکتری به‌عنوان نشانه تفسیر نمی‌شود.
	 *
	 * @param string $url نشانی مطلب.
	 * @return string
	 */
	private function link_value( $url ) {
		$url = (string) $url;

		if ( '' === $url ) {
			return '';
		}

		// نشانی تمیز را خام نشان می‌دهیم تا ظاهر پست‌ها تغییر نکند.
		if ( ! preg_match( '/[*_`\[\]\\\\]/', $url ) ) {
			return $url;
		}

		if ( false === strpos( $url, '(' ) && false === strpos( $url, ')' ) ) {
			return '[' . __( 'مشاهده در سایت', 'kanalyar' ) . '](' . $url . ')';
		}

		return self::escape( $url );
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

		return $text;
	}

	/**
	 * ساخت جفتِ «مارک‌داون / ساده» از یک مقدار پویا.
	 *
	 * نسخه‌ی مارک‌داون خنثی‌سازی می‌شود تا نشانه‌های داخل متن
	 * قالب‌بندی پیام را نشکنند.
	 *
	 * @param string $value مقدار.
	 * @return array{md:string,plain:string}
	 */
	private static function pair( $value ) {
		$value = (string) $value;

		return array(
			'md'    => self::escape( $value ),
			'plain' => $value,
		);
	}

	/**
	 * مقداری که خودش مارک‌داون دارد و نباید خنثی شود.
	 *
	 * @param string $value مقدار.
	 * @return array{md:string,plain:string}
	 */
	private static function raw( $value ) {
		$value = (string) $value;

		return array(
			'md'    => $value,
			'plain' => str_replace( array( '\\*', '\\_', '*', '_' ), array( '*', '_', '', '' ), $value ),
		);
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
