<?php

namespace Tests;

use lucatume\WPBrowser\TestCase\WPTestCase;

/**
 * Tests that the CKWC_Admin_Post_Type, CKWC_Admin_Quick_Edit and CKWC_Admin_Bulk_Edit
 * classes only enqueue assets on their screens, and don't error when the current screen
 * isn't set.
 *
 * @since   2.2.5
 */
class AdminScreenTest extends WPTestCase
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
	 * @since   2.2.5
	 */
	public function setUp(): void
	{
		parent::setUp();

		// Activate Plugin, to include the Plugin's constants in tests.
		activate_plugins('woocommerce/woocommerce.php');
		activate_plugins('convertkit-woocommerce/woocommerce-convertkit.php');

		// Load WordPress' screen functions, which are only loaded in the WordPress Administration.
		require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
		require_once ABSPATH . 'wp-admin/includes/screen.php';

		// Unset the current screen.
		unset( $GLOBALS['current_screen'] );
	}

	/**
	 * Performs actions after each test.
	 *
	 * @since   2.2.5
	 */
	public function tearDown(): void
	{
		// Unset the current screen.
		unset( $GLOBALS['current_screen'] );

		// Dequeue assets.
		wp_dequeue_script( 'ckwc-select2' );
		wp_dequeue_script( 'ckwc-admin-quick-edit' );
		wp_dequeue_script( 'ckwc-bulk-edit' );
		wp_dequeue_style( 'ckwc-bulk-quick-edit' );
		wp_dequeue_style( 'ckwc-refresh-resources' );

		parent::tearDown();
	}

	/**
	 * Test that the Product class doesn't error and doesn't enqueue assets when the current screen isn't set.
	 *
	 * @since   2.2.5
	 */
	public function testPostTypeWhenScreenNotSet()
	{
		$admin = new \CKWC_Admin_Product();
		$admin->enqueue_scripts();
		$admin->enqueue_styles();

		$this->assertFalse( wp_script_is( 'ckwc-select2', 'enqueued' ) );
		$this->assertFalse( wp_style_is( 'ckwc-refresh-resources', 'enqueued' ) );
	}

	/**
	 * Test that the Product class enqueues assets on the Edit Product screen.
	 *
	 * @since   2.2.5
	 */
	public function testPostTypeOnEditScreen()
	{
		set_current_screen( 'product' );

		$admin = new \CKWC_Admin_Product();
		$admin->enqueue_scripts();
		$admin->enqueue_styles();

		$this->assertTrue( wp_script_is( 'ckwc-select2', 'enqueued' ) );
		$this->assertTrue( wp_style_is( 'ckwc-refresh-resources', 'enqueued' ) );
	}

	/**
	 * Test that the Quick Edit class doesn't error and doesn't enqueue assets when the current screen isn't set.
	 *
	 * @since   2.2.5
	 */
	public function testQuickEditWhenScreenNotSet()
	{
		$admin = new \CKWC_Admin_Quick_Edit();
		$admin->enqueue_assets();

		$this->assertFalse( wp_script_is( 'ckwc-admin-quick-edit', 'enqueued' ) );
	}

	/**
	 * Test that the Quick Edit class enqueues assets on the Products WP_List_Table.
	 *
	 * @since   2.2.5
	 */
	public function testQuickEditOnProductsScreen()
	{
		set_current_screen( 'edit-product' );

		$admin = new \CKWC_Admin_Quick_Edit();
		$admin->enqueue_assets();

		$this->assertTrue( wp_script_is( 'ckwc-admin-quick-edit', 'enqueued' ) );
	}

	/**
	 * Test that the Bulk Edit class doesn't error and doesn't enqueue assets when the current screen isn't set.
	 *
	 * @since   2.2.5
	 */
	public function testBulkEditWhenScreenNotSet()
	{
		$admin = new \CKWC_Admin_Bulk_Edit();
		$admin->enqueue_assets();

		$this->assertFalse( wp_script_is( 'ckwc-bulk-edit', 'enqueued' ) );
	}

	/**
	 * Test that the Bulk Edit class enqueues assets on the Products WP_List_Table.
	 *
	 * @since   2.2.5
	 */
	public function testBulkEditOnProductsScreen()
	{
		set_current_screen( 'edit-product' );

		$admin = new \CKWC_Admin_Bulk_Edit();
		$admin->enqueue_assets();

		$this->assertTrue( wp_script_is( 'ckwc-bulk-edit', 'enqueued' ) );
	}
}
