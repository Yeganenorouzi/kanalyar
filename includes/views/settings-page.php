<?php
/**
 * نمای صفحه‌ی تنظیمات.
 *
 * @package DailyBalePost
 *
 * @var array        $s      تنظیمات.
 * @var array|null   $notice اطلاعیه.
 * @var array        $report گزارش سلامت.
 * @var DSB_Admin    $this   کنترلر پنل.
 */

defined( 'ABSPATH' ) || exit;

$dsb_endpoint = admin_url( 'admin-post.php' );
$dsb_next_run = $this->scheduler->next_run();
$dsb_today    = $this->scheduler->today();
$dsb_weekdays = DSB_Admin::weekdays();

$dsb_levels = array(
	'good' => array( '✅', __( 'سالم', 'daily-bale-post' ) ),
	'warn' => array( '⚠️', __( 'نیازمند بررسی', 'daily-bale-post' ) ),
	'bad'  => array( '⛔', __( 'ارسال متوقف شده', 'daily-bale-post' ) ),
	'off'  => array( '⏸️', __( 'غیرفعال', 'daily-bale-post' ) ),
);

$dsb_level = $dsb_levels[ $report['level'] ] ?? $dsb_levels['warn'];
?>
<div class="wrap dsb-wrap">

	<div class="dsb-cta">
		<div class="dsb-cta-brand">
			<div class="dsb-cta-logo">
				<img src="<?php echo esc_url( DSB_URL . 'assets/optisun-logo.svg' ); ?>" alt="<?php echo esc_attr( DSB_Admin::AGENCY_NAME ); ?>">
			</div>
			<div class="dsb-cta-text">
				<strong><?php esc_html_e( 'افزونه‌ی ارسال روزانه به بله', 'daily-bale-post' ); ?></strong>
				<span>
					<?php
					printf(
						/* translators: %s: نام آژانس. */
						esc_html__( 'طراحی و توسعه توسط %s', 'daily-bale-post' ),
						esc_html( DSB_Admin::AGENCY_NAME )
					);
					?>
				</span>
			</div>
		</div>
		<div class="dsb-cta-actions">
			<a href="tel:<?php echo esc_attr( DSB_Admin::AGENCY_PHONE ); ?>" dir="ltr">📞 <?php echo esc_html( DSB_Admin::AGENCY_PHONE ); ?></a>
			<a href="<?php echo esc_url( DSB_Admin::AGENCY_SITE ); ?>" target="_blank" rel="noopener noreferrer">🌐 optisun.ir</a>
		</div>
	</div>

	<div class="dsb-page-title">
		<span class="dashicons dashicons-share"></span>
		<h1><?php esc_html_e( 'ارسال روزانه محصول به بله', 'daily-bale-post' ); ?></h1>
	</div>
	<p class="dsb-sub">
		<?php
		printf(
			/* translators: %s: نام آژانس. */
			esc_html__( 'یکی از ابزارهای اختصاصی %s برای فروشگاه‌های ووکامرسی', 'daily-bale-post' ),
			esc_html( DSB_Admin::AGENCY_NAME )
		);
		?>
	</p>

	<?php if ( $notice ) : ?>
		<div class="dsb-notice dsb-notice-<?php echo esc_attr( $notice['type'] ); ?>">
			<div class="dsb-notice-icon"><?php echo 'success' === $notice['type'] ? '✅' : '❌'; ?></div>
			<div class="dsb-notice-body">
				<strong class="dsb-notice-title"><?php echo esc_html( $notice['title'] ); ?></strong>
				<p class="dsb-notice-msg"><?php echo esc_html( $notice['message'] ); ?></p>
				<?php if ( ! empty( $notice['details'] ) ) : ?>
					<details class="dsb-notice-details">
						<summary><?php esc_html_e( 'جزئیات فنی', 'daily-bale-post' ); ?></summary>
						<pre dir="ltr"><?php echo esc_html( $notice['details'] ); ?></pre>
					</details>
				<?php endif; ?>
			</div>
			<button type="button" class="dsb-notice-close" aria-label="<?php esc_attr_e( 'بستن', 'daily-bale-post' ); ?>">×</button>
		</div>
	<?php endif; ?>

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
			<li>
				<strong><?php esc_html_e( 'ارسال بعدی:', 'daily-bale-post' ); ?></strong>
				<span dir="ltr">
					<?php
					echo $dsb_next_run
						? esc_html( $dsb_next_run->format( 'Y-m-d H:i' ) . ' (Tehran)' )
						: esc_html__( 'زمان‌بندی فعال نیست', 'daily-bale-post' );
					?>
				</span>
			</li>
			<li>
				<strong><?php esc_html_e( 'وضعیت امروز:', 'daily-bale-post' ); ?></strong>
				<?php if ( ! empty( $dsb_today['done'] ) ) : ?>
					<span class="dsb-ok"><?php esc_html_e( 'ارسال شد', 'daily-bale-post' ); ?></span>
				<?php else : ?>
					<span class="dsb-dim">
						<?php
						printf(
							/* translators: %d: تعداد تلاش. */
							esc_html__( 'هنوز ارسال نشده — %d تلاش تا این لحظه', 'daily-bale-post' ),
							(int) $dsb_today['attempts']
						);
						?>
					</span>
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
			<button type="submit" name="dsb_action" value="repair" class="button">
				🔧 <?php esc_html_e( 'بازسازی زمان‌بندی', 'daily-bale-post' ); ?>
			</button>
		</form>
	</div>

	<form method="post" action="<?php echo esc_url( $dsb_endpoint ); ?>">
		<?php wp_nonce_field( DSB_Admin::NONCE ); ?>
		<input type="hidden" name="action" value="dsb_handle">

		<div class="dsb-card dsb-card--connect">
			<h2><span class="dsb-h2-icon">🔌</span> <?php esc_html_e( 'اتصال به بله', 'daily-bale-post' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="dsb_enabled"><?php esc_html_e( 'فعال باشد', 'daily-bale-post' ); ?></label></th>
					<td>
						<label>
							<input type="checkbox" name="dsb[enabled]" id="dsb_enabled" value="1" <?php checked( $s['enabled'], 1 ); ?>>
							<?php esc_html_e( 'ارسال خودکار روزانه فعال باشد', 'daily-bale-post' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="dsb_token"><?php esc_html_e( 'توکن بات بله', 'daily-bale-post' ); ?></label></th>
					<td>
						<input type="text" name="dsb[token]" id="dsb_token" value="<?php echo esc_attr( $s['token'] ); ?>"
							class="regular-text" dir="ltr" autocomplete="off" placeholder="123456:ABC...">
						<p class="description"><?php esc_html_e( 'توکن را از @BotFather در بله بگیرید.', 'daily-bale-post' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="dsb_channel"><?php esc_html_e( 'آیدی کانال', 'daily-bale-post' ); ?></label></th>
					<td>
						<input type="text" name="dsb[channel]" id="dsb_channel" value="<?php echo esc_attr( $s['channel'] ); ?>"
							class="regular-text" dir="ltr" placeholder="@yourchannel">
						<p class="description"><?php esc_html_e( 'یوزرنیم کانال با @ . بات باید ادمین کانال باشد.', 'daily-bale-post' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="dsb_phone"><?php esc_html_e( 'شماره تماس', 'daily-bale-post' ); ?></label></th>
					<td>
						<input type="text" name="dsb[phone]" id="dsb_phone" value="<?php echo esc_attr( $s['phone'] ); ?>"
							class="regular-text" dir="ltr" placeholder="09120000000">
						<p class="description"><?php esc_html_e( 'برای محصولات بدون قیمت نمایش داده می‌شود. اختیاری.', 'daily-bale-post' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<div class="dsb-card dsb-card--content">
			<h2><span class="dsb-h2-icon">🛍️</span> <?php esc_html_e( 'محتوای پست', 'daily-bale-post' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="dsb_categories"><?php esc_html_e( 'دسته‌بندی محصولات', 'daily-bale-post' ); ?></label></th>
					<td>
						<?php
						$dsb_terms = taxonomy_exists( 'product_cat' )
							? get_terms(
								array(
									'taxonomy'   => 'product_cat',
									'hide_empty' => false,
								)
							)
							: array();

						if ( is_wp_error( $dsb_terms ) || empty( $dsb_terms ) ) {
							echo '<em>' . esc_html__( 'دسته‌بندی محصولی پیدا نشد (ووکامرس فعال است؟)', 'daily-bale-post' ) . '</em>';
						} else {
							echo '<select name="dsb[categories][]" id="dsb_categories" multiple size="6" class="dsb-cats">';
							foreach ( $dsb_terms as $dsb_term ) {
								printf(
									'<option value="%1$d" %2$s>%3$s (%4$d)</option>',
									(int) $dsb_term->term_id,
									selected( in_array( (int) $dsb_term->term_id, (array) $s['categories'], true ), true, false ),
									esc_html( $dsb_term->name ),
									(int) $dsb_term->count
								);
							}
							echo '</select>';
						}
						?>
						<p class="description">
							<?php esc_html_e( 'برای انتخاب چند دسته، کلید Ctrl (یا Cmd) را نگه دارید. اگر چیزی انتخاب نکنید، از همه‌ی محصولات انتخاب می‌شود.', 'daily-bale-post' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="dsb_avoid"><?php esc_html_e( 'جلوگیری از تکرار', 'daily-bale-post' ); ?></label></th>
					<td>
						<input type="number" name="dsb[avoid]" id="dsb_avoid" min="0" max="500" value="<?php echo esc_attr( $s['avoid'] ); ?>" class="small-text">
						<?php esc_html_e( 'محصول اخیر', 'daily-bale-post' ); ?>
						<p class="description"><?php esc_html_e( 'این تعداد از آخرین محصولاتِ پست‌شده دوباره انتخاب نمی‌شوند. عدد ۰ یعنی بدون محدودیت.', 'daily-bale-post' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'موجودی', 'daily-bale-post' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="dsb[instock]" value="1" <?php checked( $s['instock'], 1 ); ?>>
							<?php esc_html_e( 'فقط محصولات موجود پست شوند (ناموجودها رد می‌شوند)', 'daily-bale-post' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'قیمت', 'daily-bale-post' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="dsb[show_price]" value="1" <?php checked( $s['show_price'], 1 ); ?>>
							<?php esc_html_e( 'قیمت محصول در پست نمایش داده شود', 'daily-bale-post' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'اگر غیرفعال کنید، در صورت وجود شماره تماس به‌جای قیمت «استعلام قیمت» نشان داده می‌شود.', 'daily-bale-post' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="dsb_photo_mode"><?php esc_html_e( 'ارسال تصویر', 'daily-bale-post' ); ?></label></th>
					<td>
						<select name="dsb[photo_mode]" id="dsb_photo_mode">
							<?php foreach ( DSB_Settings::photo_modes() as $dsb_mode_key => $dsb_mode_label ) : ?>
								<option value="<?php echo esc_attr( $dsb_mode_key ); ?>" <?php selected( $s['photo_mode'], $dsb_mode_key ); ?>>
									<?php echo esc_html( $dsb_mode_label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'اگر بله نتواند تصویر را از سایت بگیرد (خطای failed to get HTTP URL content)، حالت خودکار فایل را مستقیم آپلود می‌کند.', 'daily-bale-post' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<div class="dsb-card dsb-card--schedule">
			<h2><span class="dsb-h2-icon">⏰</span> <?php esc_html_e( 'زمان‌بندی ارسال', 'daily-bale-post' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'روزهای ارسال', 'daily-bale-post' ); ?></th>
					<td class="dsb-days">
						<?php foreach ( $dsb_weekdays as $dsb_num => $dsb_label ) : ?>
							<label>
								<input type="checkbox" name="dsb[days][]" value="<?php echo esc_attr( $dsb_num ); ?>"
									<?php checked( in_array( (int) $dsb_num, (array) $s['days'], true ) ); ?>>
								<?php echo esc_html( $dsb_label ); ?>
							</label>
						<?php endforeach; ?>
						<p class="description"><?php esc_html_e( 'فقط در روزهای تیک‌خورده پست ارسال می‌شود.', 'daily-bale-post' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'ساعت ارسال (به وقت تهران)', 'daily-bale-post' ); ?></th>
					<td>
						<input type="number" name="dsb[hour]" min="0" max="23" value="<?php echo esc_attr( $s['hour'] ); ?>" class="small-text">
						:
						<input type="number" name="dsb[minute]" min="0" max="59" value="<?php echo esc_attr( $s['minute'] ); ?>" class="small-text">
						<p class="description"><?php esc_html_e( 'پست در اولین بررسی بعد از این ساعت ارسال می‌شود.', 'daily-bale-post' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="dsb_max_attempts"><?php esc_html_e( 'تلاش مجدد', 'daily-bale-post' ); ?></label></th>
					<td>
						<input type="number" name="dsb[max_attempts]" id="dsb_max_attempts" min="1" max="10" value="<?php echo esc_attr( $s['max_attempts'] ); ?>" class="small-text">
						<?php esc_html_e( 'تلاش در روز', 'daily-bale-post' ); ?>
						&nbsp;|&nbsp;
						<input type="number" name="dsb[retries]" min="0" max="5" value="<?php echo esc_attr( $s['retries'] ); ?>" class="small-text">
						<?php esc_html_e( 'تکرار درخواست شبکه', 'daily-bale-post' ); ?>
						<p class="description"><?php esc_html_e( 'اگر ارسال شکست بخورد، با فاصله‌ی ۱۵ دقیقه تا این تعداد دوباره تلاش می‌شود.', 'daily-bale-post' ); ?></p>
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
							<input type="checkbox" name="dsb[alerts]" value="1" <?php checked( $s['alerts'], 1 ); ?>>
							<?php esc_html_e( 'اگر دو روز پستی ارسال نشد، ایمیل هشدار بفرست', 'daily-bale-post' ); ?>
						</label>
						<br><br>
						<input type="email" name="dsb[alert_email]" value="<?php echo esc_attr( $s['alert_email'] ); ?>"
							class="regular-text" dir="ltr" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>">
						<p class="description"><?php esc_html_e( 'خالی بگذارید تا به ایمیل مدیر سایت ارسال شود. حداکثر روزی یک ایمیل.', 'daily-bale-post' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<p class="dsb-actions">
			<button type="submit" name="dsb_action" value="save" class="button button-primary">
				💾 <?php esc_html_e( 'ذخیره تنظیمات', 'daily-bale-post' ); ?>
			</button>
			<button type="submit" name="dsb_action" value="test" class="button">
				🚀 <?php esc_html_e( 'ارسال تستی الان', 'daily-bale-post' ); ?>
			</button>
			<span class="description"><?php esc_html_e( 'ارسال تستی جای پست خودکار امروز را نمی‌گیرد.', 'daily-bale-post' ); ?></span>
		</p>
	</form>

	<div class="dsb-card dsb-card--history">
		<h2><span class="dsb-h2-icon">🗂️</span> <?php esc_html_e( 'تاریخچه‌ی ارسال', 'daily-bale-post' ); ?></h2>
		<?php $dsb_log = $this->logger->recent(); ?>
		<?php if ( empty( $dsb_log ) ) : ?>
			<p class="description"><?php esc_html_e( 'هنوز ارسالی ثبت نشده است.', 'daily-bale-post' ); ?></p>
		<?php else : ?>
			<table class="widefat striped dsb-log">
				<thead>
					<tr>
						<th><?php esc_html_e( 'زمان', 'daily-bale-post' ); ?></th>
						<th><?php esc_html_e( 'محصول', 'daily-bale-post' ); ?></th>
						<th><?php esc_html_e( 'منبع', 'daily-bale-post' ); ?></th>
						<th><?php esc_html_e( 'وضعیت', 'daily-bale-post' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $dsb_log as $dsb_row ) : ?>
					<?php
					$dsb_state  = $dsb_row['state'] ?? ( empty( $dsb_row['ok'] ) ? 'fail' : 'ok' );
					$dsb_source = 'manual' === ( $dsb_row['source'] ?? 'auto' )
						? __( 'دستی', 'daily-bale-post' )
						: __( 'خودکار', 'daily-bale-post' );
					$dsb_icon   = 'ok' === $dsb_state ? '✅' : ( 'skip' === $dsb_state ? '⏭️' : '❌' );
					?>
					<tr>
						<td dir="ltr"><?php echo esc_html( $this->local_time( $dsb_row['time'] ) ); ?></td>
						<td><?php echo esc_html( $dsb_row['name'] ); ?></td>
						<td><?php echo esc_html( $dsb_source ); ?></td>
						<td class="dsb-state-<?php echo esc_attr( $dsb_state ); ?>">
							<?php echo esc_html( $dsb_icon . ' ' . $dsb_row['msg'] ); ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<p class="description">
				<?php
				printf(
					/* translators: %d: تعداد ردیف. */
					esc_html__( '%d مورد آخر نمایش داده می‌شود.', 'daily-bale-post' ),
					(int) DSB_Logger::LIMIT
				);
				?>
			</p>
		<?php endif; ?>
	</div>

	<div class="dsb-footer">
		<?php esc_html_e( 'توسعه و پشتیبانی:', 'daily-bale-post' ); ?>
		<a href="<?php echo esc_url( DSB_Admin::AGENCY_SITE ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( DSB_Admin::AGENCY_NAME ); ?></a>
		&nbsp;|&nbsp;
		<a href="tel:<?php echo esc_attr( DSB_Admin::AGENCY_PHONE ); ?>" dir="ltr"><?php echo esc_html( DSB_Admin::AGENCY_PHONE ); ?></a>
		&nbsp;|&nbsp;
		<span dir="ltr"><?php echo esc_html( 'v' . DSB_VERSION ); ?></span>
	</div>
</div>
