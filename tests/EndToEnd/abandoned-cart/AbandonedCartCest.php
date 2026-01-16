<?php

namespace Tests\EndToEnd;

use Tests\Support\EndToEndTester;

/**
 * Tests the Abandoned Cart functionality.
 *
 * @since   2.0.5
 */
class AbandonedCartCest
{
	/**
	 * Run common actions before running the test functions in this class.
	 *
	 * @since   2.0.5
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function _before(EndToEndTester $I)
	{
		// Activate Plugin.
		$I->activateWooCommerceAndConvertKitPlugins($I);

		// Setup WooCommerce Plugin.
		$I->setupWooCommercePlugin($I);
	}

	/**
	 * Test that the subscriber is tagged when the cart is abandoned,
	 * and the tag is removed when the customer completes the checkout process.
	 *
	 * @since   2.0.5
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testAbandonedCartTagging(EndToEndTester $I)
	{
		// Setup Kit Plugin with Abandoned Cart enabled.
		$I->setupConvertKitPlugin(
			$I,
			abandonedCart: true,
			// By default, this would be 10 minutes i.e. the cart is deemed abandoned after 10 minutes of inactivity.
			// We set it to zero so that running the Abandoned Cart action immediately tags the subscriber.
			abandonedCartThreshold: 0,
			abandonedCartSubscription: 'tag:' . $_ENV['CONVERTKIT_API_TAG_ID']
		);

		// Create Product.
		$productName = 'Simple Product';
		$productID   = $I->wooCommerceCreateSimpleProduct($I, false);

		// Define Email Address for this Test.
		$emailAddress = $I->generateEmailAddress();

		// Logout as the WordPress Administrator.
		$I->logOut();

		// Add Product to Cart and load Checkout, completing fields.
		$I->wooCommerceCheckoutWithProduct(
			$I,
			productID: $productID,
			productName: $productName,
			emailAddress: $emailAddress
		);

		// Wait a second to permit the Abandoned Cart to be tracked.
		$I->wait(2);

		// Login and navigate to the Action Scheduler page, to manually run the Abandoned Cart action.
		$I->doLoginAsAdmin($I);
		$I->amOnAdminPage('admin.php?page=wc-status&status=pending&tab=action-scheduler&s=ckwc_abandoned_cart');

		// Hover mouse over Action Scheduler's table row.
		$I->moveMouseOver('tbody[data-wp-lists="list:action-scheduler"] tr:first-child');

		// Wait for export link to be visible.
		$I->waitForElementVisible('tbody[data-wp-lists="list:action-scheduler"] tr:first-child span.run a');

		// Click the export action.
		$I->click('tbody[data-wp-lists="list:action-scheduler"] tr:first-child span.run a');

		// Wait for the action to complete.
		$I->waitForElementVisible('.updated');

		// Confirm that the email address was added to Kit.
		$subscriber = $I->apiCheckSubscriberExists(
			$I,
			emailAddress: $emailAddress
		);

		// Confirm the subscriber has the expected abandoned cart tag.
		$I->apiCheckSubscriberHasTag($I, $subscriber['id'], $_ENV['CONVERTKIT_API_TAG_ID']);

		// Logout as the WordPress Administrator.
		$I->logOut();

		// Add Product to Cart and load Checkout, completing fields.
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

		// Check that the subscriber no longer has the tag.
		$I->apiCheckSubscriberHasNoTags($I, $subscriber['id']);

		// Unsubscribe the email address, so we restore the account back to its previous state.
		$I->apiUnsubscribe($subscriber['id']);
	}

	/**
	 * Test that the subscriber is not tagged when the Abandoned Cart is disabled.
	 *
	 * @since   2.0.5
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testAbandonedCartWhenDisabled(EndToEndTester $I)
	{
		// Setup Kit Plugin with Abandoned Cart disabled.
		$I->setupConvertKitPlugin(
			$I,
			abandonedCart: false
		);

		// Create Product.
		$productName = 'Simple Product';
		$productID   = $I->wooCommerceCreateSimpleProduct($I, false);

		// Define Email Address for this Test.
		$emailAddress = $I->generateEmailAddress();

		// Logout as the WordPress Administrator.
		$I->logOut();

		// Add Product to Cart and load Checkout, completing fields.
		$I->wooCommerceCheckoutWithProduct(
			$I,
			productID: $productID,
			productName: $productName,
			emailAddress: $emailAddress
		);

		// Login and navigate to the Action Scheduler page, to manually run the Abandoned Cart action.
		$I->doLoginAsAdmin($I);
		$I->amOnAdminPage('admin.php?page=wc-status&status=pending&tab=action-scheduler&s=ckwc_abandoned_cart');

		// Hover mouse over Action Scheduler's table row.
		$I->moveMouseOver('tbody[data-wp-lists="list:action-scheduler"] tr:first-child');

		// Wait for export link to be visible.
		$I->waitForElementVisible('tbody[data-wp-lists="list:action-scheduler"] tr:first-child span.run a');

		// Click the export action.
		$I->click('tbody[data-wp-lists="list:action-scheduler"] tr:first-child span.run a');

		// Wait for the action to complete.
		$I->waitForElementVisible('.updated');

		// Confirm that the email address was not added to Kit.
		$I->apiCheckSubscriberDoesNotExist(
			$I,
			emailAddress: $emailAddress
		);

		// Logout as the WordPress Administrator.
		$I->logOut();
	}

	/**
	 * Deactivate and reset Plugin(s) after each test, if the test passes.
	 * We don't use _after, as this would provide a screenshot of the Plugin
	 * deactivation and not the true test error.
	 *
	 * @since   2.0.5
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function _passed(EndToEndTester $I)
	{
		$I->deactivateWooCommerceAndConvertKitPlugins($I);
		$I->resetConvertKitPlugin($I);
	}
}
