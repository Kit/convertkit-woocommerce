<?php
/**
 * Outputs the Settings screen with an OAuth connection button.
 *
 * @package CKWC
 * @author ConvertKit
 */

?>
<div class="metabox-holder">
	<div id="oauth" class="postbox">
		<h2><?php esc_html_e( 'Connect to Kit', 'woocommerce-convertkit' ); ?></h2>

		<p class="description">
			<?php esc_html_e( 'For the Kit for WooCommerce Plugin to function, please connect your Kit account using the button below.', 'woocommerce-convertkit' ); ?><br />
		</p>

		<p class="submit">
			<a href="<?php echo esc_url( $api->get_oauth_url( ckwc_get_oauth_return_url(), get_site_url() ) ); ?>" class="button button-primary"><?php esc_html_e( 'Connect', 'woocommerce-convertkit' ); ?></a>
		</p>
	</div><!-- .postbox -->

	<?php
	wp_nonce_field( 'convertkit-settings-oauth', '_convertkit_settings_oauth_nonce' );
	?>
</div><!-- .metabox-holder -->
