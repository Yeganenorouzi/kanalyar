<?php
/**
 * تب اعلان وضعیت سفارش.
 *
 * @package Kanalyar
 *
 * @var string   $ky_endpoint نشانی admin-post.
 * @var KY_Admin $this        کنترلر پنل.
 */

defined( 'ABSPATH' ) || exit;

$ky_orders       = $this->orders_config();
$ky_destinations = $this->all_destinations();
$ky_statuses     = KY_Orders::statuses();
$ky_has_wc       = function_exists( 'wc_get_order' );
?>

<?php if ( ! $ky_has_wc ) : ?>
	<div class="ky-alert">
		<p><strong><?php esc_html_e( 'ووکامرس فعال نیست.', 'kanalyar' ); ?></strong>
		<?php esc_html_e( 'اعلان وضعیت سفارش فقط با ووکامرس کار می‌کند.', 'kanalyar' ); ?></p>
	</div>
<?php endif; ?>

<?php if ( ! $ky_destinations ) : ?>
	<div class="ky-alert">
		<p>
			<strong><?php esc_html_e( 'هنوز مقصدی تعریف نکرده‌اید.', 'kanalyar' ); ?></strong>
			<?php esc_html_e( 'اول باید یک کانال اضافه کنید تا بتوانید اعلان‌ها را به آن بفرستید.', 'kanalyar' ); ?>
		</p>
		<p>
			<a href="<?php echo esc_url( $this->tab_url( 'destinations' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'افزودن مقصد', 'kanalyar' ); ?>
			</a>
		</p>
	</div>
<?php endif; ?>

<form method="post" action="<?php echo esc_url( $ky_endpoint ); ?>">
	<?php wp_nonce_field( KY_Admin::NONCE ); ?>
	<input type="hidden" name="action" value="ky_handle">
	<input type="hidden" name="ky_tab" value="orders">

	<div class="ky-card ky-card--orders">
		<h2><span class="ky-h2-icon">🧾</span> <?php esc_html_e( 'اعلان وضعیت سفارش', 'kanalyar' ); ?></h2>

		<p class="description ky-intro">
			<?php esc_html_e( 'با هر تغییر وضعیت سفارش، یک اعلان به کانال‌های انتخابی می‌رود. این اعلان‌ها برای مدیر فروشگاه هستند، نه مشتری.', 'kanalyar' ); ?>
		</p>

		<div class="ky-alert">
			<p>
				<strong><?php esc_html_e( 'کانال مقصد را خصوصی نگه دارید.', 'kanalyar' ); ?></strong>
				<?php esc_html_e( 'اعلان می‌تواند نام، تلفن و نشانی مشتری را شامل شود. اگر کانال عمومی باشد، این اطلاعات در دسترس همه قرار می‌گیرد. اگر مطمئن نیستید، گزینه‌ی «اطلاعات مشتری» را خاموش کنید.', 'kanalyar' ); ?>
			</p>
		</div>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'فعال باشد', 'kanalyar' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="ky[orders][enabled]" value="1" <?php checked( $ky_orders['enabled'], 1 ); ?>>
						<?php esc_html_e( 'اعلان وضعیت سفارش ارسال شود', 'kanalyar' ); ?>
					</label>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'کانال‌های مقصد', 'kanalyar' ); ?></th>
				<td>
					<div class="ky-dests">
						<?php if ( ! $ky_destinations ) : ?>
							<em class="ky-dim"><?php esc_html_e( 'ابتدا در تب مقصدها یک کانال اضافه کنید.', 'kanalyar' ); ?></em>
						<?php endif; ?>
						<?php foreach ( $ky_destinations as $ky_dest ) : ?>
							<label class="ky-dest-pick">
								<input type="checkbox" name="ky[orders][destinations][]"
									value="<?php echo esc_attr( $ky_dest['id'] ); ?>"
									<?php checked( in_array( $ky_dest['id'], (array) $ky_orders['destinations'], true ) ); ?>>
								<span class="ky-chip ky-chip--<?php echo esc_attr( $ky_dest['platform'] ); ?>">
									<?php echo esc_html( KY_Messenger::platforms()[ $ky_dest['platform'] ] ?? $ky_dest['platform'] ); ?>
								</span>
								<?php echo esc_html( $ky_dest['label'] ); ?>
							</label>
						<?php endforeach; ?>
					</div>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'روی چه وضعیت‌هایی', 'kanalyar' ); ?></th>
				<td class="ky-days">
					<?php foreach ( $ky_statuses as $ky_key => $ky_label ) : ?>
						<label>
							<input type="checkbox" name="ky[orders][statuses][]" value="<?php echo esc_attr( $ky_key ); ?>"
								<?php checked( in_array( $ky_key, (array) $ky_orders['statuses'], true ) ); ?>>
							<?php echo esc_html( $ky_label ); ?>
						</label>
					<?php endforeach; ?>
					<p class="description"><?php esc_html_e( 'هر سفارش برای هر وضعیت فقط یک‌بار اعلان می‌گیرد.', 'kanalyar' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'گزینه‌ها', 'kanalyar' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="ky[orders][customer_info]" value="1" <?php checked( $ky_orders['customer_info'], 1 ); ?>>
						<?php esc_html_e( 'اطلاعات مشتری (نام، تلفن، نشانی) در اعلان بیاید', 'kanalyar' ); ?>
					</label>
					<br>
					<label>
						<input type="checkbox" name="ky[orders][silent]" value="1" <?php checked( $ky_orders['silent'], 1 ); ?>>
						<?php esc_html_e( 'ارسال بی‌صدا (بدون اعلان روی گوشی)', 'kanalyar' ); ?>
					</label>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'قالب اعلان', 'kanalyar' ); ?></th>
				<td class="ky-template">
					<textarea name="ky[orders][template]" rows="10"
						placeholder="<?php echo esc_attr( KY_Orders::default_template() ); ?>"><?php echo esc_textarea( $ky_orders['template'] ); ?></textarea>
					<p class="description"><?php esc_html_e( 'خالی بگذارید تا قالب پیش‌فرض استفاده شود. جای‌نشان‌های موجود:', 'kanalyar' ); ?></p>
					<div class="ky-tokens">
						<?php foreach ( KY_Orders::placeholders() as $ky_token => $ky_desc ) : ?>
							<code class="ky-token" title="<?php echo esc_attr( $ky_desc ); ?>"><?php echo esc_html( $ky_token ); ?></code>
						<?php endforeach; ?>
					</div>
				</td>
			</tr>
		</table>

		<p class="ky-actions">
			<button type="submit" name="ky_action" value="save_orders" class="button button-primary">
				💾 <?php esc_html_e( 'ذخیره', 'kanalyar' ); ?>
			</button>
			<?php if ( $ky_has_wc && $this->orders_active() ) : ?>
				<button type="submit" name="ky_action" value="test_order" class="button" formnovalidate>
					🚀 <?php esc_html_e( 'اعلان تستی', 'kanalyar' ); ?>
				</button>
				<span class="description"><?php esc_html_e( 'بر اساس آخرین سفارش فروشگاه ساخته می‌شود.', 'kanalyar' ); ?></span>
			<?php endif; ?>
		</p>
	</div>
</form>
