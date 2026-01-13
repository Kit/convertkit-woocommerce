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
	 * Clear the abandoned cart session data, and removes the abandoned cart tag from the subscriber
	 * in Kit.
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

		// Get tag ID from the integration's setting.
		$subscription = $this->integration->get_option( 'abandoned_cart_subscription' );

		// Bail if no subscription is configured.
		if ( empty( $subscription ) ) {
			return;
		}

		// Get email address from order.
		// We don't use the session data here, as the Action Scheduler may have triggered the abandoned cart
		// function, which itself clears session data to ensure subscribers aren't repetitively tagged.
		$email = $order->get_billing_email();

		// Setup the API.
		$api = new CKWC_API(
			CKWC_OAUTH_CLIENT_ID,
			CKWC_OAUTH_CLIENT_REDIRECT_URI,
			$this->integration->get_option( 'access_token' ),
			$this->integration->get_option( 'refresh_token' ),
			$this->integration->get_option_bool( 'debug' )
		);

		// Get the resource type and ID.
		list( $resource_type, $tag_id ) = explode( ':', $subscription );

		// Remove tag from subscriber.
		$api->remove_tag_from_subscriber_by_email( absint( $tag_id ), $email );

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
