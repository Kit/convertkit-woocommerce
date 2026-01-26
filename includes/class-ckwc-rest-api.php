<?php
/**
 * Kit REST API class.
 *
 * @package CKWC
 * @author ConvertKit
 */

/**
 * Registers a REST API route in WordPress used by the synchronous AJAX
 * script to send a WooCommerce Order to Kit.
 *
 * @package CKWC
 * @author ConvertKit
 */
class CKWC_REST_API {

	/**
	 * Holds the WooCommerce Integration instance for this Plugin.
	 *
	 * @since   2.0.6
	 *
	 * @var     CKWC_Integration
	 */
	private $integration;

	/**
	 * Constructor
	 *
	 * @since   2.0.6
	 */
	public function __construct() {

		add_action( 'rest_api_init', array( $this, 'register_routes' ) );

	}

	/**
	 * Register REST API routes.
	 *
	 * @since   2.0.6
	 */
	public function register_routes() {

		// Register route to send a WooCommerce Order to Kit.
		register_rest_route(
			'kit/v1',
			'/woocommerce/order/send/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'args'                => array(
					'id' => array(
						'validate_callback' => function ( $param ) {

							return is_numeric( $param );

						},
					),
				),
				'callback'            => function ( $request ) {

					// Get ID.
					$id = $request->get_param( 'id' );

					// Fetch integration.
					$this->integration = WP_CKWC_Integration();

					// Send purchase data for this Order to Kit.
					// We deliberately set the old status and new status to be different, and the new status to match
					// the integration's Purchase Data Event setting, otherwise the Order won't be sent to Kit's Purchase Data.
					$result = WP_CKWC()->get_class( 'order' )->send_purchase_data(
						$id,
						'new', // old status.
						$this->integration->get_option( 'send_purchases_event' ) // new status.
					);

					// Return a JSON error if the result is a WP_Error.
					if ( is_wp_error( $result ) ) {
						return rest_ensure_response(
							array(
								'success' => false,
								'data'    => $result->get_error_message(),
							)
						);
					}

					// Return JSON success.
					return rest_ensure_response(
						array(
							'success' => true,
							'data'    => sprintf(
								/* translators: %1$s: WooCommerce Order ID, %2$s: Kit API Purchase ID */
								__( 'WooCommerce Order ID #%1$s added to Kit Purchase Data successfully. Kit Purchase ID: #%2$s', 'woocommerce-convertkit' ),
								$id,
								get_post_meta( $id, 'ckwc_purchase_data_id', true )
							),
						)
					);

				},
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);

	}

}
