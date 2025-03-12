<?php

namespace Tests\EndToEnd;

use Tests\Support\EndToEndTester;

/**
 * Tests the various settings do (or do not) subscribe the customer to a ConvertKit Form,
 * Tag or Sequence on the Order Completed event when an order is placed through WooCommerce
 * Subscriptions.
 *
 * @since   1.4.4
 */
class WooCommerceSubscriptionsSubscribeEventCest
{
	/**
	 * Run common actions before running the test functions in this class.
	 *
	 * @since   1.4.4
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function _before(EndToEndTester $I)
	{
		// Activate Plugins.
		$I->activateWooCommerceAndConvertKitPlugins($I);

		// Activate WooCommerce Subscriptions Plugin.
		$I->activateThirdPartyPlugin($I, 'woocommerce-subscriptions');

		// Setup WooCommerce Plugin.
		$I->setupWooCommercePlugin($I);

		// Populate resoruces.
		$I->setupConvertKitPluginResources($I);
	}

	/**
	 * Test that the Customer is subscribed to ConvertKit when:
	 * - WooCommerce Subscriptions Plugin is enabled, and
	 * - The opt in checkbox is enabled in the integration Settings, and
	 * - The opt in checkbox is checked on the WooCommerce checkout, and
	 * - The Customer purchases a 'Subscription' WooCommerce Product, and
	 * - The Customer is subscribed at the point the WooCommerce Order is marked as completed.
	 * - The Customer is not resubscribed when the WooCommerce Subscription is renewed.
	 *
	 * @since   1.4.4
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testOptInWhenCheckedWithFormAndSubscriptionProduct(EndToEndTester $I)
	{
		// Create Product and Checkout for this test.
		$result = $I->wooCommerceCreateProductAndCheckoutWithConfig(
			$I,
			[
				'product_type'             => 'subscription',
				'display_opt_in'           => true,
				'check_opt_in'             => true,
				'plugin_form_tag_sequence' => 'form:' . $_ENV['CONVERTKIT_API_FORM_ID'],
				'subscription_event'       => 'completed',
			]
		);

		// Confirm that the email address was added to ConvertKit.
		$subscriber = $I->apiCheckSubscriberExists($I, $result['email_address']);

		// Confirm the subscriber's custom field data is empty, as no Order to Custom Field mapping was specified
		// in the integration's settings.
		$I->apiCustomFieldDataIsEmpty($I, $subscriber);

		// Unsubscribe the email address, so we restore the account back to its previous state.
		$I->apiUnsubscribe($subscriber['id']);

		// Check that the Order's Notes include a note from the Plugin confirming the Customer was subscribed to the Form.
		$I->wooCommerceOrderNoteExists($I, $result['order_id'], 'Customer subscribed to the Form: ' . $_ENV['CONVERTKIT_API_FORM_NAME'] . ' [' . $_ENV['CONVERTKIT_API_FORM_ID'] . ']');

		// Trigger a renewal of the subscription, as if the recurring payment was made, by visiting WooCommerce > Status >
		// Scheduled Actions and searching for the Subscription ID.
		// https://woocommerce.com/document/testing-subscription-renewal-payments/.
		$I->amOnAdminPage('admin.php?page=wc-status&tab=action-scheduler&s=' . $result['subscription_id'] . '&action=-1&paged=1&action2=-1&status=pending');
		$I->moveMouseOver('tbody tr td.column-hook');
		$I->click('span.run a');

		// Wait for task to complete.
		$I->waitForElement('.updated', 10);

		// Confirm that the email address is not subscribed to ConvertKit, as the Order is for a renewal, not a new subscription.
		$I->apiCheckSubscriberDoesNotExist($I, $result['email_address']);
	}

	/**
	 * Test that the Customer is subscribed to ConvertKit when:
	 * - WooCommerce Subscriptions Plugin is enabled, and
	 * - The opt in checkbox is enabled in the integration Settings, and
	 * - The opt in checkbox is checked on the WooCommerce checkout, and
	 * - The Customer purchases a 'Simple' non-subscription WooCommerce Product, and
	 * - The Customer is subscribed at the point the WooCommerce Order is marked as completed.
	 *
	 * @since   1.4.4
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testOptInWhenCheckedWithFormAndNonSubscriptionProduct(EndToEndTester $I)
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
		$subscriber = $I->apiCheckSubscriberExists($I, $result['email_address']);

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
		$I->deactivateThirdPartyPlugin($I, 'woocommerce-subscriptions');
		$I->deactivateWooCommerceAndConvertKitPlugins($I);
		$I->resetConvertKitPlugin($I);
	}
}
