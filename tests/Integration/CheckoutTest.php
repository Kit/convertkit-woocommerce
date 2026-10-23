<?php

namespace Tests;

use lucatume\WPBrowser\TestCase\WPTestCase;

/**
 * Tests for the CKWC_Checkout class.
 *
 * @since   2.2.3
 */
class CheckoutTest extends WPTestCase
{
	/**
	 * The testing implementation.
	 *
	 * @var \IntegrationTester
	 */
	protected $tester;

	/**
	 * Holds the Checkout class.
	 *
	 * @since   2.2.3
	 *
	 * @var     \CKWC_Checkout
	 */
	private $checkout;

	/**
	 * Performs actions before each test.
	 *
	 * @since   2.2.3
	 */
	public function setUp(): void
	{
		parent::setUp();

		// Activate Plugin, to include the Plugin's constants in tests.
		activate_plugins('woocommerce/woocommerce.php');
		activate_plugins('convertkit-woocommerce/woocommerce-convertkit.php');

		// Enable integration, storing Access Token and Refresh Token in Plugin's settings.
		WP_CKWC_Integration()->update_option( 'enabled', 'yes' );
		WP_CKWC_Integration()->update_option( 'access_token', $_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN'] );
		WP_CKWC_Integration()->update_option( 'refresh_token', $_ENV['CONVERTKIT_OAUTH_REFRESH_TOKEN'] );

		// Don't display the opt in checkbox, so the customer is always opted in.
		WP_CKWC_Integration()->update_option( 'display_opt_in', 'no' );

		// Initialize the class we want to test.
		$this->checkout = new \CKWC_Checkout();
	}

	/**
	 * Performs actions after each test.
	 *
	 * @since   2.2.3
	 */
	public function tearDown(): void
	{
		// Delete Credentials from Plugin's settings.
		WP_CKWC_Integration()->update_option( 'enabled', 'no' );
		WP_CKWC_Integration()->update_option( 'access_token', '' );
		WP_CKWC_Integration()->update_option( 'refresh_token', '' );
		WP_CKWC_Integration()->update_option( 'display_opt_in', '' );

		parent::tearDown();
	}

	/**
	 * Test that save_opt_in_checkbox() stores the opt in value against the Order.
	 *
	 * @since   2.2.3
	 */
	public function testSaveOptInCheckbox()
	{
		// Create a WooCommerce Order.
		$order = wc_create_order();

		// Save the opt in value. With no opt in checkbox displayed, the customer is opted in.
		$this->checkout->save_opt_in_checkbox( $order->get_id() );

		// Confirm the opt in value was stored against the Order.
		$this->assertEquals( 'yes', wc_get_order( $order->get_id() )->get_meta( 'ckwc_opt_in' ) );
	}

	/**
	 * Test that save_opt_in_checkbox() does not result in a fatal error when called
	 * with an ID that isn't a WooCommerce Order e.g. by a third party Plugin.
	 *
	 * @since   2.2.3
	 */
	public function testSaveOptInCheckboxWithInvalidOrderID()
	{
		// Create a Page, which isn't a WooCommerce Order.
		$page_id = static::factory()->post->create( [ 'post_type' => 'page' ] );

		// Save the opt in value for the Page and an ID that doesn't exist.
		$this->checkout->save_opt_in_checkbox( $page_id );
		$this->checkout->save_opt_in_checkbox( 999999 );

		// Confirm no opt in value was stored against the Page.
		$this->assertEmpty( get_post_meta( $page_id, 'ckwc_opt_in', true ) );
	}
}
