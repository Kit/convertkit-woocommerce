<?php
/**
 * ConvertKit Admin Post Type class.
 *
 * @package CKWC
 * @author ConvertKit
 */

/**
 * Registers a metabox on a WooCommerce Post Type, and handles saving its settings.
 * Used primarily by Post Type classes to register the ConvertKit Integration setting,
 * allowing a form, tag or sequence to be chosen for assignment to the purchase data
 * based on the product or coupon used.
 *
 * @package CKWC
 * @author ConvertKit
 */
class CKWC_Admin_Post_Type {

	/**
	 * The Post Type to register the metabox and settings against.
	 *
	 * @since   1.5.9
	 *
	 * @var     string
	 */
	public $post_type = '';

	/**
	 * Holds the WooCommerce Integration instance for this Plugin.
	 *
	 * @since   1.5.9
	 *
	 * @var     CKWC_Integration
	 */
	private $integration;

	/**
	 * Constructor
	 *
	 * @since   1.5.9
	 */
	public function __construct() {

		// Fetch integration.
		$this->integration = WP_CKWC_Integration();

		// Register JS and CSS.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );

		// Add meta box for the Post Type.
		add_action( 'add_meta_boxes_' . $this->post_type, array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_' . $this->post_type, array( $this, 'save' ) );

	}

	/**
	 * Enqueue Javascript for the WooCommerce Add / Edit screen for the Post Type.
	 *
	 * @since   1.4.3
	 */
	public function enqueue_scripts() {

		// Bail if we're not on the Add / Edit screen for the Post Type.
		if ( ! $this->is_edit_screen() ) {
			return;
		}

		// Enqueue Select2 JS.
		ckwc_select2_enqueue_scripts();

	}

	/**
	 * Enqueue CSS for the WooCommerce Add / Edit screen for the Post Type.
	 *
	 * @since   1.4.3
	 */
	public function enqueue_styles() {

		// Bail if we're not on the Add / Edit Post Type's screen.
		if ( ! $this->is_edit_screen() ) {
			return;
		}

		// Enqueue Select2 CSS.
		ckwc_select2_enqueue_styles();

		// Enqueue Refresh Resources CSS.
		wp_enqueue_style( 'ckwc-refresh-resources', CKWC_PLUGIN_URL . 'resources/backend/css/refresh-resources.css', array(), CKWC_PLUGIN_VERSION );

	}

	/**
	 * Adds a meta box on WooCommerce Post Type screen to choose a Sequence, Form or Tag to subscribe the Customer to,
	 * overriding the Plugin default's Sequence, Form or Tag.
	 *
	 * @since   1.0.0
	 */
	public function add_meta_boxes() {

		add_meta_box( 'ckwc', __( 'Kit Integration', 'woocommerce-convertkit' ), array( $this, 'display_meta_box' ), null, 'side', 'default' );

	}

	/**
	 * Displays a meta box on WooCommerce Post Type screen to choose a Sequence, Form or Tag to subscribe the Customer to,
	 * overriding the Plugin default's Sequence, Form or Tag.
	 *
	 * @since   1.0.0
	 *
	 * @param   WP_Post $post   Post Type (e.g. Product, Coupon).
	 */
	public function display_meta_box( $post ) {

		// If the integration isn't enabled, show a message instead.
		if ( ! $this->integration->is_enabled() ) {
			$post_type = get_post_type_object( $post->post_type );
			require_once CKWC_PLUGIN_PATH . '/views/backend/post-type/disabled.php';
			return;
		}

		// Get Forms, Tags and Sequences.
		$forms     = new CKWC_Resource_Forms();
		$sequences = new CKWC_Resource_Sequences();
		$tags      = new CKWC_Resource_Tags();

		// Get current subscription setting and other settings to render the subscription dropdown field.
		$subscription = array(
			'id'        => 'ckwc_subscription',
			'class'     => 'ckwc-select2 widefat',
			'name'      => 'ckwc_subscription',
			'value'     => get_post_meta( $post->ID, 'ckwc_subscription', true ),
			'forms'     => $forms,
			'tags'      => $tags,
			'sequences' => $sequences,
		);

		// Load meta box view.
		require_once CKWC_PLUGIN_PATH . '/views/backend/post-type/meta-box.php';

	}

	/**
	 * Saves the WooCommerce Post Type's Sequence, Form or Tag to subscribe the Customer to when
	 * either editing a WooCommerce Post Type, or using the Quick Edit functionality.
	 *
	 * @since   1.0.0
	 *
	 * @param   int $post_id    Post ID (e.g. Product or Coupon ID).
	 */
	public function save( $post_id ) {

		// If the integration isn't enabled, bail.
		if ( ! $this->integration->is_enabled() ) {
			return;
		}

		// Bail if this is an autosave.
		if ( wp_is_post_autosave( $post_id ) ) {
			return;
		}

		// Bail if this is a post revision.
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Bail if no nonce field exists.
		if ( ! isset( $_POST['ckwc_nonce'] ) ) {
			return;
		}

		// Bail if the nonce verification fails.
		if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['ckwc_nonce'] ) ), 'ckwc' ) ) {
			return;
		}

		// Bail if no Form / Tag option exists in POST data.
		if ( ! isset( $_POST['ckwc_subscription'] ) ) {
			return;
		}

		// Save Post's settings.
		update_post_meta( $post_id, 'ckwc_subscription', sanitize_text_field( wp_unslash( $_POST['ckwc_subscription'] ) ) );

	}

	/**
	 * Checks if the request is for viewing the WooCommerce Add / Edit's Post Type.
	 *
	 * @since   1.4.3
	 *
	 * @return  bool
	 */
	private function is_edit_screen() {

		// Return false if we cannot reliably determine the current screen that is viewed,
		// due to WordPress' get_current_screen() function being unavailable.
		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		// Get screen.
		$screen = get_current_screen();

		// Return false if we're not on the Add / Edit Post Type's screen.
		if ( $screen->id !== $this->post_type ) {
			return false;
		}

		return true;

	}

}
