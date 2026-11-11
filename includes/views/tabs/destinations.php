<?php
/**
 * تب مقصدها.
 *
 * @package DailyBalePost
 *
 * @var string    $dsb_endpoint نشانی admin-post.
 * @var DSB_Admin $this         کنترلر پنل.
 */

defined( 'ABSPATH' ) || exit;

$dsb_rows      = $this->destinations_list();
$dsb_platforms = DSB_Messenger::platforms();
?>

<form method="post" action="<?php echo esc_url( $dsb_endpoint ); ?>">
	<?php wp_nonce_field( DSB_Admin::NONCE ); ?>
	<input type="hidden" name="action" value="dsb_handle">
	<input type="hidden" name="dsb_tab" value="destinations">

	<div class="dsb-card dsb-card--connect">
		<h2><span class="dsb-h2-icon">🔌</span> <?php esc_html_e( 'کانال‌های مقصد', 'daily-bale-post' ); ?></h2>

		<p class="description dsb-intro">
			<?php esc_html_e( 'هر کانالی که می‌خواهید محتوا در آن منتشر شود را اینجا اضافه کنید. بات باید ادمین کانال باشد. بعد در تب «قوانین انتشار» مشخص می‌کنید چه محتوایی به کدام کانال برود.', 'daily-bale-post' ); ?>
		</p>

		<div class="dsb-repeat" data-repeat="destinations">
			<?php foreach ( $dsb_rows as $dsb_i => $dsb_row ) : ?>
				<div class="dsb-item" data-index="<?php echo esc_attr( $dsb_i ); ?>">
					<div class="dsb-item-head">
						<span class="dsb-chip dsb-chip--<?php echo esc_attr( $dsb_row['platform'] ); ?>">
							<?php echo esc_html( $dsb_platforms[ $dsb_row['platform'] ] ?? $dsb_row['platform'] ); ?>
						</span>
						<strong class="dsb-item-title"><?php echo esc_html( $dsb_row['label'] ? $dsb_row['label'] : __( 'مقصد تازه', 'daily-bale-post' ) ); ?></strong>
						<label class="dsb-switch">
							<input type="checkbox" name="dsb[destinations][<?php echo esc_attr( $dsb_i ); ?>][enabled]" value="1" <?php checked( $dsb_row['enabled'], 1 ); ?>>
							<?php esc_html_e( 'فعال', 'daily-bale-post' ); ?>
						</label>
						<button type="button" class="button-link dsb-remove" aria-label="<?php esc_attr_e( 'حذف این مقصد', 'daily-bale-post' ); ?>">🗑️ <?php esc_html_e( 'حذف', 'daily-bale-post' ); ?></button>
					</div>

					<input type="hidden" name="dsb[destinations][<?php echo esc_attr( $dsb_i ); ?>][id]" value="<?php echo esc_attr( $dsb_row['id'] ); ?>">

					<div class="dsb-grid">
						<label>
							<span><?php esc_html_e( 'پیام‌رسان', 'daily-bale-post' ); ?></span>
							<select name="dsb[destinations][<?php echo esc_attr( $dsb_i ); ?>][platform]">
								<?php foreach ( $dsb_platforms as $dsb_pk => $dsb_pl ) : ?>
									<option value="<?php echo esc_attr( $dsb_pk ); ?>" <?php selected( $dsb_row['platform'], $dsb_pk ); ?>><?php echo esc_html( $dsb_pl ); ?></option>
								<?php endforeach; ?>
							</select>
						</label>

						<label>
							<span><?php esc_html_e( 'نام دلخواه', 'daily-bale-post' ); ?></span>
							<input type="text" name="dsb[destinations][<?php echo esc_attr( $dsb_i ); ?>][label]"
								value="<?php echo esc_attr( $dsb_row['label'] ); ?>"
								placeholder="<?php esc_attr_e( 'مثلاً کانال فروشگاه', 'daily-bale-post' ); ?>">
						</label>

						<label>
							<span><?php esc_html_e( 'آیدی کانال', 'daily-bale-post' ); ?></span>
							<input type="text" name="dsb[destinations][<?php echo esc_attr( $dsb_i ); ?>][chat_id]"
								value="<?php echo esc_attr( $dsb_row['chat_id'] ); ?>" dir="ltr" placeholder="@yourchannel">
						</label>

						<label class="dsb-grid-wide">
							<span><?php esc_html_e( 'توکن بات', 'daily-bale-post' ); ?></span>
							<input type="text" name="dsb[destinations][<?php echo esc_attr( $dsb_i ); ?>][token]"
								value="<?php echo esc_attr( $dsb_row['token'] ); ?>" dir="ltr"
								autocomplete="off" placeholder="123456:ABC...">
						</label>
					</div>

					<?php if ( $dsb_row['id'] ) : ?>
						<p class="dsb-item-foot">
							<button type="submit" name="dsb_action" value="test_destination" class="button button-small"
								formnovalidate
								onclick="this.form.dsb_target.value='<?php echo esc_js( $dsb_row['id'] ); ?>'">
								🔍 <?php esc_html_e( 'تست اتصال', 'daily-bale-post' ); ?>
							</button>
							<span class="description"><?php esc_html_e( 'توکن را بررسی می‌کند؛ پیامی به کانال نمی‌فرستد.', 'daily-bale-post' ); ?></span>
						</p>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>

		<input type="hidden" name="dsb_target" value="">

		<p class="dsb-actions">
			<button type="button" class="button dsb-add" data-repeat-target="destinations">
				➕ <?php esc_html_e( 'افزودن مقصد', 'daily-bale-post' ); ?>
			</button>
			<button type="submit" name="dsb_action" value="save_destinations" class="button button-primary">
				💾 <?php esc_html_e( 'ذخیره مقصدها', 'daily-bale-post' ); ?>
			</button>
		</p>
	</div>
</form>

<div class="dsb-card">
	<h2><span class="dsb-h2-icon">💡</span> <?php esc_html_e( 'راهنمای سریع', 'daily-bale-post' ); ?></h2>
	<div class="dsb-help">
		<p>
			<strong><?php esc_html_e( 'بله:', 'daily-bale-post' ); ?></strong>
			<?php esc_html_e( 'در بله به @BotFather پیام بدهید، بات بسازید، توکن را کپی کنید و بات را ادمین کانال کنید.', 'daily-bale-post' ); ?>
		</p>
		<p>
			<strong><?php esc_html_e( 'تلگرام:', 'daily-bale-post' ); ?></strong>
			<?php esc_html_e( 'همین مراحل با @BotFather تلگرام. توجه: اگر سرور سایت در ایران باشد، معمولاً دسترسی مستقیم به سرورهای تلگرام بسته است و باید پراکسی سمت سرور تنظیم شود.', 'daily-bale-post' ); ?>
		</p>
		<p class="description">
			<?php esc_html_e( 'برای تنظیم پراکسی تلگرام، این فیلتر را در functions.php یا یک افزونه‌ی کوچک اضافه کنید:', 'daily-bale-post' ); ?>
		</p>
		<pre dir="ltr">add_filter( 'dsb_telegram_endpoint', function () {
    return 'https://your-proxy.example/bot';
} );</pre>
	</div>
</div>
