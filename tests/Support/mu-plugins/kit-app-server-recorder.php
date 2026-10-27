<?php
/**
 * Plugin Name: Kit App Server Recorder
 * Description: Records automation event requests to the Kit App Store app server in an option, returning a successful response, for end to end tests to assert against.
 *
 * @package CKWC
 * @author ConvertKit
 */

// Record automation event requests to the app server, instead of sending them.
add_filter(
	'pre_http_request',
	function ( $response, $parsed_args, $url ) {

		// Bail if this isn't an automation event request to the app server.
		if ( strpos( $url, '/api/automation-events' ) === false ) {
			return $response;
		}

		// Fetch the existing log.
		$log = get_option( 'kit_app_server_log', array() );
		if ( ! is_array( $log ) ) {
			$log = array();
		}

		// Append this request to the log.
		$log[] = array(
			'url'           => $url,
			'method'        => isset( $parsed_args['method'] ) ? $parsed_args['method'] : 'GET',
			'authorization' => isset( $parsed_args['headers']['Authorization'] ) ? $parsed_args['headers']['Authorization'] : '',
			'body'          => is_string( $parsed_args['body'] ) ? json_decode( $parsed_args['body'], true ) : array(),
		);

		update_option( 'kit_app_server_log', $log, false );

		// Return the app server's response for a stored event.
		return array(
			'headers'  => array(),
			'body'     => wp_json_encode( array( 'id' => count( $log ) ) ),
			'response' => array(
				'code'    => 201,
				'message' => 'Created',
			),
			'cookies'  => array(),
			'filename' => null,
		);

	},
	10,
	3
);
