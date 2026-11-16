<?php
/**
 * ساخت متن و دکمه‌های پست.
 *
 * @package DailyBalePost
 */

defined( 'ABSPATH' ) || exit;

/**
 * تبدیل یک محصول ووکامرس، نوشته یا نوع پست سفارشی به پیام آماده‌ی ارسال.
 *
 * مقادیر یک بار برای هر مطلب ساخته می‌شوند (prepare) و بعد با قالبِ
 * هر کانال ترکیب می‌شوند (compose)، چون قالب بله و تلگرام جداست.
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
	 * ساخت پیام با قالب و تنظیمات یک کانال.
	 *
	 * @param WP_Post|WC_Product|int $item    مطلب.
	 * @param array                  $options تنظیمات کانال (template، button، button_text).
	 * @return array{text:string,reply_markup:array|null,photo_url:string,photo_path:string}
	 */
	public function build( $item, array $options = array() ) {
		return $this->compose( $this->prepare( $item ), $options );
	}

	/**
	 * آماده‌سازی مقادیر یک مطلب، مستقل از کانال.
	 *
	 * @param WP_Post|WC_Product|int $item مطلب.
	 * @return array{tokens:array,photo_url:string,photo_path:string,url:string,title:string,is_product:bool}
	 */
	public function prepare( $item ) {
		$post    = self::to_post( $item );
		$product = $this->product( $post );
		$url     = $post ? (string) get_permalink( $post ) : '';

		return array(
			'tokens'     => $this->tokens( $post, $product, $url ),
			'photo_url'  => $this->photo_url( $post ),
			'photo_path' => $this->photo_path( $post ),
			'url'        => $url,
			'title'      => $post ? get_the_title( $post ) : '',
			'is_product' => (bool) $product,
		);
	}

	/**
	 * ترکیب مقادیر با قالب کانال.
	 *
	 * @param array $context خروجی prepare().
	 * @param array $options تنظیمات کانال.
	 * @return array{text:string,reply_markup:array|null,photo_url:string,photo_path:string}
	 */
	public function compose( array $context, array $options = array() ) {
		$tokens = isset( $context['tokens'] ) ? (array) $context['tokens'] : array();
		$text   = DSB_Template::render( $options['template'] ?? '', $tokens );

		return array(
			'text'         => $text,
			'reply_markup' => $this->markup( $context, $options ),
			'photo_url'    => (string) ( $context['photo_url'] ?? '' ),
			'photo_path'   => (string) ( $context['photo_path'] ?? '' ),
		);
	}

	/**
	 * دکمه‌ی شیشه‌ای کانال.
	 *
	 * نکته: بله و تلگرام فقط لینک http/https را در دکمه می‌پذیرند،
	 * پس دکمه‌ی tel: ساخته نمی‌شود؛ شماره در خود متن پست می‌آید.
	 *
	 * @param array $context خروجی prepare().
	 * @param array $options تنظیمات کانال.
	 * @return array|null
	 */
	private function markup( array $context, array $options ) {
		$url = (string) ( $context['url'] ?? '' );

		if ( '' === $url || ( array_key_exists( 'button', $options ) && ! $options['button'] ) ) {
			return null;
		}

		$label = trim( (string) ( $options['button_text'] ?? '' ) );

		if ( '' === $label ) {
			$label = DSB_Template::default_button( ! empty( $context['is_product'] ) );
		} else {
			$label = DSB_Template::replace( $label, (array) ( $context['tokens'] ?? array() ) );
		}

		return array(
			'inline_keyboard' => array(
				array(
					array(
						'text' => $label,
						'url'  => $url,
					),
				),
			),
		);
	}

	/**
	 * ساخت مقادیر شورت‌کدها (همه خنثی‌شده برای مارک‌داون).
	 *
	 * @param WP_Post|null    $post    مطلب.
	 * @param WC_Product|null $product محصول.
	 * @param string          $url     نشانی مطلب.
	 * @return array<string,string>
	 */
	private function tokens( $post, $product, $url ) {
		$phone = trim( (string) $this->settings->get( 'phone' ) );

		$tokens = array(
			'title'         => $post ? self::escape( get_the_title( $post ) ) : '',
			'excerpt'       => $this->summary( $post, $product ),
			'url'           => $this->safe_url( $url ),
			'phone'         => $phone ? self::escape( $phone ) : '',
			'site'          => self::escape( get_bloginfo( 'name' ) ),
			'date'          => self::escape( date_i18n( get_option( 'date_format' ) ) ),
			'type'          => $post ? self::escape( $this->type_label( $post ) ) : '',
			'categories'    => $post ? self::escape( $this->categories( $post ) ) : '',
			'price_block'   => '',
			'price'         => '',
			'price_regular' => '',
			'price_sale'    => '',
			'discount'      => '',
			'stock'         => '',
			'sku'           => '',
		);

		if ( $product ) {
			$tokens = array_merge( $tokens, $this->product_tokens( $product, $phone ) );
		}

		/**
		 * تغییر مقادیر شورت‌کدهای پیام.
		 *
		 * مقادیر باید از قبل برای مارک‌داون خنثی شده باشند.
		 *
		 * @param array           $tokens  مقادیر.
		 * @param WP_Post|null    $post    مطلب.
		 * @param WC_Product|null $product محصول.
		 */
		return (array) apply_filters( 'dsb_message_tokens', $tokens, $post, $product );
	}

	/**
	 * مقادیر مربوط به محصول ووکامرس.
	 *
	 * @param WC_Product $product محصول.
	 * @param string     $phone   شماره تماس.
	 * @return array<string,string>
	 */
	private function product_tokens( WC_Product $product, $phone ) {
		$show_price = (bool) $this->settings->get( 'show_price' );
		$unit       = __( 'تومان', 'daily-bale-post' );

		$price   = $product->get_price();
		$regular = (float) $product->get_regular_price();
		$sale    = (float) $product->get_sale_price();

		$has_price = $show_price && '' !== $price && (float) $price > 0;
		$on_sale   = $show_price && $product->is_on_sale() && $sale > 0 && $regular > $sale;

		$tokens = array(
			'stock' => $product->is_in_stock()
				? __( 'موجود', 'daily-bale-post' )
				: __( 'ناموجود', 'daily-bale-post' ),
			'sku'   => self::escape( (string) $product->get_sku() ),
		);

		if ( $has_price ) {
			$tokens['price'] = self::money( (float) $price ) . ' ' . $unit;
		}

		if ( $on_sale ) {
			$percent = (int) round( ( ( $regular - $sale ) / $regular ) * 100 );

			$tokens['price_regular'] = self::money( $regular ) . ' ' . $unit;
			$tokens['price_sale']    = self::money( $sale ) . ' ' . $unit;
			$tokens['discount']      = (string) $percent;

			$tokens['price_block'] = '💰 ' . __( 'قیمت:', 'daily-bale-post' ) . ' ' . self::money( $regular ) . ' ' . $unit . "\n"
				. '🔥 ' . sprintf(
					/* translators: 1: درصد تخفیف، 2: قیمت با تخفیف. */
					__( 'با %1$d٪ تخفیف: *%2$s*', 'daily-bale-post' ),
					$percent,
					self::money( $sale ) . ' ' . $unit
				);

			return $tokens;
		}

		if ( $has_price ) {
			$tokens['price_regular'] = self::money( $regular > 0 ? $regular : (float) $price ) . ' ' . $unit;
			$tokens['price_block']   = '💰 ' . __( 'قیمت:', 'daily-bale-post' ) . ' *' . self::money( (float) $price ) . ' ' . $unit . '*';

			return $tokens;
		}

		if ( $phone ) {
			$tokens['price_block'] = '📞 ' . __( 'استعلام قیمت:', 'daily-bale-post' ) . ' ' . self::escape( $phone );
		}

		return $tokens;
	}

	/**
	 * نام نوع محتوا.
	 *
	 * @param WP_Post $post مطلب.
	 * @return string
	 */
	private function type_label( WP_Post $post ) {
		$object = get_post_type_object( $post->post_type );

		if ( $object && isset( $object->labels->singular_name ) ) {
			return (string) $object->labels->singular_name;
		}

		return (string) $post->post_type;
	}

	/**
	 * نام دسته‌بندی‌های مطلب (اولین طبقه‌بندی که ترم دارد).
	 *
	 * @param WP_Post $post مطلب.
	 * @return string
	 */
	private function categories( WP_Post $post ) {
		foreach ( get_object_taxonomies( $post->post_type, 'objects' ) as $taxonomy ) {
			if ( empty( $taxonomy->show_ui ) ) {
				continue;
			}

			$terms = get_the_terms( $post, $taxonomy->name );

			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				continue;
			}

			return implode( '، ', wp_list_pluck( $terms, 'name' ) );
		}

		return '';
	}

	/**
	 * نشانی امن برای متن مارک‌داون.
	 *
	 * نشانی خام می‌تواند _ یا * داشته باشد و کل پست را با خطای
	 * «can't parse entities» از بین ببرد.
	 *
	 * @param string $url نشانی.
	 * @return string
	 */
	private function safe_url( $url ) {
		$url = (string) $url;

		if ( '' === $url ) {
			return '';
		}

		// نشانی تمیز را خام نشان می‌دهیم تا ظاهر پست‌ها تغییر نکند.
		if ( ! preg_match( '/[*_`\[\]\\\\]/', $url ) ) {
			return $url;
		}

		// نشانی مشکل‌دار: پرانتزِ لینک مارک‌داون محتوایش را تفسیر نمی‌کند.
		if ( false === strpos( $url, '(' ) && false === strpos( $url, ')' ) ) {
			return '[' . __( 'مشاهده صفحه‌ی مطلب', 'daily-bale-post' ) . '](' . $url . ')';
		}

		return self::escape( $url );
	}

	/**
	 * تبدیل ورودی به WP_Post.
	 *
	 * @param WP_Post|WC_Product|int $item ورودی.
	 * @return WP_Post|null
	 */
	public static function to_post( $item ) {
		if ( is_object( $item ) && method_exists( $item, 'get_id' ) && ! $item instanceof WP_Post ) {
			$item = $item->get_id();
		}

		$post = get_post( $item );

		return $post instanceof WP_Post ? $post : null;
	}

	/**
	 * محصول ووکامرسِ متناظر، اگر این مطلب محصول باشد.
	 *
	 * @param WP_Post|null $post مطلب.
	 * @return WC_Product|null
	 */
	private function product( $post ) {
		if ( ! $post || 'product' !== $post->post_type || ! function_exists( 'wc_get_product' ) ) {
			return null;
		}

		$product = wc_get_product( $post->ID );

		return $product instanceof WC_Product ? $product : null;
	}

	/**
	 * نشانی تصویر شاخص در اندازه‌ی مناسب ارسال.
	 *
	 * @param WP_Post|null $post مطلب.
	 * @return string
	 */
	private function photo_url( $post ) {
		$image_id = $post ? (int) get_post_thumbnail_id( $post ) : 0;

		if ( ! $image_id ) {
			return '';
		}

		$url = wp_get_attachment_image_url( $image_id, 'large' );

		return $url ? $url : (string) wp_get_attachment_url( $image_id );
	}

	/**
	 * مسیر فایل تصویر روی سرور، برای آپلود مستقیم.
	 *
	 * @param WP_Post|null $post مطلب.
	 * @return string
	 */
	private function photo_path( $post ) {
		$image_id = $post ? (int) get_post_thumbnail_id( $post ) : 0;

		if ( ! $image_id ) {
			return '';
		}

		$path = get_attached_file( $image_id );

		return ( $path && is_readable( $path ) ) ? $path : '';
	}

	/**
	 * متن توضیح: برای محصول توضیح کوتاه، برای بقیه چکیده یا متن مطلب.
	 *
	 * @param WP_Post|null    $post    مطلب.
	 * @param WC_Product|null $product محصول.
	 * @return string
	 */
	private function summary( $post, $product ) {
		if ( $product ) {
			return $this->to_plain_text( $product->get_short_description() );
		}

		if ( ! $post ) {
			return '';
		}

		if ( '' !== trim( (string) $post->post_excerpt ) ) {
			return $this->to_plain_text( $post->post_excerpt );
		}

		return $this->to_plain_text( strip_shortcodes( (string) $post->post_content ) );
	}

	/**
	 * تبدیل HTML به متن ساده‌ی کوتاه و امن.
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
		$text = wp_strip_all_tags( $text );
		$text = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );
		$text = preg_replace( "/\n{3,}/", "\n\n", $text );
		$text = trim( $text );

		if ( '' === $text ) {
			return '';
		}

		// کپشن بله و تلگرام حداکثر ۱۰۲۴ کاراکتر است؛ متن را محدود نگه می‌داریم.
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
	 * بدون این کار، نام مطلبی که _ یا * دارد باعث خطای
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
