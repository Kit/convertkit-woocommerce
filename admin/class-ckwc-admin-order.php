<?php
/**
 * ConvertKit Admin Order class.
 *
 * @package CKWC
 * @author ConvertKit
 */

/**
 * Registers a metabox on WooCommerce Orders and saves its settings when the
 * Order is saved in the WordPress Administration interface.
 *
 * @package CKWC
 * @author ConvertKit
 */
class CKWC_Admin_Order extends CKWC_Admin_Post_Type {

	/**
	 * The Post Type to register the metabox and settings against.
	 *
	 * @since   2.1.0
	 *
	 * @var     string
	 */
	public $post_type = 'woocommerce_page_wc-orders';

	/**
	 * The Meta Key to store the settings for the Post Type.
	 *
	 * @since   2.1.0
	 *
	 * @var     string
	 */
	public $meta_key = 'ckwc_opt_in';

	/**
	 * Constructor
	 *
	 * @since   2.1.0
	 */
	public function __construct() {

		// Register the meta box and save its settings on non-HPOS order screens.
		add_action( 'add_meta_boxes_shop_order', array( $this, 'add_meta_boxes' ) );
		add_action( 'woocommerce_process_shop_order_meta', array( $this, 'save' ) );

		// Call parent constructor.
		parent::__construct();

	}

	/**
	 * Displays a meta box on WooCommerce Order screen.
	 *
	 * @since   2.1.0
	 *
	 * @param   WC_Order|WP_Post $order   Order.
	 */
	public function display_meta_box( $order ) {

		// Fetch the Order.
		// WooCommerce supplies a WC_Order when HPOS is enabled, whereas WordPress supplies a
		// WP_Post when HPOS is disabled and Orders are stored as Posts.
		$order = wc_get_order( $order );

		// Bail if the Order could not be fetched.
		if ( ! $order ) {
			return;
		}

		// Get order meta.
		$opt_in   = $order->get_meta( 'ckwc_opt_in', true ) === 'yes' ? true : false;
		$opted_in = $order->get_meta( 'ckwc_opted_in', true ) === 'yes' ? true : false;

		// Load meta box view.
		require_once CKWC_PLUGIN_PATH . '/views/backend/post-type/order-meta-box.php';

	}

	/**
	 * Saves the opt in setting for the WooCommerce Order.
	 *
	 * @since   2.1.0
	 *
	 * @param   int $order_id    Order ID.
	 */
	public function save( $order_id ) {

		// Bail if no nonce field exists.
		if ( ! isset( $_POST['ckwc_nonce'] ) ) {
			return;
		}

		// Bail if the nonce verification fails.
		if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['ckwc_nonce'] ) ), 'ckwc' ) ) {
			return;
		}

		// Bail if no opt in value exists.
		if ( ! isset( $_POST['ckwc_opt_in'] ) ) {
			return;
		}

		// Get order.
		$order = wc_get_order( $order_id );

		// Get opt in value.
		$ckwc_opt_in = ( $_POST['ckwc_opt_in'] === 'yes' ? 'yes' : '' );

		// Update metadata.
		$order->update_meta_data( $this->meta_key, $ckwc_opt_in );
		$order->save();

	}

}
