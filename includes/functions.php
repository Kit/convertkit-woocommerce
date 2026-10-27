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
 * @since   2.0.5
 *
 * @param   bool $network_wide   Is network wide activation.
 */
function ckwc_plugin_activate( $network_wide ) {

	// Check if we are on a multisite install, activating network wide, or a single install.
	if ( ! is_multisite() || ! $network_wide ) {
		// Single Site activation.
		add_action( 'shutdown', 'ckwc_schedule_actions' );
	} else {
		// Multisite network wide activation.
		$sites = get_sites(
			array(
				'number' => 0,
			)
		);
		foreach ( $sites as $site ) {
			switch_to_blog( (int) $site->blog_id );
			add_action( 'shutdown', 'ckwc_schedule_actions' );
			restore_current_blog();
		}
	}

}

/**
 * Runs the activation and update routines when the plugin is activated
 * on a WordPress multisite setup.
 *
 * @since   2.0.5
 *
 * @param   WP_Site|int $site_or_blog_id    WP_Site or Blog ID.
 */
function ckwc_plugin_activate_new_site( $site_or_blog_id ) {

	// Check if $site_or_blog_id is a WP_Site or a blog ID.
	if ( is_a( $site_or_blog_id, 'WP_Site' ) ) {
		$site_or_blog_id = $site_or_blog_id->blog_id;
	}

	// Run installation routine.
	switch_to_blog( $site_or_blog_id );
	add_action( 'shutdown', 'ckwc_schedule_actions' );
	restore_current_blog();

}

/**
 * Runs the deactivation routine when the plugin is deactivated.
 *
 * @since   2.0.5
 *
 * @param   bool $network_wide   Is network wide deactivation.
 */
function ckwc_plugin_deactivate( $network_wide ) {

	// Check if we are on a multisite install, activating network wide, or a single install.
	if ( ! is_multisite() || ! $network_wide ) {
		// Single Site activation.
		ckwc_unschedule_actions();
	} else {
		// Multisite network wide activation.
		$sites = get_sites(
			array(
				'number' => 0,
			)
		);
		foreach ( $sites as $site ) {
			switch_to_blog( (int) $site->blog_id );
			ckwc_unschedule_actions();
			restore_current_blog();
		}
	}

}

/**
 * Schedules the WordPress Cron events.
 *
 * @since   2.0.5
 */
function ckwc_schedule_actions() {

	// Bail if the action scheduler is unavailable.
	if ( ! function_exists( 'as_schedule_recurring_action' ) ) {
		return;
	}

	// Bail if the scheduled action already exists.
	if ( as_next_scheduled_action( 'ckwc_abandoned_cart' ) ) {
		return;
	}

	// Schedule action.
	as_schedule_recurring_action(
		time(),
		15 * MINUTE_IN_SECONDS,
		'ckwc_abandoned_cart',
		array(),
		'ckwc'
	);

}

/**
 * Unschedules the WordPress Cron events.
 *
 * @since   2.0.5
 */
