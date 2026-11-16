<?php
/**
 * انتخاب محتوا برای ارسال (محصول، نوشته یا هر نوع پست سفارشی).
 *
 * @package DailyBalePost
 */

defined( 'ABSPATH' ) || exit;

/**
 * انتخاب یک مطلب تصادفی بر اساس فیلترهای تنظیمات.
 */
class DSB_Content_Selector {

	/**
	 * نام آپشن مطالب اخیر.
	 */
	const RECENT_OPTION = 'dsb_recent';

	/**
	 * نوع پست‌هایی که هرگز در فهرست انتخاب نمی‌آیند.
	 *
	 * @var string[]
	 */
	private static $blocked_types = array(
		'attachment',
		'revision',
		'nav_menu_item',
		'custom_css',
		'customize_changeset',
		'oembed_cache',
		'user_request',
		'wp_block',
		'wp_template',
		'wp_template_part',
		'wp_global_styles',
		'wp_navigation',
		'wp_font_family',
		'wp_font_face',
		'product_variation',
		'shop_order',
		'shop_order_refund',
		'shop_coupon',
		'shop_order_placehold',
		'scheduled-action',
		'elementor_library',
	);

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
	 * آیا ووکامرس در دسترس است.
	 *
	 * @return bool
	 */
	public function woocommerce_active() {
		return function_exists( 'wc_get_product' );
	}

	/**
	 * نوع پست‌هایی که کاربر می‌تواند انتخاب کند.
	 *
	 * نوع پست‌های ساخته‌شده با جت‌انجین (یا هر افزونه‌ی دیگر) چون در
	 * وردپرس ثبت می‌شوند، خودشان در این فهرست می‌آیند.
	 *
	 * @return array<string,string> slug => برچسب.
	 */
	public static function available_post_types() {
		$types = get_post_types(
			array(
				'show_ui' => true,
			),
			'objects'
		);

		$out = array();

		foreach ( $types as $type ) {
			if ( in_array( $type->name, self::$blocked_types, true ) ) {
				continue;
			}

			if ( ! empty( $type->exclude_from_search ) && empty( $type->publicly_queryable ) ) {
				continue;
			}

			$label = isset( $type->labels->name ) ? $type->labels->name : $type->name;

			$out[ $type->name ] = $label . ' (' . $type->name . ')';
		}

		/**
		 * تغییر فهرست نوع پست‌های قابل انتخاب.
		 *
		 * @param array<string,string> $out فهرست فعلی.
		 */
		return apply_filters( 'dsb_available_post_types', $out );
	}

	/**
	 * نوع پست‌های انتخاب‌شده‌ی معتبر.
	 *
	 * @return string[]
	 */
	public function post_types() {
		$selected = array_values(
			array_filter(
				(array) $this->settings->get( 'post_types' ),
				'post_type_exists'
			)
		);

		if ( $selected ) {
			return $selected;
		}

		// هیچ چیز انتخاب نشده: محصول اگر ووکامرس باشد، وگرنه نوشته.
		return $this->woocommerce_active() && post_type_exists( 'product' )
			? array( 'product' )
			: array( 'post' );
	}

	/**
	 * طبقه‌بندی‌ها و ترم‌های قابل انتخاب برای چند نوع پست.
	 *
	 * @param string[] $post_types نوع پست‌ها.
	 * @return array<string,array{label:string,terms:array<int,string>}>
	 */
	public static function available_terms( array $post_types ) {
		$groups = array();

		foreach ( $post_types as $post_type ) {
			if ( ! post_type_exists( $post_type ) ) {
				continue;
			}

			$type_object = get_post_type_object( $post_type );
			$type_label  = ( $type_object && isset( $type_object->labels->name ) )
				? $type_object->labels->name
				: $post_type;

			foreach ( get_object_taxonomies( $post_type, 'objects' ) as $taxonomy ) {
				if ( empty( $taxonomy->show_ui ) || isset( $groups[ $taxonomy->name ] ) ) {
					continue;
				}

				$terms = get_terms(
					array(
						'taxonomy'   => $taxonomy->name,
						'hide_empty' => false,
					)
				);

				if ( is_wp_error( $terms ) || empty( $terms ) ) {
					continue;
				}

				$items = array();

				foreach ( $terms as $term ) {
					$items[ (int) $term->term_id ] = $term->name . ' (' . (int) $term->count . ')';
				}

				$label = isset( $taxonomy->labels->name ) ? $taxonomy->labels->name : $taxonomy->name;

				$groups[ $taxonomy->name ] = array(
					'label' => $label . ' — ' . $type_label,
					'terms' => $items,
				);
			}
		}

		return $groups;
	}

