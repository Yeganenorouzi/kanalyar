<?php
/**
 * هدر برندینگ، فقط وقتی پروفایلی فعال باشد.
 *
 * @package Kanalyar
 */

defined( 'ABSPATH' ) || exit;

$ky_brand_name = KY_Branding::get( 'name' );
$ky_brand_logo = KY_Branding::logo_url();
$ky_brand_site = KY_Branding::get( 'site' );
$ky_brand_tel  = KY_Branding::get( 'phone' );
$ky_brand_lbl  = KY_Branding::get( 'label', __( 'افزونه‌ی انتشار خودکار', 'kanalyar' ) );
?>
<div class="ky-cta">
	<div class="ky-cta-brand">
		<?php if ( $ky_brand_logo ) : ?>
			<div class="ky-cta-logo">
				<img src="<?php echo esc_url( $ky_brand_logo ); ?>" alt="<?php echo esc_attr( $ky_brand_name ); ?>">
			</div>
		<?php endif; ?>
		<div class="ky-cta-text">
			<strong><?php echo esc_html( $ky_brand_lbl ); ?></strong>
			<span>
				<?php
				printf(
					/* translators: %s: نام آژانس یا فروشنده. */
					esc_html__( 'طراحی و توسعه توسط %s', 'kanalyar' ),
					esc_html( $ky_brand_name )
				);
				?>
			</span>
		</div>
	</div>
	<div class="ky-cta-actions">
		<?php if ( $ky_brand_tel ) : ?>
			<a href="tel:<?php echo esc_attr( $ky_brand_tel ); ?>" dir="ltr">📞 <?php echo esc_html( $ky_brand_tel ); ?></a>
		<?php endif; ?>
		<?php if ( $ky_brand_site ) : ?>
			<a href="<?php echo esc_url( $ky_brand_site ); ?>" target="_blank" rel="noopener noreferrer">
				🌐 <?php echo esc_html( wp_parse_url( $ky_brand_site, PHP_URL_HOST ) ); ?>
			</a>
		<?php endif; ?>
	</div>
</div>