function ckwc_unschedule_actions() {

	// Bail if the action scheduler is unavailable.
	if ( ! function_exists( 'as_unschedule_all_actions' ) ) {
		return;
	}

	// Unschedule action.
	as_unschedule_all_actions( 'ckwc_abandoned_cart' );

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
 * Helper method to return the URL Kit redirects to after the user authorizes the Plugin
 * via OAuth, including a nonce that is verified before the authorization code is
 * exchanged for an access token.
 *
 * @since   2.2.1
 *
 * @return  string  OAuth Return URL
 */
function ckwc_get_oauth_return_url() {

	return ckwc_get_settings_link(
		array(
			'nonce' => wp_create_nonce( CKWC_NONCE_ACTION_OAUTH_CONNECT ),
		)
	);

}

/**
 * Returns the URL the Connect button links to, to begin the OAuth process.
 *
 * The creator is sent to the Kit App Store app server to install the Kit app,
 * which then continues this Plugin's OAuth request.
 *
 * @since   2.3.0
 *
 * @param   CKWC_API $api  API instance.
 * @return  string          Connect URL
 */
function ckwc_get_oauth_connect_url( $api ) {

	return add_query_arg(
		array(
			'authorize_url' => rawurlencode( $api->get_oauth_url( ckwc_get_oauth_return_url(), get_site_url() ) ),
		),
		CKWC_APP_SERVER_URL . '/connect/start'
	);

}

/**
 * Permits redirecting to the Kit App Store app server.
 *
 * @since   2.3.0
 *
 * @param   array $hosts  Allowed redirect hosts.
 * @return  array          Allowed redirect hosts
 */
function ckwc_allowed_redirect_hosts( $hosts ) {

	$hosts[] = wp_parse_url( CKWC_APP_SERVER_URL, PHP_URL_HOST );
	return $hosts;

}
add_filter( 'allowed_redirect_hosts', 'ckwc_allowed_redirect_hosts' );

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

/**
 * Deletes the stored access token, refresh token and its expiry from the Plugin settings,
 * and clears any existing scheduled WordPress Cron event to refresh the token on expiry,
 * when the user revokes the access token.
 *
 * @since   2.1.3
 *
 * @param   string $client_id   OAuth Client ID used for the Access and Refresh Tokens.
 */
function ckwc_delete_credentials( $client_id ) {

	// Don't delete these credentials if they're not for this Client ID.
	// They're for another Kit Plugin that uses OAuth.
	if ( $client_id !== CKWC_OAUTH_CLIENT_ID ) {
		return;
	}

	// Bail if the integration is unavailable.
	if ( ! function_exists( 'WP_CKWC_Integration' ) ) {
		return;
	}

	// Delete Access and Refresh Tokens.
	WP_CKWC_Integration()->delete_credentials();

}

/**
 * Returns the abandoned_checkout automation event context for a WooCommerce session's cart.
 *
 * @since   2.3.0
 *
 * @param   array $session_data   WooCommerce session data.
 * @return  array
 */
function ckwc_get_abandoned_cart_context( $session_data ) {

	$cart    = maybe_unserialize( isset( $session_data['cart'] ) ? $session_data['cart'] : '' );
	$totals  = maybe_unserialize( isset( $session_data['cart_totals'] ) ? $session_data['cart_totals'] : '' );
	$coupons = maybe_unserialize( isset( $session_data['applied_coupons'] ) ? $session_data['applied_coupons'] : '' );

	$products = array();
	foreach ( is_array( $cart ) ? $cart : array() as $item ) {
		$product = wc_get_product( ! empty( $item['variation_id'] ) ? $item['variation_id'] : $item['product_id'] );
		if ( ! $product ) {
			continue;
		}

		$quantity   = absint( $item['quantity'] );
		$products[] = ckwc_get_automation_event_product( $product, $quantity, $quantity ? floatval( $item['line_subtotal'] ) / $quantity : 0 );
	}

	return array(
		'checkout_url'   => wc_get_checkout_url(),
		'products'       => array_slice( $products, 0, 20 ),
		'subtotal'       => ckwc_format_price( is_array( $totals ) && isset( $totals['subtotal'] ) ? $totals['subtotal'] : 0 ),
		'discount_codes' => is_array( $coupons ) ? array_values( $coupons ) : array(),
		'total'          => ckwc_format_price( is_array( $totals ) && isset( $totals['total'] ) ? $totals['total'] : 0 ),
	);

}

/**
 * Returns the product_purchased automation event context for a WooCommerce Order.
 *
 * @since   2.3.0
 *
 * @param   WC_Order $order  WooCommerce Order.
 * @return  array
 */
function ckwc_get_order_context( $order ) {

	$products = array();
	foreach ( $order->get_items() as $item ) {
		if ( ! $item->get_product() ) {
			continue;
		}

		$products[] = ckwc_get_automation_event_product( $item->get_product(), $item->get_quantity(), $order->get_item_subtotal( $item, false, true ), $order->get_currency() );
	}

	return array(
		'order_id'       => $order->get_order_number(),
		'products'       => array_slice( $products, 0, 20 ),
		'subtotal'       => ckwc_format_price( $order->get_subtotal(), $order->get_currency() ),
		'discount_codes' => array_values( $order->get_coupon_codes() ),
		'total'          => ckwc_format_price( $order->get_total(), $order->get_currency() ),
	);

}

/**
 * Returns a Product's automation event data.
 *
 * @since   2.3.0
 *
 * @param   WC_Product $product   WooCommerce Product.
 * @param   int        $quantity  Quantity.
 * @param   float      $price     Unit price.
 * @param   string     $currency  Currency code (defaults to the store's currency).
 * @return  array
 */
function ckwc_get_automation_event_product( $product, $quantity, $price, $currency = '' ) {

	$description = $product->get_short_description() ? $product->get_short_description() : $product->get_description();

	return array(
		'name'        => $product->get_name(),
		'quantity'    => $quantity,
		'price'       => ckwc_format_price( $price, $currency ),
		'description' => mb_substr( trim( html_entity_decode( wp_strip_all_tags( $description ), ENT_QUOTES, 'UTF-8' ) ), 0, 300 ),
		'image_url'   => $product->get_image_id() ? (string) wp_get_attachment_image_url( $product->get_image_id(), 'woocommerce_thumbnail' ) : '',
		'url'         => $product->get_permalink(),
	);

}

/**
 * Returns a price as plain text, e.g. $29.99.
 *
 * @since   2.3.0
 *
 * @param   float  $amount    Amount.
 * @param   string $currency  Currency code (defaults to the store's currency).
 * @return  string
 */
function ckwc_format_price( $amount, $currency = '' ) {

	return html_entity_decode( wp_strip_all_tags( wc_price( $amount, array( 'currency' => $currency ) ) ), ENT_QUOTES, 'UTF-8' );

}

// Update Access Token when refreshed by the API class.
add_action( 'convertkit_api_get_access_token', 'ckwc_maybe_update_credentials', 10, 2 );
add_action( 'convertkit_api_refresh_token', 'ckwc_maybe_update_credentials', 10, 2 );

// Delete credentials when the user revokes the access and refresh tokens.
add_action( 'convertkit_api_revoke_tokens', 'ckwc_delete_credentials', 10, 1 );

// Delete credentials if the API class uses a invalid access token.
// This prevents the Plugin making repetitive API requests that will 401.
add_action( 'convertkit_api_access_token_invalid', 'ckwc_maybe_delete_credentials', 10, 2 );