	/**
	 * انتخاب یک مطلب.
	 *
	 * @return WP_Post|WP_Error
	 */
	public function pick() {
		$post_types = $this->post_types();

		if ( array( 'product' ) === $post_types && ! $this->woocommerce_active() ) {
			return new WP_Error( 'dsb_no_woocommerce', __( 'ووکامرس فعال نیست.', 'daily-bale-post' ) );
		}

		$ids = $this->query( $post_types, true );

		// اگر فهرست «جلوگیری از تکرار» همه‌ی مطالب را حذف کرده بود، بدون آن دوباره تلاش کن.
		if ( empty( $ids ) ) {
			$ids = $this->query( $post_types, false );
		}

		if ( empty( $ids ) ) {
			return new WP_Error(
				'dsb_no_content',
				__( 'هیچ مطلبی با این فیلترها پیدا نشد.', 'daily-bale-post' )
			);
		}

		$post = get_post( $ids[0] );

		if ( ! $post instanceof WP_Post ) {
			return new WP_Error( 'dsb_bad_content', __( 'بارگذاری مطلب ناموفق بود.', 'daily-bale-post' ) );
		}

		return $post;
	}

	/**
	 * اجرای کوئری انتخاب.
	 *
	 * @param string[] $post_types     نوع پست‌ها.
	 * @param bool     $exclude_recent آیا مطالب اخیر حذف شوند.
	 * @return int[]
	 */
	private function query( array $post_types, $exclude_recent ) {
		$args = array(
			'post_type'              => $post_types,
			'post_status'            => 'publish',
			'posts_per_page'         => 1,
			'orderby'                => 'rand',
			'fields'                 => 'ids',
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
			'suppress_filters'       => false,
		);

		$tax_query = $this->tax_query( $post_types );

		if ( $tax_query ) {
			$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		$meta_query = $this->stock_query( $post_types );

		if ( $meta_query ) {
			$args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		if ( $exclude_recent ) {
			$avoid  = (int) $this->settings->get( 'avoid' );
			$recent = $this->recent();

			if ( $avoid > 0 && $recent ) {
				$args['post__not_in'] = array_slice( $recent, -$avoid );
			}
		}

		/**
		 * تغییر آرگومان‌های کوئری انتخاب محتوا.
		 *
		 * @param array $args       آرگومان‌ها.
		 * @param array $post_types نوع پست‌های انتخاب‌شده.
		 */
		$args = apply_filters( 'dsb_selector_query_args', $args, $post_types );

		return array_map( 'absint', (array) get_posts( $args ) );
	}

	/**
	 * ساخت tax_query از ترم‌های انتخاب‌شده.
	 *
	 * فقط طبقه‌بندی‌هایی اعمال می‌شوند که به نوع پست‌های انتخاب‌شده وصل‌اند؛
	 * پس اگر کاربر دسته‌ی محصول را انتخاب کرده ولی محصول را از فهرست
	 * نوع محتوا برداشته، آن فیلتر نادیده گرفته می‌شود.
	 *
	 * @param string[] $post_types نوع پست‌ها.
	 * @return array
	 */
	private function tax_query( array $post_types ) {
		$map = $this->settings->term_map();

		if ( ! $map ) {
			return array();
		}

		$allowed = array();

		foreach ( $post_types as $post_type ) {
			foreach ( get_object_taxonomies( $post_type ) as $taxonomy ) {
				$allowed[ $taxonomy ] = true;
			}
		}

		$clauses = array();

		foreach ( $map as $taxonomy => $terms ) {
			if ( ! isset( $allowed[ $taxonomy ] ) || ! taxonomy_exists( $taxonomy ) || ! $terms ) {
				continue;
			}

			$clauses[] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'term_id',
				'terms'    => $terms,
			);
		}

		if ( ! $clauses ) {
			return array();
		}

		if ( count( $clauses ) > 1 ) {
			$clauses['relation'] = 'OR';
		}

		return $clauses;
	}

	/**
	 * فیلتر موجودی، فقط برای محصولات.
	 *
	 * در حالت ترکیبی (محصول + نوشته)، شرط «یا این متا را ندارد» باعث
	 * می‌شود نوشته‌ها حذف نشوند و فقط محصول ناموجود رد شود.
	 *
	 * @param string[] $post_types نوع پست‌ها.
	 * @return array
	 */
	private function stock_query( array $post_types ) {
		if ( ! $this->settings->get( 'instock' ) || ! in_array( 'product', $post_types, true ) ) {
			return array();
		}

		$in_stock = array(
			'key'   => '_stock_status',
			'value' => 'instock',
		);

		if ( array( 'product' ) === array_values( $post_types ) ) {
			return array( $in_stock );
		}

		return array(
			'relation' => 'OR',
			$in_stock,
			array(
				'key'     => '_stock_status',
				'compare' => 'NOT EXISTS',
			),
		);
	}

	/**
	 * فهرست شناسه‌ی مطالب اخیراً ارسال‌شده.
	 *
	 * @return int[]
	 */
	public function recent() {
		$recent = get_option( self::RECENT_OPTION, array() );
		return array_values( array_filter( array_map( 'absint', (array) $recent ) ) );
	}

	/**
	 * ثبت یک مطلب در فهرست اخیر.
	 *
	 * @param int $post_id شناسه‌ی مطلب.
	 * @return void
	 */
	public function remember( $post_id ) {
		$recent   = $this->recent();
		$recent[] = absint( $post_id );

		$keep   = max( 1, (int) $this->settings->get( 'avoid' ) );
		$recent = array_slice( array_values( array_unique( $recent ) ), -$keep );

		update_option( self::RECENT_OPTION, $recent, false );
	}
}
