<?php
/**
 * پایش سلامت افزونه و هشدار.
 *
 * @package DailyBalePost
 */

defined( 'ABSPATH' ) || exit;

/**
 * تشخیص اینکه ارسال روزانه واقعاً دارد انجام می‌شود یا نه.
 */
class DSB_Health {

	/**
	 * آپشن تاریخ آخرین هشدار ارسال‌شده.
	 */
	const LAST_ALERT_OPTION = 'dsb_last_alert';

	/**
	 * آپشن زمان نصب.
	 */
	const INSTALLED_OPTION = 'dsb_installed_at';

	/**
	 * پس از چند روز بی‌پستی، وضعیت بحرانی است.
	 */
	const STALE_DAYS = 2;

	/**
	 * اگر بیشتر از این مدت هیچ بررسی‌ای نشده باشد، چرخه خراب است (ثانیه).
	 */
	const TICK_MAX_AGE = 1800;

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
	 * سازنده.
	 *
	 * @param DSB_Settings $settings تنظیمات.
	 * @param DSB_Logger   $logger   تاریخچه.
	 */
	public function __construct( DSB_Settings $settings, DSB_Logger $logger ) {
		$this->settings = $settings;
		$this->logger   = $logger;
	}

	/**
	 * تعداد روزهای گذشته از آخرین ارسال موفق.
	 *
	 * @return int|null null یعنی هنوز هیچ ارسال موفقی ثبت نشده.
	 */
	public function days_since_success() {
		$last = $this->logger->last_success();

		if ( ! $last ) {
			return null;
		}

		$diff = time() - (int) $last['time'];

		return (int) floor( $diff / DAY_IN_SECONDS );
	}

	/**
	 * گزارش وضعیت برای نمایش در پنل.
	 *
	 * @param DSB_Scheduler $scheduler زمان‌بند.
	 * @return array
	 */
	public function report( DSB_Scheduler $scheduler ) {
		$last_tick = $scheduler->last_tick();
		$last_cron = $scheduler->last_cron_tick();
		$next_tick = $scheduler->next_tick();
		$days      = $this->days_since_success();

		// اگر بیش از نیم ساعت هیچ بررسی‌ای انجام نشده، چرخه مشکل دارد.
		$check_ok   = ( $last_tick && ( time() - $last_tick ) < self::TICK_MAX_AGE );
		$cron_state = $this->cron_state( $last_cron, $scheduler->scheduled_at() );
		$cron_ok    = ( 'ok' === $cron_state );

		if ( null === $days ) {
			$level = $this->is_fresh_install() ? 'good' : 'warn';
		} elseif ( $days >= self::STALE_DAYS ) {
			$level = 'bad';
		} elseif ( ! $check_ok || 'down' === $cron_state ) {
			$level = 'warn';
		} else {
			$level = 'good';
		}

		if ( ! $this->settings->get( 'enabled' ) ) {
			$level = 'off';
		}

		return array(
			'level'        => $level,
			'cron_ok'      => $cron_ok,
			'cron_state'   => $cron_state,
			'check_ok'     => $check_ok,
			'last_tick'    => $last_tick,
			'last_cron'    => $last_cron,
			'next_tick'    => $next_tick,
			'last_success' => $this->logger->last_success(),
			'last_entry'   => $this->logger->last(),
			'days_since'   => $days,
			'wp_cron_off'  => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON,
		);
	}

	/**
	 * تشخیص وضعیت WP-Cron.
	 *
	 * تا وقتی رویداد تیک تازه ثبت شده و هنوز هیچ اجرایی نداشته،
	 * نتیجه‌گیری زودهنگام نمی‌کنیم: «در انتظار» نه «خراب».
	 *
	 * @param int $last_cron    زمان آخرین تیکِ کران.
	 * @param int $scheduled_at زمان ثبت رویداد تیک.
	 * @return string یکی از ok | pending | down.
	 */
	private function cron_state( $last_cron, $scheduled_at ) {
		if ( $last_cron && ( time() - $last_cron ) < self::TICK_MAX_AGE ) {
			return 'ok';
		}

		// هرگز اجرا نشده، ولی تازه ثبت شده است.
		if ( ! $last_cron && $scheduled_at && ( time() - $scheduled_at ) < self::TICK_MAX_AGE ) {
			return 'pending';
		}

		return 'down';
	}

