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
			__( 'ارسال به بله و تلگرام', 'daily-bale-post' ) . ' | ' . self::AGENCY_NAME,
			__( 'ارسال به بله و تلگرام', 'daily-bale-post' ),
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
		$target = isset( $_POST['dsb_target'] ) ? sanitize_key( wp_unslash( $_POST['dsb_target'] ) ) : '';
		$target = array_key_exists( $target, DSB_Messenger::platforms() ) ? $target : '';

		switch ( $action ) {
			case 'save':
				$this->do_save();
				break;

			case 'test':
				$this->do_test( $target );
				break;

			case 'check':
				$this->do_check( $target );
				break;

			case 'preview':
				$this->do_preview( $target );
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
		$this->save_posted();

		$this->notice(
			'success',
			__( 'تنظیمات ذخیره شد', 'daily-bale-post' ),
			__( 'تنظیمات با موفقیت ذخیره شد و زمان‌بندی ارسال به‌روزرسانی شد.', 'daily-bale-post' )
		);
	}

	/**
	 * ذخیره‌ی ورودی فرم تنظیمات.
	 *
	 * اکشن‌های تست و پیش‌نمایش هم اول همین را صدا می‌زنند تا کاربر
	 * مجبور نباشد قبل از هر تست یک بار دکمه‌ی ذخیره را بزند.
	 *
	 * @return bool آیا داده‌ای برای ذخیره بود.
	 */
	private function save_posted() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- نانس در handle_post بررسی شده.
		if ( ! isset( $_POST['dsb'] ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- نانس در handle_post بررسی شده.
		$raw = (array) wp_unslash( $_POST['dsb'] );

		$this->settings->save( $this->settings->sanitize( $raw ) );
		$this->scheduler->ensure_scheduled();

		return true;
	}

	/**
	 * ارسال تستی.
	 *
	 * @param string $target فقط این کانال (خالی = همه‌ی کانال‌های فعال).
	 * @return void
	 */
	private function do_test( $target = '' ) {
		$this->save_posted();

		$result = $this->scheduler->run_now( $target );

		if ( ! empty( $result['ok'] ) ) {
			$channels = ! empty( $result['sent'] )
				? implode( '، ', $result['sent'] )
				: __( 'کانال‌های فعال', 'daily-bale-post' );

			$this->notice(
				! empty( $result['failed'] ) ? 'error' : 'success',
				! empty( $result['failed'] )
					? __( 'ارسال نیمه‌موفق', 'daily-bale-post' )
					: __( 'ارسال موفق', 'daily-bale-post' ),
				sprintf(
					/* translators: 1: نام مطلب، 2: نام کانال‌ها. */
					__( 'مطلب «%1$s» با موفقیت به %2$s ارسال شد.', 'daily-bale-post' ),
					$result['product'],
					$channels
				) . ( ! empty( $result['failed'] )
					? ' ' . sprintf(
						/* translators: %s: فهرست کانال‌های ناموفق. */
						__( 'ولی این کانال‌ها ناموفق بودند: %s', 'daily-bale-post' ),
						implode( ' | ', $result['failed'] )
					)
					: '' ),
				! empty( $result['failed'] )
					? wp_json_encode( $result['raw'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE )
					: null
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
	 * بررسی اتصال یک کانال، بدون ارسال پست.
	 *
	 * @param string $target کانال.
	 * @return void
	 */
	private function do_check( $target ) {
		$this->save_posted();

		if ( ! $target ) {
			$this->notice( 'error', __( 'کانال مشخص نشد', 'daily-bale-post' ), __( 'کانالی برای بررسی انتخاب نشد.', 'daily-bale-post' ) );
			return;
		}

		$result = $this->publisher->check( $target );

		$this->notice(
			! empty( $result['ok'] ) ? 'success' : 'error',
			! empty( $result['ok'] )
				? __( 'اتصال برقرار است', 'daily-bale-post' )
				: __( 'اتصال برقرار نشد', 'daily-bale-post' ),
			$result['message'],
			empty( $result['ok'] ) && ! empty( $result['raw'] )
				? wp_json_encode( $result['raw'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE )
				: null
		);
	}

	/**
	 * پیش‌نمایش متن پیام یک کانال، بدون ارسال.
	 *
	 * @param string $target کانال.
	 * @return void
	 */
	private function do_preview( $target ) {
		$this->save_posted();

		if ( ! $target ) {
			$this->notice( 'error', __( 'کانال مشخص نشد', 'daily-bale-post' ), __( 'کانالی برای پیش‌نمایش انتخاب نشد.', 'daily-bale-post' ) );
			return;
		}

		$result = $this->publisher->preview( $target );

		if ( empty( $result['ok'] ) ) {
			$this->notice( 'error', __( 'پیش‌نمایش ساخته نشد', 'daily-bale-post' ), $result['message'] );
			return;
		}

		$this->notice(
			'success',
			__( 'پیش‌نمایش پیام', 'daily-bale-post' ),
			$result['message'],
			$result['text'],
			'rtl'
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
	 * @param string|null $details جزئیات فنی یا متن پیش‌نمایش.
	 * @param string      $dir     جهت نمایش جزئیات: ltr یا rtl.
	 * @return void
	 */
	private function notice( $type, $title, $message, $details = null, $dir = 'ltr' ) {
		set_transient(
			self::NOTICE_TRANSIENT . get_current_user_id(),
			array(
				'type'    => $type,
				'title'   => $title,
				'message' => $message,
				'details' => $details,
				'dir'     => 'rtl' === $dir ? 'rtl' : 'ltr',
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

		$s        = $this->settings->all();
		$notice   = $this->pull_notice();
		$report   = $this->health->report( $this->scheduler );
		$channels = $this->publisher->status();

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
