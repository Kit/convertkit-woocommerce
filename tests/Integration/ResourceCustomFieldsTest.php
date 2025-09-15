<?php

namespace Tests;

use lucatume\WPBrowser\TestCase\WPTestCase;

/**
 * Tests for the CKWC_Resource_Custom_Fields class.
 *
 * @since   1.4.7
 */
class ResourceCustomFieldsTest extends \Codeception\TestCase\WPTestCase
{
	/**
	 * The testing implementation.
	 *
	 * @var \WpunitTester
	 */
	protected $tester;

	/**
	 * Holds the key name that stores settings for this Plugin.
	 *
	 * @since   1.4.7
	 *
	 * @var     string
	 */
	private $settings_key = 'woocommerce_ckwc_settings';

	/**
	 * Holds the ConvertKit Resource class.
	 *
	 * @since   1.4.7
	 *
	 * @var     ConvertKit_Resource_Custom_Fields
	 */
	private $resource;

	/**
	 * Performs actions before each test.
	 *
	 * @since   1.4.7
	 */
	public function setUp(): void
	{
		parent::setUp();

		// Enable integration, storing Access Token and Refresh Token in Plugin's settings.
		WP_CKWC_Integration()->update_option( 'enabled', 'yes' );
		WP_CKWC_Integration()->update_option( 'access_token', $_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN'] );
		WP_CKWC_Integration()->update_option( 'refresh_token', $_ENV['CONVERTKIT_OAUTH_REFRESH_TOKEN'] );

		// Initialize the resource class we want to test.
		$this->resource = new \CKWC_Resource_Custom_Fields();
		$this->assertNotInstanceOf(\WP_Error::class, $this->resource->resources);
	}

	/**
	 * Performs actions after each test.
	 *
	 * @since   1.9.6.9
	 */
	public function tearDown(): void
	{
		// Disable integration, removing Access Token and Refresh Token from Plugin's settings.
		WP_CKWC_Integration()->update_option( 'enabled', 'no' );
		WP_CKWC_Integration()->update_option( 'access_token', '' );
		WP_CKWC_Integration()->update_option( 'refresh_token', '' );

		// Delete Settings and Resources from options table.
		delete_option($this->settings_key);
		delete_option($this->resource->settings_name);
		delete_option($this->resource->settings_name . '_last_queried');
		parent::tearDown();
	}

	/**
	 * Test that the refresh() function performs as expected.
	 *
	 * @since   1.4.7
	 */
	public function testRefresh()
	{
		// Confirm that the data is stored in the options table and includes some expected keys.
		$result = $this->resource->refresh();
		$this->assertIsArray($result);
		$this->assertArrayHasKey('id', reset($result));
		$this->assertArrayHasKey('name', reset($result));
	}

	/**
	 * Test that the expiry timestamp is set and returns the expected value.
	 *
	 * @since   1.4.7
	 */
	public function testExpiry()
	{
		// Define the expected expiry date based on the resource class' $cache_duration setting.
		$expectedExpiryDate = date('Y-m-d', time() + $this->resource->cache_duration);

		// Fetch the actual expiry date set when the resource class was initialized.
		$expiryDate = date('Y-m-d', $this->resource->last_queried + $this->resource->cache_duration);

		// Confirm both dates match.
		$this->assertEquals($expectedExpiryDate, $expiryDate);
	}

	/**
	 * Tests that the get() function returns resources in alphabetical ascending order
	 * by default.
	 *
	 * @since   1.4.7
	 */
	public function testGet()
	{
		// Call resource class' get() function.
		$result = $this->resource->get();

		// Assert result is an array.
		$this->assertIsArray($result);

		// Assert top level array keys are preserved.
		$this->assertArrayHasKey(array_key_first($this->resource->resources), $result);
		$this->assertArrayHasKey(array_key_last($this->resource->resources), $result);

		// Assert resource within results has expected array keys.
		$this->assertArrayHasKey('id', reset($result));
		$this->assertArrayHasKey('name', reset($result));
		$this->assertArrayHasKey('key', reset($result));
		$this->assertArrayHasKey('label', reset($result));

		// Assert order of data is in ascending alphabetical order.
		$this->assertEquals('Billing Address', reset($result)[ $this->resource->order_by ]);
		$this->assertEquals('URL', end($result)[ $this->resource->order_by ]);
	}

	/**
	 * Tests that the get() function returns resources in alphabetical descending order
	 * when a valid order_by and order properties are defined.
	 *
	 * @since   1.5.7
	 */
	public function testGetWithValidOrderByAndOrder()
	{
		// Define order_by and order.
		$this->resource->order_by = 'key';
		$this->resource->order    = 'desc';

		// Call resource class' get() function.
		$result = $this->resource->get();

		// Assert result is an array.
		$this->assertIsArray($result);

		// Assert top level array keys are preserved.
		$this->assertArrayHasKey(array_key_first($this->resource->resources), $result);
		$this->assertArrayHasKey(array_key_last($this->resource->resources), $result);

		// Assert resource within results has expected array keys.
		$this->assertArrayHasKey('id', reset($result));
		$this->assertArrayHasKey('name', reset($result));
		$this->assertArrayHasKey('key', reset($result));
		$this->assertArrayHasKey('label', reset($result));

		// Assert order of data is in descending alphabetical order.
		$this->assertEquals('url', reset($result)[ $this->resource->order_by ]);
		$this->assertEquals('billing_address', end($result)[ $this->resource->order_by ]);
	}

	/**
	 * Tests that the get() function returns resources in their original order
	 * when populated with Forms and an invalid order_by value is specified.
	 *
	 * @since   1.5.7
	 */
	public function testGetWithInvalidOrderBy()
	{
		// Define order_by with an invalid value (i.e. an array key that does not exist).
		$this->resource->order_by = 'invalid_key';

		// Call resource class' get() function.
		$result = $this->resource->get();

		// Assert result is an array.
		$this->assertIsArray($result);

		// Assert top level array keys are preserved.
		$this->assertArrayHasKey(array_key_first($this->resource->resources), $result);
		$this->assertArrayHasKey(array_key_last($this->resource->resources), $result);

		// Assert resource within results has expected array keys.
		$this->assertArrayHasKey('id', reset($result));
		$this->assertArrayHasKey('name', reset($result));
		$this->assertArrayHasKey('key', reset($result));
		$this->assertArrayHasKey('label', reset($result));

		// Assert order of data has not changed.
		$this->assertEquals('URL', reset($result)['label']);
		$this->assertEquals('Notes', end($result)['label']);
	}

	/**
	 * Test that the count() function returns the number of resources.
	 *
	 * @since   1.4.7
	 */
	public function testCount()
	{
		$result = $this->resource->get();
		$this->assertEquals($this->resource->count(), count($result));
	}

	/**
	 * Test that the exist() function performs as expected.
	 *
	 * @since   1.4.7
	 */
	public function testExist()
	{
		// Confirm that the function returns true, because resources exist.
		$result = $this->resource->exist();
		$this->assertSame($result, true);
	}
}
