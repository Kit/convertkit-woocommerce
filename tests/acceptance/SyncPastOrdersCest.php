<?php
/**
 * Tests for Sync Past Orders functionality.
 * 
 * @since 	1.4.3
 */
class SyncPastOrdersCest
{
	/**
	 * Run common actions before running the test functions in this class.
	 * 
	 * @since 	1.4.3
	 * 
	 * @param 	AcceptanceTester 	$I 	Tester
	 */
	public function _before(AcceptanceTester $I)
	{
		// Activate Plugin.
		$I->activateWooCommerceAndConvertKitPlugins($I);

		// Setup WooCommerce Plugin.
		$I->setupWooCommercePlugin($I);
	}

	/**
	 * Test that no button is displayed on the Integration Settings screen
	 * when the Integration is disabled.
	 * 
	 * @since 	1.4.3
	 * 
	 * @param 	AcceptanceTester 	$I 	Tester
	 */
	public function testNoButtonDisplayedWhenIntegrationDisabled(AcceptanceTester $I)
	{
		// Delete all existing WooCommerce Orders from the database.
		$I->dontHavePostInDatabase(['post_type' => 'shop_order']);

		// Disable the Integration.
		$I->loadConvertKitSettingsScreen($I);
		$I->checkOption('#woocommerce_ckwc_enabled');
		$I->fillField('woocommerce_ckwc_api_key', '');
		$I->fillField('woocommerce_ckwc_api_secret', '');
		$I->uncheckOption('#woocommerce_ckwc_enabled');
		$I->click('Save changes');

		// Create Product.
		$productName = 'Simple Product';
		$productID = $I->wooCommerceCreateSimpleProduct($I, false);

		// Define Email Address for this Test.
		$emailAddress = 'wordpress-' . date( 'YmdHis' ) . '@convertkit.com';

		// Unsubscribe the email address, so we restore the account back to its previous state.
		$I->apiUnsubscribe($emailAddress);

		// Logout as the WordPress Administrator.
		$I->logOut();

		// Add Product to Cart and load Checkout.
		$I->wooCommerceCheckoutWithProduct($I, $productID, $productName, $emailAddress);

		// Click Place order button.
		$I->click('Place order');

		// Wait until JS completes and redirects.
		$I->waitForElement('.woocommerce-order-received', 10);
		
		// Get data.
		$result = [
			'email_address' => $emailAddress,
			'product_id' => $productID,
			'order_id' => (int) $I->grabTextFrom('.woocommerce-order-overview__order strong'),
		];

		// Login as the Administrator
		$I->loginAsAdmin();

		// Load Settings screen.
		$I->loadConvertKitSettingsScreen($I);

		// Confirm that no Sync Past Order button is displayed.
		$I->dontSeeElementInDOM('a#ckwc_sync_past_orders');
	}

	/**
	 * Test that no button is displayed on the Integration Settings screen
	 * when the Integration is enabled, and no API credentials are specified.
	 * 
	 * @since 	1.4.3
	 * 
	 * @param 	AcceptanceTester 	$I 	Tester
	 */
	public function testNoButtonDisplayedWhenIntegrationEnabledWithNoAPICredentials(AcceptanceTester $I)
	{
		// Delete all existing WooCommerce Orders from the database.
		$I->dontHavePostInDatabase(['post_type' => 'shop_order']);

		// Enable Integration and define its API Keys.
		$I->setupConvertKitPlugin($I);

		// Create Product and Checkout for this test.
		$result = $I->wooCommerceCreateProductAndCheckoutWithConfig(
			$I,
			'simple', // Simple Product
			false, // Don't display Opt-In checkbox on Checkout
			false, // Don't check Opt-In checkbox on Checkout
			false, // Form to subscribe email address to (not used)
			false, // Don't define a subscribe Event
			false // Don't send purchase data to ConvertKit
		);

		// Login as the Administrator
		$I->loginAsAdmin();

		// Load Settings screen.
		$I->loadConvertKitSettingsScreen($I);

		// Enable integration, removing API Keys.
		$I->checkOption('#woocommerce_ckwc_enabled');
		$I->fillField('woocommerce_ckwc_api_key', '');
		$I->fillField('woocommerce_ckwc_api_secret', '');

		// Save changes.
		$I->click('Save changes');

		// Confirm that no Sync Past Order button is displayed.
		$I->dontSeeElementInDOM('a#ckwc_sync_past_orders');
	}

