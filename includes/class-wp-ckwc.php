<?php
/**
 * ConvertKit for WooCommerce class.
 *
 * @package CKWC
 * @author ConvertKit
 */

/**
 * Main ConvertKit for WooCommerce class, which registers the WooCommerce integration
 * and initialises required classes depending on the environment (frontend site, admin etc).
 *
 * @package CKWC
 * @author ConvertKit
 */
class WP_CKWC {

	/**
	 * Holds the class object.
	 *
	 * @since   1.4.2
	 *
	 * @var     object
	 */
	private static $instance;

	/**
	 * Holds singleton initialized classes that include
	 * action and filter hooks.
	 *
	 * @since   1.4.2
	 *
	 * @var     array
	 */
	private $classes = array();

	/**
	 * Constructor. Acts as a bootstrap to load the rest of the plugin
	 *
	 * @since   1.4.2
	 */
	public function __construct() {

		// Register integration.
		add_filter( 'woocommerce_integrations', array( $this, 'woocommerce_integrations_register' ) );

		// Register blocks.
		add_action( 'woocommerce_blocks_loaded', array( $this, 'woocommerce_blocks_register' ) );

		// Declare HPOS compatibility.
		add_action( 'before_woocommerce_init', array( $this, 'woocommerce_hpos_compatibility' ) );

		// Initialize.
		add_action( 'woocommerce_init', array( $this, 'woocommerce_init' ) );

		// Update.
		add_action( 'convertkit_for_woocommerce_initialize_global', array( $this, 'update' ) );

		// Load language files.
		add_action( 'init', array( $this, 'load_language_files' ) );

	}

	/**
	 * Register this Plugin's CKWC_Integration class as a WooCommerce Integration.
	 *
	 * @since   1.0.0
	 *
	 * @param   array $integrations   WooCommerce Integrations.
	 * @return  array                   WooCommerce Integrations
	 */
	public function woocommerce_integrations_register( $integrations ) {

		// Load integration.
		require_once CKWC_PLUGIN_PATH . '/includes/class-ckwc-integration.php';

		// Register integration.
		$integrations[] = 'CKWC_Integration';

		return $integrations;

	}

	/**
	 * Tells WooCommerce that this integration is compatible with HPOS.
	 *
	 * @see https://github.com/woocommerce/woocommerce/wiki/High-Performance-Order-Storage-Upgrade-Recipe-Book#declaring-extension-incompatibility
	 *
	 * @since   1.6.6
	 */
	public function woocommerce_hpos_compatibility() {

		// Don't declare compatibility if the applicable class doesn't exist.
		if ( ! class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			return;
		}

		// Declare compatibility with HPOS.
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', CKWC_PLUGIN_FILE, true ); // @phpstan-ignore-line

	}

	/**
	 * Registers the opt in checkbox block for the WooCommerce Checkout Block.
	 *
	 * @since   1.7.1
	 */
	public function woocommerce_blocks_register() {

		// 8.9.0 and higher replaces checkout block registrations with the `woocommerce_register_additional_checkout_field()` method,
		// to register the opt in field when using the WooCommerce Checkout Block.
		// See CKWC_Checkout, which uses this method.
		if ( function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
			// The block won't display or be used in 8.9.0+ checkout, so there's no point registering it.
			return;
		}

		// Load opt in checkbox block.
		require_once CKWC_PLUGIN_PATH . '/includes/blocks/opt-in/class-ckwc-opt-in-block-integration.php';

		// Register opt in checkbox block.
		add_action(
			'woocommerce_blocks_checkout_block_registration',
			function ( $integration_registry ) {

				$integration_registry->register( new CKWC_Opt_In_Block_Integration() );

			}
		);

	}

	/**
	 * Initialize admin, frontend and global Plugin classes when WooCommerce initializes,
	 * after WooCommerce has loaded its integrations.
	 *
	 * @since   1.0.0
	 */
	public function woocommerce_init() {

		// Initialize class(es) to register hooks.
		$this->initialize_admin();
		$this->initialize_cli();
		$this->initialize_frontend();
		$this->initialize_global();

	}

	/**
	 * Initialize classes for the WordPress Administration interface
	 *
	 * @since   1.4.2
	 */
	private function initialize_admin() {

		// Bail if this request isn't for the WordPress Administration interface.
		if ( ! is_admin() ) {
			return;
		}

		$this->classes['admin_ajax']              = new CKWC_Admin_AJAX();
		$this->classes['admin_bulk_edit']         = new CKWC_Admin_Bulk_Edit();
		$this->classes['admin_coupon']            = new CKWC_Admin_Coupon();
		$this->classes['admin_plugin']            = new CKWC_Admin_Plugin();
		$this->classes['admin_product']           = new CKWC_Admin_Product();
		$this->classes['admin_quick_edit']        = new CKWC_Admin_Quick_Edit();
		$this->classes['admin_refresh_resources'] = new CKWC_Admin_Refresh_Resources();

		/**
		 * Initialize integration classes for the WordPress Administration interface.
		 *
		 * @since   1.4.2
		 */
		do_action( 'convertkit_for_woocommerce_initialize_admin' );

	}

