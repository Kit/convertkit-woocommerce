<?php

namespace Tests\EndToEnd;

use Tests\Support\EndToEndTester;

/**
 * Tests for Sync Past Orders functionality.
 *
 * @since   1.9.0
 */
class SyncPastOrdersCest
{
	/**
	 * Run common actions before running the test functions in this class.
	 *
	 * @since   1.9.0
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function _before(EndToEndTester $I)
	{
		// Activate Plugin.
		$I->activateWooCommerceAndConvertKitPlugins($I);

		// Disable HPOS.
		$I->disableWooCommerceHPOS($I);

		// Setup WooCommerce Plugin.
		$I->setupWooCommercePlugin($I);

		// Activate Custom Order Numbers Plugin.
		$I->activateThirdPartyPlugin($I, 'custom-order-numbers-for-woocommerce');

		// Setup Custom Order Numbers Plugin.
		$I->setupCustomOrderNumbersPlugin($I);
	}

	/**
	 * Test that no button is displayed on the Integration Settings screen
	 * when the Integration is disabled.
	 *
	 * @since   1.9.0
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testNoButtonDisplayedWhenIntegrationDisabled(EndToEndTester $I)
	{
		// Delete all existing WooCommerce Orders from the database.
		$I->wooCommerceDeleteAllOrders($I);

		// Create Product.
		$productName = 'Simple Product';
		$productID   = $I->wooCommerceCreateSimpleProduct($I, false);

		// Define Email Address for this Test.
		$emailAddress = $I->generateEmailAddress();

		// Logout as the WordPress Administrator.
		$I->logOut();

		// Add Product to Cart and load Checkout.
		$I->wooCommerceCheckoutWithProduct(
			$I,
			productID: $productID,
			productName: $productName,
			emailAddress: $emailAddress
		);

		// Click Place order button.
		$I->waitForElementNotVisible('.blockOverlay');
		$I->scrollTo('#order_review_heading');
		$I->click('#place_order');

		// Wait until JS completes and redirects.
		$I->waitForElement('.woocommerce-order-received', 10);

		// Get data.
		$result = [
			'email_address' => $emailAddress,
			'product_id'    => $productID,
			'order_id'      => (int) $I->grabTextFrom('ul.wc-block-order-confirmation-summary-list li:first-child span.wc-block-order-confirmation-summary-list-item__value'),
		];

		// Login as the Administrator, if we're not already logged in.
		if ( ! $I->amLoggedInAsAdmin($I) ) {
			$I->doLoginAsAdmin($I);
		}

		// Load Settings screen.
		$I->loadConvertKitSettingsScreen($I);

		// Confirm that no Sync Past Order button is displayed.
		$I->dontSeeElementInDOM('a#ckwc_sync_past_orders');
	}

	/**
	 * Test that no button is displayed on the Integration Settings screen
	 * when the Integration is enabled, valid API credentials are specified
	 * but there are no WooCommerce Orders.
	 *
	 * @since   1.9.0
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testNoButtonDisplayedWhenNoOrders(EndToEndTester $I)
	{
		// Delete all existing WooCommerce Orders from the database.
		$I->wooCommerceDeleteAllOrders($I);

		// Enable Integration and define its Access and Refresh Tokens.
		$I->setupConvertKitPlugin($I);

		// Load Settings screen.
		$I->loadConvertKitSettingsScreen($I);

		// Confirm that no Sync Past Order button is displayed.
		$I->dontSeeElementInDOM('a#ckwc_sync_past_orders');
	}

	/**
	 * Test that no button is displayed on the Integration Settings screen
	 * when:
	 * - the Integration is enabled,
	 * - valid API credentials are specified,
	 * - a WooCommerce Order exists, that has had its Purchase Data sent to ConvertKit.
	 *
	 * @since   1.9.0
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testNoButtonDisplayedWhenNoPastOrders(EndToEndTester $I)
	{
		// Delete all existing WooCommerce Orders from the database.
		$I->wooCommerceDeleteAllOrders($I);

		// Create Product and Checkout for this test, sending the Order
		// to ConvertKit.
		$result = $I->wooCommerceCreateProductAndCheckoutWithConfig(
			$I,
			[
				'send_purchase_data' => true,
			]
		);

		// Load Settings screen.
		$I->loadConvertKitSettingsScreen($I);

		// Confirm that no Sync Past Order button is displayed.
		$I->dontSeeElementInDOM('a#ckwc_sync_past_orders');
	}

	/**
	 * Test that no button is displayed on the Integration Settings screen
	 * when:
	 * - the Integration is enabled,
	 * - valid API credentials are specified,
	 * - a WooCommerce Order exists, that has been refunded.
	 *
	 * @since   1.9.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testSyncPastOrderExcludesRefunds(EndToEndTester $I)
	{
		// Delete all existing WooCommerce Orders from the database.
		$I->wooCommerceDeleteAllOrders($I);

		// Create Product and Checkout for this test, sending the Order
		// to Kit.
		$result = $I->wooCommerceCreateProductAndCheckoutWithConfig($I);

		// Refund the Order.
		$I->wooCommerceRefundOrder(
			$I,
			orderID: $result['order_id'],
			amount: 10
		);

		// Load Settings screen.
		$I->loadConvertKitSettingsScreen($I);

		// Confirm that no Sync Past Order button is displayed, as the Order
		// is fully refunded.
		$I->dontSeeElementInDOM('a#ckwc_sync_past_orders');
	}

	/**
	 * Test that no button is displayed on the Integration Settings screen
	 * when:
	 * - the Integration is enabled,
	 * - valid API credentials are specified,
	 * - a WooCommerce Order exists, that has no email address.
	 *
	 * @since   1.9.3
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testSyncPastOrderExcludesOrdersWithNoEmailAddress(EndToEndTester $I)
	{
		// Delete all existing WooCommerce Orders from the database.
		$I->wooCommerceDeleteAllOrders($I);

		// Create Product and Checkout for this test, not sending the Order
		// to ConvertKit.
		$result = $I->wooCommerceCreateProductAndCheckoutWithConfig(
			$I,
			[
				'send_purchase_data' => false,
			]
		);

		// Login as the Administrator, if we're not already logged in.
		if ( ! $I->amLoggedInAsAdmin($I) ) {
			$I->doLoginAsAdmin($I);
		}

		// Enable Send Purchase Data, so the Sync Past Orders button is displayed.
		$I->setupConvertKitPlugin(
			$I,
			sendPurchaseDataEvent: 'processing'
		);

		// Load Settings screen.
		$I->loadConvertKitSettingsScreen($I);

		// Confirm that the Sync Past Order button is displayed.
		$I->seeElementInDOM('a#ckwc_sync_past_orders');

		// Remove the email address from the Order.
		$I->wooCommerceChangeOrderEmailAddress(
			$I,
			orderID: $result['order_id']
		);

		// Load Settings screen.
		$I->loadConvertKitSettingsScreen($I);

		// Confirm that no Sync Past Order button is displayed.
		$I->dontSeeElementInDOM('a#ckwc_sync_past_orders');
	}

	/**
	 * Test that a button is displayed on the Integration Settings screen
	 * when:
	 * - the Integration is enabled,
	 * - valid API credentials are specified,
	 * - a WooCommerce Order exists, that has not had its Purchase Data sent to ConvertKit,
	 * - clicking the button sends the Order to ConvertKit.
	 *
	 * @since   1.9.0
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testSyncPastOrder(EndToEndTester $I)
	{
		// Delete all existing WooCommerce Orders from the database.
		$I->wooCommerceDeleteAllOrders($I);

		// Create Product and Checkout for this test, not sending the Order
		// to ConvertKit.
		$result = $I->wooCommerceCreateProductAndCheckoutWithConfig($I);

		// Login as the Administrator, if we're not already logged in.
		if ( ! $I->amLoggedInAsAdmin($I) ) {
			$I->doLoginAsAdmin($I);
		}

		// Enable Send Purchase Data, so the Sync Past Orders button is displayed.
		$I->setupConvertKitPlugin(
			$I,
			sendPurchaseDataEvent: 'processing'
		);

		// Load Settings screen.
		$I->loadConvertKitSettingsScreen($I);

		// Confirm that the Sync Past Order button is displayed.
		$I->seeElementInDOM('a#ckwc_sync_past_orders');

		// Click the button.
		$I->click('a#ckwc_sync_past_orders');

		// Confirm the popup.
		$I->acceptPopup();

		// Confirm CSS and JS is output by the Plugin.
		$I->seeCSSEnqueued($I, 'convertkit-woocommerce/resources/backend/css/settings.css', 'ckwc-settings-css' );
		$I->seeCSSEnqueued($I, 'convertkit-woocommerce/resources/backend/css/sync-past-orders.css', 'ckwc-sync-past-orders-css' );
		$I->seeJSEnqueued($I, 'convertkit-woocommerce/resources/backend/js/synchronous-ajax.js' );
		$I->seeJSEnqueued($I, 'convertkit-woocommerce/resources/backend/js/sync-past-orders.js' );

		// Wait a few seconds for the API call to be made.
		$I->wait(5);

		// Extract the Post ID from the Order ID, as the Custom Order Numbers Plugin does not prefix
		// the order ID in the database or log entries.
		$orderIDParts = explode('-', $result['order_id']);
		$postID       = $orderIDParts[ count($orderIDParts) - 1 ];

		// Confirm that the log shows a success message.
		$I->seeInSource('WooCommerce Order ID #' . $postID . ' added to Kit Purchase Data successfully.');

		// Confirm that the purchase was added to ConvertKit.
		$purchaseDataID = $I->apiCheckPurchaseExists(
			$I,
			orderID: $result['order_id'],
			emailAddress: $result['email_address'],
			productID: $result['product_id']
		);

		// Confirm that the Cancel Sync button is disabled.
		$I->seeElementInDOM('a.cancel[disabled]');

		// Click the Return to settings button.
		$I->click('Return to settings');

		// Confirm that the Settings screen is displayed.
		$I->seeInSource('Enable Kit integration');

		// Confirm that the Transaction ID is stored in the Order's metadata.
		$I->wooCommerceOrderMetaKeyAndValueExist(
			$I,
			orderID: $result['order_id'],
			metaKey: 'ckwc_purchase_data_sent',
			metaValue: 'yes'
		);
		$I->wooCommerceOrderMetaKeyAndValueExist(
			$I,
			orderID: $result['order_id'],
			metaKey: 'ckwc_purchase_data_id',
			metaValue: $purchaseDataID
		);
	}

	/**
	 * Tests that a WooCommerce Order, which has had its Purchase Data sent to ConvertKit
	 * in Plugin version 1.4.2 or older, will be synced in 1.4.5 and higher, to ensure that
	 * the ConvertKit Purchase / Transaction ID is stored in the Order's metadata.
	 *
	 * 1.4.2 and older would mark a WooCommerce Order as sent to ConvertKit by adding the 'ckwc_purchase_data_sent'
	 * meta key to the Order, with a value of 'yes' - however did not store the ConvertKit Purchase Data API response's
	 * Transaction ID in the Order, meaning there is no way to potentially map WooCommerce Orders to ConvertKit API data.
	 *
	 * 1.9.0 and later performs the same as 1.4.2, but also storing the ConvertKit Transaction ID in the 'ckwc_purchase_data_id',
	 * allowing for the possibility of future mapping between WooCommerce and ConvertKit.
	 *
	 * This test ensures that a 1.4.2 or older Order, which was already sent to ConvertKit, will be sent again so that
	 * the ConvertKit Purchase Data is overwritten, and the ConvertKit Transaction ID is stored against the WooCommerce Order.
	 *
	 * @since   1.4.4
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testSyncPastOrderCreatedInPreviousPluginVersion(EndToEndTester $I)
	{
		// Delete all existing WooCommerce Orders from the database.
		$I->wooCommerceDeleteAllOrders($I);

		// Create Product and Checkout for this test, not sending the Order
		// to ConvertKit.
		$result = $I->wooCommerceCreateProductAndCheckoutWithConfig(
			$I,
			[
				'send_purchase_data' => true,
			]
		);

		// Extract the Post ID from the Order ID, as the Custom Order Numbers Plugin does not prefix
		// the order ID in the database.
		$orderIDParts = explode('-', $result['order_id']);
		$postID       = $orderIDParts[ count($orderIDParts) - 1 ];

		// Remove the Transaction ID metadata in the Order, as if it were sent
		// by 1.4.2 or older.
		$I->wooCommerceOrderDeleteMeta(
			$I,
			orderID: $postID,
			metaKey: 'ckwc_purchase_data_id'
		);

		// Login as the Administrator, if we're not already logged in.
		if ( ! $I->amLoggedInAsAdmin($I) ) {
			$I->doLoginAsAdmin($I);
		}

		// Load Settings screen.
		$I->loadConvertKitSettingsScreen($I);

		// Confirm that the Sync Past Order button is displayed.
		$I->seeElementInDOM('a#ckwc_sync_past_orders');

		// Click the button.
		$I->click('a#ckwc_sync_past_orders');

		// Confirm the popup.
		$I->acceptPopup();

		// Wait a few seconds for the API call to be made.
		$I->wait(5);

		// Confirm that the log shows a success message.
		$I->seeInSource('WooCommerce Order ID #' . $postID . ' added to Kit Purchase Data successfully.');

		// Confirm that the purchase was added to ConvertKit.
		$purchaseDataID = $I->apiCheckPurchaseExists(
			$I,
			orderID: $result['order_id'],
			emailAddress: $result['email_address'],
			productID: $result['product_id']
		);

		// Confirm that the Cancel Sync button is disabled.
		$I->seeElementInDOM('a.cancel[disabled]');

		// Confirm that the Transaction ID is stored in the Order's metadata.
		$I->wooCommerceOrderMetaKeyAndValueExist(
			$I,
			orderID: $result['order_id'],
			metaKey: 'ckwc_purchase_data_sent',
			metaValue: 'yes'
		);
		$I->wooCommerceOrderMetaKeyAndValueExist(
			$I,
			orderID: $result['order_id'],
			metaKey: 'ckwc_purchase_data_id',
			metaValue: $purchaseDataID
		);
	}

	/**
	 * Test that a WooCommerce Order, that has not had its Purchase Data sent to ConvertKit,
	 * is not sent to ConvertKit when attempting to access the Sync Past Orders screen
	 * and the API credentials are invalid.
	 *
	 * @since   1.9.0
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testSyncPastOrderWithInvalidAPICredentials(EndToEndTester $I)
	{
		// Delete all existing WooCommerce Orders from the database.
		$I->wooCommerceDeleteAllOrders($I);

		// Create Product and Checkout for this test, not sending the Order
		// to ConvertKit.
		$result = $I->wooCommerceCreateProductAndCheckoutWithConfig($I);

		// Login as the Administrator, if we're not already logged in.
		if ( ! $I->amLoggedInAsAdmin($I) ) {
			$I->doLoginAsAdmin($I);
		}

		// Specify invalid API credentials.
		$I->haveOptionInDatabase(
			'woocommerce_ckwc_settings',
			[
				'enabled'       => 'yes',
				'access_token'  => 'fakeAccessToken',
				'refresh_token' => 'fakeRefreshToken',
				'debug'         => 'yes',
			]
		);

		// Attempt to directly load the Sync Orders screen.
		// This won't be available via a button, as loading the Settings screen will correctly state the access token is invalid
		// and only show the Connect button to begin the OAuth flow.
		$I->amOnAdminPage('admin.php?page=wc-settings&tab=integration&section=ckwc&sub_section=sync_past_orders');

		// Confirm an error message is displayed confirming that the access token is invalid.
		$I->see('The access token is invalid');

		// Confirm the Connect button displays.
		$I->see('Connect');
		$I->dontSee('Disconnect');
		$I->dontSeeElementInDOM('button.woocommerce-save-button');

		// Confirm that the purchase was not added to ConvertKit.
		$I->apiCheckPurchaseDoesNotExist(
			$I,
			orderID: $result['order_id'],
			emailAddress: $result['email_address']
		);
	}

	/**
	 * Deactivate and reset Plugin(s) after each test, if the test passes.
	 * We don't use _after, as this would provide a screenshot of the Plugin
	 * deactivation and not the true test error.
	 *
	 * @since   1.4.4
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function _passed(EndToEndTester $I)
	{
		$I->deactivateThirdPartyPlugin($I, 'custom-order-numbers-for-woocommerce');
		$I->deactivateWooCommerceAndConvertKitPlugins($I);
		$I->resetConvertKitPlugin($I);
	}
}
