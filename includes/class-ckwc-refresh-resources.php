<?php
/**
 * ConvertKit Admin Refresh Resources class.
 *
 * @package CKWC
 * @author ConvertKit
 */

/**
 * Registers scripts, which run when a Refresh button is clicked, to refresh resources
 * asynchronously whilst editing a WooCommerce Product.
 *
 * @package CKWC
 * @author ConvertKit
 */
class CKWC_Refresh_Resources {

	/**
	 * Registers action and filter hooks.
	 *
	 * @since   1.4.8
	 */
	public function __construct() {

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

	}

	/**
	 * Refreshes sequences, forms and tags from the API, returning them as a JSON string.
	 *
	 * @since   1.4.8
	 */
	public function refresh_resources() {

		// Define an array to store resources in.
		$resources = array();

		// Fetch forms.
		$forms              = new CKWC_Resource_Forms();
		$resources['forms'] = $forms->refresh();

		// Bail if an error occured.
		if ( is_wp_error( $resources['forms'] ) ) {
			return $resources['forms'];
		}

		// Fetch sequences.
		$sequences              = new CKWC_Resource_Sequences();
		$resources['sequences'] = $sequences->refresh();

		// Bail if an error occured.
		if ( is_wp_error( $resources['sequences'] ) ) {
			return $resources['sequences'];
		}

		// Fetch tags.
		$tags              = new CKWC_Resource_Tags();
		$resources['tags'] = $tags->refresh();

		// Bail if an error occured.
		if ( is_wp_error( $resources['tags'] ) ) {
			return $resources['tags'];
		}

		// Return resources as a zero based sequential array, so that JS retains the order of resources.
		$resources['forms']     = array_values( $resources['forms'] );
		$resources['sequences'] = array_values( $resources['sequences'] );
		$resources['tags']      = array_values( $resources['tags'] );

		return $resources;

	}

	/**
	 * Enqueue JavaScript when editing a Page, Post, Custom Post Type or Category.
	 *
	 * @since   1.4.8
	 *
	 * @param   string $hook   Hook.
	 */
	public function enqueue_scripts( $hook ) {

		// Bail if we are not on an Edit Product screen.
		if ( $hook !== 'edit.php' && $hook !== 'post-new.php' && $hook !== 'post.php' ) {
			return;
		}

		// Get integration.
		$integration = WP_CKWC_Integration();

		// Bail if the integration is not authenticated with OAuth and not enabled.
		if ( ! $integration->is_enabled() ) {
			return;
		}

		// Enqueue JS to perform AJAX request to refresh resources.
		wp_enqueue_script( 'ckwc-admin-refresh-resources', CKWC_PLUGIN_URL . 'resources/backend/js/refresh-resources.js', array( 'jquery' ), CKWC_PLUGIN_VERSION, true );
		wp_localize_script(
			'ckwc-admin-refresh-resources',
			'ckwc_admin_refresh_resources',
			array(
				'ajaxurl' => rest_url( 'kit/v1/woocommerce/resources/refresh' ),
				'debug'   => $integration->get_option_bool( 'debug' ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
			)
		);

	}

}
