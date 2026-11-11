<?php
/**
 * صفحه‌ی مدیریت افزونه.
 *
 * @package Kanalyar
 */

defined( 'ABSPATH' ) || exit;

/**
 * منو، تب‌ها، فرم‌ها و تاریخچه.
 */
class KY_Admin {

	/**
	 * شناسه‌ی صفحه.
	 */
	const SLUG = 'kanalyar';

	/**
	 * اکشن نانس.
	 */
	const NONCE = 'ky_admin_action';

	/**
	 * نام اطلاعیه‌ی موقت.
	 */
	const NOTICE_TRANSIENT = 'ky_notice_';

	/**
	 * تنظیمات.
	 *
	 * @var KY_Settings
	 */
	private $settings;

	/**
	 * تاریخچه.
	 *
	 * @var KY_Logger
	 */
	private $logger;

	/**
	 * مقصدها.
	 *
	 * @var KY_Destinations
	 */
	private $destinations;

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
	 * زمان‌بند.
	 *
	 * @var KY_Scheduler
	 */
	private $scheduler;

	/**
	 * پایش سلامت.
	 *
	 * @var KY_Health
	 */
	private $health;

	/**
	 * اعلان سفارش.
	 *
	 * @var KY_Orders
	 */
	private $orders;

	/**
	 * سازنده.
	 *
	 * @param KY_Settings     $settings     تنظیمات.
	 * @param KY_Logger       $logger       تاریخچه.
	 * @param KY_Destinations $destinations مقصدها.
	 * @param KY_Rules        $rules        قوانین.
	 * @param KY_Publisher    $publisher    انتشاردهنده.
	 * @param KY_Scheduler    $scheduler    زمان‌بند.
	 * @param KY_Health       $health       پایش سلامت.
	 * @param KY_Orders       $orders       اعلان سفارش.
	 */
	public function __construct(
		KY_Settings $settings,
		KY_Logger $logger,
		KY_Destinations $destinations,
		KY_Rules $rules,
		KY_Publisher $publisher,
		KY_Scheduler $scheduler,
		KY_Health $health,
		KY_Orders $orders
	) {
		$this->settings     = $settings;
		$this->logger       = $logger;
		$this->destinations = $destinations;
		$this->rules        = $rules;
		$this->publisher    = $publisher;
		$this->scheduler    = $scheduler;
		$this->health       = $health;
		$this->orders       = $orders;
	}

	/**
	 * ثبت هوک‌ها.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'admin_post_ky_handle', array( $this, 'handle_post' ) );
		add_filter( 'plugin_action_links_' . KY_BASENAME, array( $this, 'action_links' ) );
	}

	/**
	 * تب‌های صفحه.
	 *
	 * @return array<string,string>
	 */
	public static function tabs() {
		return array(
			'rules'        => __( 'قوانین انتشار', 'kanalyar' ),
			'destinations' => __( 'مقصدها', 'kanalyar' ),
			'orders'       => __( 'اعلان سفارش', 'kanalyar' ),
			'settings'     => __( 'تنظیمات', 'kanalyar' ),
			'log'          => __( 'تاریخچه', 'kanalyar' ),
		);
	}

	/**
	 * تب جاری.
	 *
	 * @return string
	 */
	public function current_tab() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'rules';

