<?php
/**
 * تب مقصدها.
 *
 * @package Kanalyar
 *
 * @var string    $ky_endpoint نشانی admin-post.
 * @var KY_Admin $this         کنترلر پنل.
 */

defined( 'ABSPATH' ) || exit;

$ky_rows      = $this->destinations_list();
$ky_platforms = KY_Messenger::platforms();
?>

<?php KY_Help::render( 'destinations', ! $this->all_destinations() ); ?>

<form method="post" action="<?php echo esc_url( $ky_endpoint ); ?>">
	<?php wp_nonce_field( KY_Admin::NONCE ); ?>
	<input type="hidden" name="action" value="ky_handle">
	<input type="hidden" name="ky_tab" value="destinations">

	<div class="ky-card ky-card--connect">
		<h2><span class="ky-h2-icon">🔌</span> <?php esc_html_e( 'کانال‌های مقصد', 'kanalyar' ); ?></h2>

		<p class="description ky-intro">
			<?php esc_html_e( 'هر کانالی که می‌خواهید محتوا در آن منتشر شود را اینجا اضافه کنید. بعد در تب «قوانین انتشار» مشخص می‌کنید چه محتوایی به کدام کانال برود.', 'kanalyar' ); ?>
		</p>

		<div class="ky-repeat" data-repeat="destinations">
			<?php foreach ( $ky_rows as $ky_i => $ky_row ) : ?>
				<div class="ky-item" data-index="<?php echo esc_attr( $ky_i ); ?>">
					<div class="ky-item-head">
						<span class="ky-chip ky-chip--<?php echo esc_attr( $ky_row['platform'] ); ?>">
							<?php echo esc_html( $ky_platforms[ $ky_row['platform'] ] ?? $ky_row['platform'] ); ?>
						</span>
						<strong class="ky-item-title"><?php echo esc_html( $ky_row['label'] ? $ky_row['label'] : __( 'مقصد تازه', 'kanalyar' ) ); ?></strong>
						<label class="ky-switch">
							<input type="checkbox" name="ky[destinations][<?php echo esc_attr( $ky_i ); ?>][enabled]" value="1" <?php checked( $ky_row['enabled'], 1 ); ?>>
							<?php esc_html_e( 'فعال', 'kanalyar' ); ?>
						</label>
						<span class="ky-hint ky-hint--inline"><?php echo esc_html( KY_Help::field( 'destination', 'enabled' ) ); ?></span>
						<button type="button" class="button-link ky-remove" aria-label="<?php esc_attr_e( 'حذف این مقصد', 'kanalyar' ); ?>">🗑️ <?php esc_html_e( 'حذف', 'kanalyar' ); ?></button>
					</div>

					<input type="hidden" name="ky[destinations][<?php echo esc_attr( $ky_i ); ?>][id]" value="<?php echo esc_attr( $ky_row['id'] ); ?>">

					<div class="ky-grid">
						<label>
							<span><?php esc_html_e( 'پیام‌رسان', 'kanalyar' ); ?></span>
							<select name="ky[destinations][<?php echo esc_attr( $ky_i ); ?>][platform]">
								<?php foreach ( $ky_platforms as $ky_pk => $ky_pl ) : ?>
									<option value="<?php echo esc_attr( $ky_pk ); ?>" <?php selected( $ky_row['platform'], $ky_pk ); ?>><?php echo esc_html( $ky_pl ); ?></option>
								<?php endforeach; ?>
							</select>
						</label>

						<label>
							<span><?php esc_html_e( 'نام دلخواه', 'kanalyar' ); ?></span>
							<input type="text" name="ky[destinations][<?php echo esc_attr( $ky_i ); ?>][label]"
								value="<?php echo esc_attr( $ky_row['label'] ); ?>"
								placeholder="<?php esc_attr_e( 'مثلاً کانال فروشگاه', 'kanalyar' ); ?>">
						<?php KY_Help::hint( 'destination', 'label' ); ?>
						</label>

						<label>
							<span><?php esc_html_e( 'آیدی کانال', 'kanalyar' ); ?></span>
							<input type="text" name="ky[destinations][<?php echo esc_attr( $ky_i ); ?>][chat_id]"
								value="<?php echo esc_attr( $ky_row['chat_id'] ); ?>" dir="ltr" placeholder="@yourchannel">
						<?php KY_Help::hint( 'destination', 'chat_id' ); ?>
						</label>

						<label class="ky-grid-wide">
							<span><?php esc_html_e( 'توکن بات', 'kanalyar' ); ?></span>
							<input type="text" name="ky[destinations][<?php echo esc_attr( $ky_i ); ?>][token]"
								value="<?php echo esc_attr( $ky_row['token'] ); ?>" dir="ltr"
								autocomplete="off" placeholder="123456:ABC...">
						<?php KY_Help::hint( 'destination', 'token' ); ?>
						</label>
					</div>

					<?php if ( $ky_row['id'] ) : ?>
						<p class="ky-item-foot">
							<button type="submit" name="ky_action" value="test_destination" class="button button-small"
								formnovalidate
								onclick="this.form.ky_target.value='<?php echo esc_js( $ky_row['id'] ); ?>'">
								🔍 <?php esc_html_e( 'تست اتصال', 'kanalyar' ); ?>
							</button>
							<span class="description"><?php esc_html_e( 'توکن را بررسی می‌کند؛ پیامی به کانال نمی‌فرستد.', 'kanalyar' ); ?></span>
						</p>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>

		<input type="hidden" name="ky_target" value="">

		<p class="ky-actions">
			<button type="button" class="button ky-add" data-repeat-target="destinations">
				➕ <?php esc_html_e( 'افزودن مقصد', 'kanalyar' ); ?>
			</button>
			<button type="submit" name="ky_action" value="save_destinations" class="button button-primary">
				💾 <?php esc_html_e( 'ذخیره مقصدها', 'kanalyar' ); ?>
			</button>
		</p>
	</div>
</form>

<div class="ky-card">
	<h2><span class="ky-h2-icon">💡</span> <?php esc_html_e( 'راهنمای سریع', 'kanalyar' ); ?></h2>
	<div class="ky-help">
		<p>
			<strong><?php esc_html_e( 'بله:', 'kanalyar' ); ?></strong>
			<?php esc_html_e( 'در بله به @BotFather پیام بدهید، بات بسازید، توکن را کپی کنید و بات را ادمین کانال کنید.', 'kanalyar' ); ?>
		</p>
		<p>
			<strong><?php esc_html_e( 'تلگرام:', 'kanalyar' ); ?></strong>
			<?php esc_html_e( 'همین مراحل با @BotFather تلگرام. توجه: اگر سرور سایت در ایران باشد، معمولاً دسترسی مستقیم به سرورهای تلگرام بسته است و باید پراکسی سمت سرور تنظیم شود.', 'kanalyar' ); ?>
		</p>
		<p class="description">
			<?php esc_html_e( 'برای تنظیم پراکسی تلگرام، این فیلتر را در functions.php یا یک افزونه‌ی کوچک اضافه کنید:', 'kanalyar' ); ?>
		</p>
		<pre dir="ltr">add_filter( 'ky_telegram_endpoint', function () {
    return 'https://your-proxy.example/bot';
} );</pre>
	</div>
</div>
