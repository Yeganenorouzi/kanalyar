<?php
/**
 * جعبه‌ی انتشار در صفحه‌ی ویرایش مطلب.
 *
 * @package Kanalyar
 */

defined( 'ABSPATH' ) || exit;

/**
 * نمایش وضعیت ارسال یک مطلب و دکمه‌ی ارسال دستی.
 */
class KY_Metabox {

	/**
	 * اکشن نانس.
	 */
	const NONCE = 'ky_metabox_action';

	/**
	 * قوانین.
	 *
	 * @var KY_Rules
	 */
	private $rules;

	/**
	 * مقصدها.
	 *
	 * @var KY_Destinations
	 */
	private $destinations;

	/**
	 * محرک انتشار.
	 *
	 * @var KY_Trigger
	 */
	private $trigger;

	/**
	 * سازنده.
	 *
	 * @param KY_Rules        $rules        قوانین.
	 * @param KY_Destinations $destinations مقصدها.
	 * @param KY_Trigger      $trigger      محرک.
	 */
	public function __construct( KY_Rules $rules, KY_Destinations $destinations, KY_Trigger $trigger ) {
		$this->rules        = $rules;
		$this->destinations = $destinations;
		$this->trigger      = $trigger;
	}

	/**
	 * ثبت هوک‌ها.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'add_meta_boxes', array( $this, 'add_box' ) );
		add_action( 'admin_post_ky_send_post', array( $this, 'handle_send' ) );
	}

	/**
	 * افزودن جعبه به نوع‌های محتوایی که قانونی دارند.
	 *
	 * @return void
	 */
	public function add_box() {
		$types = array();

		foreach ( $this->rules->all() as $rule ) {
			$types = array_merge( $types, (array) $rule['post_types'] );
		}

		$types = array_values( array_unique( array_filter( $types ) ) );

		if ( ! $types ) {
			return;
		}

		add_meta_box(
			'ky-publish-box',
			__( 'انتشار در کانال‌ها', 'kanalyar' ),
			array( $this, 'render' ),
			$types,
			'side',
			'default'
		);
	}

	/**
	 * رندر جعبه.
	 *
	 * @param WP_Post $post مطلب.
	 * @return void
	 */
	public function render( $post ) {
		$sent    = $this->trigger->sent_map( $post->ID );
		$matched = array();

		foreach ( $this->rules->all() as $rule ) {
			if ( KY_Rules::matches( $rule, $post ) ) {
				$matched[] = $rule;
			}
		}

		echo '<div class="ky-box">';

		if ( ! $matched ) {
			echo '<p class="description">' . esc_html__( 'هیچ قانونی با این مطلب نمی‌خواند.', 'kanalyar' ) . '</p>';
			echo '</div>';
			return;
		}

		echo '<ul class="ky-box-rules">';

		foreach ( $matched as $rule ) {
			$when   = isset( $sent[ $rule['id'] ] ) ? (int) $sent[ $rule['id'] ] : 0;
			$labels = array();

			foreach ( $this->destinations->pick( (array) $rule['destinations'] ) as $destination ) {
				$labels[] = $destination['label'];
			}

			echo '<li>';
			echo '<strong>' . esc_html( $rule['label'] ) . '</strong><br>';

			if ( $when ) {
				echo '<span class="ky-box-ok">✅ ' . esc_html__( 'ارسال شد:', 'kanalyar' ) . ' ';
				echo esc_html( get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $when ), 'Y-m-d H:i' ) );
				echo '</span>';
			} else {
				echo '<span class="ky-box-pending">⏳ ' . esc_html__( 'هنوز ارسال نشده', 'kanalyar' ) . '</span>';
			}

			if ( $labels ) {
				echo '<br><span class="description">' . esc_html( implode( '، ', $labels ) ) . '</span>';
			} else {
				echo '<br><span class="description">' . esc_html__( 'بدون مقصد فعال', 'kanalyar' ) . '</span>';
			}

			echo '</li>';
		}

		echo '</ul>';

		if ( 'publish' === $post->post_status ) {
			$url = wp_nonce_url(
				admin_url( 'admin-post.php?action=ky_send_post&post=' . $post->ID ),
				self::NONCE
			);

			echo '<p>';
			echo '<a href="' . esc_url( $url ) . '" class="button button-primary">';
			echo '📤 ' . esc_html__( 'ارسال همین حالا', 'kanalyar' );
			echo '</a>';
			echo '</p>';
			echo '<p class="description">' . esc_html__( 'به همه‌ی قانون‌های بالا ارسال می‌شود، حتی اگر قبلاً رفته باشد.', 'kanalyar' ) . '</p>';
		} else {
			echo '<p class="description">' . esc_html__( 'برای ارسال، مطلب باید منتشر شده باشد.', 'kanalyar' ) . '</p>';
		}

		echo '</div>';
	}

	/**
	 * پردازش دکمه‌ی ارسال دستی.
	 *
	 * @return void
	 */
	public function handle_send() {
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;

		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'دسترسی لازم را ندارید.', 'kanalyar' ) );
		}

		check_admin_referer( self::NONCE );

		$post = get_post( $post_id );

		if ( ! $post instanceof WP_Post ) {
			wp_die( esc_html__( 'مطلب پیدا نشد.', 'kanalyar' ) );
		}

		$ids = array();

		foreach ( $this->rules->all() as $rule ) {
			if ( KY_Rules::matches( $rule, $post ) ) {
				$ids[] = $rule['id'];
			}
		}

		$results = $this->trigger->send_post( $post_id, 'manual', $ids );

		$ok = 0;

		foreach ( $results as $result ) {
			if ( ! empty( $result['ok'] ) ) {
				$ok++;
			}
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'ky_sent'  => $ok,
					'ky_total' => count( $results ),
				),
				get_edit_post_link( $post_id, 'raw' )
			)
		);
		exit;
	}
}
