<?php
/**
 * اعلان وضعیت سفارش ووکامرس.
 *
 * @package Kanalyar
 */

defined( 'ABSPATH' ) || exit;

/**
 * با هر تغییر وضعیت سفارش، یک اعلان به کانال‌های مدیریتی می‌فرستد.
 *
 * این اعلان‌ها برای مدیر فروشگاه‌اند، نه مشتری؛ ارسال به مشتری نیاز به
 * ربات دوطرفه و اتصال حساب کاربر دارد که هنوز پیاده نشده است.
 */
class KY_Orders {

	/**
	 * نام آپشن تنظیمات اعلان سفارش.
	 */
	const OPTION = 'dsb_orders';

	/**
	 * متای «برای این وضعیت اعلان رفته».
	 */
	const SENT_META = '_ky_order_sent';

	/**
	 * وضعیت مجازی «سفارش تازه».
	 */
	const STATUS_NEW = 'new';

	/**
	 * تنظیمات سراسری.
	 *
	 * @var KY_Settings
	 */
	private $settings;

	/**
	 * مقصدها.
	 *
	 * @var KY_Destinations
	 */
	private $destinations;

	/**
	 * تاریخچه.
	 *
	 * @var KY_Logger
	 */
	private $logger;

	/**
	 * کش تنظیمات.
	 *
	 * @var array|null
	 */
	private $config = null;

	/**
	 * صف سفارش‌هایی که باید در پایان درخواست اعلان شوند.
	 *
	 * @var array<int,string>
	 */
	private $queue = array();

	/**
	 * سازنده.
	 *
	 * @param KY_Settings     $settings     تنظیمات.
	 * @param KY_Destinations $destinations مقصدها.
	 * @param KY_Logger       $logger       تاریخچه.
	 */
	public function __construct( KY_Settings $settings, KY_Destinations $destinations, KY_Logger $logger ) {
		$this->settings     = $settings;
		$this->destinations = $destinations;
		$this->logger       = $logger;
	}

	/**
	 * وضعیت‌هایی که می‌توان رویشان اعلان گرفت.
	 *
	 * @return array<string,string>
	 */
	public static function statuses() {
		$out = array(
			self::STATUS_NEW => __( 'سفارش تازه ثبت شد', 'kanalyar' ),
		);

		$wc = array(
			'pending'    => __( 'در انتظار پرداخت', 'kanalyar' ),
			'processing' => __( 'در حال انجام (پرداخت شد)', 'kanalyar' ),
			'on-hold'    => __( 'در انتظار بررسی', 'kanalyar' ),
			'completed'  => __( 'تکمیل شد', 'kanalyar' ),
			'cancelled'  => __( 'لغو شد', 'kanalyar' ),
			'refunded'   => __( 'مسترد شد', 'kanalyar' ),
			'failed'     => __( 'ناموفق', 'kanalyar' ),
		);

		return array_merge( $out, $wc );
	}

	/**
	 * قالب پیش‌فرض اعلان.
	 *
	 * @return string
	 */
	public static function default_template() {
		return "🧾 *{status_label}*\n\n"
			. "سفارش: *{order_number}*\n"
			. "مبلغ: *{total}*\n"
			. "{items_count} قلم کالا\n\n"
			. "{items}\n\n"
			. "{customer}\n"
			. "پرداخت: {payment_method}\n\n"
			. "🔗 {admin_link}";
	}

	/**
	 * جای‌نشان‌های قابل استفاده در قالب اعلان.
	 *
	 * @return array<string,string>
	 */
	public static function placeholders() {
		return array(
			'{order_number}'    => __( 'شماره سفارش', 'kanalyar' ),
			'{status_label}'    => __( 'عنوان وضعیت تازه', 'kanalyar' ),
			'{status}'          => __( 'نام ماشینی وضعیت', 'kanalyar' ),
			'{old_status}'      => __( 'وضعیت قبلی', 'kanalyar' ),
			'{total}'           => __( 'مبلغ کل با واحد پول', 'kanalyar' ),
			'{total_amount}'    => __( 'فقط عدد مبلغ کل', 'kanalyar' ),
			'{items}'           => __( 'فهرست کالاها، هر کدام در یک خط', 'kanalyar' ),
			'{items_count}'     => __( 'تعداد اقلام', 'kanalyar' ),
			'{customer}'        => __( 'بلوک اطلاعات مشتری', 'kanalyar' ),
			'{customer_name}'   => __( 'نام مشتری', 'kanalyar' ),
			'{customer_phone}'  => __( 'تلفن مشتری', 'kanalyar' ),
			'{customer_email}'  => __( 'ایمیل مشتری', 'kanalyar' ),
			'{address}'         => __( 'نشانی ارسال', 'kanalyar' ),
			'{payment_method}'  => __( 'روش پرداخت', 'kanalyar' ),
			'{shipping_method}' => __( 'روش ارسال', 'kanalyar' ),
			'{note}'            => __( 'یادداشت مشتری', 'kanalyar' ),
			'{date}'            => __( 'تاریخ سفارش', 'kanalyar' ),
			'{admin_link}'      => __( 'نشانی سفارش در پیشخوان', 'kanalyar' ),
			'{site}'            => __( 'نام سایت', 'kanalyar' ),
		);
	}

