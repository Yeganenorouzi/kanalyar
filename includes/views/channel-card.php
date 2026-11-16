<?php
/**
 * کارت تنظیمات یک کانال (بله یا تلگرام).
 *
 * @package DailyBalePost
 *
 * @var array $s     تنظیمات.
 * @var array $dsb_c پیکربندی کارت جاری.
 */

defined( 'ABSPATH' ) || exit;

$dsb_status   = $dsb_c['status'];
$dsb_on       = ! empty( $s[ $dsb_c['enabled_key'] ] );
$dsb_default  = DSB_Template::default_template();
$dsb_template = (string) $s[ $dsb_c['template_key'] ];
$dsb_prefix   = 'dsb_' . $dsb_c['platform'];

if ( ! empty( $dsb_status['ready'] ) ) {
	$dsb_badge = array( 'ok', '✅ ' . __( 'آماده‌ی ارسال', 'daily-bale-post' ) );
} elseif ( ! $dsb_on ) {
	$dsb_badge = array( 'off', '⏸️ ' . __( 'خاموش — هیچ پستی به این کانال نمی‌رود', 'daily-bale-post' ) );
} else {
	$dsb_badge = array( 'warn', '⚠️ ' . __( 'روشن ولی ناقص — توکن یا آیدی کانال خالی است', 'daily-bale-post' ) );
}
?>
<div class="dsb-card <?php echo esc_attr( $dsb_c['class'] ); ?> dsb-channel <?php echo $dsb_on ? '' : 'is-off'; ?>"
	data-channel="<?php echo esc_attr( $dsb_c['platform'] ); ?>">

	<h2>
		<span class="dsb-h2-icon"><?php echo esc_html( $dsb_c['icon'] ); ?></span>
		<?php
		printf(
			/* translators: %s: نام کانال. */
			esc_html__( 'اتصال به %s', 'daily-bale-post' ),
			esc_html( $dsb_c['label'] )
		);
		?>
		<span class="dsb-chan-badge dsb-chan-badge--<?php echo esc_attr( $dsb_badge[0] ); ?>"><?php echo esc_html( $dsb_badge[1] ); ?></span>
	</h2>

	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><label for="<?php echo esc_attr( $dsb_prefix . '_enabled' ); ?>"><?php esc_html_e( 'وضعیت کانال', 'daily-bale-post' ); ?></label></th>
			<td>
				<label>
					<input type="checkbox" class="dsb-channel-toggle" name="dsb[<?php echo esc_attr( $dsb_c['enabled_key'] ); ?>]"
						id="<?php echo esc_attr( $dsb_prefix . '_enabled' ); ?>" value="1" <?php checked( $dsb_on ); ?>>
					<?php
					printf(
						/* translators: %s: نام کانال. */
						esc_html__( 'ارسال به %s فعال باشد', 'daily-bale-post' ),
						esc_html( $dsb_c['label'] )
					);
					?>
				</label>
				<p class="description"><?php esc_html_e( 'با برداشتن این تیک، این کانال کامل از مدار خارج می‌شود: نه پست خودکار، نه ارسال تستی، نه هیچ درخواستی به سرورش.', 'daily-bale-post' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="<?php echo esc_attr( $dsb_prefix . '_token' ); ?>"><?php esc_html_e( 'توکن بات', 'daily-bale-post' ); ?></label></th>
			<td>
				<input type="text" name="dsb[<?php echo esc_attr( $dsb_c['token_key'] ); ?>]" id="<?php echo esc_attr( $dsb_prefix . '_token' ); ?>"
					value="<?php echo esc_attr( $s[ $dsb_c['token_key'] ] ); ?>" class="regular-text" dir="ltr" autocomplete="off" placeholder="123456:ABC...">
				<p class="description"><?php echo esc_html( $dsb_c['token_hint'] ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="<?php echo esc_attr( $dsb_prefix . '_channel' ); ?>"><?php esc_html_e( 'آیدی کانال', 'daily-bale-post' ); ?></label></th>
			<td>
				<input type="text" name="dsb[<?php echo esc_attr( $dsb_c['channel_key'] ); ?>]" id="<?php echo esc_attr( $dsb_prefix . '_channel' ); ?>"
					value="<?php echo esc_attr( $s[ $dsb_c['channel_key'] ] ); ?>" class="regular-text" dir="ltr" placeholder="@yourchannel">
				<p class="description"><?php echo esc_html( $dsb_c['channel_hint'] ); ?></p>
			</td>
		</tr>

		<?php if ( ! empty( $dsb_c['endpoint_key'] ) ) : ?>
			<tr>
				<th scope="row"><label for="<?php echo esc_attr( $dsb_prefix . '_endpoint' ); ?>"><?php esc_html_e( 'نشانی پراکسی API', 'daily-bale-post' ); ?></label></th>
				<td>
					<input type="text" name="dsb[<?php echo esc_attr( $dsb_c['endpoint_key'] ); ?>]" id="<?php echo esc_attr( $dsb_prefix . '_endpoint' ); ?>"
						value="<?php echo esc_attr( $s[ $dsb_c['endpoint_key'] ] ); ?>" class="regular-text" dir="ltr" placeholder="https://api.telegram.org/bot">
					<p class="description"><?php esc_html_e( 'خالی بگذارید تا از api.telegram.org استفاده شود. اگر سرور سایت در ایران است، این نشانی معمولاً بسته است و باید نشانی یک پراکسی Bot API را اینجا وارد کنید.', 'daily-bale-post' ); ?></p>
				</td>
			</tr>
		<?php endif; ?>

		<tr>
			<th scope="row"><label for="<?php echo esc_attr( $dsb_prefix . '_photo' ); ?>"><?php esc_html_e( 'ارسال تصویر', 'daily-bale-post' ); ?></label></th>
			<td>
				<select name="dsb[<?php echo esc_attr( $dsb_c['photo_key'] ); ?>]" id="<?php echo esc_attr( $dsb_prefix . '_photo' ); ?>">
					<?php foreach ( DSB_Settings::photo_modes() as $dsb_mode_key => $dsb_mode_label ) : ?>
						<option value="<?php echo esc_attr( $dsb_mode_key ); ?>" <?php selected( $s[ $dsb_c['photo_key'] ], $dsb_mode_key ); ?>>
							<?php echo esc_html( $dsb_mode_label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<p class="description"><?php esc_html_e( 'برای همین کانال. اگر پیام‌رسان نتواند تصویر را از سایت بگیرد (خطای failed to get HTTP URL content)، حالت خودکار فایل را مستقیم آپلود می‌کند.', 'daily-bale-post' ); ?></p>
			</td>
		</tr>

		<tr>
			<th scope="row"><label for="<?php echo esc_attr( $dsb_prefix . '_template' ); ?>"><?php esc_html_e( 'متن پیام', 'daily-bale-post' ); ?></label></th>
			<td>
				<textarea name="dsb[<?php echo esc_attr( $dsb_c['template_key'] ); ?>]" id="<?php echo esc_attr( $dsb_prefix . '_template' ); ?>"
					class="dsb-template" rows="9" dir="rtl"
					placeholder="<?php echo esc_attr( $dsb_default ); ?>"><?php echo esc_textarea( $dsb_template ); ?></textarea>

				<p class="description">
					<?php esc_html_e( 'متن پست همین کانال. خالی بگذارید تا قالب پیش‌فرض استفاده شود. برای درج هر شورت‌کد روی آن کلیک کنید:', 'daily-bale-post' ); ?>
				</p>

				<p class="dsb-chips">
					<?php foreach ( DSB_Template::placeholders() as $dsb_token => $dsb_token_label ) : ?>
						<button type="button" class="dsb-chip" data-token="{<?php echo esc_attr( $dsb_token ); ?>}"
							data-target="<?php echo esc_attr( $dsb_prefix . '_template' ); ?>"
							title="<?php echo esc_attr( $dsb_token_label ); ?>">
							<?php echo esc_html( '{' . $dsb_token . '}' ); ?>
						</button>
					<?php endforeach; ?>
				</p>

				<p class="dsb-template-actions">
					<button type="button" class="button button-small dsb-template-reset"
						data-target="<?php echo esc_attr( $dsb_prefix . '_template' ); ?>"
						data-default="<?php echo esc_attr( $dsb_default ); ?>">
						↩️ <?php esc_html_e( 'بازگرداندن متن پیش‌فرض', 'daily-bale-post' ); ?>
					</button>
					<button type="submit" class="button button-small" name="dsb_action" value="preview"
						formnovalidate onclick="document.getElementById('dsb_target_field').value='<?php echo esc_js( $dsb_c['platform'] ); ?>';">
						👁️ <?php esc_html_e( 'پیش‌نمایش متن', 'daily-bale-post' ); ?>
					</button>
				</p>

				<details class="dsb-template-help">
					<summary><?php esc_html_e( 'راهنمای شورت‌کدها و شرط‌ها', 'daily-bale-post' ); ?></summary>
					<ul>
						<?php foreach ( DSB_Template::placeholders() as $dsb_token => $dsb_token_label ) : ?>
							<li><code dir="ltr"><?php echo esc_html( '{' . $dsb_token . '}' ); ?></code> — <?php echo esc_html( $dsb_token_label ); ?></li>
						<?php endforeach; ?>
					</ul>
					<p class="description">
						<?php esc_html_e( 'خطی که فقط شورت‌کد داشته باشد و مقدارش خالی باشد (مثلاً قیمت برای یک نوشته) از پست حذف می‌شود.', 'daily-bale-post' ); ?>
					</p>
					<p class="description">
						<?php esc_html_e( 'برای متن شرطی از این ساختار استفاده کنید:', 'daily-bale-post' ); ?>
						<code dir="ltr">{if:discount}🔥 فقط امروز!{/if:discount}</code>
					</p>
					<p class="description">
						<?php esc_html_e( 'قالب‌بندی مارک‌داون: *پررنگ* و _کج_ . علامت‌های مارک‌داونِ داخل عنوان و متن محصول خودکار خنثی می‌شوند.', 'daily-bale-post' ); ?>
					</p>
				</details>
			</td>
		</tr>

		<tr>
			<th scope="row"><label for="<?php echo esc_attr( $dsb_prefix . '_button_text' ); ?>"><?php esc_html_e( 'دکمه‌ی زیر پیام', 'daily-bale-post' ); ?></label></th>
			<td>
				<label>
					<input type="checkbox" name="dsb[<?php echo esc_attr( $dsb_c['button_key'] ); ?>]" value="1" <?php checked( $s[ $dsb_c['button_key'] ], 1 ); ?>>
					<?php esc_html_e( 'دکمه‌ی شیشه‌ای مشاهده‌ی مطلب نمایش داده شود', 'daily-bale-post' ); ?>
				</label>
				<br><br>
				<input type="text" name="dsb[<?php echo esc_attr( $dsb_c['button_text_key'] ); ?>]" id="<?php echo esc_attr( $dsb_prefix . '_button_text' ); ?>"
					value="<?php echo esc_attr( $s[ $dsb_c['button_text_key'] ] ); ?>" class="regular-text"
					placeholder="<?php echo esc_attr( DSB_Template::default_button( true ) ); ?>">
				<p class="description"><?php esc_html_e( 'متن دکمه. خالی = خودکار («مشاهده محصول» برای محصول و «مشاهده مطلب» برای بقیه). شورت‌کدها اینجا هم کار می‌کنند.', 'daily-bale-post' ); ?></p>
			</td>
		</tr>
	</table>

	<?php if ( ! empty( $dsb_c['notes'] ) ) : ?>
		<div class="dsb-alert dsb-alert--info">
			<?php foreach ( (array) $dsb_c['notes'] as $dsb_note ) : ?>
				<p><?php echo esc_html( $dsb_note ); ?></p>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<p class="dsb-chan-actions">
		<button type="submit" class="button" name="dsb_action" value="check"
			onclick="document.getElementById('dsb_target_field').value='<?php echo esc_js( $dsb_c['platform'] ); ?>';">
			🔍 <?php esc_html_e( 'تست اتصال', 'daily-bale-post' ); ?>
		</button>
		<button type="submit" class="button" name="dsb_action" value="test"
			onclick="document.getElementById('dsb_target_field').value='<?php echo esc_js( $dsb_c['platform'] ); ?>';">
			🚀
			<?php
			printf(
				/* translators: %s: نام کانال. */
				esc_html__( 'ارسال تستی فقط به %s', 'daily-bale-post' ),
				esc_html( $dsb_c['label'] )
			);
			?>
		</button>
		<span class="description"><?php esc_html_e( 'قبل از اجرا، تنظیمات همین صفحه ذخیره می‌شود.', 'daily-bale-post' ); ?></span>
	</p>
</div>
