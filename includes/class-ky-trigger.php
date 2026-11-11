<?php
/**
 * انتشار فوری هنگام منتشر شدن مطلب.
 *
 * @package Kanalyar
 */

defined( 'ABSPATH' ) || exit;

/**
 * قوانینی که محرکشان «هنگام انتشار» است را به‌محض منتشر شدن مطلب اجرا می‌کند.
 *
 * ارسال مستقیماً در لحظه‌ی ذخیره انجام نمی‌شود، چون نویسنده نباید منتظر
 * چند درخواست شبکه بماند و ممکن است تصویر شاخص هنوز ثبت نشده باشد.
 * به‌جایش مطلب در صف می‌نشیند و در پایان همان درخواست، بعد از بسته‌شدن
 * پاسخ، ارسال می‌شود.
 */
class KY_Trigger {

	/**
	 * پیشوند متای «این مطلب با این قانون ارسال شد».
	 */
	const SENT_META = '_ky_sent';

	/**
	 * قوانین.
	 *
	 * @var KY_Rules
	 */
	private $rules;

	/**
	 * انتشاردهنده.
	 *
	 * @var KY_Publisher
	 */
	private $publisher;

	/**
	 * تنظیمات.
	 *
	 * @var KY_Settings
	 */
	private $settings;

	/**
	 * صف مطالبی که باید در پایان این درخواست ارسال شوند.
	 *
	 * @var int[]
	 */
	private $queue = array();

	/**
	 * سازنده.
	 *
	 * @param KY_Settings  $settings  تنظیمات.
	 * @param KY_Rules     $rules     قوانین.
	 * @param KY_Publisher $publisher انتشاردهنده.
	 */
	public function __construct( KY_Settings $settings, KY_Rules $rules, KY_Publisher $publisher ) {
		$this->settings  = $settings;
		$this->rules     = $rules;
		$this->publisher = $publisher;
	}

	/**
	 * ثبت هوک‌ها.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'transition_post_status', array( $this, 'on_transition' ), 10, 3 );
		add_action( 'shutdown', array( $this, 'flush' ), 5 );
	}

	/**
	 * تشخیص انتشار تازه.
	 *
	 * فقط گذار از وضعیت غیرمنتشر به منتشر مهم است؛ ویرایش یک مطلبِ
	 * از قبل منتشرشده دوباره ارسال نمی‌شود.
	 *
	 * @param string  $new_status وضعیت تازه.
	 * @param string  $old_status وضعیت قبلی.
	 * @param WP_Post $post       مطلب.
	 * @return void
	 */
	public function on_transition( $new_status, $old_status, $post ) {
		if ( 'publish' !== $new_status || 'publish' === $old_status ) {
			return;
		}

		if ( ! $post instanceof WP_Post || wp_is_post_revision( $post ) || wp_is_post_autosave( $post ) ) {
			return;
		}

		if ( ! $this->settings->get( 'enabled' ) ) {
			return;
		}

		$this->queue[] = (int) $post->ID;
	}

	/**
	 * اجرای صف در پایان درخواست.
	 *
	 * @return void
	 */
	public function flush() {
		if ( ! $this->queue ) {
			return;
		}

		$ids         = array_unique( $this->queue );
		$this->queue = array();

		// نویسنده نباید منتظر ارسال بماند.
		ignore_user_abort( true );

		if ( function_exists( 'fastcgi_finish_request' ) ) {
			fastcgi_finish_request();
		} elseif ( function_exists( 'litespeed_finish_request' ) ) {
			litespeed_finish_request();
		}

		foreach ( $ids as $id ) {
			$this->send_post( $id );
		}
	}

	/**
	 * ارسال یک مطلب با همه‌ی قوانینی که با آن می‌خوانند.
	 *
	 * @param int    $post_id شناسه‌ی مطلب.
	 * @param string $source  منبع اجرا.
	 * @param array  $only    فقط این شناسه‌های قانون؛ خالی یعنی همه.
	 * @return array نتیجه به‌ازای هر قانون.
	 */
	public function send_post( $post_id, $source = 'publish', array $only = array() ) {
		$post = get_post( $post_id );

		if ( ! $post instanceof WP_Post ) {
			return array();
		}

		$out   = array();
		$rules = $only ? $this->rules->all() : $this->rules->on_publish();

		foreach ( $rules as $rule ) {
			if ( $only && ! in_array( $rule['id'], $only, true ) ) {
				continue;
			}

			if ( ! $only && ! KY_Rules::matches( $rule, $post ) ) {
				continue;
			}

			// جلوگیری از ارسال دوباره‌ی همان مطلب با همان قانون.
			if ( 'publish' === $source && $this->already_sent( $post_id, $rule['id'] ) ) {
				continue;
			}

			$result = $this->publisher->publish( $rule, $source, $post );

			if ( ! empty( $result['ok'] ) ) {
				$this->mark_sent( $post_id, $rule['id'] );
			}

			$out[ $rule['id'] ] = $result;
		}

		return $out;
	}

	/**
	 * آیا این مطلب قبلاً با این قانون ارسال شده است.
	 *
	 * @param int    $post_id شناسه‌ی مطلب.
	 * @param string $rule_id شناسه‌ی قانون.
	 * @return bool
	 */
	public function already_sent( $post_id, $rule_id ) {
		$sent = get_post_meta( $post_id, self::SENT_META, true );

		return is_array( $sent ) && isset( $sent[ $rule_id ] );
	}

	/**
	 * ثبت اینکه این مطلب با این قانون ارسال شد.
	 *
	 * @param int    $post_id شناسه‌ی مطلب.
	 * @param string $rule_id شناسه‌ی قانون.
	 * @return void
	 */
	public function mark_sent( $post_id, $rule_id ) {
		$sent = get_post_meta( $post_id, self::SENT_META, true );
		$sent = is_array( $sent ) ? $sent : array();

		$sent[ $rule_id ] = time();

		update_post_meta( $post_id, self::SENT_META, $sent );
	}

	/**
	 * تاریخچه‌ی ارسال یک مطلب.
	 *
	 * @param int $post_id شناسه‌ی مطلب.
	 * @return array<string,int> شناسه‌ی قانون => زمان.
	 */
	public function sent_map( $post_id ) {
		$sent = get_post_meta( $post_id, self::SENT_META, true );

		return is_array( $sent ) ? $sent : array();
	}
}
