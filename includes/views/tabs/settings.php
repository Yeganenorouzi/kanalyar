<?php
/**
 * تب تنظیمات کلی.
 *
 * @package Kanalyar
 *
 * @var string    $ky_endpoint نشانی admin-post.
 * @var KY_Admin $this         کنترلر پنل.
 */

defined( 'ABSPATH' ) || exit;

$s = $this->settings_all();
?>

<?php KY_Help::render( 'settings' ); ?>

<form method="post" action="<?php echo esc_url( $ky_endpoint ); ?>">
	<?php wp_nonce_field( KY_Admin::NONCE ); ?>
	<input type="hidden" name="action" value="ky_handle">
	<input type="hidden" name="ky_tab" value="settings">

	<div class="ky-card ky-card--content">
		<h2><span class="ky-h2-icon">⚙️</span> <?php esc_html_e( 'تنظیمات کلی', 'kanalyar' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'کلید اصلی', 'kanalyar' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="ky[settings][enabled]" value="1" <?php checked( $s['enabled'], 1 ); ?>>
						<?php esc_html_e( 'انتشار خودکار فعال باشد', 'kanalyar' ); ?>
					</label>
					<p class="description"><?php KY_Help::hint( 'settings', 'enabled', 'span' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="ky_phone"><?php esc_html_e( 'شماره تماس', 'kanalyar' ); ?></label></th>
				<td>
					<input type="text" name="ky[settings][phone]" id="ky_phone" value="<?php echo esc_attr( $s['phone'] ); ?>"
						class="regular-text" dir="ltr" placeholder="09120000000">
					<p class="description"><?php KY_Help::hint( 'settings', 'phone', 'span' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="ky_retries"><?php esc_html_e( 'تکرار درخواست شبکه', 'kanalyar' ); ?></label></th>
				<td>
					<input type="number" name="ky[settings][retries]" id="ky_retries" min="0" max="5" value="<?php echo esc_attr( $s['retries'] ); ?>" class="small-text">
					<p class="description"><?php KY_Help::hint( 'settings', 'retries', 'span' ); ?></p>
				</td>
			</tr>
		</table>
	</div>

	<div class="ky-card ky-card--alerts">
		<h2><span class="ky-h2-icon">🔔</span> <?php esc_html_e( 'هشدار خرابی', 'kanalyar' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'ایمیل هشدار', 'kanalyar' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="ky[settings][alerts]" value="1" <?php checked( $s['alerts'], 1 ); ?>>
						<?php esc_html_e( 'اگر دو روز هیچ پستی ارسال نشد، ایمیل هشدار بفرست', 'kanalyar' ); ?>
					</label>
					<?php KY_Help::hint( 'settings', 'alerts' ); ?>
					<br><br>
					<input type="email" name="ky[settings][alert_email]" value="<?php echo esc_attr( $s['alert_email'] ); ?>"
						class="regular-text" dir="ltr" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>">
					<p class="description"><?php KY_Help::hint( 'settings', 'alert_email', 'span' ); ?></p>
				</td>
			</tr>
		</table>
	</div>

	<p class="ky-actions">
		<button type="submit" name="ky_action" value="save_settings" class="button button-primary">
			💾 <?php esc_html_e( 'ذخیره تنظیمات', 'kanalyar' ); ?>
		</button>
	</p>
</form>
