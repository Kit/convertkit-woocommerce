<?php
/**
 * ConvertKit Admin AJAX class.
 *
 * @package CKWC
 * @author ConvertKit
 */

/**
 * Registers an AJAX action in WordPress used by the synchronous AJAX
 * script to send a WooCommerce Order to ConvertKit.
 *
 * @package CKWC
 * @author ConvertKit
 */
class CKWC_Admin_AJAX {

	/**
	 * Holds the WooCommerce Integration instance for this Plugin.
	 *
	 * @since   1.4.5
	 *
	 * @var     CKWC_Integration
	 */
	private $integration;

	/**
	 * Constructor
	 *
	 * @since   1.0.0
	 */
	public function __construct() {

		add_action( 'wp_ajax_ckwc_sync_past_orders', array( $this, 'sync_past_orders' ) );

	}

	/**
	 * Called by AJAX to send the given Order ID to ConvertKit.
	 *
	 * @since   1.4.3
	 */
	public function sync_past_orders() {

		// Validate nonce.
		check_ajax_referer( 'ckwc_sync_past_orders', 'nonce' );

		// Check that required request parameters exist.
		if ( ! isset( $_REQUEST['id'] ) ) {
			wp_send_json_error( __( 'The \'id\' parameter is missing from the request.', 'woocommerce-convertkit' ) );
		}

		// Get ID.
		$id = absint( sanitize_text_field( wp_unslash( $_REQUEST['id'] ) ) );

		// Fetch integration.
		$this->integration = WP_CKWC_Integration();

		// Send purchase data for this Order to ConvertKit.
		// We deliberately set the old status and new status to be different, and the new status to match
		// the integration's Purchase Data Event setting, otherwise the Order won't be sent to ConvertKit's Purchase Data.
		$result = WP_CKWC()->get_class( 'order' )->send_purchase_data(
			$id,
			'new', // old status.
			$this->integration->get_option( 'send_purchases_event' ) // new status.
		);

		// Return a JSON error if the result is a WP_Error.
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		// Return JSON success.
		wp_send_json_success(
			sprintf(
				/* translators: %1$s: WooCommerce Order ID, %2$s: ConvertKit API Purchase ID */
				__( 'WooCommerce Order ID #%1$s added to Kit Purchase Data successfully. Kit Purchase ID: #%2$s', 'woocommerce-convertkit' ),
				$id,
				get_post_meta( $id, 'ckwc_purchase_data_id', true )
			)
		);

	}

}