	/**
	 * Test that no button is displayed on the Integration Settings screen
	 * when the Integration is enabled, valid API credentials are specified
	 * but there are no WooCommerce Orders.
	 * 
	 * @since 	1.4.3
	 * 
	 * @param 	AcceptanceTester 	$I 	Tester
	 */
	public function testNoButtonDisplayedWhenNoOrders(AcceptanceTester $I)
	{
		// Delete all existing WooCommerce Orders from the database.
		$I->dontHavePostInDatabase(['post_type' => 'shop_order']);

		// Enable Integration and define its API Keys.
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
	 * @since 	1.4.3
	 * 
	 * @param 	AcceptanceTester 	$I 	Tester
	 */
	public function testNoButtonDisplayedWhenNoPastOrders(AcceptanceTester $I)
	{
		// Delete all existing WooCommerce Orders from the database.
		$I->dontHavePostInDatabase(['post_type' => 'shop_order']);

		// Enable Integration and define its API Keys.
		$I->setupConvertKitPlugin($I);

		// Create Product and Checkout for this test, sending the Order
		// to ConvertKit.
		$result = $I->wooCommerceCreateProductAndCheckoutWithConfig(
			$I,
			'simple', // Simple Product
			false, // Don't display Opt-In checkbox on Checkout
			false, // Don't check Opt-In checkbox on Checkout
			false, // Form to subscribe email address to (not used)
			false, // Don't define a subscribe Event
			true // Send purchase data to ConvertKit
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
	 * @since 	1.4.3
	 * 
	 * @param 	AcceptanceTester 	$I 	Tester
	 */
	public function testSyncPastOrder(AcceptanceTester $I)
	{
		// Delete all existing WooCommerce Orders from the database.
		$I->dontHavePostInDatabase(['post_type' => 'shop_order']);

		// Enable Integration and define its API Keys.
		$I->setupConvertKitPlugin($I);

		// Create Product and Checkout for this test, not sending the Order
		// to ConvertKit.
		$result = $I->wooCommerceCreateProductAndCheckoutWithConfig(
			$I,
			'simple', // Simple Product
			false, // Don't display Opt-In checkbox on Checkout
			false, // Don't check Opt-In checkbox on Checkout
			false, // Form to subscribe email address to (not used)
			false, // Don't define a subscribe Event
			false // Don't send purchase data to ConvertKit
		);

		// Login as the Administrator
		$I->loginAsAdmin();

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
		$I->seeInSource('WooCommerce Order ID #' . $result['order_id'] . ' added to ConvertKit Purchase Data successfully.');

		// Confirm that the purchase was added to ConvertKit.
		$I->apiCheckPurchaseExists($I, $result['order_id'], $result['email_address'], $result['product_id']);

		// Confirm that the Cancel Sync button is disabled.
		$I->seeElementInDOM('a.cancel[disabled]');

		// Click the Return to settings button.
		$I->click('Return to settings');

		// Confirm that the Settings screen is displayed.
		$I->seeInSource('Enable ConvertKit integration');

		// Confirm that the Transaction ID is stored in the Order's metdata.
		$I->seePostMetaInDatabase([
			'post_id' => $result['order_id'],
			'meta_key' => 'ckwc_purchase_data_sent'
		]);
		$I->seePostMetaInDatabase([
			'post_id' => $result['order_id'],
			'meta_key' => 'ckwc_purchase_data_id'
		]);
	}

	/**
	 * Tests that a WooCommerce Order, which has had its Purchase Data sent to ConvertKit
	 * in Plugin version 1.4.2 or older, will be synced in 1.4.3 and higher, to ensure that
	 * the ConvertKit Purchase / Transaction ID is stored in the Order's metadata.
	 * 
	 * 1.4.2 and older would mark a WooCommerce Order as sent to ConvertKit by adding the 'ckwc_purchase_data_sent'
	 * meta key to the Order, with a value of 'yes' - however did not store the ConvertKit Purchase Data API response's
	 * Transaction ID in the Order, meaning there is no way to potentially map WooCommerce Orders to ConvertKit API data. 
	 * 
	 * 1.4.3 and later performs the same as 1.4.2, but also storing the ConvertKit Transaction ID in the 'ckwc_purchase_data_id',
	 * allowing for the possibility of future mapping between WooCommerce and ConvertKit.
	 * 
	 * This test ensures that a 1.4.2 or older Order, which was already sent to ConvertKit, will be sent again so that
	 * the ConvertKit Purchase Data is overwritten, and the ConvertKit Transaction ID is stored against the WooCommerce Order.
	 * 
	 * @since 	1.4.4
	 * 
	 * @param 	AcceptanceTester 	$I 	Tester
	 */
	public function testSyncPastOrderCreatedInPreviousPluginVersion(AcceptanceTester $I)
	{
		// Delete all existing WooCommerce Orders from the database.
		$I->dontHavePostInDatabase(['post_type' => 'shop_order']);

		// Enable Integration and define its API Keys.
		$I->setupConvertKitPlugin($I);

		// Create Product and Checkout for this test, not sending the Order
		// to ConvertKit.
		$result = $I->wooCommerceCreateProductAndCheckoutWithConfig(
			$I,
			'simple', // Simple Product
			false, // Don't display Opt-In checkbox on Checkout
			false, // Don't check Opt-In checkbox on Checkout
			false, // Form to subscribe email address to (not used)
			false, // Don't define a subscribe Event
			true // Don't send purchase data to ConvertKit
		);

		// Remove the Transaction ID metadata in the Order, as if it were sent
		// by 1.4.2 or older.
		$I->dontHavePostMetaInDatabase([
			'post_id' => $result['order_id'],
			'meta_key' => 'ckwc_purchase_data_id'
		]);

		// Login as the Administrator
		$I->loginAsAdmin();

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
		$I->seeInSource('WooCommerce Order ID #' . $result['order_id'] . ' added to ConvertKit Purchase Data successfully.');

		// Confirm that the purchase was added to ConvertKit.
		$I->apiCheckPurchaseExists($I, $result['order_id'], $result['email_address'], $result['product_id']);

		// Confirm that the Cancel Sync button is disabled.
		$I->seeElementInDOM('a.cancel[disabled]');

		// Confirm that the Transaction ID is stored in the Order's metdata.
		$I->seePostMetaInDatabase([
			'post_id' => $result['order_id'],
			'meta_key' => 'ckwc_purchase_data_sent'
		]);
		$I->seePostMetaInDatabase([
			'post_id' => $result['order_id'],
			'meta_key' => 'ckwc_purchase_data_id'
		]);
	}

	/**
	 * Test that a WooCommerce Order, that has not had its Purchase Data sent to ConvertKit,
	 * is not sent to ConvertKit when the Sync Past Orders button is clicked and the API
	 * credentials are invalid.
	 * 
	 * @since 	1.4.3
	 * 
	 * @param 	AcceptanceTester 	$I 	Tester
	 */
	public function testSyncPastOrderWithInvalidAPICredentials(AcceptanceTester $I)
	{
		// Delete all existing WooCommerce Orders from the database.
		$I->dontHavePostInDatabase(['post_type' => 'shop_order']);

		// Enable Integration and define its API Keys.
		$I->setupConvertKitPlugin($I);

		// Create Product and Checkout for this test, not sending the Order
		// to ConvertKit.
		$result = $I->wooCommerceCreateProductAndCheckoutWithConfig(
			$I,
			'simple', // Simple Product
			false, // Don't display Opt-In checkbox on Checkout
			false, // Don't check Opt-In checkbox on Checkout
			false, // Form to subscribe email address to (not used)
			false, // Don't define a subscribe Event
			false // Don't send purchase data to ConvertKit
		);

		// Login as the Administrator
		$I->loginAsAdmin();

		// Load Settings screen.
		$I->loadConvertKitSettingsScreen($I);

		// Enable the Integration and define invalid API Credentials.
		$I->loadConvertKitSettingsScreen($I);
		$I->checkOption('#woocommerce_ckwc_enabled');
		$I->fillField('woocommerce_ckwc_api_key', 'invalidApiKey');
		$I->fillField('woocommerce_ckwc_api_secret', 'invalidApiSecret');
		$I->click('Save changes');

		// Confirm that the Sync Past Order button is displayed.
		$I->seeElementInDOM('a#ckwc_sync_past_orders');

		// Click the button.
		$I->click('a#ckwc_sync_past_orders');

		// Confirm the popup.
		$I->acceptPopup();

		// Wait a few seconds for the API call to be made.
		$I->wait(5);

		// Confirm that the log shows a success message.
		$I->seeInSource('1/1: Response Error: Authorization Failed: API Key not valid');

		// Cancel sync.
		$I->click('Cancel');

		// Wait a few seconds for the current request to complete.
		$I->wait(5);

		// Confirm that the Cancel Sync button is disabled.
		$I->seeElementInDOM('a.cancel[disabled]');

		// Confirm that the purchase was not added to ConvertKit.
		$I->apiCheckPurchaseDoesNotExist($I, $result['order_id'], $result['email_address'], $result['product_id']);
	}
}