<?php
/**
 * ConvertKit Admin Quick Edit class.
 *
 * @package CKWC
 * @author ConvertKit
 */

/**
 * Registers settings fields for output when using WordPress' Quick Edit functionality
 * in the WooCommerce Products WP_List_Table.
 *
 * @package CKWC
 * @author ConvertKit
 */
class CKWC_Admin_Quick_Edit {

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
		add_action( 'add_inline_data', array( $this, 'quick_edit_inline_data' ) );

	}

	/**
	 * Enqueues scripts and CSS for Quick Edit functionality in the Post, Page and Custom Post WP_List_Tables
	 *
	 * @since   1.4.8
	 */
	public function enqueue_assets() {

		// Bail if we're not on the Products WP_List_Table.
		if ( ! $this->is_product_wp_list_table_screen() ) {
			return;
		}

		// Enqueue JS.
		wp_enqueue_script( 'ckwc-admin-quick-edit', CKWC_PLUGIN_URL . 'resources/backend/js/quick-edit.js', array( 'jquery' ), CKWC_PLUGIN_VERSION, true );

	}

	/**
	 * Outputs hidden inline data in each Post's Title column, which the Quick Edit
	 * JS can read when the user clicks the Quick Edit link in a WP_List_Table.
	 *
	 * @since   1.4.8
	 *
	 * @param   WP_Post $post               Post.
	 */
	public function quick_edit_inline_data( $post ) {

		// Bail if we're not on the Products WP_List_Table.
		if ( $post->post_type !== 'product' ) {
			return;
		}

		// Fetch Product's Settings.
		$settings = array(
			'ckwc_subscription' => get_post_meta( $post->ID, 'ckwc_subscription', true ),
		);

		// Output the Product's ConvertKit settings as hidden data- attributes, which
		// the Quick Edit JS can read.
		foreach ( $settings as $key => $value ) {
			// If the value is blank, set it to zero.
			// This allows Quick Edit's JS to select the correct <option> value.
			if ( $value === '' ) {
				$value = 0;
			}
			?>
			<div class="ckwc" data-setting="<?php echo esc_attr( $key ); ?>" data-value="<?php echo esc_attr( $value ); ?>"><?php echo esc_attr( $value ); ?></div>
			<?php
		}

		// Output Quick Edit fields in the footer of the Administration screen.
		add_action( 'in_admin_footer', array( $this, 'quick_edit_fields' ), 10 );

	}

	/**
	 * Outputs Quick Edit settings fields in the footer of the administration screen.
	 *
	 * The Quick Edit JS will then move these hidden fields into the Quick Edit row
	 * when the user clicks on a Quick Edit link in the WP_List_Table.
	 *
	 * @since   1.4.8
	 */
	public function quick_edit_fields() {

		// Don't output Quick Edit fields if the API settings have not been defined.
		if ( ! $this->integration->is_enabled() ) {
			return;
		}

		// Get Forms, Tags and Sequences.
		$forms     = new CKWC_Resource_Forms();
		$sequences = new CKWC_Resource_Sequences();
		$tags      = new CKWC_Resource_Tags();

		// Get current subscription setting and other settings to render the subscription dropdown field.
		$subscription = array(
			'id'        => 'ckwc_subscription',
			'class'     => 'widefat',
			'name'      => 'ckwc_subscription',
			'value'     => '',
			'forms'     => $forms,
			'tags'      => $tags,
			'sequences' => $sequences,
		);

		// Output view.
		require_once CKWC_PLUGIN_PATH . '/views/backend/post-type/quick-edit.php';

	}

	/**
	 * Checks if the request is for viewing the WooCommerce Add / Edit Product screen.
	 *
	 * @since   1.4.8
	 *
	 * @return  bool
	 */
	private function is_product_wp_list_table_screen() {

		// Return false if we cannot reliably determine the current screen that is viewed,
		// due to WordPress' get_current_screen() function being unavailable.
		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		// Get screen.
		$screen = get_current_screen();

		// Return false if the current screen isn't set yet.
		if ( is_null( $screen ) ) {
			return false;
		}

		// Bail if we're not on a Post Type Edit screen.
		if ( $screen->base !== 'edit' ) {
			return false;
		}

		// Return false if we're not on the Product WP_List_Table.
		if ( $screen->post_type !== 'product' ) {
			return false;
		}

		return true;

	}

}