		return array_key_exists( $tab, self::tabs() ) ? $tab : 'rules';
	}

	/**
	 * نشانی یک تب.
	 *
	 * @param string $tab نام تب.
	 * @return string
	 */
	public function tab_url( $tab ) {
		return admin_url( 'admin.php?page=' . self::SLUG . '&tab=' . $tab );
	}

	/**
	 * افزودن منوی پیشخوان.
	 *
	 * @return void
	 */
	public function add_menu() {
		$title = __( 'انتشار خودکار', 'kanalyar' );

		add_menu_page(
			KY_Branding::is_enabled() ? $title . ' | ' . KY_Branding::get( 'name' ) : $title,
			$title,
			'manage_options',
			self::SLUG,
			array( $this, 'render' ),
			$this->menu_icon(),
			30
		);
	}

	/**
	 * لینک تنظیمات در فهرست افزونه‌ها.
	 *
	 * @param array $links لینک‌های موجود.
	 * @return array
	 */
	public function action_links( $links ) {
		array_unshift(
			$links,
			'<a href="' . esc_url( $this->tab_url( 'rules' ) ) . '">' . esc_html__( 'تنظیمات', 'kanalyar' ) . '</a>'
		);

		return $links;
	}

	/**
	 * بارگذاری استایل و فونت فقط در صفحه‌ی افزونه.
	 *
	 * @param string $hook شناسه‌ی صفحه‌ی جاری.
	 * @return void
	 */
	public function enqueue( $hook ) {
		if ( 'toplevel_page_' . self::SLUG !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'ky-vazirmatn',
			'https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css',
			array(),
			null // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		);

		wp_enqueue_style( 'ky-admin', KY_URL . 'assets/admin.css', array( 'ky-vazirmatn' ), KY_VERSION );
		wp_enqueue_script( 'ky-admin', KY_URL . 'assets/admin.js', array(), KY_VERSION, true );
	}

	/**
	 * آیکون منو.
	 *
	 * @return string
	 */
	private function menu_icon() {
		$icon = KY_Branding::menu_icon();

		return $icon ? $icon : 'dashicons-share';
	}

	/**
	 * پردازش ارسال فرم و بازگشت به صفحه (الگوی POST/Redirect/GET).
	 *
	 * @return void
	 */
	public function handle_post() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'دسترسی لازم را ندارید.', 'kanalyar' ) );
		}

		check_admin_referer( self::NONCE );

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- نانس بالا بررسی شد.
		$action = isset( $_POST['ky_action'] ) ? sanitize_key( wp_unslash( $_POST['ky_action'] ) ) : '';
		$tab    = isset( $_POST['ky_tab'] ) ? sanitize_key( wp_unslash( $_POST['ky_tab'] ) ) : 'rules';
		$target = isset( $_POST['ky_target'] ) ? sanitize_key( wp_unslash( $_POST['ky_target'] ) ) : '';
		$raw    = isset( $_POST['ky'] ) ? wp_unslash( $_POST['ky'] ) : array();
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		switch ( $action ) {
			case 'save_rules':
				$this->save_rules( (array) ( $raw['rules'] ?? array() ) );
				break;

			case 'save_destinations':
				$this->save_destinations( (array) ( $raw['destinations'] ?? array() ) );
				break;

			case 'save_settings':
				$this->save_settings( (array) ( $raw['settings'] ?? array() ) );
				break;

			case 'save_orders':
				$this->save_orders( (array) ( $raw['orders'] ?? array() ) );
				break;

			case 'test_order':
				$this->test_order();
				break;

			case 'test_rule':
				$this->test_rule( $target );
				break;

			case 'test_destination':
				$this->test_destination( $target );
				break;

			case 'reset_rule':
				$this->reset_rule( $target );
				break;

			case 'repair':
				$this->do_repair();
				break;

			case 'clear_log':
				$this->logger->clear();
				$this->notice( 'success', __( 'تاریخچه پاک شد', 'kanalyar' ), __( 'همه‌ی ردیف‌های تاریخچه حذف شدند.', 'kanalyar' ) );
				break;
		}

		wp_safe_redirect( $this->tab_url( array_key_exists( $tab, self::tabs() ) ? $tab : 'rules' ) );
		exit;
	}

	/**
	 * ذخیره‌ی قوانین.
	 *
	 * @param array $raw داده‌ی خام.
	 * @return void
	 */
	private function save_rules( array $raw ) {
		$clean = $this->rules->sanitize( $raw );
		$kept  = wp_list_pluck( $clean, 'id' );

		// وضعیت و تاریخچه‌ی قوانین حذف‌شده را هم پاک کن.
		foreach ( $this->rules->all() as $old ) {
			if ( ! in_array( $old['id'], $kept, true ) ) {
				$this->scheduler->forget_today( $old['id'] );
				$this->publisher->selector()->forget( $old['id'] );
			}
		}

		$this->rules->save( $clean );
		$this->scheduler->ensure_scheduled();

		$this->notice(
			'success',
			__( 'قوانین ذخیره شد', 'kanalyar' ),
			sprintf(
				/* translators: %d: تعداد قانون. */
				_n( '%d قانون انتشار ذخیره شد.', '%d قانون انتشار ذخیره شد.', count( $clean ), 'kanalyar' ),
				count( $clean )
			)
		);
	}

	/**
	 * ذخیره‌ی مقصدها.
	 *
	 * @param array $raw داده‌ی خام.
	 * @return void
	 */
	private function save_destinations( array $raw ) {
		$clean = $this->destinations->sanitize( $raw );
		$this->destinations->save( $clean );

		$this->notice(
			'success',
			__( 'مقصدها ذخیره شد', 'kanalyar' ),
			sprintf(
				/* translators: %d: تعداد مقصد. */
				_n( '%d مقصد ذخیره شد.', '%d مقصد ذخیره شد.', count( $clean ), 'kanalyar' ),
				count( $clean )
			)
		);
	}

	/**
	 * ذخیره‌ی تنظیمات سراسری.
	 *
	 * @param array $raw داده‌ی خام.
	 * @return void
	 */
	private function save_settings( array $raw ) {
		$this->settings->save( $this->settings->sanitize( $raw ) );
		$this->scheduler->ensure_scheduled();

		$this->notice(
			'success',
			__( 'تنظیمات ذخیره شد', 'kanalyar' ),
			__( 'تنظیمات کلی ذخیره شد و زمان‌بندی به‌روز شد.', 'kanalyar' )
		);
	}

	/**
	 * ذخیره‌ی تنظیمات اعلان سفارش.
	 *
	 * @param array $raw داده‌ی خام.
	 * @return void
	 */
	private function save_orders( array $raw ) {
		$this->orders->save( $this->orders->sanitize( $raw ) );

		$this->notice(
			'success',
			__( 'اعلان سفارش ذخیره شد', 'kanalyar' ),
			$this->orders->is_active()
				? __( 'اعلان‌ها فعال‌اند و با تغییر وضعیت سفارش ارسال می‌شوند.', 'kanalyar' )
				: __( 'تنظیمات ذخیره شد، ولی اعلان هنوز فعال نیست؛ کلید، مقصد و وضعیت‌ها را بررسی کنید.', 'kanalyar' )
		);
	}

	/**
	 * اعلان تستی روی آخرین سفارش فروشگاه.
	 *
	 * @return void
	 */
	private function test_order() {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			$this->notice( 'error', __( 'ووکامرس فعال نیست', 'kanalyar' ), __( 'برای اعلان سفارش، ووکامرس باید فعال باشد.', 'kanalyar' ) );
			return;
		}

		$found = wc_get_orders(
			array(
				'limit'   => 1,
				'orderby' => 'date',
				'order'   => 'DESC',
			)
		);

		if ( ! $found ) {
			$this->notice( 'error', __( 'سفارشی پیدا نشد', 'kanalyar' ), __( 'فروشگاه هنوز هیچ سفارشی ندارد تا اعلان تستی ساخته شود.', 'kanalyar' ) );
			return;
		}

		$order   = $found[0];
		$results = $this->orders->notify( $order->get_id(), $order->get_status() );

		if ( ! $results ) {
			$this->notice( 'error', __( 'ارسال نشد', 'kanalyar' ), __( 'هیچ مقصد فعالی برای اعلان سفارش انتخاب نشده است.', 'kanalyar' ) );
			return;
		}

		$ok    = 0;
		$lines = array();

		foreach ( $results as $row ) {
			if ( $row['ok'] ) {
				$ok++;
			}

			$lines[] = $row['destination'] . ' — ' . $row['message'];
		}

		$this->notice(
			$ok ? 'success' : 'error',
			$ok ? __( 'اعلان تستی ارسال شد', 'kanalyar' ) : __( 'اعلان تستی ناموفق بود', 'kanalyar' ),
			sprintf(
				/* translators: %s: شماره سفارش. */
				__( 'بر اساس آخرین سفارش فروشگاه (%s).', 'kanalyar' ),
				$order->get_order_number()
			),
			implode( "
", $lines )
		);
	}

	/**
	 * ارسال تستی یک قانون.
	 *
	 * @param string $rule_id شناسه‌ی قانون.
	 * @return void
	 */
	private function test_rule( $rule_id ) {
		$rule = $this->rules->get( $rule_id );

		if ( ! $rule ) {
			$this->notice( 'error', __( 'قانون پیدا نشد', 'kanalyar' ), __( 'ابتدا قانون را ذخیره کنید.', 'kanalyar' ) );
			return;
		}

		$result = $this->scheduler->run_now( $rule );

		if ( ! empty( $result['ok'] ) ) {
			$this->notice(
				'success',
				__( 'ارسال موفق', 'kanalyar' ),
				sprintf(
					/* translators: ۱: عنوان مطلب، ۲: خلاصه‌ی مقصدها. */
					__( '«%1$s» — %2$s', 'kanalyar' ),
					$result['title'],
					$result['message']
				),
				$this->format_results( $result['results'] )
			);
			return;
		}

		$this->notice(
			'error',
			__( 'ارسال ناموفق', 'kanalyar' ),
			$result['message'],
			$this->format_results( $result['results'] )
		);
	}

	/**
	 * تست اتصال یک مقصد.
	 *
	 * @param string $destination_id شناسه‌ی مقصد.
	 * @return void
	 */
	private function test_destination( $destination_id ) {
		$destination = $this->destinations->get( $destination_id );

		if ( ! $destination ) {
			$this->notice( 'error', __( 'مقصد پیدا نشد', 'kanalyar' ), __( 'ابتدا مقصد را ذخیره کنید.', 'kanalyar' ) );
			return;
		}

		$messenger = KY_Destinations::messenger( $destination, 0 );
		$result    = $messenger->check();

		if ( ! empty( $result['ok'] ) ) {
			$name = $result['result']['username'] ?? ( $result['result']['first_name'] ?? '' );

			$this->notice(
				'success',
				__( 'اتصال برقرار است', 'kanalyar' ),
				sprintf(
					/* translators: ۱: نام مقصد، ۲: نام بات. */
					__( 'توکن «%1$s» معتبر است. بات: %2$s', 'kanalyar' ),
					$destination['label'],
					$name ? '@' . $name : __( 'نامشخص', 'kanalyar' )
				)
			);
			return;
		}

		$this->notice(
			'error',
			__( 'اتصال برقرار نشد', 'kanalyar' ),
			KY_Messenger::error_message( $result ),
			wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE )
		);
	}

	/**
	 * پاک‌کردن تاریخچه‌ی ارسال یک قانون.
	 *
	 * @param string $rule_id شناسه‌ی قانون.
	 * @return void
	 */
	private function reset_rule( $rule_id ) {
		$rule = $this->rules->get( $rule_id );

		if ( ! $rule ) {
			return;
		}

		$this->publisher->selector()->forget( $rule_id );
		$this->scheduler->forget_today( $rule_id );

		$this->notice(
			'success',
			__( 'تاریخچه‌ی قانون پاک شد', 'kanalyar' ),
			sprintf(
				/* translators: %s: نام قانون. */
				__( 'قانون «%s» دوباره از ابتدا شروع می‌کند و همه‌ی مطالب دوباره واجد شرایط‌اند.', 'kanalyar' ),
				$rule['label']
			)
		);
	}

	/**
	 * بازسازی زمان‌بندی.
	 *
	 * @return void
	 */
	private function do_repair() {
		$this->scheduler->drop_legacy_event();
		$this->scheduler->reschedule();

		$this->notice(
			'success',
			__( 'زمان‌بندی بازسازی شد', 'kanalyar' ),
			__( 'رویداد زمان‌بندی از نو ثبت شد. اگر باز هم اجرا نشد، cron سمت سرور را فعال کنید.', 'kanalyar' )
		);
	}

	/**
	 * متنی‌کردن نتیجه‌ی هر مقصد، برای بخش جزئیات.
	 *
	 * @param array $results نتایج.
	 * @return string
	 */
	private function format_results( array $results ) {
		if ( ! $results ) {
			return '';
		}

		$lines = array();

		foreach ( $results as $row ) {
			$lines[] = sprintf(
				'[%s] %s — %s',
				$row['platform'],
				$row['destination'],
				$row['message']
			);
		}

		return implode( "\n", $lines );
	}

	/**
	 * ذخیره‌ی اطلاعیه برای نمایش بعد از ریدایرکت.
	 *
	 * @param string      $type    success یا error.
	 * @param string      $title   عنوان.
	 * @param string      $message متن.
	 * @param string|null $details جزئیات فنی.
	 * @return void
	 */
	private function notice( $type, $title, $message, $details = null ) {
		set_transient(
			self::NOTICE_TRANSIENT . get_current_user_id(),
			array(
				'type'    => $type,
				'title'   => $title,
				'message' => $message,
				'details' => $details,
			),
			60
		);
	}

	/**
	 * خواندن و پاک‌کردن اطلاعیه.
	 *
	 * @return array|null
	 */
	private function pull_notice() {
		$key    = self::NOTICE_TRANSIENT . get_current_user_id();
		$notice = get_transient( $key );

		if ( $notice ) {
			delete_transient( $key );
		}

		return is_array( $notice ) ? $notice : null;
	}

	/**
	 * نمایش زمان به وقت سایت.
	 *
	 * @param int $timestamp مهر زمانی یونیکس.
	 * @return string
	 */
	private function local_time( $timestamp ) {
		if ( ! $timestamp ) {
			return '—';
		}

		return get_date_from_gmt( gmdate( 'Y-m-d H:i:s', (int) $timestamp ), 'Y-m-d H:i' );
	}

	/**
	 * رندر صفحه.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$notice = $this->pull_notice();
		$report = $this->health->report( $this->scheduler );
		$tab    = $this->current_tab();

		include KY_PATH . 'includes/views/page.php';
	}

	/**
	 * تنظیمات اعلان سفارش، برای نماها.
	 *
	 * @return array
	 */
	public function orders_config() {
		return $this->orders->config();
	}

	/**
	 * آیا اعلان سفارش فعال است.
	 *
	 * @return bool
	 */
	public function orders_active() {
		return $this->orders->is_active();
	}

	/**
	 * تنظیمات سراسری، برای نماها.
	 *
	 * @return array
	 */
	public function settings_all() {
		return $this->settings->all();
	}

	/**
	 * ردیف‌های تاریخچه، از جدید به قدیم.
	 *
	 * @return array
	 */
	public function log_rows() {
		return $this->logger->recent();
	}

	/**
	 * مقصدها برای نمایش در فرم؛ اگر خالی باشد یک ردیف خالی می‌دهد.
	 *
	 * @return array[]
	 */
	public function destinations_list() {
		$rows = $this->destinations->all();

		return $rows ? $rows : array( KY_Destinations::blank() );
	}

	/**
	 * قوانین برای نمایش در فرم؛ اگر خالی باشد یک ردیف خالی می‌دهد.
	 *
	 * @return array[]
	 */
	public function rules_list() {
		$rows = $this->rules->all();

		return $rows ? $rows : array( KY_Rules::blank() );
	}

	/**
	 * همه‌ی مقصدها، برای چک‌باکس‌های انتخاب مقصد در قوانین.
	 *
	 * @return array[]
	 */
	public function all_destinations() {
		return $this->destinations->all();
	}

	/**
	 * وضعیت یک قانون برای نمایش.
	 *
	 * @param array $rule قانون.
	 * @return array
	 */
	public function rule_status( array $rule ) {
		$next = $this->scheduler->next_run( $rule );

		return array(
			'state'    => $this->scheduler->due_state( $rule ),
			'today'    => $this->scheduler->today( $rule['id'] ),
			'next'     => $next ? $next->format( 'Y-m-d H:i' ) : '',
			'stats'    => $this->publisher->selector()->stats( $rule ),
			'last'     => $this->logger->last_success( $rule['label'] ),
		);
	}

	/**
	 * فهرست روزهای هفته با شماره‌ی PHP (w).
	 *
	 * @return array<int,string>
	 */
	public static function weekdays() {
		return array(
			6 => __( 'شنبه', 'kanalyar' ),
			0 => __( 'یکشنبه', 'kanalyar' ),
			1 => __( 'دوشنبه', 'kanalyar' ),
			2 => __( 'سه‌شنبه', 'kanalyar' ),
			3 => __( 'چهارشنبه', 'kanalyar' ),
			4 => __( 'پنجشنبه', 'kanalyar' ),
			5 => __( 'جمعه', 'kanalyar' ),
		);
	}
}
