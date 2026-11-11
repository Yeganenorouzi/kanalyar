<?php
/**
 * زمان‌بندی مقاوم، روی چند قانون مستقل.
 *
 * @package DailyBalePost
 */

defined( 'ABSPATH' ) || exit;

/**
 * به‌جای یک رویداد روزانه‌ی تک‌شلیکی، یک «تیک» کوتاه‌فاصله داریم که
 * برای هر قانون می‌پرسد «امروز پستش رفته یا نه». این الگو سه ضعف
 * نسخه‌های قدیمی را برطرف می‌کند:
 *
 * ۱. اگر رویداد کران از صف وردپرس گم شود، خودش دوباره ثبتش می‌کند.
 * ۲. اگر WP-Cron اصلاً اجرا نشود، بازدیدهای عادی سایت همان بررسی را انجام می‌دهند.
 * ۳. اگر ارسال شکست بخورد، همان روز چند بار دیگر تلاش می‌شود.
 */
class DSB_Scheduler {

	/**
	 * هوک تیک دوره‌ای.
	 */
	const TICK_HOOK = 'dsb_tick';

	/**
	 * هوک روزانه‌ی نسخه‌های پیش از ۴.۰.
	 */
	const LEGACY_HOOK = 'dsb_daily_event';

	/**
	 * نام بازه‌ی زمانی سفارشی.
	 */
	const INTERVAL = 'dsb_five_minutes';

	/**
	 * فاصله‌ی تیک بر حسب ثانیه.
	 */
	const INTERVAL_SECONDS = 300;

	/**
	 * پیشوند آپشن وضعیت امروزِ هر قانون.
	 */
	const TODAY_PREFIX = 'dsb_today_';

	/**
	 * آپشن قفل اجرا.
	 */
	const LOCK_OPTION = 'dsb_run_lock';

	/**
	 * عمر قفل بر حسب ثانیه.
	 */
	const LOCK_TTL = 600;

	/**
	 * آپشن زمان آخرین بررسی (از هر مسیری).
	 */
	const LAST_TICK_OPTION = 'dsb_last_tick';

	/**
	 * آپشن زمان آخرین بررسیِ اجراشده توسط خود WP-Cron.
	 */
	const LAST_CRON_OPTION = 'dsb_last_cron_tick';

	/**
	 * آپشن زمان ثبت‌شدن رویداد تیک.
	 */
	const SCHEDULED_AT_OPTION = 'dsb_scheduled_at';

	/**
	 * ترنزینتِ محدودکننده‌ی بررسیِ مبتنی بر بازدید.
	 */
	const FALLBACK_GUARD = 'dsb_fallback_guard';

	/**
	 * فاصله‌ی حداقلی بین دو تلاش ناموفق در یک روز (ثانیه).
	 */
	const RETRY_BACKOFF = 900;

	/**
	 * تنظیمات.
	 *
	 * @var DSB_Settings
	 */
	private $settings;

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
	 * پایش سلامت.
	 *
	 * @var DSB_Health
	 */
	private $health;

	/**
	 * سازنده.
	 *
	 * @param DSB_Settings  $settings  تنظیمات.
	 * @param DSB_Rules     $rules     قوانین.
	 * @param DSB_Publisher $publisher انتشاردهنده.
	 * @param DSB_Health    $health    پایش سلامت.
	 */
	public function __construct( DSB_Settings $settings, DSB_Rules $rules, DSB_Publisher $publisher, DSB_Health $health ) {
		$this->settings  = $settings;
		$this->rules     = $rules;
		$this->publisher = $publisher;
		$this->health    = $health;
	}

	/**
	 * ثبت هوک‌ها.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_filter( 'cron_schedules', array( $this, 'add_interval' ) ); // phpcs:ignore WordPress.WP.CronInterval.ChangeDetected
		add_action( self::TICK_HOOK, array( $this, 'run_tick' ) );
		add_action( self::LEGACY_HOOK, array( $this, 'run_tick' ) );
		add_action( 'init', array( $this, 'ensure_scheduled' ) );
		add_action( 'shutdown', array( $this, 'traffic_fallback' ), 99 );
	}

	/**
	 * افزودن بازه‌ی پنج‌دقیقه‌ای به زمان‌بندی‌های وردپرس.
	 *
	 * @param array $schedules بازه‌های موجود.
	 * @return array
	 */
	public function add_interval( $schedules ) {
		$schedules[ self::INTERVAL ] = array(
			'interval' => self::INTERVAL_SECONDS,
			'display'  => __( 'هر ۵ دقیقه (انتشار خودکار)', 'daily-bale-post' ),
		);

		return $schedules;
	}

