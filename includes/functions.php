<?php
/**
 * ConvertKit for WooCommerce general plugin functions.
 *
 * @package CKWC
 * @author ConvertKit
 */

/**
 * Runs the activation and update routines when the plugin is activated.
 *
 * @since   1.9.8
 *
 * @param   bool $network_wide   Is network wide activation.
 */
function ckwc_plugin_activate( $network_wide ) {

	// Check if we are on a multisite install, activating network wide, or a single install.
	if ( ! is_multisite() || ! $network_wide ) {
		// Single Site activation.
		ckwc_schedule_refresh_token_event();
	} else {
		// Multisite network wide activation.
		$sites = get_sites(
			array(
				'number' => 0,
			)
		);
		foreach ( $sites as $site ) {
			switch_to_blog( (int) $site->blog_id );
			ckwc_schedule_refresh_token_event();
			restore_current_blog();
		}
	}

}

/**
 * Runs the activation and update routines when the plugin is activated
 * on a WordPress multisite setup.
 *
 * @since   1.9.8
 *
 * @param   WP_Site|int $site_or_blog_id    WP_Site or Blog ID.
 */
function ckwc_plugin_activate_new_site( $site_or_blog_id ) {

	// Check if $site_or_blog_id is a WP_Site or a blog ID.
	if ( is_a( $site_or_blog_id, 'WP_Site' ) ) {
		$site_or_blog_id = $site_or_blog_id->blog_id;
	}

	// Run activation routine.
	switch_to_blog( $site_or_blog_id );
	ckwc_schedule_refresh_token_event();
	restore_current_blog();

}

/**
 * Runs the deactivation routine when the plugin is deactivated.
 *
 * @since   1.9.8
 *
 * @param   bool $network_wide   Is network wide deactivation.
 */
function ckwc_plugin_deactivate( $network_wide ) {

	// Check if we are on a multisite install, activating network wide, or a single install.
	if ( ! is_multisite() || ! $network_wide ) {
		// Single Site deactivation.
		ckwc_unschedule_refresh_token_event();
	} else {
		// Multisite network wide deactivation.
		$sites = get_sites(
			array(
				'number' => 0,
			)
		);
		foreach ( $sites as $site ) {
			switch_to_blog( (int) $site->blog_id );
			wp_clear_scheduled_hook( 'ckwc_refresh_token' );
			restore_current_blog();
		}
	}

}

/**
 * Helper method to return the Plugin Settings Link
 *
 * @since   1.4.2
 *
 * @param   array $query_args     Optional Query Args.
 * @return  string                  Settings Link
 */
function ckwc_get_settings_link( $query_args = array() ) {

	$query_args = array_merge(
		$query_args,
		array(
			'page'    => 'wc-settings',
			'tab'     => 'integration',
			'section' => 'ckwc',
		)
	);

	return add_query_arg( $query_args, admin_url( 'admin.php' ) );

}

/**
 * Helper method to enqueue Select2 scripts for use within the ConvertKit Plugin.
 *
 * @since   1.4.3
 */
function ckwc_select2_enqueue_scripts() {

	wp_enqueue_script( 'ckwc-select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array( 'jquery' ), CKWC_PLUGIN_VERSION, false );
	wp_enqueue_script( 'ckwc-admin-select2', CKWC_PLUGIN_URL . 'resources/backend/js/select2.js', array( 'ckwc-select2' ), CKWC_PLUGIN_VERSION, false );

}

/**
 * Helper method to enqueue Select2 stylesheets for use within the ConvertKit Plugin.
 *
 * @since   1.4.3
 */
function ckwc_select2_enqueue_styles() {

	wp_enqueue_style( 'ckwc-select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), CKWC_PLUGIN_VERSION );
	wp_enqueue_style( 'ckwc-admin-select2', CKWC_PLUGIN_URL . 'resources/backend/css/select2.css', array(), CKWC_PLUGIN_VERSION );

}
