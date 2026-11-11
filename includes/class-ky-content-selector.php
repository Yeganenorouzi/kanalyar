<?php
/**
 * انتخاب محتوا برای انتشار.
 *
 * @package Kanalyar
 */

defined( 'ABSPATH' ) || exit;

/**
 * انتخاب یک مطلب (محصول، نوشته، برگه یا هر نوع محتوای دیگر)
 * بر اساس فیلترها و راهبرد یک قانون.
 */
class KY_Content_Selector {

	/**
	 * پیشوند آپشن فهرست ارسال‌شده‌های هر قانون.
	 */
	const RECENT_PREFIX = 'dsb_recent_';

	/**
	 * انتخاب یک مطلب برای یک قانون.
	 *
	 * @param array $rule قانون.
	 * @return WP_Post|WP_Error
	 */
	public function pick( array $rule ) {
		$post = $this->query( $rule, true );

		// راهبرد تصادفی: اگر لیست تکرار همه را حذف کرد، بدون آن دوباره تلاش کن.
		if ( ! $post && 'random' === $rule['strategy'] ) {
			$post = $this->query( $rule, false );
		}

		if ( ! $post ) {
			return new WP_Error(
				'ky_no_content',
				$this->empty_message( $rule )
			);
		}

		return $post;
	}

	/**
	 * پیام خطای متناسب با راهبرد.
	 *
	 * @param array $rule قانون.
	 * @return string
	 */
	private function empty_message( array $rule ) {
		if ( 'random' !== $rule['strategy'] ) {
			return __( 'همه‌ی مطالب این قانون قبلاً ارسال شده‌اند.', 'kanalyar' );
		}

		return __( 'هیچ مطلبی با این فیلترها پیدا نشد.', 'kanalyar' );
	}

	/**
	 * اجرای کوئری.
	 *
	 * @param array $rule           قانون.
	 * @param bool  $exclude_recent آیا ارسال‌شده‌های اخیر حذف شوند.
	 * @return WP_Post|null
	 */
	private function query( array $rule, $exclude_recent ) {
		$args = array(
			'post_type'              => $rule['post_types'],
			'post_status'            => 'publish',
			'posts_per_page'         => 1,
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'suppress_filters'       => false,
		);

		switch ( $rule['strategy'] ) {
			case 'newest':
				$args['orderby'] = 'date';
				$args['order']   = 'DESC';
				break;

			case 'oldest':
				$args['orderby'] = 'date';
				$args['order']   = 'ASC';
				break;

			default:
				$args['orderby'] = 'rand';
				break;
		}

		$tax_query = array();

		foreach ( (array) $rule['terms'] as $taxonomy => $ids ) {
			if ( ! $ids || ! taxonomy_exists( $taxonomy ) ) {
				continue;
			}

			$tax_query[] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'term_id',
				'terms'    => array_map( 'absint', $ids ),
			);
		}

		if ( $tax_query ) {
			if ( count( $tax_query ) > 1 ) {
				$tax_query['relation'] = 'OR';
			}

			$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		// فیلتر موجودی فقط وقتی معنی دارد که محصول در کار باشد.
		if ( ! empty( $rule['instock_only'] ) && $this->includes_products( $rule ) ) {
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'OR',
				array(
					'key'   => '_stock_status',
					'value' => 'instock',
				),
				array(
					'key'     => '_stock_status',
					'compare' => 'NOT EXISTS',
				),
			);
		}

		$recent = $this->recent( $rule['id'] );

		if ( $exclude_recent && $recent ) {
			$avoid = (int) $rule['avoid'];

			// راهبردهای ترتیبی همیشه باید کل تاریخچه را رد کنند،
			// وگرنه روی جدیدترین مطلب گیر می‌کنند و هر روز همان را می‌فرستند.
			if ( 'random' === $rule['strategy'] ) {
				if ( $avoid > 0 ) {
					$args['post__not_in'] = array_slice( $recent, -$avoid );
				}
			} else {
				$args['post__not_in'] = $recent;
			}
		}

		$posts = get_posts( $args );

		return $posts ? $posts[0] : null;
	}

	/**
	 * آیا این قانون محصول ووکامرس را هم شامل می‌شود.
	 *
	 * @param array $rule قانون.
	 * @return bool
	 */
	private function includes_products( array $rule ) {
		return function_exists( 'wc_get_product' ) && in_array( 'product', (array) $rule['post_types'], true );
	}

	/**
	 * فهرست شناسه‌ی مطالب اخیراً ارسال‌شده‌ی یک قانون.
	 *
	 * @param string $rule_id شناسه‌ی قانون.
	 * @return int[]
	 */
	public function recent( $rule_id ) {
		$recent = get_option( self::RECENT_PREFIX . $rule_id, array() );

		return array_values( array_filter( array_map( 'absint', (array) $recent ) ) );
	}

	/**
	 * ثبت یک مطلب در فهرست ارسال‌شده‌های یک قانون.
	 *
	 * @param array $rule    قانون.
	 * @param int   $post_id شناسه‌ی مطلب.
	 * @return void
	 */
	public function remember( array $rule, $post_id ) {
		$recent   = $this->recent( $rule['id'] );
		$recent[] = absint( $post_id );
		$recent   = array_values( array_unique( $recent ) );

		// راهبرد تصادفی فقط به پنجره‌ی اخیر نیاز دارد؛ راهبردهای ترتیبی
		// باید کل تاریخچه را بدانند تا دوباره از اول شروع نکنند.
		if ( 'random' === $rule['strategy'] ) {
			$keep   = max( 1, (int) $rule['avoid'] );
			$recent = array_slice( $recent, -$keep );
		} else {
			$recent = array_slice( $recent, -5000 );
		}

		update_option( self::RECENT_PREFIX . $rule['id'], $recent, false );
	}

	/**
	 * پاک‌کردن تاریخچه‌ی ارسال یک قانون.
	 *
	 * @param string $rule_id شناسه‌ی قانون.
	 * @return void
	 */
	public function forget( $rule_id ) {
		delete_option( self::RECENT_PREFIX . $rule_id );
	}

	/**
	 * شمارش مطالب واجد شرایط و ارسال‌شده‌ی یک قانون.
	 *
	 * @param array $rule قانون.
	 * @return array{total:int,sent:int}
	 */
	public function stats( array $rule ) {
		$sent = count( $this->recent( $rule['id'] ) );

		$counts = 0;

		foreach ( (array) $rule['post_types'] as $type ) {
			$c = wp_count_posts( $type );

			if ( isset( $c->publish ) ) {
				$counts += (int) $c->publish;
			}
		}

		return array(
			'total' => $counts,
			'sent'  => $sent,
		);
	}
}
