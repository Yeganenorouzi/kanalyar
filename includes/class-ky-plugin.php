<?php
/**
 * هسته‌ی افزونه: بارگذاری کلاس‌ها، سیم‌کشی هوک‌ها، نصب و حذف.
 *
 * @package Kanalyar
 */

defined( 'ABSPATH' ) || exit;

/**
 * کلاس اصلی افزونه (Singleton).
 */
final class KY_Plugin {

	/**
	 * کلید آپشنی که نسخه‌ی نصب‌شده را نگه می‌دارد.
	 */
	const VERSION_OPTION = 'dsb_version';

	/**
	 * نمونه‌ی یکتا.
	 *
	 * @var KY_Plugin|null
	 */
	private static $instance = null;

	/**
	 * تنظیمات.
	 *
	 * @var KY_Settings
	 */
	public $settings;

	/**
	 * تاریخچه.
	 *
	 * @var KY_Logger
	 */
	public $logger;

	/**
	 * مقصدهای ارسال.
	 *
	 * @var KY_Destinations
	 */
	public $destinations;

	/**
	 * قوانین انتشار.
	 *
	 * @var KY_Rules
	 */
	public $rules;

	/**
	 * انتشاردهنده.
	 *
	 * @var KY_Publisher
	 */
	public $publisher;

	/**
	 * زمان‌بند.
	 *
	 * @var KY_Scheduler
	 */
	public $scheduler;

	/**
	 * پایش سلامت.
	 *
	 * @var KY_Health
	 */
	public $health;

	/**
	 * پنل مدیریت.
	 *
	 * @var KY_Admin
	 */
	public $admin;

	/**
	 * گرفتن نمونه‌ی یکتا.
	 *
	 * @return KY_Plugin
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
	 * KY_Messenger_Bale  =>  includes/class-ky-messenger-bale.php
	 *
	 * @param string $class نام کلاس درخواستی.
	 * @return void
	 */
	public static function autoload( $class ) {
		if ( 0 !== strpos( $class, 'KY_' ) ) {
			return;
		}

		$file = KY_PATH . 'includes/class-' . strtolower( str_replace( '_', '-', $class ) ) . '.php';

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
		$this->settings     = new KY_Settings();
		$this->logger       = new KY_Logger();
		$this->destinations = new KY_Destinations();
		$this->rules        = new KY_Rules();
		$this->publisher    = new KY_Publisher( $this->settings, $this->logger, $this->destinations );
		$this->health       = new KY_Health( $this->settings, $this->logger, $this->rules );
		$this->scheduler    = new KY_Scheduler( $this->settings, $this->rules, $this->publisher, $this->health );

		if ( is_admin() ) {
			$this->admin = new KY_Admin(
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
		load_plugin_textdomain( 'kanalyar', false, dirname( KY_BASENAME ) . '/languages' );
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
		update_option( self::VERSION_OPTION, KY_VERSION, false );
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

		if ( version_compare( $installed, KY_VERSION, '>=' ) ) {
			return;
		}

		$this->settings->install( $this->destinations, $this->rules );

		// نسخه‌های پیش از ۴.۰ یک رویداد روزانه‌ی تک‌شلیکی داشتند که گم می‌شد.
		$this->scheduler->drop_legacy_event();
		$this->scheduler->reschedule();

		update_option( self::VERSION_OPTION, KY_VERSION, false );
	}
}
