<?php
/**
 * ConvertKit for WooCommerce general plugin functions.
 *
 * @package CKWC
 * @author ConvertKit
 */

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

/**
 * Saves the new access token, refresh token and its expiry, and schedules
 * a WordPress Cron event to refresh the token on expiry.
 *
 * @since   2.0.3
 *
 * @param   array  $result      New Access Token, Refresh Token and Expiry.
 * @param   string $client_id   OAuth Client ID used for the Access and Refresh Tokens.
 */
function ckwc_maybe_update_credentials( $result, $client_id ) {

	// Don't save these credentials if they're not for this Client ID.
	// They're for another Kit Plugin that uses OAuth.
	if ( $client_id !== CKWC_OAUTH_CLIENT_ID ) {
		return;
	}

	// Bail if the integration is unavailable.
	if ( ! function_exists( 'WP_CKWC_Integration' ) ) {
		return;
	}

	WP_CKWC_Integration()->update_credentials( $result );

}

/**
 * Deletes the stored access token, refresh token and its expiry from the Plugin settings,
 * and clears any existing scheduled WordPress Cron event to refresh the token on expiry,
 * when either:
 * - The access token is invalid
 * - The access token expired, and refreshing failed
 *
 * @since   2.0.3
 *
 * @param   WP_Error $result      Error result.
 * @param   string   $client_id   OAuth Client ID used for the Access and Refresh Tokens.
 */
function ckwc_maybe_delete_credentials( $result, $client_id ) {

	// Don't save these credentials if they're not for this Client ID.
	// They're for another Kit Plugin that uses OAuth.
	if ( $client_id !== CKWC_OAUTH_CLIENT_ID ) {
		return;
	}

	// Bail if the integration is unavailable.
	if ( ! function_exists( 'WP_CKWC_Integration' ) ) {
		return;
	}

	// If the error isn't a 401, don't delete credentials.
	// This could be e.g. a temporary network error, rate limit or similar.
	if ( $result->get_error_data( 'convertkit_api_error' ) !== 401 ) {
		return;
	}

	// Persist an error notice in the WordPress Administration until the user fixes the problem.
	WP_CKWC()->get_class( 'admin_notices' )->add( 'authorization_failed' );

	WP_CKWC_Integration()->delete_credentials();

}

// Update Access Token when refreshed by the API class.
add_action( 'convertkit_api_get_access_token', 'ckwc_maybe_update_credentials', 10, 2 );
add_action( 'convertkit_api_refresh_token', 'ckwc_maybe_update_credentials', 10, 2 );

// Delete credentials if the API class uses a invalid access token.
// This prevents the Plugin making repetitive API requests that will 401.
add_action( 'convertkit_api_access_token_invalid', 'ckwc_maybe_delete_credentials', 10, 2 );