	/**
	 * مقادیر پیش‌فرض تنظیمات.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'enabled'       => 0,
			'destinations'  => array(),
			'statuses'      => array( self::STATUS_NEW, 'processing', 'completed', 'cancelled' ),
			'template'      => '',
			'customer_info' => 1,
			'silent'        => 0,
		);
	}

	/**
	 * تنظیمات اعلان سفارش.
	 *
	 * @return array
	 */
	public function config() {
		if ( null === $this->config ) {
			$stored       = get_option( self::OPTION, array() );
			$stored       = is_array( $stored ) ? $stored : array();
			$this->config = wp_parse_args( $stored, self::defaults() );
		}

		return $this->config;
	}

	/**
	 * ذخیره‌ی تنظیمات.
	 *
	 * @param array $values مقادیر پاک‌سازی‌شده.
	 * @return void
	 */
	public function save( array $values ) {
		update_option( self::OPTION, array_merge( $this->config(), $values ), true );
		$this->config = null;
	}

	/**
	 * پاک‌سازی ورودی فرم.
	 *
	 * @param array $raw داده‌ی خام.
	 * @return array
	 */
	public function sanitize( array $raw ) {
		$statuses = self::statuses();

		$picked = isset( $raw['statuses'] ) ? (array) $raw['statuses'] : array();
		$picked = array_values(
			array_filter(
				array_map( 'sanitize_key', $picked ),
				function ( $s ) use ( $statuses ) {
					return isset( $statuses[ $s ] );
				}
			)
		);

		$dests = isset( $raw['destinations'] ) ? (array) $raw['destinations'] : array();

		return array(
			'enabled'       => empty( $raw['enabled'] ) ? 0 : 1,
			'destinations'  => array_values( array_filter( array_map( 'sanitize_key', $dests ) ) ),
			'statuses'      => $picked,
			'template'      => isset( $raw['template'] ) ? sanitize_textarea_field( $raw['template'] ) : '',
			'customer_info' => empty( $raw['customer_info'] ) ? 0 : 1,
			'silent'        => empty( $raw['silent'] ) ? 0 : 1,
		);
	}

	/**
	 * آیا اعلان سفارش آماده‌ی کار است.
	 *
	 * @return bool
	 */
	public function is_active() {
		$config = $this->config();

		return ! empty( $config['enabled'] )
			&& ! empty( $config['destinations'] )
			&& ! empty( $config['statuses'] )
			&& $this->settings->get( 'enabled' );
	}

	/**
	 * ثبت هوک‌ها.
	 *
	 * @return void
	 */
	public function register_hooks() {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return;
		}