	/**
	 * Register WP-CLI commands for this Plugin.
	 *
	 * @since   1.7.1
	 */
	private function initialize_cli() {

		// Bail if this isn't a CLI request.
		if ( ! defined( 'WP_CLI' ) ) {
			return;
		}
		if ( ! WP_CLI ) {
			return;
		}
		if ( ! class_exists( 'WP_CLI' ) ) {
			return;
		}

		$this->classes['cli_sync_past_orders'] = new CKWC_CLI_Sync_Past_Orders();

		// Register CLI commands.
		WP_CLI::add_command(
			'ckwc-sync-past-orders',
			$this->classes['cli_sync_past_orders'],
			array(
				'shortdesc' => __( 'Sync past orders with Kit Purchase Data.', 'woocommerce-convertkit' ),
				'synopsis'  => array(
					array(
						'type'     => 'assoc',
						'name'     => 'limit',
						'optional' => true,
						'multiple' => false,
					),
				),
				'when'      => 'before_wp_load',
			)
		);

		/**
		 * Register CLI commands.
		 *
		 * @since   1.7.1
		 */
		do_action( 'convertkit_for_woocommerce_initialize_cli' );

	}

	/**
	 * Initialize classes for the frontend web site
	 *
	 * @since   1.4.2
	 */
	private function initialize_frontend() {

		// Bail if this request isn't for the frontend web site.
		if ( is_admin() ) {
			return;
		}

		/**
		 * Initialize integration classes for the frontend web site.
		 *
		 * @since   1.4.2
		 */
		do_action( 'convertkit_for_woocommerce_initialize_frontend' );

	}

	/**
	 * Initialize classes required globally, across the WordPress Administration, CLI, Cron and Frontend
	 * web site.
	 *
	 * @since   1.4.2
	 */
	private function initialize_global() {

		$this->classes['checkout']         = new CKWC_Checkout();
		$this->classes['order']            = new CKWC_Order();
		$this->classes['review_request']   = new ConvertKit_Review_Request( 'Kit for WooCommerce', 'convertkit-for-woocommerce', CKWC_PLUGIN_PATH );
		$this->classes['setup']            = new CKWC_Setup();
		$this->classes['wc_subscriptions'] = new CKWC_WC_Subscriptions();

		/**
		 * Initialize integration classes for the frontend web site.
		 *
		 * @since   1.4.2
		 */
		do_action( 'convertkit_for_woocommerce_initialize_global' );

	}

	/**
	 * Runs the Plugin's update routine, which checks if
	 * the Plugin has just been updated to a newer version,
	 * and if so runs any specific processes that might be needed.
	 *
	 * @since   1.8.0
	 */
	public function update() {

		$this->get_class( 'setup' )->update();

	}

	/**
	 * Loads plugin textdomain
	 *
	 * @since   1.0.0
	 */
	public function load_language_files() {

		// If the .mo file for a given language is available in WP_LANG_DIR/convertkit
		// i.e. it's available as a translation at https://translate.wordpress.org/projects/wp-plugins/convertkit-for-woocommerce/,
		// it will be used instead of the .mo file in convertkit-for-woocommerce/languages.
		load_plugin_textdomain( 'woocommerce-convertkit', false, 'convertkit-for-woocommerce/languages' );

	}

	/**
	 * Returns the given class
	 *
	 * @since   1.4.2
	 *
	 * @param   string $name   Class Name.
	 * @return  object          Class Object
	 */
	public function get_class( $name ) {

		// If the class hasn't been loaded, throw a WordPress die screen
		// to avoid a PHP fatal error.
		if ( ! isset( $this->classes[ $name ] ) ) {
			// Define the error.
			$error = new WP_Error(
				'convertkit_for_woocommerce_get_class',
				sprintf(
					/* translators: %1$s: PHP class name */
					__( 'Kit for WooCommerce Error: Could not load Plugin class <strong>%1$s</strong>', 'woocommerce-convertkit' ),
					$name
				)
			);

			// Depending on the request, return or display an error.
			// Admin UI.
			if ( is_admin() ) {
				wp_die(
					esc_attr( $error->get_error_message() ),
					esc_html__( 'Kit for WooCommerce Error', 'woocommerce-convertkit' ),
					array(
						'back_link' => true,
					)
				);
			}

			// Cron / CLI.
			return $error;
		}

		// Return the class object.
		return $this->classes[ $name ];

	}

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @since   1.4.2
	 *
	 * @return  object Class.
	 */
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

}
