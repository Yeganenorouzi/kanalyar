<?php
/**
 * قالب اصلی صفحه‌ی مدیریت.
 *
 * @package DailyBalePost
 *
 * @var array      $report گزارش سلامت.
 * @var array|null $notice اطلاعیه.
 * @var string     $tab    تب جاری.
 * @var DSB_Admin  $this   کنترلر پنل.
 */

defined( 'ABSPATH' ) || exit;

$dsb_endpoint = admin_url( 'admin-post.php' );
?>
<div class="wrap dsb-wrap">

	<?php if ( DSB_Branding::is_enabled() ) : ?>
		<?php require DSB_PATH . 'includes/views/partials/brand-header.php'; ?>
	<?php endif; ?>

	<div class="dsb-page-title">
		<span class="dashicons dashicons-share"></span>
		<h1><?php esc_html_e( 'انتشار خودکار در شبکه‌های اجتماعی', 'daily-bale-post' ); ?></h1>
	</div>
	<p class="dsb-sub">
		<?php esc_html_e( 'محتوای سایتت را خودکار در کانال‌های بله و تلگرام منتشر کن.', 'daily-bale-post' ); ?>
	</p>

	<?php if ( $notice ) : ?>
		<div class="dsb-notice dsb-notice-<?php echo esc_attr( $notice['type'] ); ?>">
			<div class="dsb-notice-icon"><?php echo 'success' === $notice['type'] ? '✅' : '❌'; ?></div>
			<div class="dsb-notice-body">
				<strong class="dsb-notice-title"><?php echo esc_html( $notice['title'] ); ?></strong>
				<p class="dsb-notice-msg"><?php echo esc_html( $notice['message'] ); ?></p>
				<?php if ( ! empty( $notice['details'] ) ) : ?>
					<details class="dsb-notice-details">
						<summary><?php esc_html_e( 'جزئیات', 'daily-bale-post' ); ?></summary>
						<pre dir="ltr"><?php echo esc_html( $notice['details'] ); ?></pre>
					</details>
				<?php endif; ?>
			</div>
			<button type="button" class="dsb-notice-close" aria-label="<?php esc_attr_e( 'بستن', 'daily-bale-post' ); ?>">×</button>
		</div>
	<?php endif; ?>

	<?php require DSB_PATH . 'includes/views/partials/health.php'; ?>

	<nav class="dsb-tabs">
		<?php foreach ( DSB_Admin::tabs() as $dsb_key => $dsb_label ) : ?>
			<a href="<?php echo esc_url( $this->tab_url( $dsb_key ) ); ?>"
				class="dsb-tab <?php echo $tab === $dsb_key ? 'is-active' : ''; ?>">
				<?php echo esc_html( $dsb_label ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<?php require DSB_PATH . 'includes/views/tabs/' . $tab . '.php'; ?>

	<div class="dsb-footer">
		<?php if ( DSB_Branding::is_enabled() ) : ?>
			<?php esc_html_e( 'توسعه و پشتیبانی:', 'daily-bale-post' ); ?>
			<?php if ( DSB_Branding::get( 'site' ) ) : ?>
				<a href="<?php echo esc_url( DSB_Branding::get( 'site' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( DSB_Branding::get( 'name' ) ); ?></a>
			<?php else : ?>
				<?php echo esc_html( DSB_Branding::get( 'name' ) ); ?>
			<?php endif; ?>
			<?php if ( DSB_Branding::get( 'phone' ) ) : ?>
				&nbsp;|&nbsp;
				<a href="tel:<?php echo esc_attr( DSB_Branding::get( 'phone' ) ); ?>" dir="ltr"><?php echo esc_html( DSB_Branding::get( 'phone' ) ); ?></a>
			<?php endif; ?>
			&nbsp;|&nbsp;
		<?php endif; ?>
		<span dir="ltr"><?php echo esc_html( 'v' . DSB_VERSION ); ?></span>
	</div>
</div>
