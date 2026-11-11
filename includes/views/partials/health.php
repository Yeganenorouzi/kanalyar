<?php
/**
 * کارت وضعیت سلامت.
 *
 * @package Kanalyar
 *
 * @var array     $report       گزارش سلامت.
 * @var string    $ky_endpoint نشانی admin-post.
 * @var KY_Admin $this         کنترلر پنل.
 */

defined( 'ABSPATH' ) || exit;

$ky_levels = array(
	'good' => array( '✅', __( 'سالم', 'kanalyar' ) ),
	'warn' => array( '⚠️', __( 'نیازمند بررسی', 'kanalyar' ) ),
	'bad'  => array( '⛔', __( 'انتشار متوقف شده', 'kanalyar' ) ),
	'off'  => array( '⏸️', __( 'غیرفعال', 'kanalyar' ) ),
);

$ky_level = $ky_levels[ $report['level'] ] ?? $ky_levels['warn'];
?>
<div class="ky-card ky-card--health ky-health--<?php echo esc_attr( $report['level'] ); ?>">
	<h2><span class="ky-h2-icon">🩺</span> <?php esc_html_e( 'وضعیت سلامت', 'kanalyar' ); ?></h2>

	<div class="ky-health-head">
		<span class="ky-badge"><?php echo esc_html( $ky_level[0] . ' ' . $ky_level[1] ); ?></span>

		<?php if ( null === $report['days_since'] ) : ?>
			<span class="ky-health-note"><?php esc_html_e( 'هنوز هیچ ارسال موفقی ثبت نشده است.', 'kanalyar' ); ?></span>
		<?php elseif ( 0 === $report['days_since'] ) : ?>
			<span class="ky-health-note"><?php esc_html_e( 'آخرین ارسال موفق: امروز', 'kanalyar' ); ?></span>
		<?php else : ?>
			<span class="ky-health-note">
				<?php
				printf(
					/* translators: %d: تعداد روز. */
					esc_html__( 'آخرین ارسال موفق: %d روز پیش', 'kanalyar' ),
					(int) $report['days_since']
				);
				?>
			</span>
		<?php endif; ?>
	</div>

	<ul class="ky-health-list">
		<li>
			<strong><?php esc_html_e( 'قوانین فعال:', 'kanalyar' ); ?></strong>
			<?php if ( $report['active_rules'] > 0 ) : ?>
				<span class="ky-ok">
					<?php
					printf(
						/* translators: ۱: تعداد فعال، ۲: تعداد کل. */
						esc_html__( '%1$d از %2$d', 'kanalyar' ),
						(int) $report['active_rules'],
						(int) $report['total_rules']
					);
					?>
				</span>
			<?php else : ?>
				<span class="ky-bad"><?php esc_html_e( 'هیچ قانون فعالی نیست', 'kanalyar' ); ?></span>
			<?php endif; ?>
		</li>
		<li>
			<strong><?php esc_html_e( 'چرخه‌ی بررسی:', 'kanalyar' ); ?></strong>
			<?php if ( $report['check_ok'] ) : ?>
				<span class="ky-ok"><?php esc_html_e( 'در حال اجرا', 'kanalyar' ); ?></span>
			<?php else : ?>
				<span class="ky-bad"><?php esc_html_e( 'اجرا نمی‌شود', 'kanalyar' ); ?></span>
			<?php endif; ?>
			<span dir="ltr" class="ky-dim">(<?php echo esc_html( $this->local_time( $report['last_tick'] ) ); ?>)</span>
		</li>
		<li>
			<strong><?php esc_html_e( 'WP-Cron:', 'kanalyar' ); ?></strong>
			<?php if ( 'ok' === $report['cron_state'] ) : ?>
				<span class="ky-ok"><?php esc_html_e( 'سالم', 'kanalyar' ); ?></span>
				<span dir="ltr" class="ky-dim">(<?php echo esc_html( $this->local_time( $report['last_cron'] ) ); ?>)</span>
			<?php elseif ( 'pending' === $report['cron_state'] ) : ?>
				<span class="ky-pending"><?php esc_html_e( 'در انتظار اولین اجرا', 'kanalyar' ); ?></span>
				<span class="ky-dim"><?php esc_html_e( '— تا نیم‌ساعت دیگر مشخص می‌شود', 'kanalyar' ); ?></span>
			<?php else : ?>
				<span class="ky-bad"><?php esc_html_e( 'اجرا نمی‌شود — پشتیبانِ مبتنی بر بازدید فعال است', 'kanalyar' ); ?></span>
				<span dir="ltr" class="ky-dim">(<?php echo esc_html( $this->local_time( $report['last_cron'] ) ); ?>)</span>
			<?php endif; ?>
		</li>
	</ul>

	<?php if ( 'pending' === $report['cron_state'] ) : ?>
		<div class="ky-alert ky-alert--info">
			<p><?php esc_html_e( 'زمان‌بندی تازه ثبت شده است. اولین اجرای WP-Cron هنوز انجام نشده، که در دقایق اول طبیعی است. نیم‌ساعت دیگر همین صفحه را باز کنید تا وضعیت واقعی مشخص شود.', 'kanalyar' ); ?></p>
		</div>
	<?php elseif ( 'down' === $report['cron_state'] ) : ?>
		<div class="ky-alert">
			<p>
				<strong><?php esc_html_e( 'WP-Cron روی این سایت اجرا نمی‌شود.', 'kanalyar' ); ?></strong>
				<?php esc_html_e( 'افزونه از بازدیدهای سایت به‌عنوان پشتیبان استفاده می‌کند و پست ارسال می‌شود، ولی برای ارسال دقیقاً سر ساعت، یک cron واقعی سمت سرور لازم است:', 'kanalyar' ); ?>
			</p>
			<?php if ( $report['wp_cron_off'] ) : ?>
				<p class="description"><?php esc_html_e( 'ثابت DISABLE_WP_CRON روی این سایت فعال است؛ پس حتماً باید cron سرور تنظیم شده باشد.', 'kanalyar' ); ?></p>
			<?php endif; ?>
			<pre dir="ltr">*/5 * * * * wget -q -O - <?php echo esc_html( site_url( 'wp-cron.php?doing_wp_cron' ) ); ?> &gt;/dev/null 2&gt;&amp;1</pre>
		</div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( $ky_endpoint ); ?>" class="ky-inline-form">
		<?php wp_nonce_field( KY_Admin::NONCE ); ?>
		<input type="hidden" name="action" value="ky_handle">
		<input type="hidden" name="ky_tab" value="<?php echo esc_attr( $this->current_tab() ); ?>">
		<button type="submit" name="ky_action" value="repair" class="button">
			🔧 <?php esc_html_e( 'بازسازی زمان‌بندی', 'kanalyar' ); ?>
		</button>
	</form>
</div>
