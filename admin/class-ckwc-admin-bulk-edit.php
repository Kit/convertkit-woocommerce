<?php
/**
 * ConvertKit Admin Bulk Edit class.
 *
 * @package CKWC
 * @author ConvertKit
 */

/**
 * Registers settings fields for output when using WordPress' Bulk Edit functionality
 * in the WooCommerce Product and Coupon WP_List_Tables.
 *
 * @package CKWC
 * @author ConvertKit
 */
class CKWC_Admin_Bulk_Edit {

	/**
	 * Holds the WooCommerce Integration instance for this Plugin.
	 *
	 * @since   1.4.8
	 *
	 * @var     CKWC_Integration
	 */
	private $integration;

	/**
	 * Registers action and filter hooks.
	 *
	 * @since   1.4.8
	 */
	public function __construct() {

		// Fetch integration.
		$this->integration = WP_CKWC_Integration();

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'load-edit.php', array( $this, 'bulk_edit_save' ) );

	}

	/**
	 * Enqueues scripts and CSS for Bulk Edit functionality in the WooCommerce WP_List_Table
	 *
	 * @since   1.4.8
	 */
	public function enqueue_assets() {

		// Bail if we're not on a supported WP_List_Table.
		if ( ! $this->is_supported_wp_list_table_screen() ) {
			return;
		}

		// Enqueue JS.
		wp_enqueue_script( 'ckwc-bulk-edit', CKWC_PLUGIN_URL . 'resources/backend/js/bulk-edit.js', array( 'jquery' ), CKWC_PLUGIN_VERSION, true );

		// Enqueue CSS.
		wp_enqueue_style( 'ckwc-bulk-quick-edit', CKWC_PLUGIN_URL . 'resources/backend/css/bulk-quick-edit.css', array(), CKWC_PLUGIN_VERSION );

		// Output Bulk Edit fields in the footer of the Administration screen.
		add_action( 'in_admin_footer', array( $this, 'bulk_edit_fields' ), 10 );

	}

	/**
	 * Save Bulk Edit data.
	 *
	 * Logic used here follows how WordPress handles bulk editing in bulk_edit_posts().
	 *
	 * @since   2.0.0
	 */
	public function bulk_edit_save() {

		// Bail if the bulk action isn't 'edit'.
		if ( ! $this->is_bulk_edit_request() ) {
			return;
		}

		// Bail if no nonce field exists.
		if ( ! isset( $_REQUEST['ckwc_nonce'] ) ) {
			return;
		}

		// Bail if the nonce verification fails.
		if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['ckwc_nonce'] ) ), 'ckwc' ) ) {
			return;
		}

		// Bail if no Form / Tag option exists in request data.
		if ( ! isset( $_REQUEST['ckwc_subscription'] ) ) {
			return;
		}

		// Bail if the value is -1, as this means don't change the setting.
		if ( sanitize_text_field( wp_unslash( $_REQUEST['ckwc_subscription'] ) ) === '-1' ) {
			return;
		}

		// Bail if the Post Type or Post IDs are not specified.
		if ( ! isset( $_REQUEST['post_type'] ) || ! isset( $_REQUEST['post'] ) ) {
			return;
		}

		// Get Post Type object.
		$post_type = get_post_type_object( sanitize_text_field( wp_unslash( $_REQUEST['post_type'] ) ) );

		// Bail if the logged in user cannot edit Pages/Posts.
		if ( ! current_user_can( $post_type->cap->edit_posts ) ) {
			wp_die(
				sprintf(
					/* translators: Post Type name */
					esc_html__( 'Sorry, you are not allowed to edit %s.', 'woocommerce-convertkit' ),
					esc_html( $post_type->name )
				)
			);
		}

		// Get Post IDs that are bulk edited.
		$post_ids = array_map( 'intval', (array) $_REQUEST['post'] );

		// Iterate through each Post, updating its settings.
		foreach ( $post_ids as $post_id ) {
			update_post_meta( $post_id, 'ckwc_subscription', sanitize_text_field( wp_unslash( $_REQUEST['ckwc_subscription'] ) ) );
		}

	}

	/**
	 * Outputs Bulk Edit settings fields in the footer of the administration screen.
	 *
	 * The Bulk Edit JS will then move these hidden fields into the Bulk Edit row
	 * when the user clicks on a Bulk Edit action in the WP_List_Table.
	 *
	 * @since   1.4.8
	 */
	public function bulk_edit_fields() {

		// Don't output Bulk Edit fields if the API settings have not been defined.
		if ( ! $this->integration->is_enabled() ) {
			return;
		}

		// Get Forms, Tags and Sequences.
		$forms     = new CKWC_Resource_Forms();
		$sequences = new CKWC_Resource_Sequences();
		$tags      = new CKWC_Resource_Tags();

		// Get current subscription setting and other settings to render the subscription dropdown field.
		$subscription = array(
			'id'           => 'ckwc_subscription',
			'class'        => 'widefat',
			'name'         => 'ckwc_subscription',
			'value'        => '-1', // Select 'No Change' option as default.
			'forms'        => $forms,
			'tags'         => $tags,
			'sequences'    => $sequences,
			'is_bulk_edit' => true,
		);

		// Output view.
		require_once CKWC_PLUGIN_PATH . '/views/backend/post-type/bulk-edit.php';

	}

	/**
	 * Checks if the request is for viewing a list of WooCommerce Products or Coupons.
	 *
	 * @since   1.4.8
	 *
	 * @return  bool
	 */
	private function is_supported_wp_list_table_screen() {

		// Return false if we cannot reliably determine the current screen that is viewed,
		// due to WordPress' get_current_screen() function being unavailable.
		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		// Get screen.
		$screen = get_current_screen();

		// Bail if we're not on a Post Type Edit screen.
		if ( $screen->base !== 'edit' ) {
			return false;
		}

		// Return false if we're not on the Product or Coupon WP_List_Table.
		if ( $screen->post_type !== 'product' && $screen->post_type !== 'shop_coupon' ) {
			return false;
		}

		return true;

	}

	/**
	 * Determines if the request is for saving values via bulk editing.
	 *
	 * @since   1.4.8
	 *
	 * @return  bool    Is bulk edit request
	 */
	private function is_bulk_edit_request() {

		// Determine the current bulk action, if any.
		$wp_list_table = _get_list_table( 'WP_Posts_List_Table' );
		$bulk_action   = $wp_list_table->current_action();

		// Bail if the bulk action isn't edit.
		if ( $bulk_action !== 'edit' ) {
			return false;
		}
		if ( ! array_key_exists( 'bulk_edit', $_REQUEST ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return false;
		}

		return true;

	}

}
