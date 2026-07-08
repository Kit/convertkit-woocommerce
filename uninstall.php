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

// Revoke Access Token.
if ( array_key_exists( 'access_token', $settings ) && ! empty( $settings['access_token'] ) ) {
	wp_remote_post(
		'https://api.kit.com/v4/oauth/revoke',
		array(
			'headers' => array(
				'Content-Type' => 'application/x-www-form-urlencoded',
			),
			'body'    => array(
				'client_id'       => 'L0kyADsB3WP5zO5MvUpXQU64gIntQg9BBAIme17r_7A',
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
				'client_id'       => 'L0kyADsB3WP5zO5MvUpXQU64gIntQg9BBAIme17r_7A',
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

// Save settings.
update_option( 'woocommerce_ckwc_settings', $settings );
