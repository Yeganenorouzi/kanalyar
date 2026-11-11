<?php
/**
 * کارت وضعیت سلامت.
 *
 * @package DailyBalePost
 *
 * @var array     $report       گزارش سلامت.
 * @var string    $dsb_endpoint نشانی admin-post.
 * @var DSB_Admin $this         کنترلر پنل.
 */

defined( 'ABSPATH' ) || exit;

$dsb_levels = array(
	'good' => array( '✅', __( 'سالم', 'daily-bale-post' ) ),
	'warn' => array( '⚠️', __( 'نیازمند بررسی', 'daily-bale-post' ) ),
	'bad'  => array( '⛔', __( 'انتشار متوقف شده', 'daily-bale-post' ) ),
	'off'  => array( '⏸️', __( 'غیرفعال', 'daily-bale-post' ) ),
);

$dsb_level = $dsb_levels[ $report['level'] ] ?? $dsb_levels['warn'];
?>
<div class="dsb-card dsb-card--health dsb-health--<?php echo esc_attr( $report['level'] ); ?>">
	<h2><span class="dsb-h2-icon">🩺</span> <?php esc_html_e( 'وضعیت سلامت', 'daily-bale-post' ); ?></h2>

	<div class="dsb-health-head">
		<span class="dsb-badge"><?php echo esc_html( $dsb_level[0] . ' ' . $dsb_level[1] ); ?></span>

		<?php if ( null === $report['days_since'] ) : ?>
			<span class="dsb-health-note"><?php esc_html_e( 'هنوز هیچ ارسال موفقی ثبت نشده است.', 'daily-bale-post' ); ?></span>
		<?php elseif ( 0 === $report['days_since'] ) : ?>
			<span class="dsb-health-note"><?php esc_html_e( 'آخرین ارسال موفق: امروز', 'daily-bale-post' ); ?></span>
		<?php else : ?>
			<span class="dsb-health-note">
				<?php
				printf(
					/* translators: %d: تعداد روز. */
					esc_html__( 'آخرین ارسال موفق: %d روز پیش', 'daily-bale-post' ),
					(int) $report['days_since']
				);
				?>
			</span>
		<?php endif; ?>
	</div>

	<ul class="dsb-health-list">
		<li>
			<strong><?php esc_html_e( 'قوانین فعال:', 'daily-bale-post' ); ?></strong>
			<?php if ( $report['active_rules'] > 0 ) : ?>
				<span class="dsb-ok">
					<?php
					printf(
						/* translators: ۱: تعداد فعال، ۲: تعداد کل. */
						esc_html__( '%1$d از %2$d', 'daily-bale-post' ),
						(int) $report['active_rules'],
						(int) $report['total_rules']
					);
					?>
				</span>
			<?php else : ?>
				<span class="dsb-bad"><?php esc_html_e( 'هیچ قانون فعالی نیست', 'daily-bale-post' ); ?></span>
			<?php endif; ?>
		</li>
		<li>
			<strong><?php esc_html_e( 'چرخه‌ی بررسی:', 'daily-bale-post' ); ?></strong>
			<?php if ( $report['check_ok'] ) : ?>
				<span class="dsb-ok"><?php esc_html_e( 'در حال اجرا', 'daily-bale-post' ); ?></span>
			<?php else : ?>
				<span class="dsb-bad"><?php esc_html_e( 'اجرا نمی‌شود', 'daily-bale-post' ); ?></span>
			<?php endif; ?>
			<span dir="ltr" class="dsb-dim">(<?php echo esc_html( $this->local_time( $report['last_tick'] ) ); ?>)</span>
		</li>
		<li>
			<strong><?php esc_html_e( 'WP-Cron:', 'daily-bale-post' ); ?></strong>
			<?php if ( 'ok' === $report['cron_state'] ) : ?>
				<span class="dsb-ok"><?php esc_html_e( 'سالم', 'daily-bale-post' ); ?></span>
				<span dir="ltr" class="dsb-dim">(<?php echo esc_html( $this->local_time( $report['last_cron'] ) ); ?>)</span>
			<?php elseif ( 'pending' === $report['cron_state'] ) : ?>
				<span class="dsb-pending"><?php esc_html_e( 'در انتظار اولین اجرا', 'daily-bale-post' ); ?></span>
				<span class="dsb-dim"><?php esc_html_e( '— تا نیم‌ساعت دیگر مشخص می‌شود', 'daily-bale-post' ); ?></span>
			<?php else : ?>
				<span class="dsb-bad"><?php esc_html_e( 'اجرا نمی‌شود — پشتیبانِ مبتنی بر بازدید فعال است', 'daily-bale-post' ); ?></span>
				<span dir="ltr" class="dsb-dim">(<?php echo esc_html( $this->local_time( $report['last_cron'] ) ); ?>)</span>
			<?php endif; ?>
		</li>
	</ul>

	<?php if ( 'pending' === $report['cron_state'] ) : ?>
		<div class="dsb-alert dsb-alert--info">
			<p><?php esc_html_e( 'زمان‌بندی تازه ثبت شده است. اولین اجرای WP-Cron هنوز انجام نشده، که در دقایق اول طبیعی است. نیم‌ساعت دیگر همین صفحه را باز کنید تا وضعیت واقعی مشخص شود.', 'daily-bale-post' ); ?></p>
		</div>
	<?php elseif ( 'down' === $report['cron_state'] ) : ?>
		<div class="dsb-alert">
			<p>
				<strong><?php esc_html_e( 'WP-Cron روی این سایت اجرا نمی‌شود.', 'daily-bale-post' ); ?></strong>
				<?php esc_html_e( 'افزونه از بازدیدهای سایت به‌عنوان پشتیبان استفاده می‌کند و پست ارسال می‌شود، ولی برای ارسال دقیقاً سر ساعت، یک cron واقعی سمت سرور لازم است:', 'daily-bale-post' ); ?>
			</p>
			<?php if ( $report['wp_cron_off'] ) : ?>
				<p class="description"><?php esc_html_e( 'ثابت DISABLE_WP_CRON روی این سایت فعال است؛ پس حتماً باید cron سرور تنظیم شده باشد.', 'daily-bale-post' ); ?></p>
			<?php endif; ?>
			<pre dir="ltr">*/5 * * * * wget -q -O - <?php echo esc_html( site_url( 'wp-cron.php?doing_wp_cron' ) ); ?> &gt;/dev/null 2&gt;&amp;1</pre>
		</div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( $dsb_endpoint ); ?>" class="dsb-inline-form">
		<?php wp_nonce_field( DSB_Admin::NONCE ); ?>
		<input type="hidden" name="action" value="dsb_handle">
		<input type="hidden" name="dsb_tab" value="<?php echo esc_attr( $this->current_tab() ); ?>">
		<button type="submit" name="dsb_action" value="repair" class="button">
			🔧 <?php esc_html_e( 'بازسازی زمان‌بندی', 'daily-bale-post' ); ?>
		</button>
	</form>
</div>