	/**
	 * اطمینان از وجود رویداد تیک در صف کران.
	 *
	 * @return void
	 */
	public function ensure_scheduled() {
		if ( ! wp_next_scheduled( self::TICK_HOOK ) ) {
			$this->schedule_tick();
		}
	}

	/**
	 * ثبت دوباره‌ی زمان‌بندی از صفر.
	 *
	 * @return void
	 */
	public function reschedule() {
		$this->unschedule();
		$this->schedule_tick();
	}

	/**
	 * ثبت رویداد تیک و یادداشت زمان ثبت.
	 *
	 * @return void
	 */
	private function schedule_tick() {
		wp_schedule_event( time() + 60, self::INTERVAL, self::TICK_HOOK );
		update_option( self::SCHEDULED_AT_OPTION, time(), false );
	}

	/**
	 * حذف رویداد تیک.
	 *
	 * @return void
	 */
	public function unschedule() {
		wp_clear_scheduled_hook( self::TICK_HOOK );
	}

	/**
	 * حذف رویداد روزانه‌ی نسخه‌های قدیمی.
	 *
	 * @return void
	 */
	public function drop_legacy_event() {
		wp_clear_scheduled_hook( self::LEGACY_HOOK );
	}

	/**
	 * اجرای تیک زمان‌بندی‌شده.
	 *
	 * @return void
	 */
	public function run_tick() {
		$now = time();

		update_option( self::LAST_TICK_OPTION, $now, false );
		update_option( self::LAST_CRON_OPTION, $now, false );

		$this->run_due_rules();
		$this->health->maybe_alert();
	}

	/**
	 * بررسیِ مبتنی بر بازدید سایت.
	 *
	 * پشتیبانِ WP-Cron است: روی سایت‌هایی که لوپ‌بکِ wp-cron.php کار نمی‌کند،
	 * انتشار با همین مسیر انجام می‌شود.
	 *
	 * @return void
	 */
	public function traffic_fallback() {
		if ( wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return;
		}

		if ( get_transient( self::FALLBACK_GUARD ) ) {
			return;
		}

		set_transient( self::FALLBACK_GUARD, 1, self::INTERVAL_SECONDS );

		update_option( self::LAST_TICK_OPTION, time(), false );

		if ( ! $this->has_due_rule() ) {
			$this->health->maybe_alert();
			return;
		}

		// بازدیدکننده نباید منتظر ارسال بماند: اگر بستن زودهنگام پاسخ ممکن باشد
		// (PHP-FPM و LiteSpeed) پاسخ بسته می‌شود و ارسال در پس‌زمینه ادامه می‌یابد.
		ignore_user_abort( true );

		if ( function_exists( 'fastcgi_finish_request' ) ) {
			fastcgi_finish_request();
		} elseif ( function_exists( 'litespeed_finish_request' ) ) {
			litespeed_finish_request();
		}

		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 300 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}

