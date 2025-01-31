<?php
/**
 * Kit for WooCommerce Plugin.
 *
 * @package CKWC
 * @author ConvertKit
 *
 * @wordpress-plugin
 * Plugin Name: Kit (formerly ConvertKit) for WooCommerce
 * Plugin URI:  https://www.kit.com
 * Description: Integrates WooCommerce with Kit, allowing customers to be automatically sent to your Kit account.
 * Version: 1.9.2
 * Author: Kit
 * Author URI: https://www.kit.com
 * Text Domain: woocommerce-convertkit
 *
 * WC requires at least: 3.0
 * WC tested up to: 9.6.0
 */

// Bail if Plugin is already loaded.
if ( class_exists( 'WP_CKWC' ) ) {
	return;
}

// Define ConverKit Plugin paths and version number.
define( 'CKWC_PLUGIN_NAME', 'ConvertKitWooCommerce' ); // Used for user-agent in API class.
define( 'CKWC_PLUGIN_FILE', plugin_basename( __FILE__ ) );
define( 'CKWC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CKWC_PLUGIN_PATH', __DIR__ );
define( 'CKWC_PLUGIN_VERSION', '1.9.2' );
define( 'CKWC_OAUTH_CLIENT_ID', 'L0kyADsB3WP5zO5MvUpXQU64gIntQg9BBAIme17r_7A' );
define( 'CKWC_OAUTH_CLIENT_REDIRECT_URI', 'https://app.kit.com/wordpress/redirect' );

// Load shared classes, if they have not been included by another ConvertKit Plugin.
if ( ! trait_exists( 'ConvertKit_API_Traits' ) ) {
	require_once CKWC_PLUGIN_PATH . '/vendor/convertkit/convertkit-wordpress-libraries/src/class-convertkit-api-traits.php';
}
if ( ! class_exists( 'ConvertKit_API_V4' ) ) {
	require_once CKWC_PLUGIN_PATH . '/vendor/convertkit/convertkit-wordpress-libraries/src/class-convertkit-api-v4.php';
}
if ( ! class_exists( 'ConvertKit_Resource_V4' ) ) {
	require_once CKWC_PLUGIN_PATH . '/vendor/convertkit/convertkit-wordpress-libraries/src/class-convertkit-resource-v4.php';
}
if ( ! class_exists( 'ConvertKit_Review_Request' ) ) {
	require_once CKWC_PLUGIN_PATH . '/vendor/convertkit/convertkit-wordpress-libraries/src/class-convertkit-review-request.php';
}

// Load plugin files that are always required.
require_once CKWC_PLUGIN_PATH . '/includes/functions.php';
require_once CKWC_PLUGIN_PATH . '/includes/class-wp-ckwc.php';
require_once CKWC_PLUGIN_PATH . '/includes/class-ckwc-api.php';
require_once CKWC_PLUGIN_PATH . '/includes/class-ckwc-checkout.php';
require_once CKWC_PLUGIN_PATH . '/includes/class-ckwc-cli-sync-past-orders.php';
require_once CKWC_PLUGIN_PATH . '/includes/class-ckwc-order.php';
require_once CKWC_PLUGIN_PATH . '/includes/class-ckwc-resource.php';
require_once CKWC_PLUGIN_PATH . '/includes/class-ckwc-resource-custom-fields.php';
require_once CKWC_PLUGIN_PATH . '/includes/class-ckwc-resource-forms.php';
require_once CKWC_PLUGIN_PATH . '/includes/class-ckwc-resource-sequences.php';
require_once CKWC_PLUGIN_PATH . '/includes/class-ckwc-resource-tags.php';
require_once CKWC_PLUGIN_PATH . '/includes/class-ckwc-setup.php';
require_once CKWC_PLUGIN_PATH . '/includes/class-ckwc-wc-subscriptions.php';

// Load files that are only used in the WordPress Administration interface.
require_once CKWC_PLUGIN_PATH . '/admin/class-ckwc-admin-post-type.php';
require_once CKWC_PLUGIN_PATH . '/admin/class-ckwc-admin-ajax.php';
require_once CKWC_PLUGIN_PATH . '/admin/class-ckwc-admin-bulk-edit.php';
require_once CKWC_PLUGIN_PATH . '/admin/class-ckwc-admin-coupon.php';
require_once CKWC_PLUGIN_PATH . '/admin/class-ckwc-admin-plugin.php';
require_once CKWC_PLUGIN_PATH . '/admin/class-ckwc-admin-product.php';
require_once CKWC_PLUGIN_PATH . '/admin/class-ckwc-admin-quick-edit.php';
require_once CKWC_PLUGIN_PATH . '/admin/class-ckwc-admin-refresh-resources.php';

/**
 * Main function to return Plugin instance.
 *
 * @since   1.4.2
 */
function WP_CKWC() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName

	return WP_CKWC::get_instance();

}

/**
 * Main function to return the WooCommerce Integration class.
 *
 * @since   1.0.0
 *
 * @return  bool|CKWC_Integration
 */
function WP_CKWC_Integration() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName

	// Bail if WooCommerce isn't active.
	if ( ! function_exists( 'WC' ) ) {
		return false;
	}

	// Get registered WooCommerce integrations.
	$integrations = WC()->integrations->get_integrations();

	// Return our integration, if it's registered.
	return isset( $integrations['ckwc'] ) ? $integrations['ckwc'] : false;

}

// Finally, initialize the Plugin.
WP_CKWC();
