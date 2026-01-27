<?php

namespace Tests;

use lucatume\WPBrowser\TestCase\WPRestApiTestCase;

/**
 * Tests for the REST API routes.
 *
 * @since   2.0.6
 */
class RESTAPITest extends WPRestApiTestCase
{
	/**
	 * The testing implementation.
	 *
	 * @var \IntegrationTester
	 */
	protected $tester;

	/**
	 * Performs actions before each test.
	 *
	 * @since   2.0.6
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
	}

	/**
	 * Performs actions after each test.
	 *
	 * @since   2.0.6
	 */
	public function tearDown(): void
	{
		// Delete Credentials from Plugin's settings.
		WP_CKWC_Integration()->update_option( 'enabled', 'no' );
		WP_CKWC_Integration()->update_option( 'access_token', '' );
		WP_CKWC_Integration()->update_option( 'refresh_token', '' );

		parent::tearDown();
	}

	/**
	 * Test that the /wp-json/kit/v1/woocommerce/order/send REST API route returns a 401 when the user is not authorized.
	 *
	 * @since   2.0.6
	 */
	public function testSyncPastOrderWhenUnauthorized()
	{
		$request  = new \WP_REST_Request( 'POST', '/kit/v1/woocommerce/order/send/123' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertSame( 401, $response->get_status() );
	}

	/**
	 * Test that the /wp-json/kit/v1/woocommerce/order/send REST API route returns a 404
	 * when the WooCommerce Order does not exist.
	 *
	 * @since   2.0.6
	 */
	public function testSyncPastOrderWithInvalidOrderID()
	{
		// Create and become administrator.
		$this->actAsAdministrator();

		// Send request.
		$request  = new \WP_REST_Request( 'POST', '/kit/v1/woocommerce/order/send/123' );
		$response = rest_get_server()->dispatch( $request );

		// Assert response is successful.
		$this->assertSame( 200, $response->get_status() );

		// Assert response data has the expected keys and data.
		$data = $response->get_data();
		$this->assertEquals( false, $data['success'] );
		$this->assertEquals( 'Order ID #123 could not be found in WooCommerce.', $data['data'] );
	}

	/**
	 * Test that the /wp-json/kit/v1/blocks REST API route returns blocks when the user is authorized.
	 *
	 * @since   3.1.0
	 */
	public function testSyncPastOrder()
	{
		// Create and become administrator.
		$this->actAsAdministrator();

		// Create a WooCommerce Order.
		$orderID = static::factory()->post->create(
			[
				'post_type'   => 'shop_order',
				'post_status' => 'processing',
			]
		);

		// Send request.
		$request  = new \WP_REST_Request( 'POST', '/kit/v1/woocommerce/order/send/' . $orderID );
		$response = rest_get_server()->dispatch( $request );

		// Assert response is successful.
		$this->assertSame( 200, $response->get_status() );

		// Assert response data contains the expected message.
		$data = $response->get_data();
		var_dump($orderID);
		var_dump($data);
		die();
		$this->assertEquals( true, $data['success'] );
		$this->assertStringContainsString( 'WooCommerce Order ID #' . $orderID . ' added to Kit Purchase Data successfully. Kit Purchase ID: #' . get_post_meta( $orderID, 'ckwc_purchase_data_id', true ), $data['data'] );
	}

	/**
	 * Act as an administrator.
	 *
	 * @since   2.0.6
	 */
	private function actAsAdministrator()
	{
		$administrator_id = static::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $administrator_id );
	}
}
