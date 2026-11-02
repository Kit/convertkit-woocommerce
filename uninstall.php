<?php
/**
 * Uninstall routine. Runs when the Plugin is deleted
 * at Plugins > Delete.
 *
 * @package CKWC
 * @author ConvertKit
 */

// If uninstall.php is not called by WordPress, die.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

// Only WordPress and PHP methods can be used. Plugin classes and methods
// are not reliably available due to the Plugin being deactivated and going
// through deletion now.

// Get settings.
$settings = get_option( 'woocommerce_ckwc_settings' );

// Bail if no settings exist.
if ( ! $settings ) {
	return;
}

// Must match CKWC_OAUTH_CLIENT_ID and CKWC_APP_SERVER_URL in woocommerce-convertkit.php.
$ckwc_client_id      = 'NXMHMATeDPobWYsPZkoWODM6vPb1eB15IeUqt_Jyjjc';
$ckwc_app_server_url = 'https://kit-wp-app-server-production-d4wcia.laravel.cloud';

// Unregister this site from the Kit App Store app server.
// If other sites are still connected, don't revoke the tokens, as this would uninstall the Kit app for all sites.
if ( array_key_exists( 'access_token', $settings ) && ! empty( $settings['access_token'] ) ) {
	$ckwc_response = wp_remote_request(
		$ckwc_app_server_url . '/api/sites',
		array(
			'method'  => 'DELETE',
			'timeout' => 5,
			'headers' => array(
				'Accept'       => 'application/json',
				'Content-Type' => 'application/json',
			),
			'body'    => wp_json_encode(
				array(
					'site_url'     => get_site_url(),
					'client_id'    => $ckwc_client_id,
					'access_token' => $settings['access_token'],
				)
			),
		)
	);

	$ckwc_body = json_decode( wp_remote_retrieve_body( $ckwc_response ), true );
	if ( ! empty( $ckwc_body['remaining_sites'] ) ) {
		$settings['access_token']  = '';
		$settings['refresh_token'] = '';
	}
}

// Revoke Access Token.
if ( array_key_exists( 'access_token', $settings ) && ! empty( $settings['access_token'] ) ) {
	wp_remote_post(
		'https://api.kit.com/v4/oauth/revoke',
		array(
			'headers' => array(
				'Content-Type' => 'application/x-www-form-urlencoded',
			),
			'body'    => array(
				'client_id'       => $ckwc_client_id,
				'token'           => $settings['access_token'],
				'token_type_hint' => 'access_token',
			),
			'timeout' => 5,
		)
	);
}

// Revoke Refresh Token.
if ( array_key_exists( 'refresh_token', $settings ) && ! empty( $settings['refresh_token'] ) ) {
	wp_remote_post(
		'https://api.kit.com/v4/oauth/revoke',
		array(
			'headers' => array(
				'Content-Type' => 'application/x-www-form-urlencoded',
			),
			'body'    => array(
				'client_id'       => $ckwc_client_id,
				'token'           => $settings['refresh_token'],
				'token_type_hint' => 'refresh_token',
			),
			'timeout' => 5,
		)
	);
}

// Remove credentials from settings.
$settings['access_token']  = '';
$settings['refresh_token'] = '';
$settings['token_expires'] = '';
$settings['api_key']       = '';
$settings['api_secret']    = '';
$settings['app_server_site_secret'] = '';

// Save settings.
update_option( 'woocommerce_ckwc_settings', $settings );