	/**
	 * آیا افزونه تازه نصب شده است.
	 *
	 * روی نصب تازه هنوز طبیعی است که هیچ ارسالی ثبت نشده باشد،
	 * پس نه هشدار می‌دهیم نه وضعیت را قرمز می‌کنیم.
	 *
	 * @return bool
	 */
	private function is_fresh_install() {
		$installed = (int) get_option( self::INSTALLED_OPTION, 0 );

		if ( ! $installed ) {
			return true;
		}

		return ( time() - $installed ) < ( self::STALE_DAYS * DAY_IN_SECONDS );
	}

	/**
	 * ارسال هشدار ایمیلی وقتی چند روز پستی نرفته است.
	 *
	 * حداکثر روزی یک ایمیل فرستاده می‌شود.
	 *
	 * @return bool آیا ایمیلی ارسال شد.
	 */
	public function maybe_alert() {
		if ( ! $this->settings->get( 'alerts' ) || ! $this->settings->get( 'enabled' ) ) {
			return false;
		}

		$days = $this->days_since_success();

		if ( null !== $days && $days < self::STALE_DAYS ) {
			return false;
		}

		// نصب تازه؛ هنوز وقت هشدار نیست.
		if ( null === $days && $this->is_fresh_install() ) {
			return false;
		}

		$today = DSB_Settings::now()->format( 'Y-m-d' );

		if ( get_option( self::LAST_ALERT_OPTION, '' ) === $today ) {
			return false;
		}

		update_option( self::LAST_ALERT_OPTION, $today, false );

		return $this->send_alert( $days );
	}

	/**
	 * ساخت و ارسال ایمیل هشدار.
	 *
	 * @param int|null $days تعداد روزهای بی‌پستی.
	 * @return bool
	 */
	private function send_alert( $days ) {
		$site  = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$last  = $this->logger->last();
		$panel = admin_url( 'admin.php?page=dsb-settings' );

		/* translators: %s: نام سایت. */
		$subject = sprintf( __( '[%s] ارسال روزانه به بله انجام نشده است', 'daily-bale-post' ), $site );

		$lines = array();

		if ( null === $days ) {
			$lines[] = __( 'تا این لحظه هیچ ارسال موفقی در تاریخچه ثبت نشده است.', 'daily-bale-post' );
		} else {
			/* translators: %d: تعداد روز. */
			$lines[] = sprintf( __( 'از آخرین ارسال موفق %d روز گذشته است.', 'daily-bale-post' ), $days );
		}

		if ( $last ) {
			$lines[] = '';
			$lines[] = __( 'آخرین رویداد ثبت‌شده:', 'daily-bale-post' );
			$lines[] = sprintf(
				'%s — %s — %s',
				get_date_from_gmt( gmdate( 'Y-m-d H:i:s', (int) $last['time'] ), 'Y-m-d H:i' ),
				$last['name'],
				$last['msg']
			);
		}

		$lines[] = '';
		$lines[] = __( 'بررسی‌های پیشنهادی:', 'daily-bale-post' );
		$lines[] = __( '۱. توکن بات و دسترسی ادمین بودنش در کانال.', 'daily-bale-post' );
		$lines[] = __( '۲. صفحه‌ی تنظیمات افزونه، بخش «وضعیت سلامت».', 'daily-bale-post' );
		$lines[] = __( '۳. فعال بودن cron سمت سرور.', 'daily-bale-post' );
		$lines[] = '';
		$lines[] = $panel;

		return wp_mail( $this->settings->alert_email(), $subject, implode( "\n", $lines ) );
	}
}
