<?php
/**
 * Tests for Sync Past Orders functionality using WP-CLI
 *
 * @since   1.9.0
 */
class SyncPastOrdersCLICest
{
	/**
	 * Run common actions before running the test functions in this class.
	 *
	 * @since   1.9.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function _before(AcceptanceTester $I)
	{
		// Activate Plugin.
		$I->activateWooCommerceAndConvertKitPlugins($I);

		// Disable HPOS.
		$I->disableWooCommerceHPOS($I);

		// Setup WooCommerce Plugin.
		$I->setupWooCommercePlugin($I);

		// Setup ConvertKit for WooCommerce Plugin.
		$I->setupConvertKitPlugin($I);

		// Activate Custom Order Numbers Plugin.
		$I->activateThirdPartyPlugin($I, 'custom-order-numbers-for-woocommerce');

		// Setup Custom Order Numbers Plugin.
		$I->setupCustomOrderNumbersPlugin($I);

		// Delete all existing WooCommerce Orders from the database.
		$I->wooCommerceDeleteAllOrders($I);
	}

	/**
	 * Test that the CLI command returns the expected console output when
	 * attempting to sync past orders to ConvertKit Purchase Data, and no
	 * WooCommerce Orders exist.
	 *
	 * @since   1.9.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testSyncPastOrdersWhenNoOrdersExist(AcceptanceTester $I)
	{
		$I->cli([ 'ckwc-sync-past-orders' ]);
		$I->seeInShellOutput('No outstanding Orders to send to Kit');
	}

	/**
	 * Test that the CLI command returns the expected console output when
	 * attempting to sync past orders to ConvertKit Purchase Data, and
	 * WooCommerce Orders exist.
	 *
	 * @since   1.9.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testSyncPastOrders(AcceptanceTester $I)
	{
		// Create Product and Checkout for this test, not sending the Order
		// to ConvertKit.
		$result = $I->wooCommerceCreateProductAndCheckoutWithConfig($I);

		// Remove prefix from Order ID, as CLI will not show the Custom Order Number Prefix.
		$orderIDParts = explode( '-', $result['order_id'] );
		$orderID      = $orderIDParts[ count($orderIDParts) - 1 ];

		// Run CLI command.
		$I->cli([ 'ckwc-sync-past-orders' ]);
		$I->seeInShellOutput('WooCommerce Order ID #' . $orderID . ' added to Kit Purchase Data successfully. Kit Purchase ID: #');

		// Confirm that the Order was added to ConvertKit.
		$I->apiCheckPurchaseExists($I, $result['order_id'], $result['email_address'], $result['product_id']);
	}

	/**
	 * Test that the CLI command returns the expected console output when
	 * attempting to sync past orders to ConvertKit Purchase Data using
	 * the --limit argument, and WooCommerce Orders exist.
	 *
	 * @since   1.9.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testSyncPastOrdersWithLimitArgument(AcceptanceTester $I)
	{
		// Create Product and Checkout for this test, not sending the Order
		// to ConvertKit.
		$results = [
			$I->wooCommerceCreateProductAndCheckoutWithConfig($I),
			$I->wooCommerceCreateProductAndCheckoutWithConfig($I),
		];

		// Run CLI command with --limit=1 to send each Order individually.
		foreach (array_reverse($results) as $result) {
			// Remove prefix from Order ID, as CLI will not show the Custom Order Number Prefix.
			$orderIDParts = explode( '-', $result['order_id'] );
			$orderID      = $orderIDParts[ count($orderIDParts) - 1 ];

			// Run CLI command.
			$I->cli([ 'ckwc-sync-past-orders', '--limit=1' ]);
			$I->seeInShellOutput('WooCommerce Order ID #' . $orderID . ' added to Kit Purchase Data successfully. Kit Purchase ID: #');

			// Confirm that the Order was added to ConvertKit.
			$I->apiCheckPurchaseExists($I, $result['order_id'], $result['email_address'], $result['product_id']);
		}
	}

	/**
	 * Deactivate and reset Plugin(s) after each test, if the test passes.
	 * We don't use _after, as this would provide a screenshot of the Plugin
	 * deactivation and not the true test error.
	 *
	 * @since   1.4.4
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function _passed(AcceptanceTester $I)
	{
		$I->deactivateThirdPartyPlugin($I, 'custom-order-numbers-for-woocommerce');
		$I->deactivateWooCommerceAndConvertKitPlugins($I);
		$I->resetConvertKitPlugin($I);
	}
}
