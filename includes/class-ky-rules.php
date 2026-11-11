<?php
/**
 * قوانین انتشار: چه محتوایی، به کدام مقصدها، چه زمانی.
 *
 * @package Kanalyar
 */

defined( 'ABSPATH' ) || exit;

/**
 * نگه‌داری و اعتبارسنجی قوانین انتشار.
 */
class KY_Rules {

	/**
	 * نام آپشن.
	 */
	const OPTION = 'dsb_rules';

	/**
	 * کش درون‌درخواستی.
	 *
	 * @var array|null
	 */
	private $cache = null;

	/**
	 * راهبردهای انتخاب محتوا.
	 *
	 * @return array<string,string>
	 */
	public static function strategies() {
		return array(
			'random' => __( 'تصادفی — مناسب کاتالوگ محصولات', 'kanalyar' ),
			'newest' => __( 'جدیدترینِ ارسال‌نشده — مناسب بلاگ و اخبار', 'kanalyar' ),
			'oldest' => __( 'قدیمی‌ترینِ ارسال‌نشده — بازنشر آرشیو', 'kanalyar' ),
		);
	}

	/**
	 * محرک‌های اجرای یک قانون.
	 *
	 * @return array<string,string>
	 */
	public static function triggers() {
		return array(
			'schedule' => __( 'زمان‌بندی — هر روز سر ساعت، یک مطلب', 'kanalyar' ),
			'publish'  => __( 'هنگام انتشار — به‌محض منتشر شدن مطلب تازه', 'kanalyar' ),
			'both'     => __( 'هر دو — هم فوری هنگام انتشار، هم زمان‌بندی روزانه', 'kanalyar' ),
		);
	}

	/**
	 * منابع ساخت هشتگ.
	 *
	 * @return array<string,string>
	 */
	public static function hashtag_sources() {
		return array(
			'none'       => __( 'بدون هشتگ', 'kanalyar' ),
			'tags'       => __( 'از برچسب‌ها', 'kanalyar' ),
			'categories' => __( 'از دسته‌بندی‌ها', 'kanalyar' ),
			'both'       => __( 'از برچسب‌ها و دسته‌بندی‌ها', 'kanalyar' ),
		);
	}

	/**
	 * ساختار پیش‌فرض یک قانون.
	 *
	 * @return array
	 */
	public static function blank() {
		return array(
			'id'           => '',
			'label'        => '',
			'enabled'      => 1,
			'post_types'   => array( 'post' ),
			'terms'        => array(),
			'strategy'     => 'random',
			'instock_only' => 0,
			'show_price'   => 1,
			'show_excerpt' => 1,
			'avoid'        => 20,
			'photo_mode'   => 'auto',
			'destinations' => array(),
			'days'         => array( 0, 1, 2, 3, 4, 5, 6 ),
			'hour'         => 10,
			'minute'       => 45,
			'max_attempts' => 3,
			'cta'          => '',
			'template'     => '',
			'trigger'      => 'schedule',
			'hashtags'     => 'tags',
			'silent'       => 0,
			'pin'          => 0,
		);
	}

	/**
	 * همه‌ی قوانین.
	 *
	 * @return array[]
	 */
	public function all() {
		if ( null === $this->cache ) {
			$stored = get_option( self::OPTION, array() );
			$stored = is_array( $stored ) ? $stored : array();

			$this->cache = array();

			foreach ( $stored as $row ) {
				if ( is_array( $row ) && ! empty( $row['id'] ) ) {
					$this->cache[] = wp_parse_args( $row, self::blank() );
				}
			}
		}

		return $this->cache;
	}

	/**
	 * فقط قوانین فعال که حداقل یک مقصد دارند.
	 *
	 * @return array[]
	 */
	public function active() {
		return array_values(
			array_filter(
				$this->all(),
				function ( $row ) {
					return ! empty( $row['enabled'] ) && ! empty( $row['destinations'] ) && ! empty( $row['post_types'] );
				}
			)
		);
	}

