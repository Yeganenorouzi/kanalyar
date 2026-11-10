<?php
/**
 * صفحه‌ی مدیریت افزونه.
 *
 * @package DailyBalePost
 */

defined( 'ABSPATH' ) || exit;

/**
 * منو، فرم تنظیمات، وضعیت سلامت و تاریخچه.
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
	 * نام آژانس.
	 */
	const AGENCY_NAME = 'آژانس دیجیتال مارکتینگ اپتیسان';

	/**
	 * تلفن آژانس.
	 */
	const AGENCY_PHONE = '02188830601';

	/**
	 * سایت آژانس.
	 */
	const AGENCY_SITE = 'https://optisun.ir/';

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
	 * @param DSB_Settings  $settings  تنظیمات.
	 * @param DSB_Logger    $logger    تاریخچه.
	 * @param DSB_Publisher $publisher انتشاردهنده.
	 * @param DSB_Scheduler $scheduler زمان‌بند.
	 * @param DSB_Health    $health    پایش سلامت.
	 */
	public function __construct(
		DSB_Settings $settings,
		DSB_Logger $logger,
		DSB_Publisher $publisher,
		DSB_Scheduler $scheduler,
		DSB_Health $health
	) {
		$this->settings  = $settings;
		$this->logger    = $logger;
		$this->publisher = $publisher;
		$this->scheduler = $scheduler;
		$this->health    = $health;
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
	 * افزودن منوی پیشخوان.
	 *
	 * @return void
	 */
	public function add_menu() {
		add_menu_page(
			__( 'ارسال به بله', 'daily-bale-post' ) . ' | ' . self::AGENCY_NAME,
			__( 'ارسال به بله', 'daily-bale-post' ),
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
		$url = admin_url( 'admin.php?page=' . self::SLUG );

		array_unshift(
			$links,
			'<a href="' . esc_url( $url ) . '">' . esc_html__( 'تنظیمات', 'daily-bale-post' ) . '</a>'
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

		wp_enqueue_style(
			'dsb-admin',
			DSB_URL . 'assets/admin.css',
			array( 'dsb-vazirmatn' ),
			DSB_VERSION
		);

		wp_enqueue_script(
			'dsb-admin',
			DSB_URL . 'assets/admin.js',
			array(),
			DSB_VERSION,
			true
		);
	}

	/**
	 * آیکون منو.
	 *
	 * @return string
	 */
	private function menu_icon() {
		$path = DSB_PATH . 'assets/optisun-icon.svg';

		if ( ! is_readable( $path ) ) {
			return 'dashicons-share';
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		return 'data:image/svg+xml;base64,' . base64_encode( file_get_contents( $path ) );
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

		$action = isset( $_POST['dsb_action'] ) ? sanitize_key( wp_unslash( $_POST['dsb_action'] ) ) : '';

		switch ( $action ) {
			case 'save':
				$this->do_save();
				break;

			case 'test':
				$this->do_test();
				break;

			case 'repair':
				$this->do_repair();
				break;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG ) );
		exit;
	}

	/**
	 * ذخیره‌ی تنظیمات.
	 *
	 * @return void
	 */
	private function do_save() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- نانس در handle_post بررسی شده.
		$raw = isset( $_POST['dsb'] ) ? (array) wp_unslash( $_POST['dsb'] ) : array();

		$this->settings->save( $this->settings->sanitize( $raw ) );
		$this->scheduler->ensure_scheduled();

		$this->notice(
			'success',
			__( 'تنظیمات ذخیره شد', 'daily-bale-post' ),
			__( 'تنظیمات با موفقیت ذخیره شد و زمان‌بندی ارسال به‌روزرسانی شد.', 'daily-bale-post' )
		);
	}

	/**
	 * ارسال تستی.
	 *
	 * @return void
	 */
	private function do_test() {
		$result = $this->scheduler->run_now();

		if ( ! empty( $result['ok'] ) ) {
			$this->notice(
				'success',
				__( 'ارسال موفق', 'daily-bale-post' ),
				sprintf(
					/* translators: %s: نام محصول. */
					__( 'محصول «%s» با موفقیت به کانال بله ارسال شد.', 'daily-bale-post' ),
					$result['product']
				)
			);
			return;
		}

		$this->notice(
			'error',
			__( 'ارسال ناموفق', 'daily-bale-post' ),
			$result['message'],
			wp_json_encode( $result['raw'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE )
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

		$s      = $this->settings->all();
		$notice = $this->pull_notice();
		$report = $this->health->report( $this->scheduler );

		include DSB_PATH . 'includes/views/settings-page.php';
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
