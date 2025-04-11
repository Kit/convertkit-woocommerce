<?php

namespace Tests\EndToEnd;

use Tests\Support\EndToEndTester;

/**
 * Tests that Purchase Data does (or does not) get sent to ConvertKit based on the integration
 * settings.
 *
 * @since   1.4.2
 */
class PurchaseDataCest
{
	/**
	 * Run common actions before running the test functions in this class.
	 *
	 * @since   1.4.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function _before(EndToEndTester $I)
	{
		// Activate Plugin.
		$I->activateWooCommerceAndConvertKitPlugins($I);

		// Activate Custom Order Numbers, so that we can prefix Order IDs with
		// an environment-specific string.
		$I->activateThirdPartyPlugin($I, 'custom-order-numbers-for-woocommerce');

		// Setup WooCommerce Plugin.
		$I->setupWooCommercePlugin($I);

		// Setup Custom Order Numbers Plugin.
		$I->setupCustomOrderNumbersPlugin($I);

		// Populate resoruces.
		$I->setupConvertKitPluginResources($I);
	}

	/**
	 * Test that the Customer's purchase is sent to ConvertKit when:
	 * - The 'Send purchase data to ConvertKit' is enabled in the integration Settings, and
	 * - The Customer purchases a 'Simple' WooCommerce Product, and
	 * - The Order is created via the frontend checkout.
	 *
	 * @since   1.4.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testSendPurchaseDataWithSimpleProductCheckout(EndToEndTester $I)
	{
		// Create Product and Checkout for this test.
		$result = $I->wooCommerceCreateProductAndCheckoutWithConfig(
			$I,
			[
				'send_purchase_data' => true,
				'custom_fields'      => false,
			]
		);

		// Confirm that the purchase was added to ConvertKit.
		$purchaseDataID = $I->apiCheckPurchaseExists($I, $result['order_id'], $result['email_address'], $result['product_id']);

		// Check that the Order's Notes include a note from the Plugin confirming the purchase was added to ConvertKit.
		$I->wooCommerceOrderNoteExists($I, $result['order_id'], '[Kit] Purchase Data sent successfully: ID [' . $purchaseDataID . ']');

		// Confirm that the Transaction ID is stored in the Order's metadata.
		$I->wooCommerceOrderMetaKeyAndValueExist($I, $result['order_id'], 'ckwc_purchase_data_sent', 'yes', true);
		$I->wooCommerceOrderMetaKeyAndValueExist($I, $result['order_id'], 'ckwc_purchase_data_id', $purchaseDataID, true);

		// Confirm that the email address was added to ConvertKit.
		$subscriber = $I->apiCheckSubscriberExists($I, $result['email_address'], 'First');

		// Confirm the subscriber's custom field data is empty, as no Order to Custom Field mapping was specified
		// in the integration's settings.
		$I->apiCustomFieldDataIsEmpty($I, $subscriber);
	}

	/**
	 * Test that the Customer's purchase is sent to ConvertKit with custom field data when:
	 * - The 'Send purchase data to ConvertKit' is enabled in the integration Settings, and
	 * - The opt in settings are disabled, and
	 * - The Customer purchases a 'Simple' WooCommerce Product, and
	 * - The Order is created via the frontend checkout.
	 *
	 * @since   1.8.4
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testSendPurchaseDataWithCustomFieldsOnSimpleProductCheckout(EndToEndTester $I)
	{
		// Create Product and Checkout for this test.
		$result = $I->wooCommerceCreateProductAndCheckoutWithConfig(
			$I,
			[
				'display_opt_in'     => false,
				'check_opt_in'       => false,
				'send_purchase_data' => true,
				'custom_fields'      => true,
			]
		);

		// Confirm that the purchase was added to ConvertKit.
		$purchaseDataID = $I->apiCheckPurchaseExists($I, $result['order_id'], $result['email_address'], $result['product_id']);

		// Check that the Order's Notes include a note from the Plugin confirming the purchase was added to ConvertKit.
		$I->wooCommerceOrderNoteExists($I, $result['order_id'], '[Kit] Purchase Data sent successfully: ID [' . $purchaseDataID . ']');

		// Confirm that the Transaction ID is stored in the Order's metadata.
		$I->wooCommerceOrderMetaKeyAndValueExist($I, $result['order_id'], 'ckwc_purchase_data_sent', 'yes', true);
		$I->wooCommerceOrderMetaKeyAndValueExist($I, $result['order_id'], 'ckwc_purchase_data_id', $purchaseDataID, true);

		// Confirm that the email address was now added to ConvertKit.
		$subscriber = $I->apiCheckSubscriberExists($I, $result['email_address'], 'First');

		// Confirm the subscriber's custom field data exists and is correct.
		$I->apiCustomFieldDataIsValid($I, $subscriber);

		// Check that the Order's Notes include a note from the Plugin confirming the custom field data was added to ConvertKit.
		$I->wooCommerceOrderNoteExists($I, $result['order_id'], '[Kit] Purchase Data: Custom Fields sent successfully: Subscriber ID [' . $subscriber['id'] . ']');

		// Unsubscribe the email address, so we restore the account back to its previous state.
		$I->apiUnsubscribe($subscriber['id']);
	}

	/**
	 * Test that the Customer's purchase is sent to ConvertKit with custom field data when:
	 * - The 'Send purchase data to ConvertKit' is enabled in the integration Settings, and
	 * - The opt in settings are disabled, and
	 * - The Customer purchases a 'Simple' WooCommerce Product, and
	 * - The Order is created via the frontend checkout.
	 * - The Address Custom Fields in ConvertKit do not include the name
	 *
	 * @since   1.8.5
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testSendPurchaseDataWithCustomFieldsAndExcludeNameFromAddressOnSimpleProductCheckout(EndToEndTester $I)
	{
		// Define the address fields to include in the custom field data.
		$addressFields = array( 'address_1', 'city', 'state', 'postcode', 'country' );

		// Create Product and Checkout for this test.
		$result = $I->wooCommerceCreateProductAndCheckoutWithConfig(
			$I,
			[
				'display_opt_in'            => false,
				'check_opt_in'              => false,
				'send_purchase_data'        => true,
				'custom_fields'             => true,
				'address_fields' 			=> $addressFields,
			]
		);

		// Confirm that the purchase was added to ConvertKit.
		$purchaseDataID = $I->apiCheckPurchaseExists($I, $result['order_id'], $result['email_address'], $result['product_id']);

		// Check that the Order's Notes include a note from the Plugin confirming the purchase was added to ConvertKit.
		$I->wooCommerceOrderNoteExists($I, $result['order_id'], '[Kit] Purchase Data sent successfully: ID [' . $purchaseDataID . ']');

		// Confirm that the Transaction ID is stored in the Order's metadata.
		$I->wooCommerceOrderMetaKeyAndValueExist($I, $result['order_id'], 'ckwc_purchase_data_sent', 'yes', true);
		$I->wooCommerceOrderMetaKeyAndValueExist($I, $result['order_id'], 'ckwc_purchase_data_id', $purchaseDataID, true);

		// Confirm that the email address was now added to ConvertKit.
		$subscriber = $I->apiCheckSubscriberExists($I, $result['email_address'], 'First');

		// Confirm the subscriber's custom field data exists and is correct, and the name
		// is not included in the address.
		$I->apiCustomFieldDataIsValid($I, $subscriber, $addressFields );

		// Check that the Order's Notes include a note from the Plugin confirming the custom field data was added to ConvertKit.
		$I->wooCommerceOrderNoteExists($I, $result['order_id'], '[Kit] Purchase Data: Custom Fields sent successfully: Subscriber ID [' . $subscriber['id'] . ']');

		// Unsubscribe the email address, so we restore the account back to its previous state.
		$I->apiUnsubscribe($subscriber['id']);
	}

	/**
	 * Test that the Customer's purchase is not sent to ConvertKit when:
	 * - The 'Send purchase data to ConvertKit' is disabled in the integration Settings, and
	 * - The Customer purchases a 'Simple' WooCommerce Product, and
	 * - The Order is created via the frontend checkout.
	 *
	 * @since   1.4.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testDontSendPurchaseDataWithSimpleProductCheckout(EndToEndTester $I)
	{
		// Create Product and Checkout for this test.
		$result = $I->wooCommerceCreateProductAndCheckoutWithConfig($I);

		// Confirm that the purchase was not added to ConvertKit.
		$I->apiCheckPurchaseDoesNotExist($I, $result['order_id'], $result['email_address']);

		// Check that the Order's Notes does not include a note from the Plugin confirming the purchase was added to ConvertKit.
		$I->wooCommerceOrderNoteDoesNotExist($I, $result['order_id'], '[Kit] Purchase Data sent successfully');
	}

	/**
	 * Test that the Customer's purchase is sent to ConvertKit when:
	 * - The 'Send purchase data to ConvertKit' is enabled in the integration Settings, and
	 * - The Customer purchases a 'Virtual' WooCommerce Product, and
	 * - The Order is created via the frontend checkout.
	 *
	 * @since   1.4.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testSendPurchaseDataWithVirtualProductCheckout(EndToEndTester $I)
	{
		// Create Product and Checkout for this test.
		$result = $I->wooCommerceCreateProductAndCheckoutWithConfig(
			$I,
			[
				'product_type'       => 'virtual',
				'send_purchase_data' => true,
			]
		);

		// Confirm that the purchase was added to ConvertKit.
		$purchaseDataID = $I->apiCheckPurchaseExists($I, $result['order_id'], $result['email_address'], $result['product_id']);

		// Check that the Order's Notes include a note from the Plugin confirming the purchase was added to ConvertKit.
		$I->wooCommerceOrderNoteExists($I, $result['order_id'], '[Kit] Purchase Data sent successfully: ID [' . $purchaseDataID . ']');

		// Confirm that the Transaction ID is stored in the Order's metadata.
		$I->wooCommerceOrderMetaKeyAndValueExist($I, $result['order_id'], 'ckwc_purchase_data_sent', 'yes', true);
		$I->wooCommerceOrderMetaKeyAndValueExist($I, $result['order_id'], 'ckwc_purchase_data_id', $purchaseDataID, true);
	}

	/**
	 * Test that the Customer's purchase is not sent to ConvertKit when:
	 * - The 'Send purchase data to ConvertKit' is disabled in the integration Settings, and
	 * - The Customer purchases a 'Virtual' WooCommerce Product, and
	 * - The Order is created via the frontend checkout.
	 *
	 * @since   1.4.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testDontSendPurchaseDataWithVirtualProductCheckout(EndToEndTester $I)
	{
		// Create Product and Checkout for this test.
		$result = $I->wooCommerceCreateProductAndCheckoutWithConfig(
			$I,
			[
				'product_type' => 'virtual',
			]
		);

		// Confirm that the purchase was not added to ConvertKit.
		$I->apiCheckPurchaseDoesNotExist($I, $result['order_id'], $result['email_address']);

		// Check that the Order's Notes does not include a note from the Plugin confirming the purchase was added to ConvertKit.
		$I->wooCommerceOrderNoteDoesNotExist($I, $result['order_id'], '[Kit] Purchase Data sent successfully');
	}

	/**
	 * Test that the Customer's purchase is sent to ConvertKit when:
	 * - The 'Send purchase data to ConvertKit' is enabled in the integration Settings, and
	 * - The Customer purchases a WooCommerce Product with zero value, and
	 * - The Order is created via the frontend checkout.
	 *
	 * @since   1.4.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testSendPurchaseDataWithZeroValueProductCheckout(EndToEndTester $I)
	{
		// Create Product and Checkout for this test.
		$result = $I->wooCommerceCreateProductAndCheckoutWithConfig(
			$I,
			[
				'product_type'       => 'zero',
				'send_purchase_data' => true,
			]
		);

		// Confirm that the purchase was added to ConvertKit.
		$purchaseDataID = $I->apiCheckPurchaseExists($I, $result['order_id'], $result['email_address'], $result['product_id']);

		// Check that the Order's Notes include a note from the Plugin confirming the purchase was added to ConvertKit.
		$I->wooCommerceOrderNoteExists($I, $result['order_id'], '[Kit] Purchase Data sent successfully: ID [' . $purchaseDataID . ']');

		// Confirm that the Transaction ID is stored in the Order's metadata.
		$I->wooCommerceOrderMetaKeyAndValueExist($I, $result['order_id'], 'ckwc_purchase_data_sent', 'yes', true);
		$I->wooCommerceOrderMetaKeyAndValueExist($I, $result['order_id'], 'ckwc_purchase_data_id', $purchaseDataID, true);
	}

	/**
	 * Test that the Customer's purchase is not sent to ConvertKit when:
	 * - The 'Send purchase data to ConvertKit' is enabled in the integration Settings, and
	 * - The Customer purchases a WooCommerce Product with zero value, and
	 * - The Order is created via the frontend checkout.
	 *
	 * @since   1.4.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testDontSendPurchaseDataWithZeroValueProductCheckout(EndToEndTester $I)
	{
		// Create Product and Checkout for this test.
		$result = $I->wooCommerceCreateProductAndCheckoutWithConfig(
			$I,
			[
				'product_type' => 'zero',
			]
		);

		// Confirm that the purchase was added to ConvertKit.
		$I->apiCheckPurchaseDoesNotExist($I, $result['order_id'], $result['email_address']);

		// Check that the Order's Notes does not include a note from the Plugin confirming the purchase was added to ConvertKit.
		$I->wooCommerceOrderNoteDoesNotExist($I, $result['order_id'], '[Kit] Purchase Data sent successfully');
	}

	/**
	 * Test that a manual order (an order created in the WordPress Administration interface) is sent to ConvertKit when:
	 * - The 'Send purchase data to ConvertKit' is enabled in the integration Settings, and
	 * - The Order contains a 'Simple' WooCommerce Product, and
	 * - The Order's payment method is blank (N/A), and
	 * - The Order is created via the WordPress Administration interface.
	 *
	 * @since   1.4.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testSendPurchaseDataWithSimpleProductNoPaymentMethodManualOrder(EndToEndTester $I)
	{
		// Enable Integration and define its Access and Refresh Tokens.
		$I->setupConvertKitPlugin(
			$I,
			$_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN'],
			$_ENV['CONVERTKIT_OAUTH_REFRESH_TOKEN'],
			false, // Don't define a subscribe event.
			false, // Don't subscribe the customer to anything.
			'first', // Name format.
			false, // Don't map custom fields.
			false, // Don't display an opt in.
			'processing' // Send purchase data on 'processing' event.
		);

		// Create Product for this test.
		$productID = $I->wooCommerceCreateSimpleProduct($I);

		// Create Manual Order.
		$result = $I->wooCommerceCreateManualOrder(
			$I,
			$productID, // Product ID.
			'Simple Product', // Product Name.
			'wc-processing', // Order Status.
			'' // Payment Method.
		);

		// Confirm that the purchase was added to ConvertKit.
		$purchaseDataID = $I->apiCheckPurchaseExists($I, $result['order_id'], $result['email_address'], $result['product_id']);

		// Check that the Order's Notes include a note from the Plugin confirming the purchase was added to ConvertKit.
		$I->wooCommerceOrderNoteExists($I, $result['order_id'], '[Kit] Purchase Data sent successfully: ID [' . $purchaseDataID . ']');

		// Confirm that the Transaction ID is stored in the Order's metadata.
		$I->wooCommerceOrderMetaKeyAndValueExist($I, $result['order_id'], 'ckwc_purchase_data_sent', 'yes', true);
		$I->wooCommerceOrderMetaKeyAndValueExist($I, $result['order_id'], 'ckwc_purchase_data_id', $purchaseDataID, true);
	}

	/**
	 * Test that a manual order (an order created in the WordPress Administration interface) is not sent to ConvertKit when:
	 * - The 'Send purchase data to ConvertKit' is disabled in the integration Settings, and
	 * - The Order contains a 'Simple' WooCommerce Product, and
	 * - The Order's payment method is blank (N/A), and
	 * - The Order is created via the WordPress Administration interface.
	 *
	 * @since   1.4.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testDontSendPurchaseDataWithSimpleProductNoPaymentMethodManualOrder(EndToEndTester $I)
	{
		// Enable Integration and define its Access and Refresh Tokens.
		$I->setupConvertKitPlugin(
			$I,
			$_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN'],
			$_ENV['CONVERTKIT_OAUTH_REFRESH_TOKEN'],
			false, // Don't define a subscribe event.
			false, // Don't subscribe the customer to anything.
			'first', // Name format.
			false, // Don't map custom fields.
			false, // Don't display an opt in.
			false // Don't send purchase data.
		);

		// Create Product for this test.
		$productID = $I->wooCommerceCreateSimpleProduct($I);

		// Create Manual Order.
		$result = $I->wooCommerceCreateManualOrder(
			$I,
			$productID, // Product ID.
			'Simple Product', // Product Name.
			'wc-processing', // Order Status.
			'' // Payment Method.
		);

		// Confirm that the purchase was not added to ConvertKit.
		$I->apiCheckPurchaseDoesNotExist($I, $result['order_id'], $result['email_address']);

		// Check that the Order's Notes does not include a note from the Plugin confirming the purchase was added to ConvertKit.
		$I->wooCommerceOrderNoteDoesNotExist($I, $result['order_id'], '[Kit] Purchase Data sent successfully');
	}

	/**
	 * Test that a manual order (an order created in the WordPress Administration interface) is sent to ConvertKit when:
	 * - The 'Send purchase data to ConvertKit' is enabled in the integration Settings, and
	 * - The Order contains a 'Virtual' WooCommerce Product, and
	 * - The Order's payment method is blank (N/A), and
	 * - The Order is created via the WordPress Administration interface.
	 *
	 * @since   1.4.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testSendPurchaseDataWithVirtualProductNoPaymentMethodManualOrder(EndToEndTester $I)
	{
		// Enable Integration and define its Access and Refresh Tokens.
		$I->setupConvertKitPlugin(
			$I,
			$_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN'],
			$_ENV['CONVERTKIT_OAUTH_REFRESH_TOKEN'],
			false, // Don't define a subscribe event.
			false, // Don't subscribe the customer to anything.
			'first', // Name format.
			false, // Don't map custom fields.
			false, // Don't display an opt in.
			'processing' // Send purchase data on 'processing' event.
		);

		// Create Product for this test.
		$productID = $I->wooCommerceCreateVirtualProduct($I);

		// Create Manual Order.
		$result = $I->wooCommerceCreateManualOrder(
			$I,
			$productID, // Product ID.
			'Virtual Product', // Product Name.
			'wc-processing', // Order Status.
			'' // Payment Method.
		);

		// Confirm that the purchase was added to ConvertKit.
		$purchaseDataID = $I->apiCheckPurchaseExists($I, $result['order_id'], $result['email_address'], $result['product_id']);

		// Check that the Order's Notes include a note from the Plugin confirming the purchase was added to ConvertKit.
		$I->wooCommerceOrderNoteExists($I, $result['order_id'], '[Kit] Purchase Data sent successfully: ID [' . $purchaseDataID . ']');

		// Confirm that the Transaction ID is stored in the Order's metadata.
		$I->wooCommerceOrderMetaKeyAndValueExist($I, $result['order_id'], 'ckwc_purchase_data_sent', 'yes', true);
		$I->wooCommerceOrderMetaKeyAndValueExist($I, $result['order_id'], 'ckwc_purchase_data_id', $purchaseDataID, true);
	}

	/**
	 * Test that a manual order (an order created in the WordPress Administration interface) is not sent to ConvertKit when:
	 * - The 'Send purchase data to ConvertKit' is disabled in the integration Settings, and
	 * - The Order contains a 'Virtual' WooCommerce Product, and
	 * - The Order's payment method is blank (N/A), and
	 * - The Order is created via the WordPress Administration interface.
	 *
	 * @since   1.4.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testDontSendPurchaseDataWithVirtualProductNoPaymentMethodManualOrder(EndToEndTester $I)
	{
		// Enable Integration and define its Access and Refresh Tokens.
		$I->setupConvertKitPlugin(
			$I,
			$_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN'],
			$_ENV['CONVERTKIT_OAUTH_REFRESH_TOKEN'],
			false, // Don't define a subscribe event.
			false, // Don't subscribe the customer to anything.
			'first', // Name format.
			false, // Don't map custom fields.
			false, // Don't display an opt in.
			false // Don't send purchase data.
		);

		// Create Product for this test.
		$productID = $I->wooCommerceCreateVirtualProduct($I);

		// Create Manual Order.
		$result = $I->wooCommerceCreateManualOrder(
			$I,
			$productID, // Product ID.
			'Virtual Product', // Product Name.
			'wc-processing', // Order Status.
			'' // Payment Method.
		);

		// Confirm that the purchase was not added to ConvertKit.
		$I->apiCheckPurchaseDoesNotExist($I, $result['order_id'], $result['email_address']);

		// Check that the Order's Notes does not include a note from the Plugin confirming the purchase was added to ConvertKit.
		$I->wooCommerceOrderNoteDoesNotExist($I, $result['order_id'], '[Kit] Purchase Data sent successfully');
	}

	/**
	 * Test that a manual order (an order created in the WordPress Administration interface) is sent to ConvertKit when:
	 * - The 'Send purchase data to ConvertKit' is enabled in the integration Settings, and
	 * - The Order contains a 'Zero Value' WooCommerce Product, and
	 * - The Order's payment method is blank (N/A), and
	 * - The Order is created via the WordPress Administration interface.
	 *
	 * @since   1.4.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testSendPurchaseDataWithZeroValueProductNoPaymentMethodManualOrder(EndToEndTester $I)
	{
		// Enable Integration and define its Access and Refresh Tokens.
		$I->setupConvertKitPlugin(
			$I,
			$_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN'],
			$_ENV['CONVERTKIT_OAUTH_REFRESH_TOKEN'],
			false, // Don't define a subscribe event.
			false, // Don't subscribe the customer to anything.
			'first', // Name format.
			false, // Don't map custom fields.
			false, // Don't display an opt in.
			'processing' // Send purchase data on 'processing' event.
		);

		// Create Product for this test.
		$productID = $I->wooCommerceCreateZeroValueProduct($I);

		// Create Manual Order.
		$result = $I->wooCommerceCreateManualOrder(
			$I,
			$productID, // Product ID.
			'Zero Value Product', // Product Name.
			'wc-processing', // Order Status.
			'' // Payment Method.
		);

		// Confirm that the purchase was added to ConvertKit.
		$purchaseDataID = $I->apiCheckPurchaseExists($I, $result['order_id'], $result['email_address'], $result['product_id']);

		// Check that the Order's Notes include a note from the Plugin confirming the purchase was added to ConvertKit.
		$I->wooCommerceOrderNoteExists($I, $result['order_id'], '[Kit] Purchase Data sent successfully: ID [' . $purchaseDataID . ']');

		// Confirm that the Transaction ID is stored in the Order's metadata.
		$I->wooCommerceOrderMetaKeyAndValueExist($I, $result['order_id'], 'ckwc_purchase_data_sent', 'yes', true);
		$I->wooCommerceOrderMetaKeyAndValueExist($I, $result['order_id'], 'ckwc_purchase_data_id', $purchaseDataID, true);
	}

	/**
	 * Test that a manual order (an order created in the WordPress Administration interface) is not sent to ConvertKit when:
	 * - The 'Send purchase data to ConvertKit' is disabled in the integration Settings, and
	 * - The Order contains a 'Zero Value' WooCommerce Product, and
	 * - The Order's payment method is blank (N/A), and
	 * - The Order is created via the WordPress Administration interface.
	 *
	 * @since   1.4.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testDontSendPurchaseDataWithZeroValueProductNoPaymentMethodManualOrder(EndToEndTester $I)
	{
		// Enable Integration and define its Access and Refresh Tokens.
		$I->setupConvertKitPlugin(
			$I,
			$_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN'],
			$_ENV['CONVERTKIT_OAUTH_REFRESH_TOKEN'],
			false, // Don't define a subscribe event.
			false, // Don't subscribe the customer to anything.
			'first', // Name format.
			false, // Don't map custom fields.
			false, // Don't display an opt in.
			false // Don't send purchase data.
		);

		// Create Product for this test.
		$productID = $I->wooCommerceCreateZeroValueProduct($I);

		// Create Manual Order.
		$result = $I->wooCommerceCreateManualOrder(
			$I,
			$productID, // Product ID.
			'Zero Value Product', // Product Name.
			'wc-processing', // Order Status.
			'' // Payment Method.
		);

		// Confirm that the purchase was not added to ConvertKit.
		$I->apiCheckPurchaseDoesNotExist($I, $result['order_id'], $result['email_address']);

		// Check that the Order's Notes does not include a note from the Plugin confirming the purchase was added to ConvertKit.
		$I->wooCommerceOrderNoteDoesNotExist($I, $result['order_id'], '[Kit] Purchase Data sent successfully');
	}

	/**
	 * Test that a manual order (an order created in the WordPress Administration interface) is sent to ConvertKit when:
	 * - The 'Send purchase data to ConvertKit' is enabled in the integration Settings, and
	 * - The Order contains a 'Simple' WooCommerce Product, and
	 * - The Order's payment method is Cash on Delivery (COD), and
	 * - The Order is created via the WordPress Administration interface.
	 *
	 * @since   1.4.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testSendPurchaseDataWithSimpleProductCODManualOrder(EndToEndTester $I)
	{
		// Enable Integration and define its Access and Refresh Tokens.
		$I->setupConvertKitPlugin(
			$I,
			$_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN'],
			$_ENV['CONVERTKIT_OAUTH_REFRESH_TOKEN'],
			false, // Don't define a subscribe event.
			false, // Don't subscribe the customer to anything.
			'first', // Name format.
			false, // Don't map custom fields.
			false, // Don't display an opt in.
			'processing' // Send purchase data on 'processing' event.
		);

		// Create Product for this test.
		$productID = $I->wooCommerceCreateSimpleProduct($I);

		// Create Manual Order.
		$result = $I->wooCommerceCreateManualOrder(
			$I,
			$productID, // Product ID.
			'Simple Product', // Product Name.
			'wc-processing', // Order Status.
			'cod' // Payment Method.
		);

		// Confirm that the purchase was added to ConvertKit.
		$purchaseDataID = $I->apiCheckPurchaseExists($I, $result['order_id'], $result['email_address'], $result['product_id']);

		// Check that the Order's Notes include a note from the Plugin confirming the purchase was added to ConvertKit.
		$I->wooCommerceOrderNoteExists($I, $result['order_id'], '[Kit] Purchase Data sent successfully: ID [' . $purchaseDataID . ']');

		// Confirm that the Transaction ID is stored in the Order's metadata.
		$I->wooCommerceOrderMetaKeyAndValueExist($I, $result['order_id'], 'ckwc_purchase_data_sent', 'yes', true);
		$I->wooCommerceOrderMetaKeyAndValueExist($I, $result['order_id'], 'ckwc_purchase_data_id', $purchaseDataID, true);
	}

	/**
	 * Test that a manual order (an order created in the WordPress Administration interface) is not sent to ConvertKit when:
	 * - The 'Send purchase data to ConvertKit' is disabled in the integration Settings, and
	 * - The Order contains a 'Simple' WooCommerce Product, and
	 * - The Order's payment method is Cash on Delivery (COD), and
	 * - The Order is created via the WordPress Administration interface.
	 *
	 * @since   1.4.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testDontSendPurchaseDataWithSimpleProductCODManualOrder(EndToEndTester $I)
	{
		// Create Product and Checkout for this test.
		$result = $I->wooCommerceCreateProductAndCheckoutWithConfig($I);

		// Create Product for this test.
		$productID = $I->wooCommerceCreateSimpleProduct($I);

		// Create Manual Order.
		$result = $I->wooCommerceCreateManualOrder(
			$I,
			$productID, // Product ID.
			'Simple Product', // Product Name.
			'wc-processing', // Order Status.
			'cod' // Payment Method.
		);

		// Confirm that the purchase was not added to ConvertKit.
		$I->apiCheckPurchaseDoesNotExist($I, $result['order_id'], $result['email_address']);

		// Check that the Order's Notes does not include a note from the Plugin confirming the purchase was added to ConvertKit.
		$I->wooCommerceOrderNoteDoesNotExist($I, $result['order_id'], '[Kit] Purchase Data sent successfully');
	}

	/**
	 * Test that a manual order (an order created in the WordPress Administration interface) is sent to ConvertKit when:
	 * - The 'Send purchase data to ConvertKit' is enabled in the integration Settings, and
	 * - The Order contains a 'Virtual' WooCommerce Product, and
	 * - The Order's payment method is Cash on Delivery (COD), and
	 * - The Order is created via the WordPress Administration interface.
	 *
	 * @since   1.4.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testSendPurchaseDataWithVirtualProductCODManualOrder(EndToEndTester $I)
	{
		// Enable Integration and define its Access and Refresh Tokens.
		$I->setupConvertKitPlugin(
			$I,
			$_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN'],
			$_ENV['CONVERTKIT_OAUTH_REFRESH_TOKEN'],
			false, // Don't define a subscribe event.
			false, // Don't subscribe the customer to anything.
			'first', // Name format.
			false, // Don't map custom fields.
			false, // Don't display an opt in.
			'processing' // Send purchase data on 'processing' event.
		);

		// Create Product for this test.
		$productID = $I->wooCommerceCreateVirtualProduct($I);

		// Create Manual Order.
		$result = $I->wooCommerceCreateManualOrder(
			$I,
			$productID, // Product ID.
			'Virtual Product', // Product Name.
			'wc-processing', // Order Status.
			'cod' // Payment Method.
		);

		// Confirm that the purchase was added to ConvertKit.
		$purchaseDataID = $I->apiCheckPurchaseExists($I, $result['order_id'], $result['email_address'], $result['product_id']);

		// Check that the Order's Notes include a note from the Plugin confirming the purchase was added to ConvertKit.
		$I->wooCommerceOrderNoteExists($I, $result['order_id'], '[Kit] Purchase Data sent successfully: ID [' . $purchaseDataID . ']');

		// Confirm that the Transaction ID is stored in the Order's metadata.
		$I->wooCommerceOrderMetaKeyAndValueExist($I, $result['order_id'], 'ckwc_purchase_data_sent', 'yes', true);
		$I->wooCommerceOrderMetaKeyAndValueExist($I, $result['order_id'], 'ckwc_purchase_data_id', $purchaseDataID, true);
	}

	/**
	 * Test that a manual order (an order created in the WordPress Administration interface) is not sent to ConvertKit when:
	 * - The 'Send purchase data to ConvertKit' is disabled in the integration Settings, and
	 * - The Order contains a 'Virtual' WooCommerce Product, and
	 * - The Order's payment method is Cash on Delivery (COD), and
	 * - The Order is created via the WordPress Administration interface.
	 *
	 * @since   1.4.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testDontSendPurchaseDataWithVirtualProductCODManualOrder(EndToEndTester $I)
	{
		// Create Product and Checkout for this test.
		$result = $I->wooCommerceCreateProductAndCheckoutWithConfig($I);

		// Create Product for this test.
		$productID = $I->wooCommerceCreateVirtualProduct($I);

		// Create Manual Order.
		$result = $I->wooCommerceCreateManualOrder(
			$I,
			$productID, // Product ID.
			'Virtual Product', // Product Name.
			'wc-processing', // Order Status.
			'cod' // Payment Method.
		);

		// Confirm that the purchase was not added to ConvertKit.
		$I->apiCheckPurchaseDoesNotExist($I, $result['order_id'], $result['email_address']);

		// Check that the Order's Notes does not include a note from the Plugin confirming the purchase was added to ConvertKit.
		$I->wooCommerceOrderNoteDoesNotExist($I, $result['order_id'], '[Kit] Purchase Data sent successfully');
	}

	/**
	 * Test that a manual order (an order created in the WordPress Administration interface) is sent to ConvertKit when:
	 * - The 'Send purchase data to ConvertKit' is enabled in the integration Settings, and
	 * - The Order contains a 'Zero Value' WooCommerce Product, and
	 * - The Order's payment method is Cash on Delivery (COD), and
	 * - The Order is created via the WordPress Administration interface.
	 *
	 * @since   1.4.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testSendPurchaseDataWithZeroValueProductCODManualOrder(EndToEndTester $I)
	{
		// Enable Integration and define its Access and Refresh Tokens.
		$I->setupConvertKitPlugin(
			$I,
			$_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN'],
			$_ENV['CONVERTKIT_OAUTH_REFRESH_TOKEN'],
			false, // Don't define a subscribe event.
			false, // Don't subscribe the customer to anything.
			'first', // Name format.
			false, // Don't map custom fields.
			false, // Don't display an opt in.
			'processing' // Send purchase data on 'processing' event.
		);

		// Create Product for this test.
		$productID = $I->wooCommerceCreateZeroValueProduct($I);

		// Create Manual Order.
		$result = $I->wooCommerceCreateManualOrder(
			$I,
			$productID, // Product ID.
			'Zero Value Product', // Product Name.
			'wc-processing', // Order Status.
			'cod' // Payment Method.
		);

		// Confirm that the purchase was added to ConvertKit.
		$purchaseDataID = $I->apiCheckPurchaseExists($I, $result['order_id'], $result['email_address'], $result['product_id']);

		// Check that the Order's Notes include a note from the Plugin confirming the purchase was added to ConvertKit.
		$I->wooCommerceOrderNoteExists($I, $result['order_id'], '[Kit] Purchase Data sent successfully: ID [' . $purchaseDataID . ']');

		// Confirm that the Transaction ID is stored in the Order's metadata.
		$I->wooCommerceOrderMetaKeyAndValueExist($I, $result['order_id'], 'ckwc_purchase_data_sent', 'yes', true);
		$I->wooCommerceOrderMetaKeyAndValueExist($I, $result['order_id'], 'ckwc_purchase_data_id', $purchaseDataID, true);
	}

	/**
	 * Test that a manual order (an order created in the WordPress Administration interface) is not sent to ConvertKit when:
	 * - The 'Send purchase data to ConvertKit' is disabled in the integration Settings, and
	 * - The Order contains a 'Zero Value' WooCommerce Product, and
	 * - The Order's payment method is Cash on Delivery (COD), and
	 * - The Order is created via the WordPress Administration interface.
	 *
	 * @since   1.4.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testDontSendPurchaseDataWithZeroValueProductCODManualOrder(EndToEndTester $I)
	{
		// Create Product and Checkout for this test.
		$result = $I->wooCommerceCreateProductAndCheckoutWithConfig($I);

		// Create Product for this test.
		$productID = $I->wooCommerceCreateZeroValueProduct($I);

		// Create Manual Order.
		$result = $I->wooCommerceCreateManualOrder(
			$I,
			$productID, // Product ID.
			'Zero Value Product', // Product Name.
			'wc-processing', // Order Status.
			'cod' // Payment Method.
		);

		// Confirm that the purchase was not added to ConvertKit.
		$I->apiCheckPurchaseDoesNotExist($I, $result['order_id'], $result['email_address']);

		// Check that the Order's Notes does not include a note from the Plugin confirming the purchase was added to ConvertKit.
		$I->wooCommerceOrderNoteDoesNotExist($I, $result['order_id'], '[Kit] Purchase Data sent successfully');
	}

	/**
	 * Test that the Customer's purchase is sent to ConvertKit when:
	 * - The 'Send purchase data to ConvertKit' is enabled in the integration Settings, and
	 * - The Send Purchase Data Event is set to Order Completed, and
	 * - The Customer purchases a 'Simple' WooCommerce Product, and
	 * - The Order is created via the frontend checkout, and
	 * - The Order's status is changed from processing to completed.
	 *
	 * @since   1.4.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testSendPurchaseDataOnOrderCompletedWithSimpleProductCheckout(EndToEndTester $I)
	{
		// Create Product and Checkout for this test.
		$result = $I->wooCommerceCreateProductAndCheckoutWithConfig(
			$I,
			[
				'send_purchase_data' => 'completed',
			]
		);

		// Confirm that the purchase was not added to ConvertKit.
		$I->apiCheckPurchaseDoesNotExist($I, $result['order_id'], $result['email_address']);

		// Check that the Order's Notes does not include a note from the Plugin confirming the purchase was added to ConvertKit.
		$I->wooCommerceOrderNoteDoesNotExist($I, $result['order_id'], '[Kit] Purchase Data sent successfully');

		// Change Order Status = Completed.
		$I->wooCommerceChangeOrderStatus($I, $result['order_id'], 'wc-completed');

		// Confirm that the purchase was added to ConvertKit.
		$purchaseDataID = $I->apiCheckPurchaseExists($I, $result['order_id'], $result['email_address'], $result['product_id']);

		// Check that the Order's Notes include a note from the Plugin confirming the purchase was added to ConvertKit.
		$I->wooCommerceOrderNoteExists($I, $result['order_id'], '[Kit] Purchase Data sent successfully: ID [' . $purchaseDataID . ']');

		// Confirm that the Transaction ID is stored in the Order's metadata.
		$I->wooCommerceOrderMetaKeyAndValueExist($I, $result['order_id'], 'ckwc_purchase_data_sent', 'yes', true);
		$I->wooCommerceOrderMetaKeyAndValueExist($I, $result['order_id'], 'ckwc_purchase_data_id', $purchaseDataID, true);
	}

	/**
	 * Test that the Customer's purchase is not sent to ConvertKit when:
	 * - The 'Send purchase data to ConvertKit' is enabled in the integration Settings, and
	 * - The Send Purchase Data Event is set to Order Completed, and
	 * - The Customer purchases a 'Simple' WooCommerce Product, and
	 * - The Order is created via the frontend checkout, and
	 * - The Order's status is changed from processing to cancelled.
	 *
	 * @since   1.4.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testDontSendPurchaseDataOnOrderCancelledWithSimpleProductCheckout(EndToEndTester $I)
	{
		// Create Product and Checkout for this test.
		$result = $I->wooCommerceCreateProductAndCheckoutWithConfig(
			$I,
			[
				'send_purchase_data' => 'completed',
			]
		);

		// Confirm that the purchase was not added to ConvertKit.
		$I->apiCheckPurchaseDoesNotExist($I, $result['order_id'], $result['email_address']);

		// Check that the Order's Notes does not include a note from the Plugin confirming the purchase was added to ConvertKit.
		$I->wooCommerceOrderNoteDoesNotExist($I, $result['order_id'], '[Kit] Purchase Data sent successfully');

		// Change Order Status = Completed.
		$I->wooCommerceChangeOrderStatus($I, $result['order_id'], 'wc-cancelled');

		// Confirm that the purchase was not added to ConvertKit.
		$I->apiCheckPurchaseDoesNotExist($I, $result['order_id'], $result['email_address']);

		// Check that the Order's Notes does not include a note from the Plugin confirming the purchase was added to ConvertKit.
		$I->wooCommerceOrderNoteDoesNotExist($I, $result['order_id'], '[Kit] Purchase Data sent successfully');
	}

	/**
	 * Test that the name format setting is honored for the created subscriber in ConvertKit when
	 * they opt in to subscribe and purchase data is also sent.
	 *
	 * @since   1.6.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testSendPurchaseDataNameFormatHonoredWhenSubscribed(EndToEndTester $I)
	{
		// Create Product and Checkout for this test.
		$result = $I->wooCommerceCreateProductAndCheckoutWithConfig(
			$I,
			[
				'plugin_form_tag_sequence' => 'form:' . $_ENV['CONVERTKIT_API_FORM_ID'],
				'subscription_event'       => 'pending',
				'send_purchase_data'       => true,
				'name_format'              => 'both',
			]
		);

		// Confirm that the email address was now added to ConvertKit with a valid name.
		$subscriber = $I->apiCheckSubscriberExists($I, $result['email_address'], 'First Last');

		// Confirm that the purchase was added to ConvertKit.
		$purchaseDataID = $I->apiCheckPurchaseExists($I, $result['order_id'], $result['email_address'], $result['product_id']);

		// Check that the Order's Notes include a note from the Plugin confirming the purchase was added to ConvertKit.
		$I->wooCommerceOrderNoteExists($I, $result['order_id'], '[Kit] Purchase Data sent successfully: ID [' . $purchaseDataID . ']');

		// Confirm that the Transaction ID is stored in the Order's metadata.
		$I->wooCommerceOrderMetaKeyAndValueExist($I, $result['order_id'], 'ckwc_purchase_data_sent', 'yes', true);
		$I->wooCommerceOrderMetaKeyAndValueExist($I, $result['order_id'], 'ckwc_purchase_data_id', $purchaseDataID, true);

		// Unsubscribe the email address, so we restore the account back to its previous state.
		$I->apiUnsubscribe($subscriber['id']);
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