		add_action( 'woocommerce_new_order', array( $this, 'on_new_order' ), 10, 1 );
		add_action( 'woocommerce_order_status_changed', array( $this, 'on_status_changed' ), 10, 4 );
		add_action( 'shutdown', array( $this, 'flush' ), 5 );
	}

	/**
	 * ثبت سفارش تازه.
	 *
	 * @param int $order_id شناسه‌ی سفارش.
	 * @return void
	 */
	public function on_new_order( $order_id ) {
		$this->enqueue( (int) $order_id, self::STATUS_NEW, '' );
	}

	/**
	 * تغییر وضعیت سفارش.
	 *
	 * @param int    $order_id شناسه‌ی سفارش.
	 * @param string $from     وضعیت قبلی.
	 * @param string $to       وضعیت تازه.
	 * @param mixed  $order    آبجکت سفارش.
	 * @return void
	 */
	public function on_status_changed( $order_id, $from, $to, $order = null ) {
		$this->enqueue( (int) $order_id, sanitize_key( $to ), sanitize_key( $from ) );
	}

	/**
	 * افزودن به صف، اگر این وضعیت انتخاب شده باشد.
	 *
	 * @param int    $order_id شناسه‌ی سفارش.
	 * @param string $status   وضعیت.
	 * @param string $from     وضعیت قبلی.
	 * @return void
	 */
	private function enqueue( $order_id, $status, $from ) {
		if ( ! $order_id || ! $this->is_active() ) {
			return;
		}

		$config = $this->config();

		if ( ! in_array( $status, (array) $config['statuses'], true ) ) {
			return;
		}

		if ( $this->already_sent( $order_id, $status ) ) {
			return;
		}

		$this->queue[] = array(
			'order'  => $order_id,
			'status' => $status,
			'from'   => $from,
		);
	}

	/**
	 * ارسال صف در پایان درخواست.
	 *
	 * مشتری نباید در صفحه‌ی پرداخت منتظر چند درخواست شبکه بماند،
	 * پس ارسال بعد از بسته‌شدن پاسخ انجام می‌شود.
	 *
	 * @return void
	 */
	public function flush() {
		if ( ! $this->queue ) {
			return;
		}

		$jobs        = $this->queue;
		$this->queue = array();

		ignore_user_abort( true );

		if ( function_exists( 'fastcgi_finish_request' ) ) {
			fastcgi_finish_request();
		} elseif ( function_exists( 'litespeed_finish_request' ) ) {
			litespeed_finish_request();
		}

		foreach ( $jobs as $job ) {
			$this->notify( $job['order'], $job['status'], $job['from'] );
		}
	}

	/**
	 * ساخت و ارسال اعلان یک سفارش.
	 *
	 * @param int    $order_id شناسه‌ی سفارش.
	 * @param string $status   وضعیت.
	 * @param string $from     وضعیت قبلی.
	 * @return array نتیجه به‌ازای هر مقصد.
	 */
	public function notify( $order_id, $status, $from = '' ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return array();
		}

		$config  = $this->config();
		$targets = $this->destinations->pick( (array) $config['destinations'] );

		if ( ! $targets ) {
			return array();
		}

		$builder  = new KY_Order_Message( $config );
		$parts    = $builder->build( $order, $status, $from );
		$retries  = (int) $this->settings->get( 'retries' );
		$statuses = self::statuses();
		$label    = $statuses[ $status ] ?? $status;

		$results = array();
		$any_ok  = false;

		foreach ( $targets as $destination ) {
			$api = $this->messenger( $destination, $retries );

			$api->with(
				array(
					'silent' => ! empty( $config['silent'] ),
					'pin'    => false,
				)
			);

			$text   = $api->supports_markdown() ? $parts['text'] : $parts['plain'];
			$markup = $api->supports_buttons() ? $parts['reply_markup'] : null;

			$result = $api->send_message( $text, $markup );
			$ok     = ! empty( $result['ok'] );
			$any_ok = $any_ok || $ok;

			$results[] = array(
				'destination' => $destination['label'],
				'ok'          => $ok,
				'message'     => $ok ? __( 'ارسال شد', 'kanalyar' ) : KY_Messenger::error_message( $result ),
			);

			$this->logger->add(
				$ok ? KY_Logger::STATE_OK : KY_Logger::STATE_FAIL,
				/* translators: %s: شماره سفارش. */
				sprintf( __( 'سفارش %s', 'kanalyar' ), $parts['order_number'] ),
				$ok ? $label : KY_Messenger::error_message( $result ),
				'order',
				__( 'اعلان سفارش', 'kanalyar' ),
				$destination['label']
			);
		}

		if ( $any_ok ) {
			$this->mark_sent( $order_id, $status );
		}

		return $results;
	}

	/**
	 * ساخت کلاینت یک مقصد. برای تست قابل بازنویسی است.
	 *
	 * @param array $destination مقصد.
	 * @param int   $retries     تلاش مجدد.
	 * @return KY_Messenger
	 */
	protected function messenger( array $destination, $retries ) {
		return KY_Destinations::messenger( $destination, $retries );
	}

	/**
	 * آیا برای این سفارش و این وضعیت قبلاً اعلان رفته است.
	 *
	 * @param int    $order_id شناسه‌ی سفارش.
	 * @param string $status   وضعیت.
	 * @return bool
	 */
	public function already_sent( $order_id, $status ) {
		$sent = get_post_meta( $order_id, self::SENT_META, true );

		return is_array( $sent ) && isset( $sent[ $status ] );
	}

	/**
	 * ثبت ارسال اعلان.
	 *
	 * @param int    $order_id شناسه‌ی سفارش.
	 * @param string $status   وضعیت.
	 * @return void
	 */
	public function mark_sent( $order_id, $status ) {
		$sent = get_post_meta( $order_id, self::SENT_META, true );
		$sent = is_array( $sent ) ? $sent : array();

		$sent[ $status ] = time();

		update_post_meta( $order_id, self::SENT_META, $sent );
	}
}
