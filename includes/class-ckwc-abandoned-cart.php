<?php
/**
 * ConvertKit Abandoned Cart class.
 *
 * @package CKWC
 * @author ConvertKit
 */

/**
 * Determines whether to track an abandoned cart on the WooCommerce Cart,
 * based on the integration's settings.
 *
 * @package CKWC
 */
class CKWC_Abandoned_Cart {

	/**
	 * Holds the WooCommerce Integration instance for this Plugin.
	 *
	 * @since   2.0.5
	 *
	 * @var     CKWC_Integration
	 */
	private $integration;

	/**
	 * Constructor
	 *
	 * @since   2.0.5
	 */
	public function __construct() {

		// Fetch integration.
		$this->integration = WP_CKWC_Integration();

		// If the integration isn't enabled, don't load any other actions or filters.
		if ( ! $this->integration->is_enabled() ) {
			return;
		}

		// If Abandoned Cart isn't enabled, don't load any other actions or filters.
		if ( ! $this->integration->get_option_bool( 'abandoned_cart' ) ) {
			return;
		}

		// If this site isn't registered with the Kit App Store app server, don't load any other actions or filters.
		if ( empty( $this->integration->get_option( 'app_server_site_secret' ) ) ) {
			return;
		}

		// Track the abandoned cart when products are added, edited or removed from the cart.
		add_action( 'woocommerce_add_to_cart', array( $this, 'track_abandoned_cart' ) );
		add_action( 'woocommerce_after_cart_item_quantity_update', array( $this, 'track_abandoned_cart' ) );
		add_action( 'woocommerce_cart_item_removed', array( $this, 'track_abandoned_cart' ) );

		// Track the abandoned cart when the cart is loaded.
		add_action( 'woocommerce_before_cart', array( $this, 'track_abandoned_cart' ) );

		// Track the abandoned cart when the checkout is loaded.
		add_action( 'woocommerce_checkout_init', array( $this, 'track_abandoned_cart' ) );

		// Store the abandoned cart email in the session when entered at checkout.
		add_action( 'wp_ajax_ckwc_abandoned_cart_email', array( $this, 'store_abandoned_cart_email' ) );
		add_action( 'wp_ajax_nopriv_ckwc_abandoned_cart_email', array( $this, 'store_abandoned_cart_email' ) );

		// Clear the abandoned cart when the checkout is completed.
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'clear_abandoned_cart' ), 10, 3 );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'clear_abandoned_cart_block' ) );

		// Enqueue the abandoned cart script.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

	}

	/**
	 * Track the abandoned cart.
	 *
	 * @since   2.0.5
	 */
	public function track_abandoned_cart() {

		// Get cart.
		$cart = WC()->cart;

		// Bail if the cart is not initialized.
		if ( ! $cart ) { // @phpstan-ignore-line
			return;
		}

		// If the cart is empty, remove the abandoned cart flag.
		if ( $cart->is_empty() ) {
			WC()->session->__unset( 'ckwc_abandoned_cart_timestamp' );
			return;
		}

		// Update the abandoned cart flag timestamp.
		WC()->session->set(
			'ckwc_abandoned_cart_timestamp',
			time()
		);

	}

	/**
	 * Store the abandoned cart email in the session, if entered at checkout.
	 *
	 * @since   2.0.5
	 */
	public function store_abandoned_cart_email() {

		// Check the nonce.
		check_ajax_referer( 'ckwc_abandoned_cart', 'nonce' );

		// Get the email.
		if ( ! isset( $_POST['email'] ) ) {
			wp_send_json_error( __( 'Email is required.', 'woocommerce-convertkit' ) );
		}

		$email = sanitize_email( wp_unslash( $_POST['email'] ) );

		// If the email is empty or not a valid email, die.
		if ( empty( $email ) || ! is_email( $email ) ) {
			wp_send_json_error( __( 'Invalid email.', 'woocommerce-convertkit' ) );
		}

		// Add email to the session.
		WC()->session->set(
			'ckwc_abandoned_cart_email',
			$email
		);

		// Update the abandoned cart flag timestamp.
		WC()->session->set(
			'ckwc_abandoned_cart_timestamp',
			time()
		);

		wp_send_json_success();

	}

	/**
	 * Clear the abandoned cart session data, and send the product purchased event to Kit
	 * if the customer is a subscriber.
	 *
	 * @since   2.0.5
	 *
	 * @param   int      $order_id     WooCommerce Order ID.
	 * @param   array    $posted_data  Posted data.
	 * @param   WC_Order $order        WooCommerce Order object.
	 */
	public function clear_abandoned_cart( $order_id, $posted_data, $order ) {

		// Clear the abandoned cart data.
		WC()->session->__unset( 'ckwc_abandoned_cart_email' );
		WC()->session->__unset( 'ckwc_abandoned_cart_timestamp' );

		// Setup the API.
		$api = new CKWC_API(
			CKWC_OAUTH_CLIENT_ID,
			CKWC_OAUTH_CLIENT_REDIRECT_URI,
			$this->integration->get_option( 'access_token' ),
			$this->integration->get_option( 'refresh_token' ),
			$this->integration->get_option_bool( 'debug' )
		);

		// Send the product purchased event for Kit's automation event node, if the customer is a subscriber.
		// We use the order's email, as the Action Scheduler may have already cleared the session's email.
		$subscriber_id = $api->get_subscriber_id( $order->get_billing_email() );
		if ( $subscriber_id && ! is_wp_error( $subscriber_id ) ) {
			$this->integration->send_automation_event( 'product_purchased', $subscriber_id, ckwc_get_order_context( $order ) );
		}

	}

	/**
	 * Clear the abandoned cart when the checkout is completed using the Checkout block.
	 *
	 * @since   2.2.3
	 *
	 * @param   WC_Order $order  WooCommerce Order object.
	 */
	public function clear_abandoned_cart_block( $order ) {

		$this->clear_abandoned_cart( $order->get_id(), array(), $order );

	}

	/**
	 * Enqueue the abandoned cart script.
	 *
	 * @since   2.0.5
	 */
	public function enqueue_scripts() {

		// Don't enqueue scripts if not on the checkout page.
		if ( ! is_checkout() ) {
			return;
		}

		// Enqueue the script.
		wp_enqueue_script(
			'ckwc-abandoned-cart',
			CKWC_PLUGIN_URL . 'resources/frontend/js/abandoned-cart.js',
			array( 'jquery' ),
			CKWC_PLUGIN_VERSION,
			true
		);

		// Localize script with AJAX URL and nonce.
		wp_localize_script(
			'ckwc-abandoned-cart',
			'ckwc_abandoned_cart',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'ckwc_abandoned_cart' ),
			)
		);

	}

}
