<?php
/**
 * تب قوانین انتشار.
 *
 * @package DailyBalePost
 *
 * @var string    $dsb_endpoint نشانی admin-post.
 * @var DSB_Admin $this         کنترلر پنل.
 */

defined( 'ABSPATH' ) || exit;

$dsb_rules        = $this->rules_list();
$dsb_destinations = $this->all_destinations();
$dsb_types        = DSB_Rules::available_post_types();
$dsb_strategies   = DSB_Rules::strategies();
$dsb_photo_modes  = DSB_Settings::photo_modes();
$dsb_weekdays     = DSB_Admin::weekdays();
?>

<?php if ( ! $dsb_destinations ) : ?>
	<div class="dsb-alert">
		<p>
			<strong><?php esc_html_e( 'هنوز مقصدی تعریف نکرده‌اید.', 'daily-bale-post' ); ?></strong>
			<?php esc_html_e( 'اول باید حداقل یک کانال بله یا تلگرام اضافه کنید تا بتوانید در قوانین انتخابش کنید.', 'daily-bale-post' ); ?>
		</p>
		<p>
			<a href="<?php echo esc_url( $this->tab_url( 'destinations' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'افزودن مقصد', 'daily-bale-post' ); ?>
			</a>
		</p>
	</div>
<?php endif; ?>

