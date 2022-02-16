<?php
/**
 * Tests the various settings do (or do not) subscribe the customer to a ConvertKit Form,
 * Tag or Sequence on the Order Completed event when an order is placed through WooCommerce
 * Subscriptions.
 * 
 * @since 	1.4.4
 */
class WooCommerceSubscriptionsSubscribeEventCest
{
	/**
	 * Run common actions before running the test functions in this class.
	 * 
	 * @since 	1.4.4
	 * 
	 * @param 	AcceptanceTester 	$I 	Tester
	 */
	public function _before(AcceptanceTester $I)
	{
		// Activate Plugins.
		$I->activateWooCommerceAndConvertKitPlugins($I);

		// Setup WooCommerce Plugin.
		$I->setupWooCommercePlugin($I);

		// Enable Integration and define its API Keys.
		$I->setupConvertKitPlugin($I);
	}

	/**
	 * Test that the Customer is subscribed to ConvertKit when:
	 * - The opt in checkbox is enabled in the integration Settings, and
	 * - The opt in checkbox is checked on the WooCommerce checkout, and
	 * - The Customer purchases a 'Subscription' WooCommerce Product, and
	 * - The Customer is subscribed at the point the WooCommerce Order is marked as completed.
	 * 
	 * @since 	1.4.4
	 * 
	 * @param 	AcceptanceTester 	$I 	Tester
	 */
	public function testOptInWhenCheckedWithFormAndSubscriptionProduct(AcceptanceTester $I)
	{
		// Create Product and Checkout for this test.
		$result = $I->wooCommerceCreateProductAndCheckoutWithConfig(
			$I,
			'subscription', // Subscription Product
			true, // Display Opt-In checkbox on Checkout
			true, // Check Opt-In checkbox on Checkout
			$_ENV['CONVERTKIT_API_FORM_NAME'], // Form to subscribe email address to
			'Order Completed', // Subscribe on WooCommerce "Order Completed" event
			false // Don't send purchase data to ConvertKit
		);

		// Confirm that the email address was added to ConvertKit.
		$subscriber = $I->apiCheckSubscriberExists($I, $result['email_address']);

		// Confirm the subscriber's custom field data is empty, as no Order to Custom Field mapping was specified
		// in the integration's settings.
		$I->apiCustomFieldDataIsEmpty($I, $subscriber);

		// Unsubscribe the email address, so we restore the account back to its previous state.
		$I->apiUnsubscribe($result['email_address']);

		// Check that the Order's Notes include a note from the Plugin confirming the Customer was subscribed to the Form.
		$I->wooCommerceOrderNoteExists($I, $result['order_id'], 'Customer subscribed to the Form: ' . $_ENV['CONVERTKIT_API_FORM_NAME'] . ' [' . $_ENV['CONVERTKIT_API_FORM_ID'] . ']');
	
		// Trigger a renewal of the subscription, as if the recurring payment was made, by visiting WooCommerce > Status > 
		// Scheduled Actions and searching for the Subscription ID.
		// https://woocommerce.com/document/testing-subscription-renewal-payments/
		$I->loginAsAdmin();
		$I->amOnAdminPage('admin.php?page=wc-status&tab=action-scheduler&s='.$result['subscription_id'].'&action=-1&paged=1&action2=-1');
		$I->moveMouseOver('tbody tr td.column-hook');
		$I->click('span.run a');

		// Wait for task to complete.
		$I->waitForElement('.updated', 10);

		// Confirm that the email address is not subscribed to ConvertKit, as the Order is for a renewal, not a new subscription.
		$I->apiCheckSubscriberDoesNotExist($I, $result['email_address']);
	}

}