		$this->run_due_rules();
		$this->health->maybe_alert();
	}

	/**
	 * آیا حداقل یک قانون آماده‌ی اجراست.
	 *
	 * @return bool
	 */
	public function has_due_rule() {
		foreach ( $this->rules->active() as $rule ) {
			if ( 'due' === $this->due_state( $rule )['code'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * اجرای همه‌ی قوانینی که نوبتشان رسیده.
	 *
	 * @return array نتایج، کلید = شناسه‌ی قانون.
	 */
	public function run_due_rules() {
		$out = array();

		foreach ( $this->rules->active() as $rule ) {
			$out[ $rule['id'] ] = $this->maybe_publish( $rule );
		}

		return $out;
	}

	/**
	 * تشخیص اینکه یک قانون همین حالا باید اجرا شود یا نه.
	 *
	 * @param array $rule قانون.
	 * @return array{code:string,message:string}
	 */
	public function due_state( array $rule ) {
		if ( ! $this->settings->get( 'enabled' ) ) {
			return $this->state( 'disabled', __( 'انتشار خودکار در تنظیمات کلی خاموش است.', 'daily-bale-post' ) );
		}

		if ( empty( $rule['enabled'] ) ) {
			return $this->state( 'disabled', __( 'این قانون غیرفعال است.', 'daily-bale-post' ) );
		}

		if ( empty( $rule['destinations'] ) ) {
			return $this->state( 'no-destination', __( 'مقصدی برای این قانون انتخاب نشده است.', 'daily-bale-post' ) );
		}

		$now   = DSB_Settings::now();
		$today = $now->format( 'Y-m-d' );
		$days  = array_map( 'intval', (array) $rule['days'] );

		if ( $days && ! in_array( (int) $now->format( 'w' ), $days, true ) ) {
			return $this->state( 'day-off', __( 'امروز جزو روزهای این قانون نیست.', 'daily-bale-post' ) );
		}

		$record = $this->today_record( $rule['id'], $today );

		if ( ! empty( $record['done'] ) ) {
			return $this->state( 'done', __( 'پست امروز این قانون ارسال شده است.', 'daily-bale-post' ) );
		}

		$target = $now->setTime( (int) $rule['hour'], (int) $rule['minute'], 0 );

		if ( $now < $target ) {
			return $this->state( 'early', __( 'هنوز به ساعت این قانون نرسیده‌ایم.', 'daily-bale-post' ) );
		}

		$max = max( 1, (int) $rule['max_attempts'] );

		if ( (int) $record['attempts'] >= $max ) {
			return $this->state( 'exhausted', __( 'تلاش‌های امروز این قانون به پایان رسیده است.', 'daily-bale-post' ) );
		}

		if ( $record['last_attempt'] && ( time() - (int) $record['last_attempt'] ) < self::RETRY_BACKOFF ) {
			return $this->state( 'cooldown', __( 'در فاصله‌ی انتظار بین دو تلاش.', 'daily-bale-post' ) );
		}

		return $this->state( 'due', __( 'آماده‌ی ارسال.', 'daily-bale-post' ) );
	}

	/**
	 * اجرای یک قانون در صورت فرارسیدن زمانش.
	 *
	 * @param array $rule قانون.
	 * @return array نتیجه یا وضعیت رد شدن.
	 */
	public function maybe_publish( array $rule ) {
		$state = $this->due_state( $rule );

		if ( 'due' !== $state['code'] ) {
			return $state;
		}

		if ( ! $this->acquire_lock() ) {
			return $this->state( 'locked', __( 'یک ارسال دیگر در حال انجام است.', 'daily-bale-post' ) );
		}

		try {
			$today  = DSB_Settings::now()->format( 'Y-m-d' );
			$record = $this->today_record( $rule['id'], $today );

			// بررسی دوباره زیر قفل، تا دو درخواست هم‌زمان دوبار پست نگذارند.
			if ( ! empty( $record['done'] ) ) {
				return $this->state( 'done', __( 'پست امروز این قانون ارسال شده است.', 'daily-bale-post' ) );
			}

			$record['attempts']     = (int) $record['attempts'] + 1;
			$record['last_attempt'] = time();
			$this->save_today( $rule['id'], $record );

			$result = $this->publisher->publish( $rule, 'auto' );

			if ( ! empty( $result['ok'] ) ) {
				$record['done'] = true;
				$this->save_today( $rule['id'], $record );
			}

			return $result;
		} finally {
			$this->release_lock();
		}
	}

	/**
	 * اجرای دستی و فوری یک قانون (دکمه‌ی ارسال تستی).
	 *
	 * سهمیه‌ی روزانه را مصرف نمی‌کند تا پست خودکار همان روز هم ارسال شود.
	 *
	 * @param array $rule قانون.
	 * @return array
	 */
	public function run_now( array $rule ) {
		return $this->publisher->publish( $rule, 'manual' );
	}

	/**
	 * خواندن وضعیت امروزِ یک قانون، با ریست خودکار در تغییر روز.
	 *
	 * @param string $rule_id شناسه‌ی قانون.
	 * @param string $today   تاریخ امروز به وقت تهران.
	 * @return array
	 */
	private function today_record( $rule_id, $today ) {
		$record = get_option( self::TODAY_PREFIX . $rule_id, array() );
		$record = is_array( $record ) ? $record : array();

		$blank = array(
			'date'         => $today,
			'attempts'     => 0,
			'done'         => false,
			'last_attempt' => 0,
		);

		if ( ( $record['date'] ?? '' ) !== $today ) {
			return $blank;
		}

		return wp_parse_args( $record, $blank );
	}

	/**
	 * ذخیره‌ی وضعیت امروزِ یک قانون.
	 *
	 * @param string $rule_id شناسه‌ی قانون.
	 * @param array  $record  وضعیت.
	 * @return void
	 */
	private function save_today( $rule_id, array $record ) {
		update_option( self::TODAY_PREFIX . $rule_id, $record, false );
	}

	/**
	 * وضعیت امروزِ یک قانون، برای نمایش در پنل.
	 *
	 * @param string $rule_id شناسه‌ی قانون.
	 * @return array
	 */
	public function today( $rule_id ) {
		return $this->today_record( $rule_id, DSB_Settings::now()->format( 'Y-m-d' ) );
	}

	/**
	 * پاک‌کردن وضعیت امروزِ یک قانون.
	 *
	 * @param string $rule_id شناسه‌ی قانون.
	 * @return void
	 */
	public function forget_today( $rule_id ) {
		delete_option( self::TODAY_PREFIX . $rule_id );
	}

	/**
	 * گرفتن قفل اجرا.
	 *
	 * add_option به‌خاطر ایندکس یکتای option_name اتمیک است و
	 * جلوی ارسال هم‌زمان چند پراسس را می‌گیرد.
	 *
	 * @return bool
	 */
	private function acquire_lock() {
		if ( add_option( self::LOCK_OPTION, time(), '', false ) ) {
			return true;
		}

		$held = (int) get_option( self::LOCK_OPTION, 0 );

		// قفل جامانده از یک اجرای ناتمام.
		if ( $held && ( time() - $held ) > self::LOCK_TTL ) {
			update_option( self::LOCK_OPTION, time(), false );
			return true;
		}

		return false;
	}

	/**
	 * آزادکردن قفل.
	 *
	 * @return void
	 */
	private function release_lock() {
		delete_option( self::LOCK_OPTION );
	}

	/**
	 * زمان اجرای بعدی تیک.
	 *
	 * @return int|false
	 */
	public function next_tick() {
		return wp_next_scheduled( self::TICK_HOOK );
	}

	/**
	 * زمان آخرین بررسی، از هر مسیری.
	 *
	 * @return int
	 */
	public function last_tick() {
		return (int) get_option( self::LAST_TICK_OPTION, 0 );
	}

	/**
	 * زمان آخرین بررسیِ اجراشده توسط خود WP-Cron.
	 *
	 * @return int
	 */
	public function last_cron_tick() {
		return (int) get_option( self::LAST_CRON_OPTION, 0 );
	}

	/**
	 * زمانی که رویداد تیک ثبت شده است.
	 *
	 * @return int
	 */
	public function scheduled_at() {
		return (int) get_option( self::SCHEDULED_AT_OPTION, 0 );
	}

	/**
	 * زمان ارسال بعدی یک قانون.
	 *
	 * @param array $rule قانون.
	 * @return DateTimeImmutable|null
	 */
	public function next_run( array $rule ) {
		if ( ! $this->settings->get( 'enabled' ) || empty( $rule['enabled'] ) ) {
			return null;
		}

		$days = array_map( 'intval', (array) $rule['days'] );

		// فهرست خالی یعنی «همه‌ی روزها»، مطابق رفتار due_state().
		if ( empty( $days ) ) {
			$days = range( 0, 6 );
		}

		$now    = DSB_Settings::now();
		$record = $this->today_record( $rule['id'], $now->format( 'Y-m-d' ) );

		for ( $offset = 0; $offset <= 7; $offset++ ) {
			$day = $now->modify( '+' . $offset . ' days' )->setTime(
				(int) $rule['hour'],
				(int) $rule['minute'],
				0
			);

			if ( ! in_array( (int) $day->format( 'w' ), $days, true ) ) {
				continue;
			}

			if ( 0 === $offset && ( $day < $now || ! empty( $record['done'] ) ) ) {
				continue;
			}

			return $day;
		}

		return null;
	}

	/**
	 * ساخت آرایه‌ی وضعیت.
	 *
	 * @param string $code    کد وضعیت.
	 * @param string $message توضیح.
	 * @return array{code:string,message:string,ok:bool}
	 */
	private function state( $code, $message ) {
		return array(
			'code'    => $code,
			'message' => $message,
			'ok'      => false,
		);
	}
}