	/**
	 * قوانین فعالی که زمان‌بندی روزانه دارند.
	 *
	 * @return array[]
	 */
	public function scheduled() {
		return array_values(
			array_filter(
				$this->active(),
				function ( $row ) {
					return in_array( $row['trigger'], array( 'schedule', 'both' ), true );
				}
			)
		);
	}

	/**
	 * قوانین فعالی که هنگام انتشار مطلب اجرا می‌شوند.
	 *
	 * @return array[]
	 */
	public function on_publish() {
		return array_values(
			array_filter(
				$this->active(),
				function ( $row ) {
					return in_array( $row['trigger'], array( 'publish', 'both' ), true );
				}
			)
		);
	}

	/**
	 * آیا یک مطلب با فیلترهای یک قانون می‌خواند.
	 *
	 * @param array   $rule قانون.
	 * @param WP_Post $post مطلب.
	 * @return bool
	 */
	public static function matches( array $rule, WP_Post $post ) {
		if ( ! in_array( $post->post_type, (array) $rule['post_types'], true ) ) {
			return false;
		}

		$terms = (array) $rule['terms'];

		if ( ! $terms ) {
			return true;
		}

		// فیلتر دسته‌بندی‌ها با OR ترکیب می‌شود، مثل خود کوئری.
		foreach ( $terms as $taxonomy => $ids ) {
			if ( ! $ids || ! taxonomy_exists( $taxonomy ) ) {
				continue;
			}

			if ( has_term( array_map( 'absint', $ids ), $taxonomy, $post ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * یک قانون بر اساس شناسه.
	 *
	 * @param string $id شناسه.
	 * @return array|null
	 */
	public function get( $id ) {
		foreach ( $this->all() as $row ) {
			if ( $row['id'] === $id ) {
				return $row;
			}
		}

		return null;
	}

	/**
	 * ذخیره‌ی فهرست قوانین.
	 *
	 * @param array[] $rows قوانین پاک‌سازی‌شده.
	 * @return void
	 */
	public function save( array $rows ) {
		update_option( self::OPTION, array_values( $rows ), true );
		$this->cache = null;
	}

	/**
	 * پاک‌سازی ورودی فرم قوانین.
	 *
	 * @param array $raw داده‌ی خام.
	 * @return array[]
	 */
	public function sanitize( array $raw ) {
		$strategies  = self::strategies();
		$photo_modes = KY_Settings::photo_modes();
		$triggers    = self::triggers();
		$hashtags    = self::hashtag_sources();
		$out         = array();
		$seen        = array();

		foreach ( $raw as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$types = isset( $row['post_types'] ) ? (array) $row['post_types'] : array();
			$types = array_values( array_filter( array_map( 'sanitize_key', $types ), 'post_type_exists' ) );

			$label = sanitize_text_field( $row['label'] ?? '' );

			// ردیف بی‌محتوا نادیده گرفته می‌شود.
			if ( '' === $label && empty( $types ) ) {
				continue;
			}

			$id = sanitize_key( $row['id'] ?? '' );

			if ( '' === $id || isset( $seen[ $id ] ) ) {
				$id = self::new_id();
			}

			$seen[ $id ] = true;

			$days = isset( $row['days'] ) ? array_map( 'intval', (array) $row['days'] ) : array();
			$days = array_values( array_unique( array_filter( $days, array( 'KY_Settings', 'is_weekday' ) ) ) );
			sort( $days );

			$dests = isset( $row['destinations'] ) ? (array) $row['destinations'] : array();
			$dests = array_values( array_filter( array_map( 'sanitize_key', $dests ) ) );

			$strategy = sanitize_key( $row['strategy'] ?? 'random' );
			$photo    = sanitize_key( $row['photo_mode'] ?? 'auto' );
			$trigger  = sanitize_key( $row['trigger'] ?? 'schedule' );
			$hashtag  = sanitize_key( $row['hashtags'] ?? 'tags' );

			// قالب پیام می‌تواند چند خطی باشد، پس sanitize_textarea_field.
			$template = isset( $row['template'] ) ? sanitize_textarea_field( $row['template'] ) : '';

			$out[] = array(
				'id'           => $id,
				'label'        => '' !== $label ? $label : __( 'قانون بدون نام', 'kanalyar' ),
				'enabled'      => empty( $row['enabled'] ) ? 0 : 1,
				'post_types'   => $types ? $types : array( 'post' ),
				'terms'        => self::sanitize_terms( $row['terms'] ?? array() ),
				'strategy'     => isset( $strategies[ $strategy ] ) ? $strategy : 'random',
				'instock_only' => empty( $row['instock_only'] ) ? 0 : 1,
				'show_price'   => empty( $row['show_price'] ) ? 0 : 1,
				'show_excerpt' => empty( $row['show_excerpt'] ) ? 0 : 1,
				'avoid'        => max( 0, min( 500, (int) ( $row['avoid'] ?? 20 ) ) ),
				'photo_mode'   => isset( $photo_modes[ $photo ] ) ? $photo : 'auto',
				'destinations' => $dests,
				'days'         => $days,
				'hour'         => max( 0, min( 23, (int) ( $row['hour'] ?? 10 ) ) ),
				'minute'       => max( 0, min( 59, (int) ( $row['minute'] ?? 45 ) ) ),
				'max_attempts' => max( 1, min( 10, (int) ( $row['max_attempts'] ?? 3 ) ) ),
				'cta'          => sanitize_text_field( $row['cta'] ?? '' ),
				'template'     => $template,
				'trigger'      => isset( $triggers[ $trigger ] ) ? $trigger : 'schedule',
				'hashtags'     => isset( $hashtags[ $hashtag ] ) ? $hashtag : 'tags',
				'silent'       => empty( $row['silent'] ) ? 0 : 1,
				'pin'          => empty( $row['pin'] ) ? 0 : 1,
			);
		}

		return $out;
	}

	/**
	 * پاک‌سازی فیلتر دسته‌بندی‌ها.
	 *
	 * ساختار: array( 'category' => array( 3, 7 ), 'product_cat' => array( 12 ) )
	 *
	 * @param mixed $raw داده‌ی خام.
	 * @return array
	 */
	private static function sanitize_terms( $raw ) {
		$out = array();

		foreach ( (array) $raw as $taxonomy => $ids ) {
			$taxonomy = sanitize_key( $taxonomy );

			if ( ! $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
				continue;
			}

			$ids = array_values( array_filter( array_map( 'absint', (array) $ids ) ) );

			if ( $ids ) {
				$out[ $taxonomy ] = $ids;
			}
		}

		return $out;
	}

	/**
	 * ساخت شناسه‌ی یکتا.
	 *
	 * @return string
	 */
	public static function new_id() {
		return 'rule_' . substr( md5( uniqid( '', true ) ), 0, 10 );
	}

	/**
	 * نوع‌های محتوایی که می‌توان از آن‌ها پست ساخت.
	 *
	 * @return array<string,string> اسلاگ => نام.
	 */
	public static function available_post_types() {
		$types = get_post_types(
			array(
				'public' => true,
			),
			'objects'
		);

		$out = array();

		foreach ( $types as $slug => $type ) {
			if ( 'attachment' === $slug ) {
				continue;
			}

			$out[ $slug ] = $type->labels->name ? $type->labels->name : $slug;
		}

		/**
		 * تغییر فهرست نوع‌های محتوایی قابل انتشار.
		 *
		 * @param array $out نوع‌ها.
		 */
		$out = apply_filters( 'ky_available_post_types', $out );

		// نام فیلتر در نسخه‌های پیش از ۶.
		return apply_filters( 'dsb_available_post_types', $out );
	}

	/**
	 * دسته‌بندی‌های قابل فیلتر برای چند نوع محتوا.
	 *
	 * @param array $post_types نوع‌های محتوا.
	 * @return array<string,object> اسلاگ تکسونومی => آبجکت.
	 */
	public static function taxonomies_for( array $post_types ) {
		$out = array();

		foreach ( $post_types as $type ) {
			foreach ( get_object_taxonomies( $type, 'objects' ) as $slug => $tax ) {
				if ( $tax->public && $tax->show_ui ) {
					$out[ $slug ] = $tax;
				}
			}
		}

		return $out;
	}
}
