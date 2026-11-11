<?php
/**
 * هسته‌ی افزونه: بارگذاری کلاس‌ها، سیم‌کشی هوک‌ها، نصب و حذف.
 *
 * @package DailyBalePost
 */

defined( 'ABSPATH' ) || exit;

/**
 * کلاس اصلی افزونه (Singleton).
 */
final class DSB_Plugin {

	/**
	 * کلید آپشنی که نسخه‌ی نصب‌شده را نگه می‌دارد.
	 */
	const VERSION_OPTION = 'dsb_version';

	/**
	 * نمونه‌ی یکتا.
	 *
	 * @var DSB_Plugin|null
	 */
	private static $instance = null;

	/**
	 * تنظیمات.
	 *
	 * @var DSB_Settings
	 */
	public $settings;

	/**
	 * تاریخچه.
	 *
	 * @var DSB_Logger
	 */
	public $logger;

	/**
	 * مقصدهای ارسال.
	 *
	 * @var DSB_Destinations
	 */
	public $destinations;

	/**
	 * قوانین انتشار.
	 *
	 * @var DSB_Rules
	 */
	public $rules;

	/**
	 * انتشاردهنده.
	 *
	 * @var DSB_Publisher
	 */
	public $publisher;

	/**
	 * زمان‌بند.
	 *
	 * @var DSB_Scheduler
	 */
	public $scheduler;

	/**
	 * پایش سلامت.
	 *
	 * @var DSB_Health
	 */
	public $health;

	/**
	 * پنل مدیریت.
	 *
	 * @var DSB_Admin
	 */
	public $admin;

	/**
	 * گرفتن نمونه‌ی یکتا.
	 *
	 * @return DSB_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * سازنده.
	 */
	private function __construct() {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
		$this->init_components();
		$this->register_hooks();
	}

	/**
	 * جلوگیری از کلون‌شدن.
	 */
	private function __clone() {}

	/**
	 * بارگذاری خودکار کلاس‌ها بر اساس قرارداد نام‌گذاری وردپرس.
	 *
	 * DSB_Messenger_Bale  =>  includes/class-dsb-messenger-bale.php
	 *
	 * @param string $class نام کلاس درخواستی.
	 * @return void
	 */
	public static function autoload( $class ) {
		if ( 0 !== strpos( $class, 'DSB_' ) ) {
			return;
		}

		$file = DSB_PATH . 'includes/class-' . strtolower( str_replace( '_', '-', $class ) ) . '.php';

		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}

	/**
	 * ساخت سرویس‌ها.
	 *
	 * @return void
	 */
	private function init_components() {
		$this->settings     = new DSB_Settings();
		$this->logger       = new DSB_Logger();
		$this->destinations = new DSB_Destinations();
		$this->rules        = new DSB_Rules();
		$this->publisher    = new DSB_Publisher( $this->settings, $this->logger, $this->destinations );
		$this->health       = new DSB_Health( $this->settings, $this->logger, $this->rules );
		$this->scheduler    = new DSB_Scheduler( $this->settings, $this->rules, $this->publisher, $this->health );

		if ( is_admin() ) {
			$this->admin = new DSB_Admin(
				$this->settings,
				$this->logger,
				$this->destinations,
				$this->rules,
				$this->publisher,
				$this->scheduler,
				$this->health
			);
		}
	}

	/**
	 * ثبت هوک‌ها.
	 *
	 * @return void
	 */
	private function register_hooks() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'plugins_loaded', array( $this, 'maybe_upgrade' ) );

		$this->scheduler->register_hooks();

		if ( $this->admin ) {
			$this->admin->register_hooks();
		}
	}

	/**
	 * بارگذاری فایل ترجمه.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'daily-bale-post', false, dirname( DSB_BASENAME ) . '/languages' );
	}

	/**
	 * فعال‌سازی افزونه.
	 *
	 * @return void
	 */
	public static function activate() {
		$plugin = self::instance();
		$plugin->settings->install( $plugin->destinations, $plugin->rules );
		$plugin->scheduler->reschedule();
		update_option( self::VERSION_OPTION, DSB_VERSION, false );
	}

	/**
	 * غیرفعال‌سازی افزونه.
	 *
	 * @return void
	 */
	public static function deactivate() {
		self::instance()->scheduler->unschedule();
	}

	/**
	 * اجرای مهاجرت‌ها وقتی نسخه‌ی روی دیتابیس قدیمی‌تر از فایل‌هاست.
	 *
	 * @return void
	 */
	public function maybe_upgrade() {
		$installed = get_option( self::VERSION_OPTION, '0' );

		if ( version_compare( $installed, DSB_VERSION, '>=' ) ) {
			return;
		}

		$this->settings->install( $this->destinations, $this->rules );

		// نسخه‌های پیش از ۴.۰ یک رویداد روزانه‌ی تک‌شلیکی داشتند که گم می‌شد.
		$this->scheduler->drop_legacy_event();
		$this->scheduler->reschedule();

		update_option( self::VERSION_OPTION, DSB_VERSION, false );
	}
}
