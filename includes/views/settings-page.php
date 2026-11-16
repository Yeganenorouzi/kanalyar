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

// نوع محتوای انتخاب‌شده، برای ساختن فهرست طبقه‌بندی‌ها.
$dsb_post_types     = DSB_Content_Selector::available_post_types();
$dsb_selected_types = array_values( array_filter( (array) $s['post_types'] ) );
$dsb_term_groups    = DSB_Content_Selector::available_terms(
	$dsb_selected_types ? $dsb_selected_types : array_keys( $dsb_post_types )
);
$dsb_selected_terms = (array) $s['terms'];

// وضعیت هر کانال، برای نشان‌دادن روی کارت خودش.
$dsb_chan_map = array();

foreach ( (array) $channels as $dsb_row ) {
	$dsb_chan_map[ $dsb_row['platform'] ] = $dsb_row;
}

$dsb_channel_cards = array(
	array(
		'platform'        => 'bale',
		'label'           => DSB_Messenger::platform_label( 'bale' ),
		'icon'            => '🔌',
		'class'           => 'dsb-card--connect',
		'enabled_key'     => 'bale_enabled',
		'token_key'       => 'token',
		'channel_key'     => 'channel',
		'template_key'    => 'bale_template',
		'photo_key'       => 'bale_photo_mode',
		'button_key'      => 'bale_button',
		'button_text_key' => 'bale_button_text',
		'endpoint_key'    => '',
		'token_hint'      => __( 'توکن را از @BotFather در بله بگیرید.', 'daily-bale-post' ),
		'channel_hint'    => __( 'یوزرنیم کانال بله با @ . بات باید ادمین کانال باشد.', 'daily-bale-post' ),
		'notes'           => array(),
		'status'          => $dsb_chan_map['bale'] ?? array(),
	),
	array(
		'platform'        => 'telegram',
		'label'           => DSB_Messenger::platform_label( 'telegram' ),
		'icon'            => '✈️',
		'class'           => 'dsb-card--telegram',
		'enabled_key'     => 'telegram_enabled',
		'token_key'       => 'telegram_token',
		'channel_key'     => 'telegram_channel',
		'template_key'    => 'telegram_template',
		'photo_key'       => 'telegram_photo_mode',
		'button_key'      => 'telegram_button',
		'button_text_key' => 'telegram_button_text',
		'endpoint_key'    => 'telegram_endpoint',
		'token_hint'      => __( 'توکن را از @BotFather در تلگرام بگیرید؛ توکن بله و تلگرام با هم فرق دارند.', 'daily-bale-post' ),
		'channel_hint'    => __( 'یوزرنیم کانال با @ ، یا برای کانال خصوصی آیدی عددی مثل -1001234567890. بات باید ادمین کانال باشد.', 'daily-bale-post' ),
		'notes'           => array(
			__( 'اگر در تاریخچه برای تلگرام خطای قطعی شبکه (cURL یا timeout) دیدید، یعنی سرور به api.telegram.org دسترسی ندارد؛ نشانی پراکسی را در فیلد بالا بگذارید. ارسال به بله مستقل از این موضوع کار می‌کند.', 'daily-bale-post' ),
		),
		'status'          => $dsb_chan_map['telegram'] ?? array(),
	),
);

$dsb_levels = array(
	'good' => array( '✅', __( 'سالم', 'daily-bale-post' ) ),
	'warn' => array( '⚠️', __( 'نیازمند بررسی', 'daily-bale-post' ) ),
	'bad'  => array( '⛔', __( 'ارسال متوقف شده', 'daily-bale-post' ) ),
	'off'  => array( '⏸️', __( 'غیرفعال', 'daily-bale-post' ) ),
);

