<?php
/**
 * ConvertKit Opt In Block class.
 *
 * @package CKWC
 * @author ConvertKit
 */

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

/**
 * Registers an opt in checkbox block for the WooCommerce Checkout Block.
 *
 * @package CKWC
 * @author ConvertKit
 */
class CKWC_Opt_In_Block_Integration implements IntegrationInterface {

	/**
	 * Holds the WooCommerce Integration instance for this Plugin.
	 *
	 * @since   1.7.1
	 *
	 * @var     CKWC_Integration
	 */
	private $integration;

	/**
	 * The name of the integration.
	 *
	 * @since   1.7.1
	 *
	 * @return string
	 */
	public function get_name() {

		return 'ckwc_opt_in';

	}

	/**
	 * The block metadata and attributes.
	 *
	 * @since   1.7.1
	 *
	 * @return  array
	 */
	public function get_metadata() {

		return array(
			'name'          => 'ckwc/opt-in',
			'title'         => __( 'Kit Opt In', 'woocommerce-convertkit' ),
			'category'      => 'woocommerce',
			'description'   => __( 'Displays a Kit opt in checkbox at Checkout.', 'woocommerce-convertkit' ),
			'keywords'      => array(
				'subscriber',
				'newsletter',
				'email',
				'convertkit',
				'opt in',
				'checkout',
			),

			// Don't support common block properties, and only permit this block once within the Checkout Block.
			'supports'      => array(
				'html'     => false,
				'align'    => false,
				'multiple' => false,
				'reusable' => false,

				// Remove support for locking/unlocking this block in the block editor UI, as
				// the block's visibility is controlled by the integration's settings.
				'lock'     => false,
			),

			// Where to display the block within the WooCommerce Checkout Block.
			'parent'        => array(
				'woocommerce/checkout-contact-information-block',
			),

			// Attributes.
			'attributes'    => array(
				// Lock the block so it cannot be deleted; the integration's settings will determine whether
				// to display or hide the block.
				'lock'        => array(
					'type'    => 'object',
					'default' => array(
						'remove' => true,
						'move'   => true,
					),
				),

				// The checkbox property.
				'ckwc_opt_in' => array(
					'type' => 'boolean',
				),
			),

			// Editor script for this block.
			'editor_script' => 'ckwc-opt-in-block',
		);

	}

	/**
	 * Register frontend and backend scripts on block registration.
	 *
	 * @since   1.7.1
	 */
	public function initialize() {

		// Fetch integration.
		$this->integration = WP_CKWC_Integration();

		$this->register_scripts();
		$this->register_block();

	}

	/**
	 * Registers block editor and frontend checkout scripts.
	 *
	 * @since   1.7.1
	 */
	public function register_scripts() {

		// Frontend checkout.
		wp_register_script(
			'ckwc-opt-in-block-frontend',
			CKWC_PLUGIN_URL . 'resources/frontend/js/opt-in-block.js',
			array(),
			CKWC_PLUGIN_VERSION,
			true
		);

		// Block editor.
		wp_register_script(
			'ckwc-opt-in-block',
			CKWC_PLUGIN_URL . 'resources/backend/js/opt-in-block.js',
			array(),
			CKWC_PLUGIN_VERSION,
			true
		);

	}

	/**
	 * Registers the block with WordPress.
	 *
	 * @since   1.7.1
	 */
	public function register_block() {

		// Get metadata.
		$metadata = $this->get_metadata();

		register_block_type(
			$metadata['name'],
			$metadata
		);

	}

	/**
	 * Returns scripts to enqueue in the frontend site.
	 *
	 * @since   1.7.1
	 *
	 * @return  array
	 */
	public function get_script_handles() {

		return array( 'ckwc-opt-in-block-frontend' );

	}

	/**
	 * Returns scripts to enqueue in the block editor.
	 *
	 * @since   1.7.1
	 *
	 * @return  array
	 */
	public function get_editor_script_handles() {

		return array( 'ckwc-opt-in-block' );

	}

	/**
	 * Includes settings from CKWC_Integration as an object for the block editor
	 * and frontend scripts, which can be accessed using wc.wcSettings.getSetting( 'ckwc_opt_in_data' ),
	 * as these settings determine if the checkbox should be available, and if so its default checked
	 * state and label.
	 *
	 * Typically these would be presented as options to the user in the block
	 * editor, however this plugin has historically stored the settings
	 * at WooCommerce > Settings > Integrations > Kit, prior to WooCommerce
	 * introducing the concept of a Checkout Block - so we need to honor those settings.
	 *
	 * @since   1.7.1
	 */
	public function get_script_data() {

		return array(
			'enabled'                        => $this->integration->is_enabled(),
			'displayOptIn'                   => $this->integration->get_option_bool( 'display_opt_in' ),
			'optInLabel'                     => $this->integration->get_option( 'opt_in_label' ),
			'optInStatus'                    => $this->integration->get_option( 'opt_in_status' ),
			'metadata'                       => $this->get_metadata(),
			'integrationSettingsURL'         => ckwc_get_settings_link(),
			'integrationSettingsButtonLabel' => __( 'Configure Opt In', 'woocommerce-convertkit' ),
		);

	}

}
