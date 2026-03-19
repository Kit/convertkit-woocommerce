<?php
/**
 * Outputs a dropdown field comprising of all Forms, Tags and Sequences for the ConvertKit account.
 *
 * @package CKWC
 * @author ConvertKit
 */

?>
<div class="ckwc-select2-container ckwc-select2-container-grid">
	<?php
	// Load subscription dropdown field.
	require CKWC_PLUGIN_PATH . '/views/backend/subscription-dropdown-field.php';
	?>

	<button class="ckwc-refresh-resources" class="button button-secondary" title="<?php esc_attr_e( 'Refresh sequences, forms and tags from Kit account', 'woocommerce-convertkit' ); ?>" data-field="#<?php echo esc_attr( $subscription['id'] ); ?>">
		<span class="dashicons dashicons-update"></span>
	</button>
</div>

<p class="description">
	<?php
	switch ( $post->post_type ) {
		case 'product':
			esc_html_e( 'The Kit form, tag or sequence to subscribe customers to who purchase this product.', 'woocommerce-convertkit' );
			break;
		case 'shop_coupon':
			esc_html_e( 'The Kit form, tag or sequence to subscribe customers to who use this coupon.', 'woocommerce-convertkit' );
			break;
	}
	?>
</p>

<?php
wp_nonce_field( 'ckwc', 'ckwc_nonce' );
