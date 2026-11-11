<?php
/**
 * تب تنظیمات کلی.
 *
 * @package DailyBalePost
 *
 * @var string    $dsb_endpoint نشانی admin-post.
 * @var DSB_Admin $this         کنترلر پنل.
 */

defined( 'ABSPATH' ) || exit;

$s = $this->settings_all();
?>

<form method="post" action="<?php echo esc_url( $dsb_endpoint ); ?>">
	<?php wp_nonce_field( DSB_Admin::NONCE ); ?>
	<input type="hidden" name="action" value="dsb_handle">
	<input type="hidden" name="dsb_tab" value="settings">

	<div class="dsb-card dsb-card--content">
		<h2><span class="dsb-h2-icon">⚙️</span> <?php esc_html_e( 'تنظیمات کلی', 'daily-bale-post' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'کلید اصلی', 'daily-bale-post' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="dsb[settings][enabled]" value="1" <?php checked( $s['enabled'], 1 ); ?>>
						<?php esc_html_e( 'انتشار خودکار فعال باشد', 'daily-bale-post' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'با برداشتن این تیک، همه‌ی قوانین موقتاً متوقف می‌شوند.', 'daily-bale-post' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="dsb_phone"><?php esc_html_e( 'شماره تماس', 'daily-bale-post' ); ?></label></th>
				<td>
					<input type="text" name="dsb[settings][phone]" id="dsb_phone" value="<?php echo esc_attr( $s['phone'] ); ?>"
						class="regular-text" dir="ltr" placeholder="09120000000">
					<p class="description"><?php esc_html_e( 'برای محصولات بدون قیمت، به‌جای قیمت «استعلام قیمت» با این شماره نمایش داده می‌شود.', 'daily-bale-post' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="dsb_retries"><?php esc_html_e( 'تکرار درخواست شبکه', 'daily-bale-post' ); ?></label></th>
				<td>
					<input type="number" name="dsb[settings][retries]" id="dsb_retries" min="0" max="5" value="<?php echo esc_attr( $s['retries'] ); ?>" class="small-text">
					<p class="description"><?php esc_html_e( 'در صورت قطعی لحظه‌ای شبکه، هر درخواست تا این تعداد دوباره فرستاده می‌شود.', 'daily-bale-post' ); ?></p>
				</td>
			</tr>
		</table>
	</div>

	<div class="dsb-card dsb-card--alerts">
		<h2><span class="dsb-h2-icon">🔔</span> <?php esc_html_e( 'هشدار خرابی', 'daily-bale-post' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'ایمیل هشدار', 'daily-bale-post' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="dsb[settings][alerts]" value="1" <?php checked( $s['alerts'], 1 ); ?>>
						<?php esc_html_e( 'اگر دو روز هیچ پستی ارسال نشد، ایمیل هشدار بفرست', 'daily-bale-post' ); ?>
					</label>
					<br><br>
					<input type="email" name="dsb[settings][alert_email]" value="<?php echo esc_attr( $s['alert_email'] ); ?>"
						class="regular-text" dir="ltr" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>">
					<p class="description"><?php esc_html_e( 'خالی بگذارید تا به ایمیل مدیر سایت برود. حداکثر روزی یک ایمیل.', 'daily-bale-post' ); ?></p>
				</td>
			</tr>
		</table>
	</div>

	<p class="dsb-actions">
		<button type="submit" name="dsb_action" value="save_settings" class="button button-primary">
			💾 <?php esc_html_e( 'ذخیره تنظیمات', 'daily-bale-post' ); ?>
		</button>
	</p>
</form>
