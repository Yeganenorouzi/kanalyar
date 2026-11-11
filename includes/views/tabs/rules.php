<?php
/**
 * تب قوانین انتشار.
 *
 * @package Kanalyar
 *
 * @var string    $ky_endpoint نشانی admin-post.
 * @var KY_Admin $this         کنترلر پنل.
 */

defined( 'ABSPATH' ) || exit;

$ky_rules        = $this->rules_list();
$ky_destinations = $this->all_destinations();
$ky_types        = KY_Rules::available_post_types();
$ky_strategies   = KY_Rules::strategies();
$ky_photo_modes  = KY_Settings::photo_modes();
$ky_triggers     = KY_Rules::triggers();
$ky_hashtags     = KY_Rules::hashtag_sources();
$ky_weekdays     = KY_Admin::weekdays();
?>

<?php if ( ! $ky_destinations ) : ?>
	<div class="ky-alert">
		<p>
			<strong><?php esc_html_e( 'هنوز مقصدی تعریف نکرده‌اید.', 'kanalyar' ); ?></strong>
			<?php esc_html_e( 'اول باید حداقل یک کانال بله یا تلگرام اضافه کنید تا بتوانید در قوانین انتخابش کنید.', 'kanalyar' ); ?>
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
	<input type="hidden" name="ky_tab" value="rules">
	<input type="hidden" name="ky_target" value="">

	<div class="ky-repeat" data-repeat="rules">
		<?php foreach ( $ky_rules as $ky_i => $ky_rule ) : ?>
			<?php
			$ky_status = $ky_rule['id'] ? $this->rule_status( $ky_rule ) : null;
			$ky_name   = 'ky[rules][' . $ky_i . ']';
			$ky_taxes  = KY_Rules::taxonomies_for( (array) $ky_rule['post_types'] );
			?>
			<div class="ky-card ky-card--rule ky-item" data-index="<?php echo esc_attr( $ky_i ); ?>">

				<div class="ky-item-head">
					<span class="ky-h2-icon">📣</span>
					<strong class="ky-item-title">
						<?php echo esc_html( $ky_rule['label'] ? $ky_rule['label'] : __( 'قانون تازه', 'kanalyar' ) ); ?>
					</strong>
					<label class="ky-switch">
						<input type="checkbox" name="<?php echo esc_attr( $ky_name ); ?>[enabled]" value="1" <?php checked( $ky_rule['enabled'], 1 ); ?>>
						<?php esc_html_e( 'فعال', 'kanalyar' ); ?>
					</label>
					<button type="button" class="button-link ky-remove" aria-label="<?php esc_attr_e( 'حذف این قانون', 'kanalyar' ); ?>">🗑️ <?php esc_html_e( 'حذف', 'kanalyar' ); ?></button>
				</div>

				<input type="hidden" name="<?php echo esc_attr( $ky_name ); ?>[id]" value="<?php echo esc_attr( $ky_rule['id'] ); ?>">

				<?php if ( $ky_status ) : ?>
					<div class="ky-rule-status">
						<span><strong><?php esc_html_e( 'وضعیت:', 'kanalyar' ); ?></strong> <?php echo esc_html( $ky_status['state']['message'] ); ?></span>
						<?php if ( $ky_status['next'] ) : ?>
							<span dir="ltr" class="ky-dim"><?php echo esc_html( '⏭ ' . $ky_status['next'] ); ?></span>
						<?php endif; ?>
						<span class="ky-dim">
							<?php
							printf(
								/* translators: ۱: ارسال‌شده، ۲: کل. */
								esc_html__( 'ارسال‌شده: %1$d از %2$d', 'kanalyar' ),
								(int) $ky_status['stats']['sent'],
								(int) $ky_status['stats']['total']
							);
							?>
						</span>
					</div>
				<?php endif; ?>

				<h3 class="ky-sub-head"><?php esc_html_e( 'چه محتوایی', 'kanalyar' ); ?></h3>
				<div class="ky-grid">
					<label class="ky-grid-wide">
						<span><?php esc_html_e( 'نام قانون', 'kanalyar' ); ?></span>
						<input type="text" name="<?php echo esc_attr( $ky_name ); ?>[label]"
							value="<?php echo esc_attr( $ky_rule['label'] ); ?>"
							placeholder="<?php esc_attr_e( 'مثلاً محصولات روزانه', 'kanalyar' ); ?>">
					</label>

					<label>
						<span><?php esc_html_e( 'نوع محتوا', 'kanalyar' ); ?></span>
						<select name="<?php echo esc_attr( $ky_name ); ?>[post_types][]" multiple size="5">
							<?php foreach ( $ky_types as $ky_slug => $ky_label ) : ?>
								<option value="<?php echo esc_attr( $ky_slug ); ?>"
									<?php selected( in_array( $ky_slug, (array) $ky_rule['post_types'], true ) ); ?>>
									<?php echo esc_html( $ky_label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<small><?php esc_html_e( 'محصول، نوشته، برگه یا هر نوع دیگری. چندتایی با Ctrl.', 'kanalyar' ); ?></small>
					</label>

					<label>
						<span><?php esc_html_e( 'چه وقت اجرا شود', 'kanalyar' ); ?></span>
						<select name="<?php echo esc_attr( $ky_name ); ?>[trigger]" class="ky-trigger">
							<?php foreach ( $ky_triggers as $ky_tk => $ky_tl ) : ?>
								<option value="<?php echo esc_attr( $ky_tk ); ?>" <?php selected( $ky_rule['trigger'], $ky_tk ); ?>>
									<?php echo esc_html( $ky_tl ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<small><?php esc_html_e( '«هنگام انتشار» مطلب تازه را فوری می‌فرستد.', 'kanalyar' ); ?></small>
					</label>

					<label>
						<span><?php esc_html_e( 'ترتیب انتخاب', 'kanalyar' ); ?></span>
						<select name="<?php echo esc_attr( $ky_name ); ?>[strategy]">
							<?php foreach ( $ky_strategies as $ky_sk => $ky_sl ) : ?>
								<option value="<?php echo esc_attr( $ky_sk ); ?>" <?php selected( $ky_rule['strategy'], $ky_sk ); ?>>
									<?php echo esc_html( $ky_sl ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<small><?php esc_html_e( 'در حالت‌های ترتیبی، هر مطلب فقط یک‌بار ارسال می‌شود.', 'kanalyar' ); ?></small>
					</label>
				</div>

				<?php if ( $ky_taxes ) : ?>
					<div class="ky-grid">
						<?php foreach ( $ky_taxes as $ky_tax_slug => $ky_tax ) : ?>
							<?php
							$ky_terms = get_terms(
								array(
									'taxonomy'   => $ky_tax_slug,
									'hide_empty' => false,
									'number'     => 300,
								)
							);

							if ( is_wp_error( $ky_terms ) || ! $ky_terms ) {
								continue;
							}

							$ky_selected = (array) ( $ky_rule['terms'][ $ky_tax_slug ] ?? array() );
							?>
							<label>
								<span><?php echo esc_html( $ky_tax->labels->name ); ?></span>
								<select name="<?php echo esc_attr( $ky_name ); ?>[terms][<?php echo esc_attr( $ky_tax_slug ); ?>][]" multiple size="5">
									<?php foreach ( $ky_terms as $ky_term ) : ?>
										<option value="<?php echo esc_attr( $ky_term->term_id ); ?>"
											<?php selected( in_array( (int) $ky_term->term_id, array_map( 'intval', $ky_selected ), true ) ); ?>>
											<?php echo esc_html( $ky_term->name ); ?> (<?php echo (int) $ky_term->count; ?>)
										</option>
									<?php endforeach; ?>
								</select>
								<small><?php esc_html_e( 'خالی = همه', 'kanalyar' ); ?></small>
							</label>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<div class="ky-grid">
					<label>
						<span><?php esc_html_e( 'هشتگ', 'kanalyar' ); ?></span>
						<select name="<?php echo esc_attr( $ky_name ); ?>[hashtags]">
							<?php foreach ( $ky_hashtags as $ky_hk => $ky_hl ) : ?>
								<option value="<?php echo esc_attr( $ky_hk ); ?>" <?php selected( $ky_rule['hashtags'], $ky_hk ); ?>>
									<?php echo esc_html( $ky_hl ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<small><?php esc_html_e( 'با جای‌نشان {hashtags} در قالب پیام نمایش داده می‌شود.', 'kanalyar' ); ?></small>
					</label>
				</div>

				<div class="ky-checks">
					<label>
						<input type="checkbox" name="<?php echo esc_attr( $ky_name ); ?>[show_excerpt]" value="1" <?php checked( $ky_rule['show_excerpt'], 1 ); ?>>
						<?php esc_html_e( 'خلاصه‌ی مطلب در پست بیاید', 'kanalyar' ); ?>
					</label>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( $ky_name ); ?>[show_price]" value="1" <?php checked( $ky_rule['show_price'], 1 ); ?>>
						<?php esc_html_e( 'قیمت محصول نمایش داده شود', 'kanalyar' ); ?>
					</label>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( $ky_name ); ?>[instock_only]" value="1" <?php checked( $ky_rule['instock_only'], 1 ); ?>>
						<?php esc_html_e( 'فقط محصولات موجود', 'kanalyar' ); ?>
					</label>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( $ky_name ); ?>[silent]" value="1" <?php checked( $ky_rule['silent'], 1 ); ?>>
						<?php esc_html_e( 'ارسال بی‌صدا (بدون اعلان)', 'kanalyar' ); ?>
					</label>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( $ky_name ); ?>[pin]" value="1" <?php checked( $ky_rule['pin'], 1 ); ?>>
						<?php esc_html_e( 'سنجاق‌کردن در کانال', 'kanalyar' ); ?>
					</label>
				</div>

				<h3 class="ky-sub-head"><?php esc_html_e( 'قالب پیام', 'kanalyar' ); ?></h3>
				<div class="ky-template">
					<textarea name="<?php echo esc_attr( $ky_name ); ?>[template]" rows="7"
						placeholder="<?php echo esc_attr( KY_Message::DEFAULT_TEMPLATE ); ?>"><?php echo esc_textarea( $ky_rule['template'] ); ?></textarea>
					<p class="description"><?php esc_html_e( 'خالی بگذارید تا قالب پیش‌فرض استفاده شود. جای‌نشان‌های موجود:', 'kanalyar' ); ?></p>
					<div class="ky-tokens">
						<?php foreach ( KY_Message::placeholders() as $ky_token => $ky_desc ) : ?>
							<code class="ky-token" title="<?php echo esc_attr( $ky_desc ); ?>"><?php echo esc_html( $ky_token ); ?></code>
						<?php endforeach; ?>
					</div>
				</div>

				<h3 class="ky-sub-head"><?php esc_html_e( 'به کدام کانال‌ها', 'kanalyar' ); ?></h3>
				<div class="ky-dests">
					<?php if ( ! $ky_destinations ) : ?>
						<em class="ky-dim"><?php esc_html_e( 'ابتدا در تب مقصدها یک کانال اضافه کنید.', 'kanalyar' ); ?></em>
					<?php endif; ?>
					<?php foreach ( $ky_destinations as $ky_dest ) : ?>
						<label class="ky-dest-pick">
							<input type="checkbox"
								name="<?php echo esc_attr( $ky_name ); ?>[destinations][]"
								value="<?php echo esc_attr( $ky_dest['id'] ); ?>"
								<?php checked( in_array( $ky_dest['id'], (array) $ky_rule['destinations'], true ) ); ?>>
							<span class="ky-chip ky-chip--<?php echo esc_attr( $ky_dest['platform'] ); ?>">
								<?php echo esc_html( KY_Messenger::platforms()[ $ky_dest['platform'] ] ?? $ky_dest['platform'] ); ?>
							</span>
							<?php echo esc_html( $ky_dest['label'] ); ?>
							<?php if ( empty( $ky_dest['enabled'] ) ) : ?>
								<em class="ky-dim">(<?php esc_html_e( 'غیرفعال', 'kanalyar' ); ?>)</em>
							<?php endif; ?>
						</label>
					<?php endforeach; ?>
				</div>

				<h3 class="ky-sub-head"><?php esc_html_e( 'چه زمانی', 'kanalyar' ); ?></h3>
				<div class="ky-days">
					<?php foreach ( $ky_weekdays as $ky_num => $ky_day ) : ?>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( $ky_name ); ?>[days][]" value="<?php echo esc_attr( $ky_num ); ?>"
								<?php checked( in_array( (int) $ky_num, array_map( 'intval', (array) $ky_rule['days'] ), true ) ); ?>>
							<?php echo esc_html( $ky_day ); ?>
						</label>
					<?php endforeach; ?>
				</div>

				<div class="ky-grid">
					<label>
						<span><?php esc_html_e( 'ساعت (وقت تهران)', 'kanalyar' ); ?></span>
						<span class="ky-time">
							<input type="number" name="<?php echo esc_attr( $ky_name ); ?>[hour]" min="0" max="23" value="<?php echo esc_attr( $ky_rule['hour'] ); ?>">
							:
							<input type="number" name="<?php echo esc_attr( $ky_name ); ?>[minute]" min="0" max="59" value="<?php echo esc_attr( $ky_rule['minute'] ); ?>">
						</span>
					</label>

					<label>
						<span><?php esc_html_e( 'جلوگیری از تکرار', 'kanalyar' ); ?></span>
						<input type="number" name="<?php echo esc_attr( $ky_name ); ?>[avoid]" min="0" max="500" value="<?php echo esc_attr( $ky_rule['avoid'] ); ?>">
						<small><?php esc_html_e( 'فقط در حالت تصادفی. ۰ = بدون محدودیت.', 'kanalyar' ); ?></small>
					</label>

					<label>
						<span><?php esc_html_e( 'تلاش در روز', 'kanalyar' ); ?></span>
						<input type="number" name="<?php echo esc_attr( $ky_name ); ?>[max_attempts]" min="1" max="10" value="<?php echo esc_attr( $ky_rule['max_attempts'] ); ?>">
						<small><?php esc_html_e( 'در صورت شکست، با فاصله‌ی ۱۵ دقیقه.', 'kanalyar' ); ?></small>
					</label>

					<label>
						<span><?php esc_html_e( 'ارسال تصویر', 'kanalyar' ); ?></span>
						<select name="<?php echo esc_attr( $ky_name ); ?>[photo_mode]">
							<?php foreach ( $ky_photo_modes as $ky_pm => $ky_pml ) : ?>
								<option value="<?php echo esc_attr( $ky_pm ); ?>" <?php selected( $ky_rule['photo_mode'], $ky_pm ); ?>>
									<?php echo esc_html( $ky_pml ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</label>

					<label class="ky-grid-wide">
						<span><?php esc_html_e( 'متن دکمه', 'kanalyar' ); ?></span>
						<input type="text" name="<?php echo esc_attr( $ky_name ); ?>[cta]"
							value="<?php echo esc_attr( $ky_rule['cta'] ); ?>"
							placeholder="<?php esc_attr_e( 'خالی = خودکار بر اساس نوع محتوا', 'kanalyar' ); ?>">
					</label>
				</div>

				<?php if ( $ky_rule['id'] ) : ?>
					<p class="ky-item-foot">
						<button type="submit" name="ky_action" value="test_rule" class="button button-small" formnovalidate
							onclick="this.form.ky_target.value='<?php echo esc_js( $ky_rule['id'] ); ?>'">
							🚀 <?php esc_html_e( 'ارسال تستی', 'kanalyar' ); ?>
						</button>
						<button type="submit" name="ky_action" value="reset_rule" class="button button-small" formnovalidate
							onclick="this.form.ky_target.value='<?php echo esc_js( $ky_rule['id'] ); ?>'; return confirm('<?php echo esc_js( __( 'تاریخچه‌ی ارسال این قانون پاک شود؟ همه‌ی مطالب دوباره واجد شرایط می‌شوند.', 'kanalyar' ) ); ?>');">
							♻️ <?php esc_html_e( 'شروع دوباره', 'kanalyar' ); ?>
						</button>
						<span class="description"><?php esc_html_e( 'ارسال تستی جای پست خودکار امروز را نمی‌گیرد.', 'kanalyar' ); ?></span>
					</p>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>

	<p class="ky-actions">
		<button type="button" class="button ky-add" data-repeat-target="rules">
			➕ <?php esc_html_e( 'افزودن قانون', 'kanalyar' ); ?>
		</button>
		<button type="submit" name="ky_action" value="save_rules" class="button button-primary">
			💾 <?php esc_html_e( 'ذخیره قوانین', 'kanalyar' ); ?>
		</button>
	</p>
</form>