<form method="post" action="<?php echo esc_url( $dsb_endpoint ); ?>">
	<?php wp_nonce_field( DSB_Admin::NONCE ); ?>
	<input type="hidden" name="action" value="dsb_handle">
	<input type="hidden" name="dsb_tab" value="rules">
	<input type="hidden" name="dsb_target" value="">

	<div class="dsb-repeat" data-repeat="rules">
		<?php foreach ( $dsb_rules as $dsb_i => $dsb_rule ) : ?>
			<?php
			$dsb_status = $dsb_rule['id'] ? $this->rule_status( $dsb_rule ) : null;
			$dsb_name   = 'dsb[rules][' . $dsb_i . ']';
			$dsb_taxes  = DSB_Rules::taxonomies_for( (array) $dsb_rule['post_types'] );
			?>
			<div class="dsb-card dsb-card--rule dsb-item" data-index="<?php echo esc_attr( $dsb_i ); ?>">

				<div class="dsb-item-head">
					<span class="dsb-h2-icon">📣</span>
					<strong class="dsb-item-title">
						<?php echo esc_html( $dsb_rule['label'] ? $dsb_rule['label'] : __( 'قانون تازه', 'daily-bale-post' ) ); ?>
					</strong>
					<label class="dsb-switch">
						<input type="checkbox" name="<?php echo esc_attr( $dsb_name ); ?>[enabled]" value="1" <?php checked( $dsb_rule['enabled'], 1 ); ?>>
						<?php esc_html_e( 'فعال', 'daily-bale-post' ); ?>
					</label>
					<button type="button" class="button-link dsb-remove" aria-label="<?php esc_attr_e( 'حذف این قانون', 'daily-bale-post' ); ?>">🗑️ <?php esc_html_e( 'حذف', 'daily-bale-post' ); ?></button>
				</div>

				<input type="hidden" name="<?php echo esc_attr( $dsb_name ); ?>[id]" value="<?php echo esc_attr( $dsb_rule['id'] ); ?>">

				<?php if ( $dsb_status ) : ?>
					<div class="dsb-rule-status">
						<span><strong><?php esc_html_e( 'وضعیت:', 'daily-bale-post' ); ?></strong> <?php echo esc_html( $dsb_status['state']['message'] ); ?></span>
						<?php if ( $dsb_status['next'] ) : ?>
							<span dir="ltr" class="dsb-dim"><?php echo esc_html( '⏭ ' . $dsb_status['next'] ); ?></span>
						<?php endif; ?>
						<span class="dsb-dim">
							<?php
							printf(
								/* translators: ۱: ارسال‌شده، ۲: کل. */
								esc_html__( 'ارسال‌شده: %1$d از %2$d', 'daily-bale-post' ),
								(int) $dsb_status['stats']['sent'],
								(int) $dsb_status['stats']['total']
							);
							?>
						</span>
					</div>
				<?php endif; ?>

				<h3 class="dsb-sub-head"><?php esc_html_e( 'چه محتوایی', 'daily-bale-post' ); ?></h3>
				<div class="dsb-grid">
					<label class="dsb-grid-wide">
						<span><?php esc_html_e( 'نام قانون', 'daily-bale-post' ); ?></span>
						<input type="text" name="<?php echo esc_attr( $dsb_name ); ?>[label]"
							value="<?php echo esc_attr( $dsb_rule['label'] ); ?>"
							placeholder="<?php esc_attr_e( 'مثلاً محصولات روزانه', 'daily-bale-post' ); ?>">
					</label>

					<label>
						<span><?php esc_html_e( 'نوع محتوا', 'daily-bale-post' ); ?></span>
						<select name="<?php echo esc_attr( $dsb_name ); ?>[post_types][]" multiple size="5">
							<?php foreach ( $dsb_types as $dsb_slug => $dsb_label ) : ?>
								<option value="<?php echo esc_attr( $dsb_slug ); ?>"
									<?php selected( in_array( $dsb_slug, (array) $dsb_rule['post_types'], true ) ); ?>>
									<?php echo esc_html( $dsb_label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<small><?php esc_html_e( 'محصول، نوشته، برگه یا هر نوع دیگری. چندتایی با Ctrl.', 'daily-bale-post' ); ?></small>
					</label>

					<label>
						<span><?php esc_html_e( 'ترتیب انتخاب', 'daily-bale-post' ); ?></span>
						<select name="<?php echo esc_attr( $dsb_name ); ?>[strategy]">
							<?php foreach ( $dsb_strategies as $dsb_sk => $dsb_sl ) : ?>
								<option value="<?php echo esc_attr( $dsb_sk ); ?>" <?php selected( $dsb_rule['strategy'], $dsb_sk ); ?>>
									<?php echo esc_html( $dsb_sl ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<small><?php esc_html_e( 'در حالت‌های ترتیبی، هر مطلب فقط یک‌بار ارسال می‌شود.', 'daily-bale-post' ); ?></small>
					</label>
				</div>

				<?php if ( $dsb_taxes ) : ?>
					<div class="dsb-grid">
						<?php foreach ( $dsb_taxes as $dsb_tax_slug => $dsb_tax ) : ?>
							<?php
							$dsb_terms = get_terms(
								array(
									'taxonomy'   => $dsb_tax_slug,
									'hide_empty' => false,
									'number'     => 300,
								)
							);

							if ( is_wp_error( $dsb_terms ) || ! $dsb_terms ) {
								continue;
							}

							$dsb_selected = (array) ( $dsb_rule['terms'][ $dsb_tax_slug ] ?? array() );
							?>
							<label>
								<span><?php echo esc_html( $dsb_tax->labels->name ); ?></span>
								<select name="<?php echo esc_attr( $dsb_name ); ?>[terms][<?php echo esc_attr( $dsb_tax_slug ); ?>][]" multiple size="5">
									<?php foreach ( $dsb_terms as $dsb_term ) : ?>
										<option value="<?php echo esc_attr( $dsb_term->term_id ); ?>"
											<?php selected( in_array( (int) $dsb_term->term_id, array_map( 'intval', $dsb_selected ), true ) ); ?>>
											<?php echo esc_html( $dsb_term->name ); ?> (<?php echo (int) $dsb_term->count; ?>)
										</option>
									<?php endforeach; ?>
								</select>
								<small><?php esc_html_e( 'خالی = همه', 'daily-bale-post' ); ?></small>
							</label>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<div class="dsb-checks">
					<label>
						<input type="checkbox" name="<?php echo esc_attr( $dsb_name ); ?>[show_excerpt]" value="1" <?php checked( $dsb_rule['show_excerpt'], 1 ); ?>>
						<?php esc_html_e( 'خلاصه‌ی مطلب در پست بیاید', 'daily-bale-post' ); ?>
					</label>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( $dsb_name ); ?>[show_price]" value="1" <?php checked( $dsb_rule['show_price'], 1 ); ?>>
						<?php esc_html_e( 'قیمت محصول نمایش داده شود', 'daily-bale-post' ); ?>
					</label>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( $dsb_name ); ?>[instock_only]" value="1" <?php checked( $dsb_rule['instock_only'], 1 ); ?>>
						<?php esc_html_e( 'فقط محصولات موجود', 'daily-bale-post' ); ?>
					</label>
				</div>

				<h3 class="dsb-sub-head"><?php esc_html_e( 'به کدام کانال‌ها', 'daily-bale-post' ); ?></h3>
				<div class="dsb-dests">
					<?php if ( ! $dsb_destinations ) : ?>
						<em class="dsb-dim"><?php esc_html_e( 'ابتدا در تب مقصدها یک کانال اضافه کنید.', 'daily-bale-post' ); ?></em>
					<?php endif; ?>
					<?php foreach ( $dsb_destinations as $dsb_dest ) : ?>
						<label class="dsb-dest-pick">
							<input type="checkbox"
								name="<?php echo esc_attr( $dsb_name ); ?>[destinations][]"
								value="<?php echo esc_attr( $dsb_dest['id'] ); ?>"
								<?php checked( in_array( $dsb_dest['id'], (array) $dsb_rule['destinations'], true ) ); ?>>
							<span class="dsb-chip dsb-chip--<?php echo esc_attr( $dsb_dest['platform'] ); ?>">
								<?php echo esc_html( DSB_Messenger::platforms()[ $dsb_dest['platform'] ] ?? $dsb_dest['platform'] ); ?>
							</span>
							<?php echo esc_html( $dsb_dest['label'] ); ?>
							<?php if ( empty( $dsb_dest['enabled'] ) ) : ?>
								<em class="dsb-dim">(<?php esc_html_e( 'غیرفعال', 'daily-bale-post' ); ?>)</em>
							<?php endif; ?>
						</label>
					<?php endforeach; ?>
				</div>

				<h3 class="dsb-sub-head"><?php esc_html_e( 'چه زمانی', 'daily-bale-post' ); ?></h3>
				<div class="dsb-days">
					<?php foreach ( $dsb_weekdays as $dsb_num => $dsb_day ) : ?>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( $dsb_name ); ?>[days][]" value="<?php echo esc_attr( $dsb_num ); ?>"
								<?php checked( in_array( (int) $dsb_num, array_map( 'intval', (array) $dsb_rule['days'] ), true ) ); ?>>
							<?php echo esc_html( $dsb_day ); ?>
						</label>
					<?php endforeach; ?>
				</div>

				<div class="dsb-grid">
					<label>
						<span><?php esc_html_e( 'ساعت (وقت تهران)', 'daily-bale-post' ); ?></span>
						<span class="dsb-time">
							<input type="number" name="<?php echo esc_attr( $dsb_name ); ?>[hour]" min="0" max="23" value="<?php echo esc_attr( $dsb_rule['hour'] ); ?>">
							:
							<input type="number" name="<?php echo esc_attr( $dsb_name ); ?>[minute]" min="0" max="59" value="<?php echo esc_attr( $dsb_rule['minute'] ); ?>">
						</span>
					</label>

					<label>
						<span><?php esc_html_e( 'جلوگیری از تکرار', 'daily-bale-post' ); ?></span>
						<input type="number" name="<?php echo esc_attr( $dsb_name ); ?>[avoid]" min="0" max="500" value="<?php echo esc_attr( $dsb_rule['avoid'] ); ?>">
						<small><?php esc_html_e( 'فقط در حالت تصادفی. ۰ = بدون محدودیت.', 'daily-bale-post' ); ?></small>
					</label>

					<label>
						<span><?php esc_html_e( 'تلاش در روز', 'daily-bale-post' ); ?></span>
						<input type="number" name="<?php echo esc_attr( $dsb_name ); ?>[max_attempts]" min="1" max="10" value="<?php echo esc_attr( $dsb_rule['max_attempts'] ); ?>">
						<small><?php esc_html_e( 'در صورت شکست، با فاصله‌ی ۱۵ دقیقه.', 'daily-bale-post' ); ?></small>
					</label>

					<label>
						<span><?php esc_html_e( 'ارسال تصویر', 'daily-bale-post' ); ?></span>
						<select name="<?php echo esc_attr( $dsb_name ); ?>[photo_mode]">
							<?php foreach ( $dsb_photo_modes as $dsb_pm => $dsb_pml ) : ?>
								<option value="<?php echo esc_attr( $dsb_pm ); ?>" <?php selected( $dsb_rule['photo_mode'], $dsb_pm ); ?>>
									<?php echo esc_html( $dsb_pml ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</label>

					<label class="dsb-grid-wide">
						<span><?php esc_html_e( 'متن دکمه', 'daily-bale-post' ); ?></span>
						<input type="text" name="<?php echo esc_attr( $dsb_name ); ?>[cta]"
							value="<?php echo esc_attr( $dsb_rule['cta'] ); ?>"
							placeholder="<?php esc_attr_e( 'خالی = خودکار بر اساس نوع محتوا', 'daily-bale-post' ); ?>">
					</label>
				</div>

				<?php if ( $dsb_rule['id'] ) : ?>
					<p class="dsb-item-foot">
						<button type="submit" name="dsb_action" value="test_rule" class="button button-small" formnovalidate
							onclick="this.form.dsb_target.value='<?php echo esc_js( $dsb_rule['id'] ); ?>'">
							🚀 <?php esc_html_e( 'ارسال تستی', 'daily-bale-post' ); ?>
						</button>
						<button type="submit" name="dsb_action" value="reset_rule" class="button button-small" formnovalidate
							onclick="this.form.dsb_target.value='<?php echo esc_js( $dsb_rule['id'] ); ?>'; return confirm('<?php echo esc_js( __( 'تاریخچه‌ی ارسال این قانون پاک شود؟ همه‌ی مطالب دوباره واجد شرایط می‌شوند.', 'daily-bale-post' ) ); ?>');">
							♻️ <?php esc_html_e( 'شروع دوباره', 'daily-bale-post' ); ?>
						</button>
						<span class="description"><?php esc_html_e( 'ارسال تستی جای پست خودکار امروز را نمی‌گیرد.', 'daily-bale-post' ); ?></span>
					</p>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>

	<p class="dsb-actions">
		<button type="button" class="button dsb-add" data-repeat-target="rules">
			➕ <?php esc_html_e( 'افزودن قانون', 'daily-bale-post' ); ?>
		</button>
		<button type="submit" name="dsb_action" value="save_rules" class="button button-primary">
			💾 <?php esc_html_e( 'ذخیره قوانین', 'daily-bale-post' ); ?>
		</button>
	</p>
</form>
