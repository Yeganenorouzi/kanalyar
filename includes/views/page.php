<?php
/**
 * قالب اصلی صفحه‌ی مدیریت.
 *
 * @package Kanalyar
 *
 * @var array      $report گزارش سلامت.
 * @var array|null $notice اطلاعیه.
 * @var string     $tab    تب جاری.
 * @var KY_Admin  $this   کنترلر پنل.
 */

defined( 'ABSPATH' ) || exit;

$ky_endpoint = admin_url( 'admin-post.php' );
?>
<div class="wrap ky-wrap">

	<?php if ( KY_Branding::is_enabled() ) : ?>
		<?php require KY_PATH . 'includes/views/partials/brand-header.php'; ?>
	<?php endif; ?>

	<div class="ky-page-title">
		<span class="dashicons dashicons-share"></span>
		<h1><?php esc_html_e( 'انتشار خودکار در شبکه‌های اجتماعی', 'kanalyar' ); ?></h1>
	</div>
	<p class="ky-sub">
		<?php esc_html_e( 'محتوای سایتت را خودکار در کانال‌های بله و تلگرام منتشر کن.', 'kanalyar' ); ?>
	</p>

	<?php if ( $notice ) : ?>
		<div class="ky-notice ky-notice-<?php echo esc_attr( $notice['type'] ); ?>">
			<div class="ky-notice-icon"><?php echo 'success' === $notice['type'] ? '✅' : '❌'; ?></div>
			<div class="ky-notice-body">
				<strong class="ky-notice-title"><?php echo esc_html( $notice['title'] ); ?></strong>
				<p class="ky-notice-msg"><?php echo esc_html( $notice['message'] ); ?></p>
				<?php if ( ! empty( $notice['details'] ) ) : ?>
					<details class="ky-notice-details">
						<summary><?php esc_html_e( 'جزئیات', 'kanalyar' ); ?></summary>
						<pre dir="ltr"><?php echo esc_html( $notice['details'] ); ?></pre>
					</details>
				<?php endif; ?>
			</div>
			<button type="button" class="ky-notice-close" aria-label="<?php esc_attr_e( 'بستن', 'kanalyar' ); ?>">×</button>
		</div>
	<?php endif; ?>

	<?php require KY_PATH . 'includes/views/partials/health.php'; ?>

	<nav class="ky-tabs">
		<?php foreach ( KY_Admin::tabs() as $ky_key => $ky_label ) : ?>
			<a href="<?php echo esc_url( $this->tab_url( $ky_key ) ); ?>"
				class="ky-tab <?php echo $tab === $ky_key ? 'is-active' : ''; ?>">
				<?php echo esc_html( $ky_label ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<?php require KY_PATH . 'includes/views/tabs/' . $tab . '.php'; ?>

	<div class="ky-footer">
		<?php if ( KY_Branding::is_enabled() ) : ?>
			<?php esc_html_e( 'توسعه و پشتیبانی:', 'kanalyar' ); ?>
			<?php if ( KY_Branding::get( 'site' ) ) : ?>
				<a href="<?php echo esc_url( KY_Branding::get( 'site' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( KY_Branding::get( 'name' ) ); ?></a>
			<?php else : ?>
				<?php echo esc_html( KY_Branding::get( 'name' ) ); ?>
			<?php endif; ?>
			<?php if ( KY_Branding::get( 'phone' ) ) : ?>
				&nbsp;|&nbsp;
				<a href="tel:<?php echo esc_attr( KY_Branding::get( 'phone' ) ); ?>" dir="ltr"><?php echo esc_html( KY_Branding::get( 'phone' ) ); ?></a>
			<?php endif; ?>
			&nbsp;|&nbsp;
		<?php endif; ?>
		<span dir="ltr"><?php echo esc_html( 'v' . KY_VERSION ); ?></span>
	</div>
</div>
