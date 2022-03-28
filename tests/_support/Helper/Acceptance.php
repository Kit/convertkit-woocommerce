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
	 * @param 	string 				$ariaAttributeName 	Aria Attribute Name (aria-controls|aria-owns)
	 */
	public function fillSelect2Field($I, $container, $value, $ariaAttributeName = 'aria-controls')
	{
		$fieldID = $I->grabAttributeFrom($container, 'id');
		$fieldName = str_replace('-container', '', str_replace('select2-', '', $fieldID));
		$I->click('#'.$fieldID);
		$I->waitForElementVisible('.select2-search__field[' . $ariaAttributeName . '="select2-' . $fieldName . '-results"]');
		$I->fillField('.select2-search__field[' . $ariaAttributeName . '="select2-' . $fieldName . '-results"]', $value);
		$I->waitForElementVisible('ul#select2-' . $fieldName . '-results li.select2-results__option--highlighted');
		$I->pressKey('.select2-search__field[' . $ariaAttributeName . '="select2-' . $fieldName . '-results"]', \Facebook\WebDriver\WebDriverKeys::ENTER);
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
	 * Helper method to activate the ConvertKit Plugin, checking
	 * it activated and no errors were output.
	 * 
	 * @since 	1.9.6
	 */
	public function activateConvertKitPlugin($I)
	{
		$I->activateThirdPartyPlugin($I, 'convertkit-for-woocommerce');
	}

	/**
	 * Helper method to deactivate the ConvertKit Plugin, checking
	 * it activated and no errors were output.
	 * 
	 * @since 	1.9.6
	 */
	public function deactivateConvertKitPlugin($I)
	{
		$I->deactivateThirdPartyPlugin($I, 'convertkit-for-woocommerce');
	}

	/**
	 * Helper method to activate the following Plugins:
	 * - WooCommerce
	 * - WooCommerce Stripe Gateway
	 * - ConvertKit for WooCommerce
	 * 
	 * @since 	1.0.0
	 */
	public function activateWooCommerceAndConvertKitPlugins($I)
	{
		// Activate ConvertKit Plugin.
		$I->activateConvertKitPlugin($I);

		// Activate WooCommerce Plugin.
		$I->activateThirdPartyPlugin($I, 'woocommerce');

		// Activate WooCommerce Stripe Gateway Plugin.
		$I->activateThirdPartyPlugin($I, 'woocommerce-gateway-stripe');

		// Flush Permalinks by visiting Settings > Permalinks, so that newly registered Post Types e.g.
		// WooCommerce Products work.
		$I->amOnAdminPage('options-permalink.php');
	}

	/**
	 * Helper method to activate a third party Plugin, checking
	 * it activated and no errors were output.
	 * 
	 * @since 	1.9.6.7
	 * 
	 * @param 	string 	$name 	Plugin Slug.
	 */
	public function activateThirdPartyPlugin($I, $name)
	{
		// Login as the Administrator
		$I->loginAsAdmin();

		// Go to the Plugins screen in the WordPress Administration interface.
		$I->amOnPluginsPage();

		// Activate the Plugin.
		$I->activatePlugin($name);

		// Check that the Plugin activated successfully.
		$I->seePluginActivated($name);

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);
	}

	/**
	 * Helper method to activate a third party Plugin, checking
	 * it activated and no errors were output.
	 * 
	 * @since 	1.9.6.7
	 * 
	 * @param 	string 	$name 	Plugin Slug.
	 */
	public function deactivateThirdPartyPlugin($I, $name)
	{
		// Login as the Administrator
		$I->loginAsAdmin();

		// Go to the Plugins screen in the WordPress Administration interface.
		$I->amOnPluginsPage();

		// Deactivate the Plugin.
		$I->deactivatePlugin($name);

		// Check that the Plugin deactivated successfully.
		$I->seePluginDeactivated($name);

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);
	}

	/**
	 * Helper method to reset the ConvertKit Plugin settings, as if it's a clean installation.
	 * 
	 * @since 	1.4.4
	 */
	public function resetConvertKitPlugin($I)
	{
		// Plugin Settings.
		$I->dontHaveOptionInDatabase('woocommerce_ckwc_settings');

		// Resources.
		$I->dontHaveOptionInDatabase('ckwc_custom_fields');
		$I->dontHaveOptionInDatabase('ckwc_forms');
		$I->dontHaveOptionInDatabase('ckwc_sequences');
		$I->dontHaveOptionInDatabase('ckwc_tags');

		// Review Request.
		$I->dontHaveOptionInDatabase('convertkit-for-woocommerce-review-request');
		$I->dontHaveOptionInDatabase('convertkit-for-woocommerce-review-dismissed');
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
		// Setup Cash on Delivery as Payment Method.
		$I->haveOptionInDatabase('woocommerce_cod_settings', [
			'enabled' => 'yes',
			'title' => 'Cash on delivery',
			'description' => 'Pay with cash upon delivery',
			'instructions' => 'Pay with cash upon delivery',
			'enable_for_methods' => [],
			'enable_for_virtual' => 'yes',
		]);

		// Setup Stripe as Payment Method, as it's required for subscription products.
		$I->haveOptionInDatabase('woocommerce_stripe_settings', [
			'enabled' => 'yes',
			'title' => 'Credit Card (Stripe)',
			'description' => 'Pay with your credit card via Stripe.',
			'api_credentials' => '',
			'testmode' => 'yes',
			'test_publishable_key' => $_ENV['STRIPE_TEST_PUBLISHABLE_KEY'],
			'test_secret_key' => $_ENV['STRIPE_TEST_SECRET_KEY'],
			'publishable_key' => '',
			'secret_key' => '',
			'webhook' => '',
			'test_webhook_secret' => '',
			'webhook_secret' => '',
			'inline_cc_form' => 'yes', // Required so one iframe is output by Stripe, instead of 3.
			'statement_descriptor' => '',
			'capture' => 'yes',
			'payment_request' => 'no',
			'payment_request_button_type' => 'buy',
			'payment_request_button_theme' => 'dark',
			'payment_request_button_locations' => [
				'checkout',
			],
			'payment_request_button_size' => 'default',
			'saved_cards' => 'no',
			'logging' => 'no',
			'upe_checkout_experience_enabled' => 'disabled',
			'title_upe' => '',
			'is_short_statement_descriptor_enabled' => 'no',
			'upe_checkout_experience_accepted_payments' => [],
			'short_statement_descriptor' => 'CK',
		]);
	}

	/**
	 * Helper method to setup the Custom Order Numbers Plugin.
	 * 
	 * @since 	1.0.0
	 */
	public function setupCustomOrderNumbersPlugin($I)
	{
		// Setup WooCommerce Order Number prefix based on the current date and PHP version.
		$I->haveOptionInDatabase('alg_wc_custom_order_numbers_prefix', 'ckwc-' . date( 'Y-m-d-H-i-s' ) . '-php-' . PHP_VERSION_ID . '-');
	}

	/**
	 * Helper method to:
	 * - configure the Plugin's opt in, subscribe event and purchase options,
	 * - create a WooCommerce Product (simple|virtual|zero|subscription)
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
		$pluginFormTagSequence = false,
		$subscriptionEvent = false,
		$sendPurchaseData = false,
		$productFormTagSequence = false,
		$customFields = false
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

			// If sendPurchaseData is true, set send purchase data event to processing.
			// Otherwise set to the string value of sendPurchaseData i.e. completed.
			$sendPurchaseDataEvent = (($sendPurchaseData === true) ? 'processing' : $sendPurchaseData);
			$I->selectOption('#woocommerce_ckwc_send_purchases_event', $sendPurchaseDataEvent);
		} else {
			$I->uncheckOption('#woocommerce_ckwc_send_purchases');
		}
		
		// Save.
		$I->click('Save changes');

		// Define Form, Tag or Sequence to subscribe the Customer to, now that the API credentials are 
		// saved and the Forms, Tags and Sequences are listed.
		if ($pluginFormTagSequence) {
			$I->fillSelect2Field($I, '#select2-woocommerce_ckwc_subscription-container', $pluginFormTagSequence);
		} else {
			$I->fillSelect2Field($I, '#select2-woocommerce_ckwc_subscription-container', 'Select a subscription option...');
		}

		// Define Order to Custom Field mappings, now that the API credentials are 
		// saved and the Forms, Tags and Sequences are listed.
		if ($customFields) {
			$I->selectOption('#woocommerce_ckwc_custom_field_phone', 'Phone Number');
			$I->selectOption('#woocommerce_ckwc_custom_field_billing_address', 'Billing Address');
			$I->selectOption('#woocommerce_ckwc_custom_field_shipping_address', 'Shipping Address');
			$I->selectOption('#woocommerce_ckwc_custom_field_payment_method', 'Payment Method');
			$I->selectOption('#woocommerce_ckwc_custom_field_customer_note', 'Notes');
		} else {
			$I->selectOption('#woocommerce_ckwc_custom_field_phone', '(Don\'t send or map)');
			$I->selectOption('#woocommerce_ckwc_custom_field_billing_address', '(Don\'t send or map)');
			$I->selectOption('#woocommerce_ckwc_custom_field_shipping_address', '(Don\'t send or map)');
			$I->selectOption('#woocommerce_ckwc_custom_field_payment_method', '(Don\'t send or map)');
			$I->selectOption('#woocommerce_ckwc_custom_field_customer_note', '(Don\'t send or map)');
		}

		$I->click('Save changes');
		
		// Create Product
		switch ($productType) {
			case 'zero':
				$productName = 'Zero Value Product';
				$paymentMethod = 'cod';
				$productID = $I->wooCommerceCreateZeroValueProduct($I, $productFormTagSequence);
				break;

			case 'virtual':
				$productName = 'Virtual Product';
				$paymentMethod = 'cod';
				$productID = $I->wooCommerceCreateVirtualProduct($I, $productFormTagSequence);
				break;

			case 'subscription':
				$productName = 'Subscription Product';
				$paymentMethod = 'stripe';
				$productID = $I->wooCommerceCreateSubscriptionProduct($I, $productFormTagSequence);
				break;

			case 'simple':
				$productName = 'Simple Product';
				$paymentMethod = 'cod';
				$productID = $I->wooCommerceCreateSimpleProduct($I, $productFormTagSequence);
				break;
		}

		// Define Email Address for this Test.
		$emailAddress = $I->generateEmailAddress();

		// Unsubscribe the email address, so we restore the account back to its previous state.
		$I->apiUnsubscribe($emailAddress);

		// Logout as the WordPress Administrator.
		$I->logOut();

		// Add Product to Cart and load Checkout.
		$I->wooCommerceCheckoutWithProduct($I, $productID, $productName, $emailAddress, $paymentMethod);

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
		$I->click('#place_order');

		// Wait until JS completes and redirects.
		$I->waitForElement('.woocommerce-order-received', 30);
		
		// Confirm order received is displayed.
		// WooCommerce changed the default wording between 5.x and 6.x, so perform
		// a few checks to be certain.
		$I->seeElementInDOM('body.woocommerce-order-received');
		$I->seeInSource('Order');
		$I->seeInSource('received');
		$I->seeInSource('<h2 class="woocommerce-order-details__title">Order details</h2>');

		// Return data
		return [
			'email_address' => $emailAddress,
			'product_id' => $productID,
			'order_id' => $I->grabTextFrom('.woocommerce-order-overview__order strong'),
			'subscription_id' => ( ( $productType == 'subscription' ) ? (int) filter_var($I->grabTextFrom('.woocommerce-orders-table__cell-order-number a'), FILTER_SANITIZE_NUMBER_INT) : 0 ),
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

		// If the Order ID contains dashes, it's prefixed by the Custom Order Numbers Plugin.
		if (strpos($orderID, '-') !== false) {
			$orderIDParts = explode('-', $orderID);
			$orderID = $orderIDParts[count($orderIDParts)-1];
		}
		
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
	public function wooCommerceCreateSimpleProduct($I, $productFormTagSequence = false)
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
				'_product_version' => '6.3.0',
				'_regular_price' => 10,
				'_sold_individually' => 'no',
				'_stock' => null,
				'_stock_status' => 'instock',
				'_tax_class' => '',
				'_tax_status' => 'taxable',
				'_virtual' => 'no',
				'_wc_average_rating' => 0,
				'_wc_review_count' => 0,

				// ConvertKit Integration Form/Tag/Sequence
				'ckwc_subscription' => ( $productFormTagSequence ? $productFormTagSequence : '' ),
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
	public function wooCommerceCreateVirtualProduct($I, $productFormTagSequence = false)
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
				'_product_version' => '6.3.0',
				'_regular_price' => 10,
				'_sold_individually' => 'no',
				'_stock' => null,
				'_stock_status' => 'instock',
				'_tax_class' => '',
				'_tax_status' => 'taxable',
				'_virtual' => 'yes',
				'_wc_average_rating' => 0,
				'_wc_review_count' => 0,

				// ConvertKit Integration Form/Tag/Sequence
				'ckwc_subscription' => ( $productFormTagSequence ? $productFormTagSequence : '' ),
			],
		]);
	}

	/**
	 * Creates a 'Subscription product' in WooCommerce that can be used for tests, which
	 * is set to renew daily.
	 * 
	 * @since 	1.4.4
	 * 
	 * @return 	int 	Product ID
	 */
	public function wooCommerceCreateSubscriptionProduct($I, $productFormTagSequence = false)
	{
		return $I->havePostInDatabase([
			'post_type'		=> 'product',
			'post_status'	=> 'publish',
			'post_name' 	=> 'subscription-product',
			'post_title'	=> 'Subscription Product',
			'post_content'	=> 'Subscription Product Content',
			'meta_input' => [
				'_backorders' => 'no',
				'_download_expiry' => -1,
				'_download_limit' => -1,
				'_downloadable' => 'yes',
				'_manage_stock' => 'no',
				'_price' => 10,
				'_product_version' => '6.2.0',
				'_regular_price' => 10,
				'_sold_individually' => 'no',
				'_stock' => null,
				'_stock_status' => 'instock',
				'_subscription_length' => 0,
				'_subscription_limit' => 'no',
				'_subscription_one_time_shipping' => 'no',
				'_subscription_payment_sync_date' => 0,
				'_subscription_period' => 'day',
				'_subscription_period_interval' => 1,
				'_subscription_price' => 10,
				'_subscription_sign_up_fee' => 0,
				'_subscription_trial_length' => 0,
				'_subscription_trial_period' => 'day',
				'_tax_class' => '',
				'_tax_status' => 'taxable',
				'_virtual' => 'yes',
				'_wc_average_rating' => 0,
				'_wc_review_count' => 0,

				// ConvertKit Integration Form/Tag/Sequence.
				'ckwc_subscription' => ( $productFormTagSequence ? $productFormTagSequence : '' ),
			],
			'tax_input' => [
				[ 'product_type' => 'subscription' ],
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
	public function wooCommerceCreateZeroValueProduct($I, $productFormTagSequence = false)
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
				'_product_version' => '6.3.0',
				'_regular_price' => 0,
				'_sold_individually' => 'no',
				'_stock' => null,
				'_stock_status' => 'instock',
				'_tax_class' => '',
				'_tax_status' => 'taxable',
				'_virtual' => 'no',
				'_wc_average_rating' => 0,
				'_wc_review_count' => 0,

				// ConvertKit Integration Form/Tag/Sequence
				'ckwc_subscription' => ( $productFormTagSequence ? $productFormTagSequence : '' ),
			],
		]);
	}

	/**
	 * Adds the given Product ID to the Cart, loading the Checkout screen
	 * and prefilling the standard WooCommerce Billing Fields.
	 * 
	 * @since 	1.0.0
	 * 
	 * @param 	AcceptanceTester 	$I 	 			AcceptanceTester.
	 * @param 	string  			$productID 		Product ID.
	 * @param 	string  			$productName	Product Name.
	 * @param 	string  			$emailAddress 	Email Address (wordpress@convertkit.com).
	 * @param 	string  			$paymentMethod 	Payment Method (cod|stripe).
	 */
	public function wooCommerceCheckoutWithProduct($I, $productID, $productName, $emailAddress = 'wordpress@convertkit.com', $paymentMethod = 'cod')
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
		$I->fillField('#order_comments', 'Notes');

		// Depending on the payment method required, complete some fields.
		switch ($paymentMethod) {
			/**
			 * Card
			 */
			case 'stripe':
				// Complete Credit Card Details.
				$I->click('label[for="payment_method_stripe"]');
				$I->switchToIFrame('iframe[name^="__privateStripeFrame"]'); // Switch to Stripe iFrame.
				$I->fillField('cardnumber', '4242424242424242');
				$I->fillfield('exp-date', '01/26');
				$I->fillField('cvc', '123');
				$I->switchToIFrame(); // Switch back to main window.
				break;

			/**
			 * COD
			 */
			default:
				// COD is selected by default, so no need to click anything.
				break;
		}
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
		$emailAddress = $I->generateEmailAddress();

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
		$I->fillSelect2Field($I, '#select2-customer_user-container', $emailAddress, 'aria-owns');
		$I->selectOption('#_payment_method', $paymentMethod);

		// Add Product.
		$I->click('button.add-line-item');
		$I->click('button.add-order-item');
		$I->fillSelect2Field($I, '.wc-backbone-modal-content .select2-selection__rendered', $productName, 'aria-owns');
		$I->click('#btn-ok');

		// Create Order.
		$I->executeJS('window.scrollTo(0,0);');
		$I->click('button.save_order');

		// Check that no WooCommerce, PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Determine the Order ID.
		$orderID = $I->grabTextFrom('h2.woocommerce-order-data__heading');
		$orderID = str_replace('Order #', '', $orderID);
		$orderID = str_replace('details', '', $orderID);
		$orderID = trim($orderID);

		// Return.
		return [
			'email_address' => $emailAddress,
			'product_id' => $productID,
			'order_id' => $orderID,
		];
	}

	/**
	 * Check the given Order ID contains an Order Note with the given text.
	 * 
	 * @since 	1.4.2
	 * 
	 * @param 	AcceptanceTester $I 			AcceptanceTester
	 * @param 	int 			 $orderID 		Order ID
	 * @param 	string 			 $noteText		Order Note Text
	 */ 	
	public function wooCommerceOrderNoteExists($I, $orderID, $noteText)
	{
		// Logout.
		$I->logOut();
		
		// Login as Administrator.
		$I->loginAsAdmin();

		// If the Order ID contains dashes, it's prefixed by the Custom Order Numbers Plugin.
		if (strpos($orderID, '-') !== false) {
			$orderIDParts = explode('-', $orderID);
			$orderID = $orderIDParts[count($orderIDParts)-1];
		}

		// Load Edit Order screen.
		$I->amOnAdminPage('post.php?post=' . $orderID . '&action=edit');

		// Confirm note text exists.
		$I->seeInSource($noteText);
	}

	/**
	 * Check the given Order ID does not contain an Order Note with the given text.
	 * 
	 * @since 	1.4.2
	 * 
	 * @param 	AcceptanceTester $I 			AcceptanceTester
	 * @param 	int 			 $orderID 		Order ID
	 * @param 	string 			 $noteText		Order Note Text
	 */ 	
	public function wooCommerceOrderNoteDoesNotExist($I, $orderID, $noteText)
	{
		// Login as Administrator.
		$I->loginAsAdmin();

		// If the Order ID contains dashes, it's prefixed by the Custom Order Numbers Plugin.
		if (strpos($orderID, '-') !== false) {
			$orderIDParts = explode('-', $orderID);
			$orderID = $orderIDParts[count($orderIDParts)-1];
		}

		// Load Edit Order screen.
		$I->amOnAdminPage('post.php?post=' . $orderID . '&action=edit');

		// Confirm note text does not exist.
		$I->dontSeeInSource($noteText);
	}

	/**
	 * Helper method to delete option table rows for review requests.
	 * Useful for resetting the review state between tests.
	 * 
	 * @since 	1.4.3
	 */
	public function deleteConvertKitReviewRequestOptions($I)
	{
		$I->dontHaveOptionInDatabase('convertkit-for-woocommerce-review-request');
		$I->dontHaveOptionInDatabase('convertkit-for-woocommerce-review-dismissed');
	}

	/**
	 * Generates a unique email address for use in a test, comprising of a prefix,
	 * date + time and PHP version number.
	 * 
	 * This ensures that if tests are run in parallel, the same email address
	 * isn't used for two tests across parallel testing runs.
	 * 
	 * @since 	1.4.5
	 */
	public function generateEmailAddress()
	{
		return 'wordpress-' . date( 'Y-m-d-H-i-s' ) . '-php-' . PHP_VERSION_ID . '@convertkit.com';
	}

	/**
	 * Check the given email address exists as a subscriber on ConvertKit.
	 * 
	 * @param 	AcceptanceTester $I 			AcceptanceTester
	 * @param 	string 			$emailAddress 	Email Address
	 * @param 	mixed 			$firstName 		Name (false = don't check name matches)
	 * @return 	array 							Subscriber
	 */ 	
	public function apiCheckSubscriberExists($I, $emailAddress, $firstName = false)
	{
		// Run request.
		$results = $this->apiRequest('subscribers', 'GET', [
			'email_address' => $emailAddress,
		]);

		// Check at least one subscriber was returned and it matches the email address.
		$I->assertGreaterThan(0, $results['total_subscribers']);
		$I->assertEquals($emailAddress, $results['subscribers'][0]['email_address']);

		// If defined, check that the name matches for the subscriber.
		if ($firstName) {
			$I->assertEquals($firstName, $results['subscribers'][0]['first_name']);
		}

		return $results['subscribers'][0];
	}

	/**
	 * Check the subscriber array's custom field data is valid.
	 * 
	 * @param 	AcceptanceTester $I 			AcceptanceTester
	 * @param 	array 			$subscriber 	Subscriber from API
	 */ 	
	public function apiCustomFieldDataIsValid($I, $subscriber)
	{
		$I->assertEquals($subscriber['fields']['phone_number'], '123-123-1234');
		$I->assertEquals($subscriber['fields']['billing_address'], 'First Last, Address Line 1, City, CA 12345');
		$I->assertEquals($subscriber['fields']['shipping_address'], '');
		$I->assertEquals($subscriber['fields']['payment_method'], 'cod');
		$I->assertEquals($subscriber['fields']['notes'], 'Notes');
	}

	/**
	 * Check the subscriber array's custom field data is empty.
	 * 
	 * @param 	AcceptanceTester $I 			AcceptanceTester
	 * @param 	array 			$subscriber 	Subscriber from API
	 */ 	
	public function apiCustomFieldDataIsEmpty($I, $subscriber)
	{
		$I->assertEquals($subscriber['fields']['phone_number'], '');
		$I->assertEquals($subscriber['fields']['billing_address'], '');
		$I->assertEquals($subscriber['fields']['shipping_address'], '');
		$I->assertEquals($subscriber['fields']['payment_method'], '');
		$I->assertEquals($subscriber['fields']['notes'], '');
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
	 * Check the given email address and name exists as a subscriber on ConvertKit.
	 * 
	 * @param 	AcceptanceTester $I 			AcceptanceTester
	 * @param 	string 			$emailAddress 	Email Address
	 * @param 	string 			$name 			Name
	 */ 	
	public function apiCheckSubscriberEmailAndNameExists($I, $emailAddress, $name)
	{
		// Run request.
		$results = $this->apiRequest('subscribers', 'GET', [
			'email_address' => $emailAddress,
		]);

		// Check at least one subscriber was returned and it matches the email address.
		$I->assertGreaterThan(0, $results['total_subscribers']);
		$I->assertEquals($emailAddress, $results['subscribers'][0]['email_address']);

		// Check that the first_name matches the given name.
		$I->assertEquals($name, $results['subscribers'][0]['first_name']);
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
		$purchase = $this->apiExtractPurchaseFromPurchases($this->apiGetPurchases(), $orderID);

		// Check data returned for this Order ID.
		$I->assertIsArray($purchase);
		$I->assertEquals($orderID, $purchase['transaction_id']);
		$I->assertEquals($emailAddress, $purchase['email_address']);

		// Iterate through the array of products, to find a pid matching the Product ID.
		$productExistsInPurchase = false;
		foreach ($purchase['products'] as $product) {
			if ($productID == $product['pid']) {
				$productExistsInPurchase = true;
				break;
			}
		}

		// Check that the Product exists in the purchase data.
		$I->assertTrue($productExistsInPurchase);
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
		$purchase = $this->apiExtractPurchaseFromPurchases($this->apiGetPurchases(), $orderID);

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
		if (!isset($purchases)) {
			return [
				'id' => 0,
				'order_id' => 0,
				'email_address' => 'no',
			];
		}

		// Iterate through purchases to find one where the transaction ID matches the order ID.
		foreach ($purchases as $purchase) {
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
			'email_address' => 'no2',
		];
	}

	/**
	 * Returns all purchases from the API.
	 * 
	 * @return 	array
	 */
	public function apiGetPurchases()
	{
		// Get first page of purchases.
		$purchases = $this->apiRequest('purchases', 'GET');
		$data = $purchases['purchases'];
		$totalPages = $purchases['total_pages'];

		if ($totalPages == 1) {
			return $data;
		}

		// Get additional pages of purchases.
		for ($page = 2; $page <= $totalPages; $page++) {
			$purchases = $this->apiRequest('purchases', 'GET', [
				'page' => $page,
			]);

			$data = array_merge($data, $purchases['purchases']);
		}

		return $data;
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
