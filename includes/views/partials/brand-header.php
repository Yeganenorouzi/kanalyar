<?php
/**
 * هدر برندینگ، فقط وقتی پروفایلی فعال باشد.
 *
 * @package DailyBalePost
 */

defined( 'ABSPATH' ) || exit;

$dsb_brand_name = DSB_Branding::get( 'name' );
$dsb_brand_logo = DSB_Branding::logo_url();
$dsb_brand_site = DSB_Branding::get( 'site' );
$dsb_brand_tel  = DSB_Branding::get( 'phone' );
$dsb_brand_lbl  = DSB_Branding::get( 'label', __( 'افزونه‌ی انتشار خودکار', 'daily-bale-post' ) );
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
