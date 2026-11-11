<?php
/**
 * صفحه‌ی مدیریت افزونه.
 *
 * @package DailyBalePost
 */

defined( 'ABSPATH' ) || exit;

/**
 * منو، تب‌ها، فرم‌ها و تاریخچه.
 */
class DSB_Admin {

	/**
	 * شناسه‌ی صفحه.
	 */
	const SLUG = 'dsb-settings';

	/**
	 * اکشن نانس.
	 */
	const NONCE = 'dsb_admin_action';

	/**
	 * نام اطلاعیه‌ی موقت.
	 */
	const NOTICE_TRANSIENT = 'dsb_notice_';

	/**
	 * تنظیمات.
	 *
	 * @var DSB_Settings
	 */
	private $settings;

	/**
	 * تاریخچه.
	 *
	 * @var DSB_Logger
	 */
	private $logger;

	/**
	 * مقصدها.
	 *
	 * @var DSB_Destinations
	 */
	private $destinations;

	/**
	 * قوانین.
	 *
	 * @var DSB_Rules
	 */
	private $rules;

	/**
	 * انتشاردهنده.
	 *
	 * @var DSB_Publisher
	 */
	private $publisher;

	/**
	 * زمان‌بند.
	 *
	 * @var DSB_Scheduler
	 */
	private $scheduler;

	/**
	 * پایش سلامت.
	 *
	 * @var DSB_Health
	 */
	private $health;

	/**
	 * سازنده.
	 *
	 * @param DSB_Settings     $settings     تنظیمات.
	 * @param DSB_Logger       $logger       تاریخچه.
	 * @param DSB_Destinations $destinations مقصدها.
	 * @param DSB_Rules        $rules        قوانین.
	 * @param DSB_Publisher    $publisher    انتشاردهنده.
	 * @param DSB_Scheduler    $scheduler    زمان‌بند.
	 * @param DSB_Health       $health       پایش سلامت.
	 */
	public function __construct(
		DSB_Settings $settings,
		DSB_Logger $logger,
		DSB_Destinations $destinations,
		DSB_Rules $rules,
		DSB_Publisher $publisher,
		DSB_Scheduler $scheduler,
		DSB_Health $health
	) {
		$this->settings     = $settings;
		$this->logger       = $logger;
		$this->destinations = $destinations;
		$this->rules        = $rules;
		$this->publisher    = $publisher;
		$this->scheduler    = $scheduler;
		$this->health       = $health;
	}

