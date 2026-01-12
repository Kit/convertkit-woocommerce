<?php
/**
 * Outputs a dropdown field comprising of all Forms, Tags and Sequences for the ConvertKit account.
 *
 * @package CKWC
 * @author ConvertKit
 */

?>
<select class="<?php echo esc_attr( $subscription['class'] ); ?>" id="<?php echo esc_attr( $subscription['id'] ); ?>" name="<?php echo esc_attr( $subscription['name'] ); ?>">
	<?php
	// If Bulk Edit is true, add a No Change option and select it.
	if ( array_key_exists( 'is_bulk_edit', $subscription ) && $subscription['is_bulk_edit'] === true ) {
		?>
		<option value="-1" data-preserve-on-refresh="1"<?php selected( '', $subscription['value'] ); ?>><?php esc_html_e( '— No Change —', 'woocommerce-convertkit' ); ?></option>
		<?php
	}
	?>

	<option <?php selected( '', $subscription['value'] ); ?> value="" data-preserve-on-refresh="1">
		<?php esc_html_e( 'Select a subscription option...', 'woocommerce-convertkit' ); ?>
	</option>

	<?php
	if ( isset( $subscription['sequences'] ) ) {
		?>
		<optgroup label="<?php esc_attr_e( 'Sequences', 'woocommerce-convertkit' ); ?>" id="ckwc-sequences" data-option-value-prefix="course:">
			<?php
			if ( $subscription['sequences']->exist() ) {
				foreach ( $subscription['sequences']->get() as $sequence ) {
					// 'course:' is deliberate for backward compat. functionality when Sequences used to be called Courses.
					?>
					<option value="course:<?php echo esc_attr( $sequence['id'] ); ?>"<?php selected( 'course:' . esc_attr( $sequence['id'] ), $subscription['value'] ); ?>><?php echo esc_html( $sequence['name'] ); ?></option>
					<?php
				}
			}
			?>
		</optgroup>
		<?php
	}

	if ( isset( $subscription['forms'] ) ) {
		?>
		<optgroup label="<?php esc_attr_e( 'Forms', 'woocommerce-convertkit' ); ?>" id="ckwc-forms" data-option-value-prefix="form:">
			<?php
			if ( $subscription['forms']->exist() ) {
				foreach ( $subscription['forms']->get() as $form ) {
					?>
					<option value="form:<?php echo esc_attr( $form['id'] ); ?>"<?php selected( 'form:' . esc_attr( $form['id'] ), $subscription['value'] ); ?>><?php echo esc_html( $form['name'] ); ?></option>
					<?php
				}
			}
			?>
		</optgroup>
		<?php
	}

	if ( isset( $subscription['tags'] ) ) {
		?>
		<optgroup label="<?php esc_attr_e( 'Tags', 'woocommerce-convertkit' ); ?>" id="ckwc-tags" data-option-value-prefix="tag:">
			<?php
			if ( $subscription['tags']->exist() ) {
				foreach ( $subscription['tags']->get() as $convertkit_tag ) {
					?>
					<option value="tag:<?php echo esc_attr( $convertkit_tag['id'] ); ?>"<?php selected( 'tag:' . esc_attr( $convertkit_tag['id'] ), $subscription['value'] ); ?>><?php echo esc_html( $convertkit_tag['name'] ); ?></option>
					<?php
				}
			}
			?>
		</optgroup>
		<?php
	}
	?>
</select>
