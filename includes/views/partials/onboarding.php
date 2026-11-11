<?php
/**
 * پنل شروع سریع، وقتی افزونه هنوز پیکربندی نشده است.
 *
 * @package Kanalyar
 *
 * @var KY_Admin $this کنترلر پنل.
 */

defined( 'ABSPATH' ) || exit;

$ky_has_dest  = (bool) $this->all_destinations();
$ky_has_rule  = (bool) $this->rules_ready();
$ky_has_sent  = (bool) $this->has_any_success();

// وقتی همه‌چیز سر جایش است، این پنل اصلاً نمایش داده نمی‌شود.
if ( $ky_has_dest && $ky_has_rule && $ky_has_sent ) {
	return;
}

$ky_steps = array(
	array(
		'done'  => $ky_has_dest,
		'title' => __( 'یک کانال اضافه کنید', 'kanalyar' ),
		'text'  => __( 'بات بسازید، توکن بگیرید، بات را ادمین کانال کنید و اینجا ثبتش کنید.', 'kanalyar' ),
		'tab'   => 'destinations',
		'cta'   => __( 'رفتن به مقصدها', 'kanalyar' ),
	),
	array(
		'done'  => $ky_has_rule,
		'title' => __( 'یک قانون انتشار بسازید', 'kanalyar' ),
		'text'  => __( 'مشخص کنید چه محتوایی، به کدام کانال، چه روزها و چه ساعتی برود.', 'kanalyar' ),
		'tab'   => 'rules',
		'cta'   => __( 'رفتن به قوانین', 'kanalyar' ),
	),
	array(
		'done'  => $ky_has_sent,
		'title' => __( 'یک ارسال تستی بزنید', 'kanalyar' ),
		'text'  => __( 'تا مطمئن شوید پیام واقعاً در کانال می‌نشیند، قبل از اینکه به زمان‌بندی تکیه کنید.', 'kanalyar' ),
		'tab'   => 'rules',
		'cta'   => __( 'ارسال تستی', 'kanalyar' ),
	),
);
?>
<div class="ky-card ky-card--start">
	<h2><span class="ky-h2-icon">🚀</span> <?php esc_html_e( 'شروع سریع', 'kanalyar' ); ?></h2>

	<p class="description ky-intro">
		<?php esc_html_e( 'سه قدم تا اولین پست خودکار. هر قدمی که کامل شود، تیک می‌خورد.', 'kanalyar' ); ?>
	</p>

	<ol class="ky-steps">
		<?php foreach ( $ky_steps as $ky_i => $ky_step ) : ?>
			<li class="<?php echo $ky_step['done'] ? 'is-done' : ''; ?>">
				<span class="ky-step-mark"><?php echo $ky_step['done'] ? '✅' : esc_html( $ky_i + 1 ); ?></span>
				<span class="ky-step-body">
					<strong><?php echo esc_html( $ky_step['title'] ); ?></strong>
					<span class="description"><?php echo esc_html( $ky_step['text'] ); ?></span>
				</span>
				<?php if ( ! $ky_step['done'] ) : ?>
					<a href="<?php echo esc_url( $this->tab_url( $ky_step['tab'] ) ); ?>" class="button button-small">
						<?php echo esc_html( $ky_step['cta'] ); ?>
					</a>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ol>
</div>
