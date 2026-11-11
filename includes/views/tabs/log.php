<?php
/**
 * تب تاریخچه.
 *
 * @package DailyBalePost
 *
 * @var string    $dsb_endpoint نشانی admin-post.
 * @var DSB_Admin $this         کنترلر پنل.
 */

defined( 'ABSPATH' ) || exit;

$dsb_log = $this->log_rows();
?>

<div class="dsb-card dsb-card--history">
	<h2><span class="dsb-h2-icon">🗂️</span> <?php esc_html_e( 'تاریخچه‌ی ارسال', 'daily-bale-post' ); ?></h2>

	<?php if ( ! $dsb_log ) : ?>
		<p class="description"><?php esc_html_e( 'هنوز ارسالی ثبت نشده است.', 'daily-bale-post' ); ?></p>
	<?php else : ?>
		<table class="widefat striped dsb-log">
			<thead>
				<tr>
					<th><?php esc_html_e( 'زمان', 'daily-bale-post' ); ?></th>
					<th><?php esc_html_e( 'قانون', 'daily-bale-post' ); ?></th>
					<th><?php esc_html_e( 'مطلب', 'daily-bale-post' ); ?></th>
					<th><?php esc_html_e( 'مقصد', 'daily-bale-post' ); ?></th>
					<th><?php esc_html_e( 'منبع', 'daily-bale-post' ); ?></th>
					<th><?php esc_html_e( 'وضعیت', 'daily-bale-post' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $dsb_log as $dsb_row ) : ?>
				<?php
				$dsb_state  = $dsb_row['state'] ?? 'fail';
				$dsb_source = 'manual' === ( $dsb_row['source'] ?? 'auto' )
					? __( 'دستی', 'daily-bale-post' )
					: __( 'خودکار', 'daily-bale-post' );
				$dsb_icon   = 'ok' === $dsb_state ? '✅' : ( 'skip' === $dsb_state ? '⏭️' : '❌' );
				?>
				<tr>
					<td dir="ltr"><?php echo esc_html( $this->local_time( $dsb_row['time'] ) ); ?></td>
					<td><?php echo esc_html( $dsb_row['rule'] ?? '—' ); ?></td>
					<td><?php echo esc_html( $dsb_row['name'] ); ?></td>
					<td><?php echo esc_html( $dsb_row['destination'] ?? '—' ); ?></td>
					<td><?php echo esc_html( $dsb_source ); ?></td>
					<td class="dsb-state-<?php echo esc_attr( $dsb_state ); ?>">
						<?php echo esc_html( $dsb_icon . ' ' . $dsb_row['msg'] ); ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<form method="post" action="<?php echo esc_url( $dsb_endpoint ); ?>" class="dsb-inline-form">
			<?php wp_nonce_field( DSB_Admin::NONCE ); ?>
			<input type="hidden" name="action" value="dsb_handle">
			<input type="hidden" name="dsb_tab" value="log">
			<button type="submit" name="dsb_action" value="clear_log" class="button button-small"
				onclick="return confirm('<?php echo esc_js( __( 'همه‌ی تاریخچه پاک شود؟', 'daily-bale-post' ) ); ?>');">
				🗑️ <?php esc_html_e( 'پاک‌کردن تاریخچه', 'daily-bale-post' ); ?>
			</button>
		</form>

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
