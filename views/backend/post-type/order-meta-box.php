<?php
/**
 * Outputs a checkbox field to opt the customer in as a subscriber, assigning them any Forms,
 * Tags and Sequences defined at Plugin and Product level.
 *
 * @package CKWC
 * @author ConvertKit
 */

?>
<p>
	<label for="ckwc_opt_in">
		<?php esc_html_e( 'Opt In Customer', 'woocommerce-convertkit' ); ?>
	</label>
	<?php
	if ( $opted_in ) {
		?>
		<br />
		<strong>
		<?php esc_html_e( 'Opted In', 'woocommerce-convertkit' ); ?>
		</strong>
		<?php
	} else {
		?>
		<select name="ckwc_opt_in" id="ckwc_opt_in">
			<option value="yes" <?php selected( $opt_in, true ); ?>><?php esc_html_e( 'Yes', 'woocommerce-convertkit' ); ?></option>
			<option value="" <?php selected( $opt_in, false ); ?>><?php esc_html_e( 'No', 'woocommerce-convertkit' ); ?></option>
		</select>
		<?php
	}
	?>
</p>
<?php
if ( ! $opted_in ) {
	?>
	<p class="description">
		<?php
		esc_html_e( 'If enabled, the customer will be subscribed to forms, tags and sequences defined in the Plugin, Product and Coupons when the order is saved and the order status matches that defined in the Plugin settings.', 'woocommerce-convertkit' );
		?>
	</p>
	<?php
}

wp_nonce_field( 'ckwc', 'ckwc_nonce' );
