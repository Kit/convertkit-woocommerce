<?php
/**
 * ConvertKit Resource class.
 *
 * @package CKWC
 * @author ConvertKit
 */

/**
 * Abstract class defining variables and functions for a ConvertKit API Resource
 * (forms, sequences, tags).
 *
 * @since   1.4.2
 */
class CKWC_Resource extends ConvertKit_Resource_V4 {

	/**
	 * Constructor.
	 *
	 * @since   1.4.7
	 */
	public function __construct() {

		// Initialize the API if the integration is connected to ConvertKit and has been enabled in the Plugin Settings.
		if ( WP_CKWC_Integration()->is_enabled() ) {
			$this->api = new CKWC_API(
				CKWC_OAUTH_CLIENT_ID,
				CKWC_OAUTH_CLIENT_REDIRECT_URI,
				WP_CKWC_Integration()->get_option( 'access_token' ),
				WP_CKWC_Integration()->get_option( 'refresh_token' ),
				WP_CKWC_Integration()->get_option_bool( 'debug' )
			);
		}

		// Get last query time and existing resources.
		$this->last_queried = get_option( $this->settings_name . '_last_queried' );
		$this->resources    = get_option( $this->settings_name );

	}

	/**
	 * Fetches resources (custom fields, forms, sequences or tags) from the API, storing them in the options table
	 * with a last queried timestamp.
	 *
	 * If the refresh results in a 401, removes the access and refresh tokens from the settings.
	 *
	 * @since   2.0.3
	 *
	 * @return  WP_Error|array
	 */
	public function refresh() {

		// Call parent refresh method.
		$result = parent::refresh();

		// If an error occured, maybe delete credentials from the Plugin's settings
		// if the error is a 401 unauthorized.
		if ( is_wp_error( $result ) ) {
			ckwc_maybe_delete_credentials( $result, CKWC_OAUTH_CLIENT_ID );
		}

		return $result;

	}

}