	/**
	 * ثبت هوک‌ها.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'admin_post_dsb_handle', array( $this, 'handle_post' ) );
		add_filter( 'plugin_action_links_' . DSB_BASENAME, array( $this, 'action_links' ) );
	}

	/**
	 * تب‌های صفحه.
	 *
	 * @return array<string,string>
	 */
	public static function tabs() {
		return array(
			'rules'        => __( 'قوانین انتشار', 'daily-bale-post' ),
			'destinations' => __( 'مقصدها', 'daily-bale-post' ),
			'settings'     => __( 'تنظیمات', 'daily-bale-post' ),
			'log'          => __( 'تاریخچه', 'daily-bale-post' ),
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
		$title = __( 'انتشار خودکار', 'daily-bale-post' );

		add_menu_page(
			DSB_Branding::is_enabled() ? $title . ' | ' . DSB_Branding::get( 'name' ) : $title,
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
			'<a href="' . esc_url( $this->tab_url( 'rules' ) ) . '">' . esc_html__( 'تنظیمات', 'daily-bale-post' ) . '</a>'
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
			'dsb-vazirmatn',
			'https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css',
			array(),
			null // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		);

		wp_enqueue_style( 'dsb-admin', DSB_URL . 'assets/admin.css', array( 'dsb-vazirmatn' ), DSB_VERSION );
		wp_enqueue_script( 'dsb-admin', DSB_URL . 'assets/admin.js', array(), DSB_VERSION, true );
	}

	/**
	 * آیکون منو.
	 *
	 * @return string
	 */
	private function menu_icon() {
		$icon = DSB_Branding::menu_icon();

		return $icon ? $icon : 'dashicons-share';
	}

	/**
	 * پردازش ارسال فرم و بازگشت به صفحه (الگوی POST/Redirect/GET).
	 *
	 * @return void
	 */
	public function handle_post() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'دسترسی لازم را ندارید.', 'daily-bale-post' ) );
		}

		check_admin_referer( self::NONCE );

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- نانس بالا بررسی شد.
		$action = isset( $_POST['dsb_action'] ) ? sanitize_key( wp_unslash( $_POST['dsb_action'] ) ) : '';
		$tab    = isset( $_POST['dsb_tab'] ) ? sanitize_key( wp_unslash( $_POST['dsb_tab'] ) ) : 'rules';
		$target = isset( $_POST['dsb_target'] ) ? sanitize_key( wp_unslash( $_POST['dsb_target'] ) ) : '';
		$raw    = isset( $_POST['dsb'] ) ? wp_unslash( $_POST['dsb'] ) : array();
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
				$this->notice( 'success', __( 'تاریخچه پاک شد', 'daily-bale-post' ), __( 'همه‌ی ردیف‌های تاریخچه حذف شدند.', 'daily-bale-post' ) );
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
			__( 'قوانین ذخیره شد', 'daily-bale-post' ),
			sprintf(
				/* translators: %d: تعداد قانون. */
				_n( '%d قانون انتشار ذخیره شد.', '%d قانون انتشار ذخیره شد.', count( $clean ), 'daily-bale-post' ),
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
			__( 'مقصدها ذخیره شد', 'daily-bale-post' ),
			sprintf(
				/* translators: %d: تعداد مقصد. */
				_n( '%d مقصد ذخیره شد.', '%d مقصد ذخیره شد.', count( $clean ), 'daily-bale-post' ),
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
			__( 'تنظیمات ذخیره شد', 'daily-bale-post' ),
			__( 'تنظیمات کلی ذخیره شد و زمان‌بندی به‌روز شد.', 'daily-bale-post' )
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
			$this->notice( 'error', __( 'قانون پیدا نشد', 'daily-bale-post' ), __( 'ابتدا قانون را ذخیره کنید.', 'daily-bale-post' ) );
			return;
		}

		$result = $this->scheduler->run_now( $rule );

		if ( ! empty( $result['ok'] ) ) {
			$this->notice(
				'success',
				__( 'ارسال موفق', 'daily-bale-post' ),
				sprintf(
					/* translators: ۱: عنوان مطلب، ۲: خلاصه‌ی مقصدها. */
					__( '«%1$s» — %2$s', 'daily-bale-post' ),
					$result['title'],
					$result['message']
				),
				$this->format_results( $result['results'] )
			);
			return;
		}

		$this->notice(
			'error',
			__( 'ارسال ناموفق', 'daily-bale-post' ),
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
			$this->notice( 'error', __( 'مقصد پیدا نشد', 'daily-bale-post' ), __( 'ابتدا مقصد را ذخیره کنید.', 'daily-bale-post' ) );
			return;
		}

		$messenger = DSB_Destinations::messenger( $destination, 0 );
		$result    = $messenger->check();

		if ( ! empty( $result['ok'] ) ) {
			$name = $result['result']['username'] ?? ( $result['result']['first_name'] ?? '' );

			$this->notice(
				'success',
				__( 'اتصال برقرار است', 'daily-bale-post' ),
				sprintf(
					/* translators: ۱: نام مقصد، ۲: نام بات. */
					__( 'توکن «%1$s» معتبر است. بات: %2$s', 'daily-bale-post' ),
					$destination['label'],
					$name ? '@' . $name : __( 'نامشخص', 'daily-bale-post' )
				)
			);
			return;
		}

		$this->notice(
			'error',
			__( 'اتصال برقرار نشد', 'daily-bale-post' ),
			DSB_Messenger::error_message( $result ),
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
			__( 'تاریخچه‌ی قانون پاک شد', 'daily-bale-post' ),
			sprintf(
				/* translators: %s: نام قانون. */
				__( 'قانون «%s» دوباره از ابتدا شروع می‌کند و همه‌ی مطالب دوباره واجد شرایط‌اند.', 'daily-bale-post' ),
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
			__( 'زمان‌بندی بازسازی شد', 'daily-bale-post' ),
			__( 'رویداد زمان‌بندی از نو ثبت شد. اگر باز هم اجرا نشد، cron سمت سرور را فعال کنید.', 'daily-bale-post' )
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

		include DSB_PATH . 'includes/views/page.php';
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

		return $rows ? $rows : array( DSB_Destinations::blank() );
	}

	/**
	 * قوانین برای نمایش در فرم؛ اگر خالی باشد یک ردیف خالی می‌دهد.
	 *
	 * @return array[]
	 */
	public function rules_list() {
		$rows = $this->rules->all();

		return $rows ? $rows : array( DSB_Rules::blank() );
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
			6 => __( 'شنبه', 'daily-bale-post' ),
			0 => __( 'یکشنبه', 'daily-bale-post' ),
			1 => __( 'دوشنبه', 'daily-bale-post' ),
			2 => __( 'سه‌شنبه', 'daily-bale-post' ),
			3 => __( 'چهارشنبه', 'daily-bale-post' ),
			4 => __( 'پنجشنبه', 'daily-bale-post' ),
			5 => __( 'جمعه', 'daily-bale-post' ),
		);
	}
}
