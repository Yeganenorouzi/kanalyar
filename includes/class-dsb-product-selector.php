<?php
/**
 * انتخاب محصول برای ارسال.
 *
 * @package DailyBalePost
 */

defined( 'ABSPATH' ) || exit;

/**
 * انتخاب یک محصول تصادفی بر اساس فیلترهای تنظیمات.
 */
class DSB_Product_Selector {

	/**
	 * نام آپشن محصولات اخیر.
	 */
	const RECENT_OPTION = 'dsb_recent';

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
	 * انتخاب یک محصول.
	 *
	 * @return WC_Product|WP_Error
	 */
	public function pick() {
		if ( ! $this->woocommerce_active() ) {
			return new WP_Error( 'dsb_no_woocommerce', __( 'ووکامرس فعال نیست.', 'daily-bale-post' ) );
		}

		$ids = $this->query( true );

		// اگر لیست «جلوگیری از تکرار» همه‌ی محصولات را حذف کرده بود، بدون آن دوباره تلاش کن.
		if ( empty( $ids ) ) {
			$ids = $this->query( false );
		}

		if ( empty( $ids ) ) {
			return new WP_Error(
				'dsb_no_product',
				__( 'هیچ محصولی با این فیلترها پیدا نشد.', 'daily-bale-post' )
			);
		}

		$product = wc_get_product( $ids[0] );

		if ( ! $product instanceof WC_Product ) {
			return new WP_Error( 'dsb_bad_product', __( 'بارگذاری محصول ناموفق بود.', 'daily-bale-post' ) );
		}

		return $product;
	}

	/**
	 * اجرای کوئری محصولات.
	 *
	 * @param bool $exclude_recent آیا محصولات اخیر حذف شوند.
	 * @return int[]
	 */
	private function query( $exclude_recent ) {
		$args = array(
			'post_type'              => 'product',
			'post_status'            => 'publish',
			'posts_per_page'         => 1,
			'orderby'                => 'rand',
			'fields'                 => 'ids',
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
			'suppress_filters'       => false,
		);

		$categories = array_filter( array_map( 'absint', (array) $this->settings->get( 'categories' ) ) );

		if ( $categories ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'term_id',
					'terms'    => $categories,
				),
			);
		}

		if ( $this->settings->get( 'instock' ) ) {
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => '_stock_status',
					'value' => 'instock',
				),
			);
		}

		if ( $exclude_recent ) {
			$avoid  = (int) $this->settings->get( 'avoid' );
			$recent = $this->recent();

			if ( $avoid > 0 && $recent ) {
				$args['post__not_in'] = array_slice( $recent, -$avoid );
			}
		}

		return array_map( 'absint', (array) get_posts( $args ) );
	}

	/**
	 * فهرست شناسه‌ی محصولات اخیراً ارسال‌شده.
	 *
	 * @return int[]
	 */
	public function recent() {
		$recent = get_option( self::RECENT_OPTION, array() );
		return array_values( array_filter( array_map( 'absint', (array) $recent ) ) );
	}

	/**
	 * ثبت یک محصول در فهرست اخیر.
	 *
	 * @param int $product_id شناسه‌ی محصول.
	 * @return void
	 */
	public function remember( $product_id ) {
		$recent   = $this->recent();
		$recent[] = absint( $product_id );

		$keep   = max( 1, (int) $this->settings->get( 'avoid' ) );
		$recent = array_slice( array_values( array_unique( $recent ) ), -$keep );

		update_option( self::RECENT_OPTION, $recent, false );
	}
}
