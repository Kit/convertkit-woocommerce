<?php
/**
 * Tests the various settings do (or do not) subscribe the customer to a ConvertKit Form
 * or Tag on the Order Completed event when an order is placed through WooCommerce.
 *
 * @since   1.4.2
 */
class SubscribeOnOrderCompletedEventCest
{
	/**
	 * Run common actions before running the test functions in this class.
	 *
	 * @since   1.4.2
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function _before(AcceptanceTester $I)
	{
		// Activate Plugin.
		$I->activateWooCommerceAndConvertKitPlugins($I);

		// Setup WooCommerce Plugin.
		$I->setupWooCommercePlugin($I);

		// Populate resoruces.
		$I->setupConvertKitPluginResources($I);
	}

	/**
	 * Test that the Customer is subscribed to ConvertKit when:
	 * - The opt in checkbox is enabled in the integration Settings, and
	 * - The opt in checkbox is checked on the WooCommerce checkout, and
	 * - The Customer purchases a 'Simple' WooCommerce Product, and
	 * - The Customer is subscribed at the point the WooCommerce Order is marked as processing.
	 *
	 * @since   1.4.2
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testOptInWhenCheckedWithFormAndSimpleProduct(AcceptanceTester $I)
	{
		// Create Product and Checkout for this test.
		$result = $I->wooCommerceCreateProductAndCheckoutWithConfig(
			$I,
			[
				'display_opt_in'           => true,
				'check_opt_in'             => true,
				'plugin_form_tag_sequence' => 'form:' . $_ENV['CONVERTKIT_API_FORM_ID'],
				'subscription_event'       => 'completed',
			]
		);

		// Confirm that the email address wasn't yet added to ConvertKit.
		$I->apiCheckSubscriberDoesNotExist($I, $result['email_address']);

		// Change the Order status = Completed, to trigger the Order Completed event.
		$I->wooCommerceChangeOrderStatus($I, $result['order_id'], 'wc-completed');

		// Confirm that the email address was now added to ConvertKit.
		$subscriber = $I->apiCheckSubscriberExists($I, $result['email_address'], 'First');

		// Check that the subscriber has the expected form and referrer value set.
		$I->apiCheckSubscriberHasForm(
			$I,
			$subscriber['id'],
			$_ENV['CONVERTKIT_API_FORM_ID'],
			$_ENV['TEST_SITE_WP_URL']
		);

		// Unsubscribe the email address, so we restore the account back to its previous state.
		$I->apiUnsubscribe($subscriber['id']);
	}

	/**
	 * Test that the Customer is NOT subscribed to ConvertKit when:
	 * - The opt in checkbox is enabled in the integration Settings, and
	 * - The opt in checkbox is unchecked on the WooCommerce checkout, and
	 * - The Customer purchases a 'Simple' WooCommerce Product, and
	 * - The Customer is subscribed at the point the WooCommerce Order is created.
	 *
	 * @since   1.4.2
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testOptInWhenUncheckedWithFormAndSimpleProduct(AcceptanceTester $I)
	{
		// Create Product and Checkout for this test.
		$result = $I->wooCommerceCreateProductAndCheckoutWithConfig(
			$I,
			[
				'display_opt_in'           => true,
				'plugin_form_tag_sequence' => 'form:' . $_ENV['CONVERTKIT_API_FORM_ID'],
				'subscription_event'       => 'completed',
			]
		);

		// Confirm that the email address was not added to ConvertKit.
		$I->apiCheckSubscriberDoesNotExist($I, $result['email_address']);

		// Change the Order status = Completed, to trigger the Order Completed event.
		$I->wooCommerceChangeOrderStatus($I, $result['order_id'], 'wc-completed');

		// Confirm that the email address was not added to ConvertKit.
		$I->apiCheckSubscriberDoesNotExist($I, $result['email_address']);
	}

	/**
	 * Test that the Customer is subscribed to ConvertKit when:
	 * - The opt in checkbox is disabled in the integration Settings, and
	 * - The Customer purchases a 'Simple' WooCommerce Product, and
	 * - The Customer is subscribed at the point the WooCommerce Order is created.
	 *
	 * @since   1.4.2
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testOptInDisabledWithFormAndSimpleProduct(AcceptanceTester $I)
	{
		// Create Product and Checkout for this test.
		$result = $I->wooCommerceCreateProductAndCheckoutWithConfig(
			$I,
			[
				'plugin_form_tag_sequence' => 'form:' . $_ENV['CONVERTKIT_API_FORM_ID'],
				'subscription_event'       => 'completed',
			]
		);

		// Confirm that the email address was not added to ConvertKit.
		$I->apiCheckSubscriberDoesNotExist($I, $result['email_address']);

		// Change the Order status = Completed, to trigger the Order Completed event.
		$I->wooCommerceChangeOrderStatus($I, $result['order_id'], 'wc-completed');

		// Confirm that the email address was added to ConvertKit.
		$subscriber = $I->apiCheckSubscriberExists($I, $result['email_address'], 'First');

		// Check that the subscriber has the expected form and referrer value set.
		$I->apiCheckSubscriberHasForm(
			$I,
			$subscriber['id'],
			$_ENV['CONVERTKIT_API_FORM_ID'],
			$_ENV['TEST_SITE_WP_URL']
		);

		// Unsubscribe the email address, so we restore the account back to its previous state.
		$I->apiUnsubscribe($subscriber['id']);
	}

	/**
	 * Test that the Customer is not subscribed to ConvertKit when:
	 * - The opt in checkbox is enabled in the integration Settings, and
	 * - No Form is selected in the integration Settings, and
	 * - The opt in checkbox is checked on the WooCommerce checkout, and
	 * - The Customer purchases a 'Simple' WooCommerce Product, and
	 * - The Customer is subscribed at the point the WooCommerce Order is marked as processing.
	 *
	 * @since   1.4.2
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testOptInWhenCheckedWithNoFormAndSimpleProduct(AcceptanceTester $I)
	{
		// Create Product and Checkout for this test.
		$result = $I->wooCommerceCreateProductAndCheckoutWithConfig(
			$I,
			[
				'display_opt_in'     => true,
				'check_opt_in'       => true,
				'subscription_event' => 'completed',
			]
		);

		// Confirm that the email address wasn't yet added to ConvertKit.
		$I->apiCheckSubscriberDoesNotExist($I, $result['email_address']);

		// Change the Order status = Completed, to trigger the Order Completed event.
		$I->wooCommerceChangeOrderStatus($I, $result['order_id'], 'wc-completed');

		// Confirm that the email address was still not added to ConvertKit.
		$I->apiCheckSubscriberDoesNotExist($I, $result['email_address']);
	}

	/**
	 * Test that the Customer is NOT subscribed to ConvertKit when:
	 * - The opt in checkbox is enabled in the integration Settings, and
	 * - No Form is selected in the integration Settings, and
	 * - The opt in checkbox is unchecked on the WooCommerce checkout, and
	 * - The Customer purchases a 'Simple' WooCommerce Product, and
	 * - The Customer is subscribed at the point the WooCommerce Order is created.
	 *
	 * @since   1.4.2
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testOptInWhenUncheckedWithNoFormAndSimpleProduct(AcceptanceTester $I)
	{
		// Create Product and Checkout for this test.
		$result = $I->wooCommerceCreateProductAndCheckoutWithConfig(
			$I,
			[
				'display_opt_in'     => true,
				'subscription_event' => 'completed',
			]
		);

		// Confirm that the email address wasn't yet added to ConvertKit.
		$I->apiCheckSubscriberDoesNotExist($I, $result['email_address']);

		// Change the Order status = Completed, to trigger the Order Completed event.
		$I->wooCommerceChangeOrderStatus($I, $result['order_id'], 'wc-completed');

		// Confirm that the email address was still not added to ConvertKit.
		$I->apiCheckSubscriberDoesNotExist($I, $result['email_address']);
	}

	/**
	 * Test that the Customer is subscribed to ConvertKit when:
	 * - The opt in checkbox is enabled in the integration Settings, and
	 * - Order data is mapped to ConvertKit Custom fields in the integration Settings, and
	 * - The opt in checkbox is checked on the WooCommerce checkout, and
	 * - The Customer purchases a 'Simple' WooCommerce Product, and
	 * - The Customer is subscribed at the point the WooCommerce Order is marked as completed.
	 *
	 * @since   1.4.3
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testOptInWhenCheckedWithFormCustomFieldsAndSimpleProduct(AcceptanceTester $I)
	{
		// Create Product and Checkout for this test.
		$result = $I->wooCommerceCreateProductAndCheckoutWithConfig(
			$I,
			[
				'display_opt_in'           => true,
				'check_opt_in'             => true,
				'plugin_form_tag_sequence' => 'form:' . $_ENV['CONVERTKIT_API_FORM_ID'],
				'subscription_event'       => 'completed',
				'custom_fields'            => true,
			]
		);

		// Confirm that the email address wasn't yet added to ConvertKit.
		$I->apiCheckSubscriberDoesNotExist($I, $result['email_address']);

		// Change the Order status = Completed, to trigger the Order Completed event.
		$I->wooCommerceChangeOrderStatus($I, $result['order_id'], 'wc-completed');

		// Confirm that the email address was now added to ConvertKit.
		$subscriber = $I->apiCheckSubscriberExists($I, $result['email_address'], 'First');

		// Confirm the subscriber's custom field data exists and is correct.
		$I->apiCustomFieldDataIsValid($I, $subscriber);

		// Unsubscribe the email address, so we restore the account back to its previous state.
		$I->apiUnsubscribe($subscriber['id']);
	}

	/**
	 * Test that the Customer is subscribed to ConvertKit when:
	 * - The opt in checkbox is enabled in the integration Settings, and
	 * - Order data is mapped to ConvertKit Custom fields in the integration Settings, and
	 * - The opt in checkbox is checked on the WooCommerce checkout, and
	 * - The Customer purchases a 'Simple' WooCommerce Product, and
	 * - The Customer is subscribed at the point the WooCommerce Order is marked as completed.
	 * - The Customer's name is not included in the address custom field.
	 *
	 * @since   1.8.5
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testOptInWhenCheckedWithFormCustomFieldsAndExcludeNameFromAddressOnSimpleProduct(AcceptanceTester $I)
	{
		// Create Product and Checkout for this test.
		$result = $I->wooCommerceCreateProductAndCheckoutWithConfig(
			$I,
			[
				'display_opt_in'            => true,
				'check_opt_in'              => true,
				'plugin_form_tag_sequence'  => 'form:' . $_ENV['CONVERTKIT_API_FORM_ID'],
				'subscription_event'        => 'completed',
				'custom_fields'             => true,
				'exclude_name_from_address' => true,
			]
		);

		// Confirm that the email address wasn't yet added to ConvertKit.
		$I->apiCheckSubscriberDoesNotExist($I, $result['email_address']);

		// Change the Order status = Completed, to trigger the Order Completed event.
		$I->wooCommerceChangeOrderStatus($I, $result['order_id'], 'wc-completed');

		// Confirm that the email address was now added to ConvertKit.
		$subscriber = $I->apiCheckSubscriberExists($I, $result['email_address'], 'First');

		// Confirm the subscriber's custom field data exists and is correct, and the name
		// is not included in the address.
		$I->apiCustomFieldDataIsValid($I, $subscriber, true);

		// Unsubscribe the email address, so we restore the account back to its previous state.
		$I->apiUnsubscribe($subscriber['id']);
	}

	/**
	 * Test that the Customer is subscribed to ConvertKit when:
	 * - The opt in checkbox is enabled in the integration Settings, and
	 * - Order data is mapped to ConvertKit Custom fields in the integration Settings, and
	 * - The opt in checkbox is checked on the WooCommerce checkout, and
	 * - The Customer purchases a 'Simple' WooCommerce Product, and
	 * - The Customer is subscribed at the point the WooCommerce Order is marked as completed.
	 *
	 * @since   1.4.3
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testOptInWhenCheckedWithTagCustomFieldsAndSimpleProduct(AcceptanceTester $I)
	{
		// Create Product and Checkout for this test.
		$result = $I->wooCommerceCreateProductAndCheckoutWithConfig(
			$I,
			[
				'display_opt_in'           => true,
				'check_opt_in'             => true,
				'plugin_form_tag_sequence' => 'tag:' . $_ENV['CONVERTKIT_API_TAG_ID'],
				'subscription_event'       => 'completed',
				'custom_fields'            => true,
			]
		);

		// Confirm that the email address wasn't yet added to ConvertKit.
		$I->apiCheckSubscriberDoesNotExist($I, $result['email_address']);

		// Change the Order status = Completed, to trigger the Order Completed event.
		$I->wooCommerceChangeOrderStatus($I, $result['order_id'], 'wc-completed');

		// Confirm that the email address was now added to ConvertKit.
		$subscriber = $I->apiCheckSubscriberExists($I, $result['email_address'], 'First');

		// Confirm the subscriber's custom field data exists and is correct.
		$I->apiCustomFieldDataIsValid($I, $subscriber);

		// Unsubscribe the email address, so we restore the account back to its previous state.
		$I->apiUnsubscribe($subscriber['id']);
	}

	/**
	 * Test that the Customer is subscribed to ConvertKit when:
	 * - The opt in checkbox is enabled in the integration Settings, and
	 * - Order data is mapped to ConvertKit Custom fields in the integration Settings, and
	 * - The opt in checkbox is checked on the WooCommerce checkout, and
	 * - The Customer purchases a 'Simple' WooCommerce Product, and
	 * - The Customer is subscribed at the point the WooCommerce Order is marked as completed.
	 *
	 * @since   1.4.3
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testOptInWhenCheckedWithSequenceCustomFieldsAndSimpleProduct(AcceptanceTester $I)
	{
		// Create Product and Checkout for this test.
		$result = $I->wooCommerceCreateProductAndCheckoutWithConfig(
			$I,
			[
				'display_opt_in'           => true,
				'check_opt_in'             => true,
				'plugin_form_tag_sequence' => 'course:' . $_ENV['CONVERTKIT_API_SEQUENCE_ID'],
				'subscription_event'       => 'completed',
				'custom_fields'            => true,
			]
		);

		// Confirm that the email address wasn't yet added to ConvertKit.
		$I->apiCheckSubscriberDoesNotExist($I, $result['email_address']);

		// Change the Order status = Completed, to trigger the Order Completed event.
		$I->wooCommerceChangeOrderStatus($I, $result['order_id'], 'wc-completed');

		// Confirm that the email address was now added to ConvertKit.
		$subscriber = $I->apiCheckSubscriberExists($I, $result['email_address'], 'First');

		// Confirm the subscriber's custom field data exists and is correct.
		$I->apiCustomFieldDataIsValid($I, $subscriber);

		// Unsubscribe the email address, so we restore the account back to its previous state.
		$I->apiUnsubscribe($subscriber['id']);
	}

	/**
	 * Test that the Customer is subscribed to ConvertKit when:
	 * - The opt in checkbox is disabled in the integration Settings, and
	 * - No Form is selected in the integration Settings, and
	 * - The Customer purchases a 'Simple' WooCommerce Product, and
	 * - The Customer is subscribed at the point the WooCommerce Order is created.
	 *
	 * @since   1.4.2
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testOptInDisabledWithNoFormAndSimpleProduct(AcceptanceTester $I)
	{
		// Create Product and Checkout for this test.
		$result = $I->wooCommerceCreateProductAndCheckoutWithConfig(
			$I,
			[
				'subscription_event' => 'completed',
			]
		);

		// Confirm that the email address wasn't yet added to ConvertKit.
		$I->apiCheckSubscriberDoesNotExist($I, $result['email_address']);

		// Change the Order status = Completed, to trigger the Order Completed event.
		$I->wooCommerceChangeOrderStatus($I, $result['order_id'], 'wc-completed');

		// Confirm that the email address was still not added to ConvertKit.
		$I->apiCheckSubscriberDoesNotExist($I, $result['email_address']);
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
		$I->deactivateWooCommerceAndConvertKitPlugins($I);
		$I->resetConvertKitPlugin($I);
	}
}
