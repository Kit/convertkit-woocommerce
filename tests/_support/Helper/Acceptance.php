<?php
namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Acceptance extends \Codeception\Module
{
	/**
	 * Helper method to assert that there are non PHP errors, warnings or notices output
	 * 
	 * @since 	1.0.0
	 */
	public function checkNoWarningsAndNoticesOnScreen($I)
	{
		// Check that the <body> class does not have a php-error class, which indicates a suppressed PHP function call error.
		$I->dontSeeElement('.php-error');

		// Check that no Xdebug errors exist.
		$I->dontSeeElement('.xdebug-error');
		$I->dontSeeElement('.xe-notice');
	}

	/**
	 * Helper method to assert that the field's value contains the given value.
	 * 
	 * @since 	1.0.0
	 */
	public function seeFieldContains($I, $element, $value)
	{
		$this->assertNotFalse(strpos($I->grabValueFrom($element), $value));
	}

	/**
	 * Helper method to enter text into a jQuery Select2 Field, selecting the option that appears.
	 * 
	 * @since 	1.0.0
	 * 
	 * @param 	AcceptanceTester 	$I
	 * @param 	string 				$container 	Field CSS Class / ID
	 * @param 	string 				$value 		Field Value
	 */
	public function fillSelect2Field($I, $container, $value)
	{
		$fieldID = $I->grabAttributeFrom($container, 'id');
		$fieldName = str_replace('-container', '', str_replace('select2-', '', $fieldID));
	    $I->click('#'.$fieldID);
	    $I->waitForElementVisible('.select2-search__field[aria-owns="select2-' . $fieldName . '-results"]');
	    $I->fillField('.select2-search__field[aria-owns="select2-' . $fieldName . '-results"]', $value);
	    $I->waitForElementVisible('ul#select2-' . $fieldName . '-results li.select2-results__option--highlighted');
	    $I->pressKey('.select2-search__field[aria-owns="select2-' . $fieldName . '-results"]', \Facebook\WebDriver\WebDriverKeys::ENTER);
	}

	/**
	 * Helper method to close the Gutenberg "Welcome to the block editor" dialog, which
	 * might show for each Page/Post test performed due to there being no persistence
	 * remembering that the user dismissed the dialog.
	 * 
	 * @since 	1.0.0
	 */
	public function maybeCloseGutenbergWelcomeModal($I)
	{
		try {
			$I->performOn('.components-modal__screen-overlay', [
				'click' => '.components-modal__screen-overlay .components-modal__header button.components-button'
			], 3);
		} catch ( \Facebook\WebDriver\Exception\TimeoutException $e ) {
		}
	}

	/**
	 * Helper method to activate the Plugin.
	 * 
	 * @since 	1.0.0
	 */
	public function activateConvertKitPlugin($I)
	{
		// Login as the Administrator
		$I->loginAsAdmin();

		// Go to the Plugins screen in the WordPress Administration interface.
		$I->amOnPluginsPage();

		// Activate the WooCommerce Plugin.
		$I->activatePlugin('woocommerce');

		// Activate the Plugin.
		$I->activatePlugin('convertkit-woocommerce-addon');

		// Check that the Plugin activated successfully.
		$I->seePluginActivated('convertkit-woocommerce-addon');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Flush Permalinks by visiting Settings > Permalinks, so that newly registered Post Types e.g.
		// WooCommerce Products work.
		$I->amOnAdminPage('options-permalink.php');

	}

	/**
	 * Helper method to deactivate the Plugin.
	 * 
	 * @since 	1.0.0
	 */
	public function deactivateConvertKitPlugin($I)
	{
		// Login as the Administrator
		$I->loginAsAdmin();

		// Go to the Plugins screen in the WordPress Administration interface.
		$I->amOnPluginsPage();

		// Deactivate the Plugin.
		$I->deactivatePlugin('convertkit-woocommerce-addon');

		// Check that the Plugin deactivated successfully.
		$I->seePluginDeactivated('convertkit-woocommerce-addon');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Flush Permalinks by visiting Settings > Permalinks, so that newly registered Post Types e.g.
		// WooCommerce Products work.
		$I->amOnAdminPage('options-permalink.php');
	}

	/**
	 * Helper method to load the WooCommerce > Settings > Integration > ConvertKit screen.
	 * 
	 * @since 	1.0.0
	 */
	public function loadConvertKitSettingsScreen($I)
	{
		$I->amOnAdminPage('admin.php?page=wc-settings&tab=integration&section=ckwc');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);
	}

	/**
	 * Helper method to setup the Plugin's API Key and Secret, and enable the integration.
	 * 
	 * @since 	1.0.0
	 */
	public function setupConvertKitPlugin($I)
	{
		// Go to the Plugin's Settings Screen.
		$I->loadConvertKitSettingsScreen($I);

		// Enable the Integration.
		$I->checkOption('#woocommerce_ckwc_enabled');

		// Complete API Fields.
		$I->fillField('woocommerce_ckwc_api_key', $_ENV['CONVERTKIT_API_KEY']);
		$I->fillField('woocommerce_ckwc_api_secret', $_ENV['CONVERTKIT_API_SECRET']);

		// Click the Save Changes button.
		$I->click('Save changes');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Check the value of the fields match the inputs provided.
		$I->seeCheckboxIsChecked('#woocommerce_ckwc_enabled');	
		$I->seeInField('woocommerce_ckwc_api_key', $_ENV['CONVERTKIT_API_KEY']);
		$I->seeInField('woocommerce_ckwc_api_secret', $_ENV['CONVERTKIT_API_SECRET']);
	}

	/**
	 * Helper method to setup the WooCommerce Plugin.
	 * 
	 * @since 	1.0.0
	 */
	public function setupWooCommercePlugin($I)
	{
		// Enable "Cash on Delivery" Payment Method.
		// If no Payment Method is enabled, WooCommerce Checkout tests will always fail.
		$I->amOnAdminPage('admin.php?page=wc-settings&tab=checkout&section=cod');
		$I->checkOption('#woocommerce_cod_enabled');
		$I->click('Save changes');
	}

	/**
	 * Helper method to:
	 * - configure the Plugin's opt in, subscribe event and purchase options,
	 * - create a WooCommerce Product (simple or virtual)
	 * - log out as the WordPress Administrator
	 * - add the WooCommerce Product to the cart
	 * - complete checkout
	 * 
	 * This is quite a monolithic function, however this flow is used across 20+ tests,
	 * so it's better to have the code here than in every single test.
	 * 
	 * @since 	1.9.6
	 */
	public function wooCommerceCreateProductAndCheckoutWithConfig(
		$I,
		$productType = 'simple',
		$displayOptIn = false,
		$checkOptIn = false,
		$formOrTag = false,
		$subscriptionEvent = false,
		$sendPurchaseData = false
	)
	{
		// Define Opt In setting.
		if ($displayOptIn) {
			$I->checkOption('#woocommerce_ckwc_display_opt_in');	
		} else {
			$I->uncheckOption('#woocommerce_ckwc_display_opt_in');
		}

		// Define Subscription Event setting.
		if ($subscriptionEvent) {
			$I->selectOption('#woocommerce_ckwc_event', $subscriptionEvent);	
		}

		// Define Send Purchase Data setting.
		if ($sendPurchaseData) {
			$I->checkOption('#woocommerce_ckwc_send_purchases');	
		} else {
			$I->uncheckOption('#woocommerce_ckwc_send_purchases');
		}
		
		// Save.
		$I->click('Save changes');

		// Define Form to subscribe the Customer to, now that the API credentials are saved and the Forms are listed.
		if ($formOrTag) {
			$I->selectOption('#woocommerce_ckwc_subscription', $formOrTag);
			$I->click('Save changes');
		}

		// Create Product
		switch ($productType) {
			case 'zero':
				$productName = 'Zero Value Product';
				$productID = $I->wooCommerceCreateZeroValueProduct($I);
				break;

			case 'virtual':
				$productName = 'Virtual Product';
				$productID = $I->wooCommerceCreateVirtualProduct($I);
				break;

			case 'simple':
				$productName = 'Simple Product';
				$productID = $I->wooCommerceCreateSimpleProduct($I);
				break;
		}

		// Define Email Address for this Test.
		$emailAddress = 'wordpress-' . date( 'YmdHis' ) . '@convertkit.com';

		// Unsubscribe the email address, so we restore the account back to its previous state.
		$I->apiUnsubscribe($emailAddress);

		// Logout as the WordPress Administrator.
		$I->logOut();

		// Add Product to Cart and load Checkout.
		$I->wooCommerceCheckoutWithProduct($I, $productID, $productName, $emailAddress);

		// Handle Opt-In Checkbox
		if ($displayOptIn) {
			if ($checkOptIn) {
				$I->checkOption('#ckwc_opt_in');
			} else {
				$I->uncheckOption('#ckwc_opt_in');	
			}
		} else {
			$I->dontSeeElement('#ckwc_opt_in');
		}
		
		// Click Place order button.
		$I->click('Place order');

		// Wait until JS completes and redirects.
		$I->waitForElement('.woocommerce-order-received', 10);
		
		// Confirm 'Order Received' is displayed
		$I->seeInSource('Order received');
		$I->seeInSource('<h2 class="woocommerce-order-details__title">Order details</h2>');

		// Return data
		return [
			'email_address' => $emailAddress,
			'product_id' => $productID,
			'order_id' => (int) $I->grabTextFrom('.woocommerce-order-overview__order strong'),
		];
	}

	/**
	 * Changes the order status for the given Order ID to the given Order Status.
	 * 
	 * @since 	1.9.6
	 * 
	 * @param 	AcceptanceTester 	$I
	 * @param 	int 				$orderID 		WooCommerce Order ID
	 * @param 	string 				$orderStatus 	Order Status
	 */
	public function wooCommerceChangeOrderStatus($I, $orderID, $orderStatus)
	{
		// We perform the order status change by editing the Order as a WordPress Administrator would,
		// so that WooCommerce triggers its actions and filters that our integration hooks into.
		$I->loginAsAdmin();
		$I->amOnAdminPage('post.php?post=' . $orderID . '&action=edit');
		$I->submitForm('form#post', [
			'order_status' => $orderStatus,
		]);
	}

	/**
	 * Creates a 'Simple product' in WooCommerce that can be used for tests.
	 * 
	 * @since 	1.0.0
	 * 
	 * @return 	int 	Product ID
	 */
	public function wooCommerceCreateSimpleProduct($I)
	{
		return $I->havePostInDatabase([
			'post_type'		=> 'product',
			'post_status'	=> 'publish',
			'post_name' 	=> 'simple-product',
			'post_title'	=> 'Simple Product',
			'post_content'	=> 'Simple Product Content',
			'meta_input' => [
				'_backorders' => 'no',
				'_download_expiry' => -1,
				'_download_limit' => -1,
				'_downloadable' => 'no',
				'_manage_stock' => 'no',
				'_price' => 10,
				'_product_version' => '5.9.0',
				'_regular_price' => 10,
				'_sold_individually' => 'no',
				'_stock' => null,
				'_stock_status' => 'instock',
				'_tax_class' => '',
				'_tax_status' => 'taxable',
				'_virtual' => 'no',
				'_wc_average_rating' => 0,
				'_wc_review_count' => 0,
			],
		]);
	}

	/**
	 * Creates a 'Simple product' in WooCommerce that is set to be 'Virtual', that can be used for tests.
	 * 
	 * @since 	1.0.0
	 * 
	 * @return 	int 	Product ID
	 */
	public function wooCommerceCreateVirtualProduct($I)
	{
		return $I->havePostInDatabase([
			'post_type'		=> 'product',
			'post_status'	=> 'publish',
			'post_name' 	=> 'virtual-product',
			'post_title'	=> 'Virtual Product',
			'post_content'	=> 'Virtual Product Content',
			'meta_input' => [
				'_backorders' => 'no',
				'_download_expiry' => -1,
				'_download_limit' => -1,
				'_downloadable' => 'no',
				'_manage_stock' => 'no',
				'_price' => 10,
				'_product_version' => '5.9.0',
				'_regular_price' => 10,
				'_sold_individually' => 'no',
				'_stock' => null,
				'_stock_status' => 'instock',
				'_tax_class' => '',
				'_tax_status' => 'taxable',
				'_virtual' => 'yes',
				'_wc_average_rating' => 0,
				'_wc_review_count' => 0,
			],
		]);
	}

	/**
	 * Creates a zero value 'Simple product' in WooCommerce that can be used for tests.
	 * 
	 * @since 	1.0.0
	 * 
	 * @return 	int 	Product ID
	 */
	public function wooCommerceCreateZeroValueProduct($I)
	{
		return $I->havePostInDatabase([
			'post_type'		=> 'product',
			'post_status'	=> 'publish',
			'post_name' 	=> 'zero-value-product',
			'post_title'	=> 'Zero Value Product',
			'post_content'	=> 'Zero Value Product Content',
			'meta_input' => [
				'_backorders' => 'no',
				'_download_expiry' => -1,
				'_download_limit' => -1,
				'_downloadable' => 'no',
				'_manage_stock' => 'no',
				'_price' => 0,
				'_product_version' => '5.9.0',
				'_regular_price' => 0,
				'_sold_individually' => 'no',
				'_stock' => null,
				'_stock_status' => 'instock',
				'_tax_class' => '',
				'_tax_status' => 'taxable',
				'_virtual' => 'no',
				'_wc_average_rating' => 0,
				'_wc_review_count' => 0,
			],
		]);
	}

	/**
	 * Adds the given Product ID to the Cart, loading the Checkout screen
	 * and prefilling the standard WooCommerce Billing Fields.
	 * 
	 * @since 	1.0.0
	 */
	public function wooCommerceCheckoutWithProduct($I, $productID, $productName, $emailAddress = 'wordpress@convertkit.com')
	{
		// Load the Product on the frontend site.
		$I->amOnPage('/?p=' . $productID );

		// Check that no WooCommerce, PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Add Product to Cart.
		$I->click('button[name=add-to-cart]');

		// View Cart.
		$I->click('.woocommerce-message a.button.wc-forward');

		// Check that no WooCommerce, PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Check that the Product exists in the Cart.
		$I->seeInSource($productName);

		// Proceed to Checkout.
		$I->click('a.checkout-button');

		// Check that no WooCommerce, PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Complete Billing Details.
		$I->fillField('#billing_first_name', 'First');
		$I->fillField('#billing_last_name', 'Last');
		$I->fillField('#billing_address_1', 'Address Line 1');
		$I->fillField('#billing_city', 'City');
		$I->fillField('#billing_postcode', '12345');
		$I->fillField('#billing_phone', '123-123-1234');
		$I->fillField('#billing_email', $emailAddress);
	}

	/**
	 * Creates an Order as if the user were creating an Order through the WordPress Administration
	 * interface.
	 * 
	 * @since 	1.0.0
	 * 
	 * @param 	AcceptanceTester 	$I
	 * @param 	int 				$productID 		Product ID
	 * @param 	string 				$productName 	Product Name
	 * @param 	string 				$orderStatus 	Order Status
	 * @param 	string 				$paymentMethod 	Payment Method
	 * @return 	int 								Order ID
	 */
	public function wooCommerceCreateManualOrder($I, $productID, $productName, $orderStatus, $paymentMethod)
	{
		// Login as Administrator.
		$I->loginAsAdmin();

		// Define Email Address for this Manual Order.
		$emailAddress = 'wordpress-' . date( 'YmdHis' ) . '@convertkit.com';

		// Create User for this Manual Order.
		$userID = $I->haveUserInDatabase('test', 'subscriber', [
			'user_email' => $emailAddress,
		]);

		// Load New Order screen.
		$I->amOnAdminPage('post-new.php?post_type=shop_order');

		// Check that no WooCommerce, PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Define Order Status.
	    $I->selectOption('#order_status', $orderStatus);

	    // Define User and Payment Method.
		$I->fillSelect2Field($I, '#select2-customer_user-container', $emailAddress);
		$I->selectOption('#_payment_method', $paymentMethod);

		// Add Product.
		$I->click('button.add-line-item');
		$I->click('button.add-order-item');
		$I->fillSelect2Field($I, '.wc-backbone-modal-content .select2-selection__rendered', $productName);
		$I->click('#btn-ok');

		// Create Order.
		$I->executeJS('window.scrollTo(0,0);'); // Otherwise button hidden behind admin bar and test fails.
		$I->click('button.save_order');

		// Check that no WooCommerce, PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Return.
		return [
			'email_address' => $emailAddress,
			'product_id' => $productID,
			'order_id' => (int) filter_var($I->grabTextFrom('h2.woocommerce-order-data__heading'), FILTER_SANITIZE_NUMBER_INT),
		];
	}

	/**
	 * Check the given email address exists as a subscriber on ConvertKit.
	 * 
	 * @param 	AcceptanceTester $I 			AcceptanceTester
	 * @param 	string 			$emailAddress 	Email Address
	 */ 	
	public function apiCheckSubscriberExists($I, $emailAddress)
	{
		// Run request.
		$results = $this->apiRequest('subscribers', 'GET', [
			'email_address' => $emailAddress,
		]);

		// Check at least one subscriber was returned and it matches the email address.
		$I->assertGreaterThan(0, $results['total_subscribers']);
		$I->assertEquals($emailAddress, $results['subscribers'][0]['email_address']);
	}

	/**
	 * Check the given email address does not exists as a subscriber on ConvertKit.
	 * 
	 * @param 	AcceptanceTester $I 			AcceptanceTester
	 * @param 	string 			$emailAddress 	Email Address
	 */ 	
	public function apiCheckSubscriberDoesNotExist($I, $emailAddress)
	{
		// Run request.
		$results = $this->apiRequest('subscribers', 'GET', [
			'email_address' => $emailAddress,
		]);

		// Check no subscribers are returned by this request.
		$I->assertEquals(0, $results['total_subscribers']);
	}

	/**
	 * Check the given order ID exists as a purchase on ConvertKit.
	 * 
	 * @param 	AcceptanceTester $I 			AcceptanceTester
	 * @param 	int 			$orderID 		Order ID
	 * @param 	string 			$emailAddress 	Email Address
	 * @param 	int 			$productID 		Product ID
	 */ 
	public function apiCheckPurchaseExists($I, $orderID, $emailAddress, $productID)
	{
		// Run request.
		$purchase = $this->apiExtractPurchaseFromPurchases($this->apiRequest('purchases', 'GET'), $orderID);

		// Check data returned for this Order ID.
		$I->assertIsArray($purchase);
		$I->assertEquals($orderID, $purchase['transaction_id']);
		$I->assertEquals($emailAddress, $purchase['email_address']);
		$I->assertEquals($productID, $purchase['products'][0]['pid']);
	}

	/**
	 * Check the given order ID does not exist as a purchase on ConvertKit.
	 * 
	 * @param 	AcceptanceTester $I 			AcceptanceTester
	 * @param 	int 			$orderID 		Order ID
	 * @param 	string 			$emailAddress 	Email Address
	 */ 
	public function apiCheckPurchaseDoesNotExist($I, $orderID, $emailAddress)
	{
		// Run request.
		$purchase = $this->apiExtractPurchaseFromPurchases($this->apiRequest('purchases', 'GET'), $orderID);

		// Check data not returned for this Order ID.
		// We check the email address, because each test will reset, meaning the Order ID will match that
		// of a previous test, and therefore the API will return data from an existing test.
		$I->assertIsArray($purchase);
		$I->assertNotEquals($emailAddress, $purchase['email_address']);
	}

	/**
	 * Returns a Purchase from the /purchases API endpoint based on the given Order ID (transaction_id).
	 * 
	 * We cannot use /purchases/{id} as {id} is the ConvertKit ID, not the WooCommerce Order ID (which
	 * is stored in the transaction_id).
	 * 
	 * @param 	array 	$purchases 	Purchases Data
	 * @param 	int 	$orderID 	Order ID
	 * @return 	array
	 */
	private function apiExtractPurchaseFromPurchases($purchases, $orderID)
	{
		// Bail if no purchases exist.
		if (!isset($purchases['purchases'])) {
			return [
				'id' => 0,
				'order_id' => 0,
				'email_address' => '',
			];
		}

		// Iterate through purchases to find one where the transaction ID matches the order ID.
		foreach ($purchases['purchases'] as $purchase) {
			// Skip if order ID does not match
			if ($purchase['transaction_id'] != $orderID) {
				continue;
			}

			return $purchase;
		}

		// No purchase exists with the given order ID. Return a blank array.
		return [
			'id' => 0,
			'order_id' => 0,
			'email_address' => '',
		];
	}

	/**
	 * Unsubscribes the given email address. Useful for clearing the API
	 * between tests.
	 * 
	 * @param 	string 			$emailAddress 	Email Address
	 */ 	
	public function apiUnsubscribe($emailAddress)
	{
		// Run request.
		$this->apiRequest('unsubscribe', 'PUT', [
			'email' => $emailAddress,
		]);
	}

	/**
	 * Sends a request to the ConvertKit API, typically used to read an endpoint to confirm
	 * that data in an Acceptance Test was added/edited/deleted successfully.
	 * 
	 * @param 	string 	$endpoint 	Endpoint
	 * @param 	string 	$method 	Method (GET|POST|PUT)
	 * @param 	array 	$params 	Endpoint Parameters
	 */
	public function apiRequest($endpoint, $method = 'GET', $params = array())
	{
		// Build query parameters.
		$params = array_merge($params, [
			'api_key' => $_ENV['CONVERTKIT_API_KEY'],
			'api_secret' => $_ENV['CONVERTKIT_API_SECRET'],
		]);

		// Send request.
		try {
			$client = new \GuzzleHttp\Client();
			$result = $client->request($method, 'https://api.convertkit.com/v3/' . $endpoint . '?' . http_build_query($params), [
				'headers' => [
					'Accept-Encoding' => 'gzip',
					'timeout'         => 5,
				],
			]);

			// Return JSON decoded response.
			return json_decode($result->getBody()->getContents(), true);
		} catch(\GuzzleHttp\Exception\ClientException $e) {
			return [];
		}
	}
}