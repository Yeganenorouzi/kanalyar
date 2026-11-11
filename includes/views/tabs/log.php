<?php
/**
 * تب تاریخچه.
 *
 * @package Kanalyar
 *
 * @var string    $ky_endpoint نشانی admin-post.
 * @var KY_Admin $this         کنترلر پنل.
 */

defined( 'ABSPATH' ) || exit;

$ky_log = $this->log_rows();
?>

<?php KY_Help::render( 'log' ); ?>

<div class="ky-card ky-card--history">
	<h2><span class="ky-h2-icon">🗂️</span> <?php esc_html_e( 'تاریخچه‌ی ارسال', 'kanalyar' ); ?></h2>

	<?php if ( ! $ky_log ) : ?>
		<p class="description"><?php esc_html_e( 'هنوز ارسالی ثبت نشده است.', 'kanalyar' ); ?></p>
	<?php else : ?>
		<table class="widefat striped ky-log">
			<thead>
				<tr>
					<th><?php esc_html_e( 'زمان', 'kanalyar' ); ?></th>
					<th><?php esc_html_e( 'قانون', 'kanalyar' ); ?></th>
					<th><?php esc_html_e( 'مطلب', 'kanalyar' ); ?></th>
					<th><?php esc_html_e( 'مقصد', 'kanalyar' ); ?></th>
					<th><?php esc_html_e( 'منبع', 'kanalyar' ); ?></th>
					<th><?php esc_html_e( 'وضعیت', 'kanalyar' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $ky_log as $ky_row ) : ?>
				<?php
				$ky_state  = $ky_row['state'] ?? 'fail';
				$ky_source = 'manual' === ( $ky_row['source'] ?? 'auto' )
					? __( 'دستی', 'kanalyar' )
					: __( 'خودکار', 'kanalyar' );
				$ky_icon   = 'ok' === $ky_state ? '✅' : ( 'skip' === $ky_state ? '⏭️' : '❌' );
				?>
				<tr>
					<td dir="ltr"><?php echo esc_html( $this->local_time( $ky_row['time'] ) ); ?></td>
					<td><?php echo esc_html( $ky_row['rule'] ?? '—' ); ?></td>
					<td><?php echo esc_html( $ky_row['name'] ); ?></td>
					<td><?php echo esc_html( $ky_row['destination'] ?? '—' ); ?></td>
					<td><?php echo esc_html( $ky_source ); ?></td>
					<td class="ky-state-<?php echo esc_attr( $ky_state ); ?>">
						<?php echo esc_html( $ky_icon . ' ' . $ky_row['msg'] ); ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<form method="post" action="<?php echo esc_url( $ky_endpoint ); ?>" class="ky-inline-form">
			<?php wp_nonce_field( KY_Admin::NONCE ); ?>
			<input type="hidden" name="action" value="ky_handle">
			<input type="hidden" name="ky_tab" value="log">
			<button type="submit" name="ky_action" value="clear_log" class="button button-small"
				onclick="return confirm('<?php echo esc_js( __( 'همه‌ی تاریخچه پاک شود؟', 'kanalyar' ) ); ?>');">
				🗑️ <?php esc_html_e( 'پاک‌کردن تاریخچه', 'kanalyar' ); ?>
			</button>
		</form>

		<p class="description">
			<?php
			printf(
				/* translators: %d: تعداد ردیف. */
				esc_html__( '%d مورد آخر نمایش داده می‌شود.', 'kanalyar' ),
				(int) KY_Logger::LIMIT
			);
			?>
		</p>
	<?php endif; ?>
</div>