$dsb_level = $dsb_levels[ $report['level'] ] ?? $dsb_levels['warn'];
?>
<div class="wrap dsb-wrap">

	<?php if ( DSB_Branding::is_enabled() ) : ?>
		<?php
		$dsb_brand_name = DSB_Branding::get( 'name' );
		$dsb_brand_logo = DSB_Branding::logo_url();
		$dsb_brand_site = DSB_Branding::get( 'site' );
		$dsb_brand_tel  = DSB_Branding::get( 'phone' );
		$dsb_brand_lbl  = DSB_Branding::get( 'label', __( 'افزونه‌ی ارسال روزانه به بله و تلگرام', 'daily-bale-post' ) );
		?>
		<div class="dsb-cta">
			<div class="dsb-cta-brand">
				<?php if ( $dsb_brand_logo ) : ?>
					<div class="dsb-cta-logo">
						<img src="<?php echo esc_url( $dsb_brand_logo ); ?>" alt="<?php echo esc_attr( $dsb_brand_name ); ?>">
					</div>
				<?php endif; ?>
				<div class="dsb-cta-text">
					<strong><?php echo esc_html( $dsb_brand_lbl ); ?></strong>
					<span>
						<?php
						printf(
							/* translators: %s: نام آژانس یا فروشنده. */
							esc_html__( 'طراحی و توسعه توسط %s', 'daily-bale-post' ),
							esc_html( $dsb_brand_name )
						);
						?>
					</span>
				</div>
			</div>
			<div class="dsb-cta-actions">
				<?php if ( $dsb_brand_tel ) : ?>
					<a href="tel:<?php echo esc_attr( $dsb_brand_tel ); ?>" dir="ltr">📞 <?php echo esc_html( $dsb_brand_tel ); ?></a>
				<?php endif; ?>
				<?php if ( $dsb_brand_site ) : ?>
					<a href="<?php echo esc_url( $dsb_brand_site ); ?>" target="_blank" rel="noopener noreferrer">
						🌐 <?php echo esc_html( wp_parse_url( $dsb_brand_site, PHP_URL_HOST ) ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>

	<div class="dsb-page-title">
		<span class="dashicons dashicons-share"></span>
		<h1><?php esc_html_e( 'ارسال روزانه محتوا به بله و تلگرام', 'daily-bale-post' ); ?></h1>
	</div>
	<p class="dsb-sub">
		<?php esc_html_e( 'هر روز یک محصول، نوشته یا مطلب سایت را خودکار در کانال بله و تلگرام منتشر کن.', 'daily-bale-post' ); ?>
	</p>

	<?php if ( $notice ) : ?>
		<div class="dsb-notice dsb-notice-<?php echo esc_attr( $notice['type'] ); ?>">
			<div class="dsb-notice-icon"><?php echo 'success' === $notice['type'] ? '✅' : '❌'; ?></div>
			<div class="dsb-notice-body">
				<strong class="dsb-notice-title"><?php echo esc_html( $notice['title'] ); ?></strong>
				<p class="dsb-notice-msg"><?php echo esc_html( $notice['message'] ); ?></p>
				<?php if ( ! empty( $notice['details'] ) ) : ?>
					<?php $dsb_dir = ( 'rtl' === ( $notice['dir'] ?? 'ltr' ) ) ? 'rtl' : 'ltr'; ?>
					<details class="dsb-notice-details" open>
						<summary>
							<?php
							echo 'rtl' === $dsb_dir
								? esc_html__( 'متن پیام', 'daily-bale-post' )
								: esc_html__( 'جزئیات فنی', 'daily-bale-post' );
							?>
						</summary>
						<pre dir="<?php echo esc_attr( $dsb_dir ); ?>"><?php echo esc_html( $notice['details'] ); ?></pre>
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

		<input type="hidden" name="dsb_target" id="dsb_target_field" value="">

		<?php // دکمه‌ی پیش‌فرضِ فرم: با زدن Enter داخل فیلدها، تنظیمات ذخیره می‌شود، نه اکشن‌های تست. ?>
		<button type="submit" name="dsb_action" value="save" class="dsb-default-submit" tabindex="-1" aria-hidden="true"
			onclick="document.getElementById('dsb_target_field').value='';"></button>

		<div class="dsb-card dsb-card--master">
			<h2><span class="dsb-h2-icon">🎛️</span> <?php esc_html_e( 'کلید اصلی و کانال‌ها', 'daily-bale-post' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="dsb_enabled"><?php esc_html_e( 'فعال باشد', 'daily-bale-post' ); ?></label></th>
					<td>
						<label>
							<input type="checkbox" name="dsb[enabled]" id="dsb_enabled" value="1" <?php checked( $s['enabled'], 1 ); ?>>
							<?php esc_html_e( 'ارسال خودکار روزانه فعال باشد', 'daily-bale-post' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'کلید اصلی افزونه. با برداشتن این تیک، هیچ پستی به هیچ کانالی نمی‌رود — حتی اگر کانال‌ها روشن باشند.', 'daily-bale-post' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'کانال‌ها', 'daily-bale-post' ); ?></th>
					<td>
						<ul class="dsb-chan-summary">
							<?php foreach ( (array) $channels as $dsb_chan ) : ?>
								<li>
									<strong><?php echo esc_html( $dsb_chan['label'] ); ?>:</strong>
									<?php if ( ! empty( $dsb_chan['ready'] ) ) : ?>
										<span class="dsb-ok"><?php esc_html_e( 'روشن و آماده', 'daily-bale-post' ); ?></span>
									<?php elseif ( empty( $dsb_chan['enabled'] ) ) : ?>
										<span class="dsb-dim"><?php esc_html_e( 'خاموش', 'daily-bale-post' ); ?></span>
									<?php else : ?>
										<span class="dsb-bad"><?php esc_html_e( 'روشن ولی توکن یا آیدی کانال ندارد', 'daily-bale-post' ); ?></span>
									<?php endif; ?>
								</li>
							<?php endforeach; ?>
						</ul>
						<p class="description"><?php esc_html_e( 'هر کانال تنظیمات، متن پیام و حالت تصویرِ خودش را دارد؛ خاموش‌کردن یکی به دیگری کاری ندارد.', 'daily-bale-post' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<?php
		foreach ( $dsb_channel_cards as $dsb_c ) {
			include DSB_PATH . 'includes/views/channel-card.php';
		}
		?>

		<div class="dsb-card dsb-card--content">
			<h2><span class="dsb-h2-icon">🛍️</span> <?php esc_html_e( 'محتوای پست', 'daily-bale-post' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="dsb_post_types"><?php esc_html_e( 'نوع محتوا', 'daily-bale-post' ); ?></label></th>
					<td>
						<?php if ( empty( $dsb_post_types ) ) : ?>
							<em><?php esc_html_e( 'هیچ نوع محتوایی پیدا نشد.', 'daily-bale-post' ); ?></em>
						<?php else : ?>
							<select name="dsb[post_types][]" id="dsb_post_types" multiple size="6" class="dsb-cats">
								<?php foreach ( $dsb_post_types as $dsb_type_slug => $dsb_type_label ) : ?>
									<option value="<?php echo esc_attr( $dsb_type_slug ); ?>" <?php selected( in_array( $dsb_type_slug, $dsb_selected_types, true ) ); ?>>
										<?php echo esc_html( $dsb_type_label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						<?php endif; ?>
						<p class="description">
							<?php esc_html_e( 'از کدام نوع محتوایی پست گرفته شود: محصول ووکامرس، نوشته، برگه یا هر نوع پست سفارشی. اگر چند مورد انتخاب کنید، هر روز از میان همه‌ی آن‌ها یکی تصادفی ارسال می‌شود.', 'daily-bale-post' ); ?>
						</p>
						<p class="description">
							<?php esc_html_e( 'نوع پست‌هایی که با جت‌انجین (JetEngine) یا هر افزونه‌ی دیگری ساخته شده باشند، خودشان در همین فهرست می‌آیند.', 'daily-bale-post' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="dsb_terms"><?php esc_html_e( 'دسته‌بندی و ترم', 'daily-bale-post' ); ?></label></th>
					<td>
						<?php if ( empty( $dsb_term_groups ) ) : ?>
							<em><?php esc_html_e( 'برای نوع محتوای انتخاب‌شده، طبقه‌بندی‌ای با ترم پیدا نشد.', 'daily-bale-post' ); ?></em>
						<?php else : ?>
							<select name="dsb[terms][]" id="dsb_terms" multiple size="8" class="dsb-cats">
								<?php foreach ( $dsb_term_groups as $dsb_tax => $dsb_group ) : ?>
									<optgroup label="<?php echo esc_attr( $dsb_group['label'] ); ?>">
										<?php foreach ( $dsb_group['terms'] as $dsb_term_id => $dsb_term_label ) : ?>
											<?php $dsb_value = $dsb_tax . ':' . (int) $dsb_term_id; ?>
											<option value="<?php echo esc_attr( $dsb_value ); ?>" <?php selected( in_array( $dsb_value, $dsb_selected_terms, true ) ); ?>>
												<?php echo esc_html( $dsb_term_label ); ?>
											</option>
										<?php endforeach; ?>
									</optgroup>
								<?php endforeach; ?>
							</select>
						<?php endif; ?>
						<p class="description">
							<?php esc_html_e( 'برای انتخاب چند مورد، کلید Ctrl (یا Cmd) را نگه دارید. اگر چیزی انتخاب نکنید، همه‌ی مطالبِ نوع محتوای انتخاب‌شده در گردش قرار می‌گیرند.', 'daily-bale-post' ); ?>
						</p>
						<p class="description">
							<?php esc_html_e( 'این فهرست بر اساس نوع محتوای ذخیره‌شده ساخته می‌شود؛ بعد از تغییر نوع محتوا یک بار ذخیره کنید تا طبقه‌بندی‌های تازه (از جمله طبقه‌بندی‌های جت‌انجین) اینجا بیایند.', 'daily-bale-post' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="dsb_phone"><?php esc_html_e( 'شماره تماس', 'daily-bale-post' ); ?></label></th>
					<td>
						<input type="text" name="dsb[phone]" id="dsb_phone" value="<?php echo esc_attr( $s['phone'] ); ?>"
							class="regular-text" dir="ltr" placeholder="09120000000">
						<p class="description"><?php esc_html_e( 'برای محصولات بدون قیمت در متن پست نمایش داده می‌شود. اختیاری.', 'daily-bale-post' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="dsb_avoid"><?php esc_html_e( 'جلوگیری از تکرار', 'daily-bale-post' ); ?></label></th>
					<td>
						<input type="number" name="dsb[avoid]" id="dsb_avoid" min="0" max="500" value="<?php echo esc_attr( $s['avoid'] ); ?>" class="small-text">
						<?php esc_html_e( 'مطلب اخیر', 'daily-bale-post' ); ?>
						<p class="description"><?php esc_html_e( 'این تعداد از آخرین مطالبِ پست‌شده دوباره انتخاب نمی‌شوند. عدد ۰ یعنی بدون محدودیت.', 'daily-bale-post' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'موجودی', 'daily-bale-post' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="dsb[instock]" value="1" <?php checked( $s['instock'], 1 ); ?>>
							<?php esc_html_e( 'فقط محصولات موجود پست شوند (ناموجودها رد می‌شوند)', 'daily-bale-post' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'فقط روی محصولات ووکامرس اثر دارد؛ نوشته‌ها و نوع پست‌های دیگر با این فیلتر حذف نمی‌شوند.', 'daily-bale-post' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'قیمت', 'daily-bale-post' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="dsb[show_price]" value="1" <?php checked( $s['show_price'], 1 ); ?>>
							<?php esc_html_e( 'قیمت محصول در پست نمایش داده شود', 'daily-bale-post' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'بخش قیمت فقط برای محصولات ووکامرس ساخته می‌شود؛ نوشته‌ها بدون قیمت ارسال می‌شوند.', 'daily-bale-post' ); ?></p>
						<p class="description"><?php esc_html_e( 'اگر غیرفعال کنید، در صورت وجود شماره تماس به‌جای قیمت «استعلام قیمت» نشان داده می‌شود.', 'daily-bale-post' ); ?></p>
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
			<button type="submit" name="dsb_action" value="test" class="button"
				onclick="document.getElementById('dsb_target_field').value='';">
				🚀 <?php esc_html_e( 'ارسال تستی به همه‌ی کانال‌های فعال', 'daily-bale-post' ); ?>
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
						<th><?php esc_html_e( 'مطلب', 'daily-bale-post' ); ?></th>
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
		<span dir="ltr"><?php echo esc_html( 'Daily Bale Post v' . DSB_VERSION ); ?></span>
	</div>
</div>
