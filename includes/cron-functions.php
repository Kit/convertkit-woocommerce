<?php
/**
 * ConvertKit for WooCommerce Cron functions.
 *
 * @package CKWC
 * @author ConvertKit
 */


/**
 * Subscribe abandoned cart emails to the abandoned cart tag
 * by checking the WooCommerce sessions table.
 *
 * @since   2.0.5
 */
function ckwc_subscribe_abandoned_cart() {

	// Bail if the integration is unavailable.
	if ( ! function_exists( 'WP_CKWC_Integration' ) ) {
		return;
	}

	// Bail if the integration is not enabled.
	if ( ! WP_CKWC_Integration()->is_enabled() ) {
		return;
	}

	// Bail if Abandoned Cart is not enabled.
	if ( ! WP_CKWC_Integration()->get_option_bool( 'abandoned_cart' ) ) {
		return;
	}

	// Bail if no tag is configured.
	$subscription = WP_CKWC_Integration()->get_option( 'abandoned_cart_subscription' );
	if ( empty( $subscription ) ) {
		return;
	}

	// Get the tag ID.
	list( $resource_type, $tag_id ) = explode( ':', $subscription );

	// Calculate the threshold.
	$threshold = time() - ( absint( WP_CKWC_Integration()->get_option( 'abandoned_cart_threshold' ) ) * MINUTE_IN_SECONDS );

	// Setup the API.
	$api = new CKWC_API(
		CKWC_OAUTH_CLIENT_ID,
		CKWC_OAUTH_CLIENT_REDIRECT_URI,
		WP_CKWC_Integration()->get_option( 'access_token' ),
		WP_CKWC_Integration()->get_option( 'refresh_token' ),
		WP_CKWC_Integration()->get_option_bool( 'debug' )
	);

	// Get sessions.
	global $wpdb;
	$table   = $wpdb->prefix . 'woocommerce_sessions';
	$results = $wpdb->get_results( "SELECT session_key, session_value FROM $table" );

	foreach ( $results as $row ) {
		// Get the session data.
		$session_data = maybe_unserialize( $row->session_value );

		// If no email or timestamp is set, continue.
		if ( empty( $session_data['ckwc_abandoned_cart_email'] ) || empty( $session_data['ckwc_abandoned_cart_timestamp'] ) ) {
			continue;
		}

		$email     = $session_data['ckwc_abandoned_cart_email'];
		$timestamp = intval( $session_data['ckwc_abandoned_cart_timestamp'] );

		// Check if cart looks abandoned.
		if ( $timestamp < $threshold ) {
			// Create subscriber and tag.
			$api->create_subscriber( $email );
			$api->tag_subscriber_by_email( $tag_id, $email );

			// Remove session data so we don't trigger this email again.
			unset( $session_data['ckwc_abandoned_cart_email'] );
			unset( $session_data['ckwc_abandoned_cart_timestamp'] );
			$wpdb->update(
				$table,
				array( 'session_value' => maybe_serialize( $session_data ) ),
				array( 'session_key' => $row->session_key ),
				array( '%s' ),
				array( '%s' )
			);
		}
	}

}

// @TODO Schedule the cron event on plugin activation and update; unschedule in deactivation.
add_action( 'ckwc_subscribe_abandoned_cart', 'ckwc_subscribe_abandoned_cart' );

/**
 * Refresh the OAuth access token, triggered by WordPress' Cron.
 *
 * @since   1.9.8
 */
function ckwc_refresh_token() {

	// Bail if the integration is unavailable.
	if ( ! function_exists( 'WP_CKWC_Integration' ) ) {
		return;
	}

	// Bail if the integration is not enabled.
	if ( ! WP_CKWC_Integration()->is_enabled() ) {
		return;
	}

	// Bail if no access and refresh token exists.
	if ( empty( WP_CKWC_Integration()->get_option( 'access_token' ) ) ) {
		return;
	}
	if ( empty( WP_CKWC_Integration()->get_option( 'refresh_token' ) ) ) {
		return;
	}

	// Initialize the API.
	$api = new CKWC_API(
		CKWC_OAUTH_CLIENT_ID,
		CKWC_OAUTH_CLIENT_REDIRECT_URI,
		WP_CKWC_Integration()->get_option( 'access_token' ),
		WP_CKWC_Integration()->get_option( 'refresh_token' ),
		WP_CKWC_Integration()->get_option_bool( 'debug' )
	);

	// Refresh the token.
	$result = $api->refresh_token();

	// If an error occured, don't save the new tokens.
	// Logging is handled by the CKWC_API class.
	if ( is_wp_error( $result ) ) {
		return;
	}

	// Update settings with new tokens.
	WP_CKWC_Integration()->update_option( 'access_token', $result['access_token'] );
	WP_CKWC_Integration()->update_option( 'refresh_token', $result['refresh_token'] );
	WP_CKWC_Integration()->update_option( 'token_expires', ( time() + $result['expires_in'] ) );

}

// Register action to run above function; this action is created by WordPress' wp_schedule_event() function
// in ckwc_plugin_activate().
add_action( 'ckwc_refresh_token', 'ckwc_refresh_token' );
