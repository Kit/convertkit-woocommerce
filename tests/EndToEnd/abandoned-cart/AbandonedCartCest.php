<?php

namespace Tests\EndToEnd;

use Tests\Support\EndToEndTester;

/**
 * Tests the Abandoned Cart functionality, which sends abandoned checkout and product purchased
 * automation events to the Kit App Store app server.
 *
 * Requests to the app server are recorded and not sent, by the Kit App Server Recorder mu-plugin.
 *
 * @since   2.0.5
 */
class AbandonedCartCest
{
	/**
	 * The site secret the app server issues when the site connects via the Kit App Store.
	 *
	 * @since   2.3.0
	 *
	 * @var     string
	 */
	private $siteSecret = 'test-site-secret';

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
	 * Test that no subscriber is created and no event is sent when Abandoned Cart is disabled.
	 *
	 * @since   2.0.5
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testAbandonedCartWhenDisabled(EndToEndTester $I)
	{
		// Setup Kit Plugin with Abandoned Cart disabled, connected via the Kit App Store.
		$I->setupConvertKitPlugin(
			$I,
			abandonedCart: false,
			appServerSiteSecret: $this->siteSecret
		);

		// Abandon a cart.
		$emailAddress = $this->abandonCart($I);

		// Confirm that the email address was not added to Kit, and no event was sent.
		$I->apiCheckSubscriberDoesNotExist(
			$I,
			emailAddress: $emailAddress
		);
		$I->assertCount(0, $I->grabAppServerEventRequests($I));
	}

	/**
	 * Test that no subscriber is created and no event is sent when Abandoned Cart is enabled,
	 * but the site isn't connected via the Kit App Store.
	 *
	 * @since   2.3.0
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testAbandonedCartWhenNotConnectedViaKitAppStore(EndToEndTester $I)
	{
		// Setup Kit Plugin with Abandoned Cart enabled, without an app server site secret.
		$I->setupConvertKitPlugin(
			$I,
			abandonedCart: true,
			abandonedCartThreshold: 0
		);

		// Abandon a cart.
		$emailAddress = $this->abandonCart($I);

		// Confirm that the email address was not added to Kit, and no event was sent.
		$I->apiCheckSubscriberDoesNotExist(
			$I,
			emailAddress: $emailAddress
		);
		$I->assertCount(0, $I->grabAppServerEventRequests($I));
	}

	/**
	 * Test that the abandoned checkout event is sent when the cart is abandoned,
	 * and the product purchased event is sent when the customer completes the checkout process.
	 *
	 * @since   2.0.5
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testAbandonedCartEvents(EndToEndTester $I)
	{
		// Setup Kit Plugin with Abandoned Cart enabled, connected via the Kit App Store.
		$I->setupConvertKitPlugin(
			$I,
			abandonedCart: true,
			// By default, this would be 15 minutes i.e. the cart is deemed abandoned after 15 minutes of inactivity.
			// We set it to zero so that running the Abandoned Cart action immediately sends the event.
			abandonedCartThreshold: 0,
			appServerSiteSecret: $this->siteSecret
		);

		// Abandon a cart.
		$productName  = 'Simple Product';
		$productID    = $I->wooCommerceCreateSimpleProduct($I, false);
		$emailAddress = $this->abandonCart($I, $productID, $productName);

		// Confirm that the email address was added to Kit, and the abandoned checkout event was sent for the subscriber.
		$subscriber = $I->apiCheckSubscriberExists(
			$I,
			emailAddress: $emailAddress
		);
		$this->seeAbandonedCheckoutEvent($I, $subscriber['id'], $productName);

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

		// Get Order ID.
		$orderID = (int) $I->grabTextFrom('ul.wc-block-order-confirmation-summary-list li:first-child span.wc-block-order-confirmation-summary-list-item__value');

		// Confirm the product purchased event was sent for the subscriber.
		$event = $this->seeProductPurchasedEvent($I, $subscriber['id'], $productName);
		$I->assertEquals((string) $orderID, (string) $event['body']['context']['order_id']);

		// Unsubscribe the email address, so we restore the account back to its previous state.
		$I->apiUnsubscribe($subscriber['id']);
	}

	/**
	 * Test that the abandoned checkout event is sent when the cart is abandoned using the Checkout block,
	 * and the product purchased event is sent when the customer completes the checkout process.
	 *
	 * @since   2.2.3
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testAbandonedCartEventsWithCheckoutBlock(EndToEndTester $I)
	{
		// Setup Kit Plugin with Abandoned Cart enabled, connected via the Kit App Store.
		$I->setupConvertKitPlugin(
			$I,
			abandonedCart: true,
			// By default, this would be 15 minutes i.e. the cart is deemed abandoned after 15 minutes of inactivity.
			// We set it to zero so that running the Abandoned Cart action immediately sends the event.
			abandonedCartThreshold: 0,
			appServerSiteSecret: $this->siteSecret
		);

		// Abandon a cart using the Checkout block.
		$productName  = 'Simple Product';
		$productID    = $I->wooCommerceCreateSimpleProduct($I, false);
		$emailAddress = $this->abandonCart($I, $productID, $productName, false);

		// Confirm that the email address was added to Kit, and the abandoned checkout event was sent for the subscriber.
		$subscriber = $I->apiCheckSubscriberExists(
			$I,
			emailAddress: $emailAddress
		);
		$this->seeAbandonedCheckoutEvent($I, $subscriber['id'], $productName);

		// Logout as the WordPress Administrator.
		$I->logOut();

		// Add Product to Cart and load the Checkout block, completing fields.
		$I->wooCommerceCheckoutWithProduct(
			$I,
			productID: $productID,
			productName: $productName,
			emailAddress: $emailAddress,
			useLegacyCheckout: false
		);

		// Click Place order button.
		$I->click('button.wc-block-components-checkout-place-order-button');

		// WooCommerce has a bug where clicking the Place Order button the first time doesn't do anything,
		// so click it again if it's still visible.
		try {
			$I->wait(2);
			$I->seeElement('button.wc-block-components-checkout-place-order-button');
			$I->click('button.wc-block-components-checkout-place-order-button');
		} catch (\Exception $e) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Don't throw an error. Continue through the assertions.
		}

		// Wait until the Order is received.
		$I->waitForElement('body.woocommerce-order-received', 30);

		// Confirm the product purchased event was sent for the subscriber.
		$this->seeProductPurchasedEvent($I, $subscriber['id'], $productName);

		// Unsubscribe the email address, so we restore the account back to its previous state.
		$I->apiUnsubscribe($subscriber['id']);
	}

	/**
	 * Adds a Product to the Cart as a guest, completes the email address at Checkout without placing the order,
	 * then runs the Abandoned Cart action as the WordPress Administrator.
	 *
	 * @since   2.3.0
	 *
	 * @param   EndToEndTester $I                  Tester.
	 * @param   bool|int       $productID          Product ID (false = create a Simple Product).
	 * @param   string         $productName        Product Name.
	 * @param   bool           $useLegacyCheckout  Use the legacy Checkout shortcode, instead of the Checkout block.
	 * @return  string                             Email Address.
	 */
	private function abandonCart(EndToEndTester $I, $productID = false, $productName = 'Simple Product', $useLegacyCheckout = true)
	{
		// Create Product.
		if ( ! $productID) {
			$productID = $I->wooCommerceCreateSimpleProduct($I, false);
		}

		// Define Email Address for this Test.
		$emailAddress = $I->generateEmailAddress();

		// Logout as the WordPress Administrator.
		$I->logOut();

		// Add Product to Cart and load Checkout, completing fields.
		$I->wooCommerceCheckoutWithProduct(
			$I,
			productID: $productID,
			productName: $productName,
			emailAddress: $emailAddress,
			useLegacyCheckout: $useLegacyCheckout
		);

		// Wait a second to permit the Abandoned Cart to be tracked.
		$I->wait(2);

		// Login and navigate to the Action Scheduler page, to manually run the Abandoned Cart action.
		$I->doLoginAsAdmin($I);
		$I->amOnAdminPage('admin.php?page=wc-status&status=pending&tab=action-scheduler&s=ckwc_abandoned_cart');

		// Hover mouse over Action Scheduler's table row.
		$I->moveMouseOver('tbody[data-wp-lists="list:action-scheduler"] tr:first-child');

		// Wait for the run link to be visible.
		$I->waitForElementVisible('tbody[data-wp-lists="list:action-scheduler"] tr:first-child span.run a');

		// Click the run action.
		$I->click('tbody[data-wp-lists="list:action-scheduler"] tr:first-child span.run a');

		// Wait for the action to complete.
		$I->waitForText('Successfully executed action', 10);

		return $emailAddress;
	}

	/**
	 * Check the abandoned checkout event was sent to the app server for the given subscriber, with the cart's Product.
	 *
	 * @since   2.3.0
	 *
	 * @param   EndToEndTester $I              Tester.
	 * @param   int            $subscriberID   Kit Subscriber ID.
	 * @param   string         $productName    Product Name.
	 * @return  array                          Event request.
	 */
	private function seeAbandonedCheckoutEvent(EndToEndTester $I, $subscriberID, $productName)
	{
		$event = $I->grabAppServerEventRequest($I, 'abandoned_checkout');

		// Confirm the request is authenticated and identifies this site.
		$this->seeAppServerEventRequest($I, $event, $subscriberID);

		// Confirm the context contains the cart.
		$I->assertEquals($productName, $event['body']['context']['products'][0]['name']);
		$I->assertEquals(1, $event['body']['context']['products'][0]['quantity']);
		$I->assertStringContainsString('/checkout', $event['body']['context']['checkout_url']);
		$I->assertNotEmpty($event['body']['context']['total']);

		return $event;
	}

	/**
	 * Check the product purchased event was sent to the app server for the given subscriber, with the Order's Product.
	 *
	 * @since   2.3.0
	 *
	 * @param   EndToEndTester $I              Tester.
	 * @param   int            $subscriberID   Kit Subscriber ID.
	 * @param   string         $productName    Product Name.
	 * @return  array                          Event request.
	 */
	private function seeProductPurchasedEvent(EndToEndTester $I, $subscriberID, $productName)
	{
		$event = $I->grabAppServerEventRequest($I, 'product_purchased');

		// Confirm the request is authenticated and identifies this site.
		$this->seeAppServerEventRequest($I, $event, $subscriberID);

		// Confirm the context contains the Order.
		$I->assertEquals($productName, $event['body']['context']['products'][0]['name']);
		$I->assertNotEmpty($event['body']['context']['order_id']);
		$I->assertNotEmpty($event['body']['context']['total']);

		return $event;
	}

	/**
	 * Check an event request to the app server is authenticated with the site secret, and identifies this site and subscriber.
	 *
	 * @since   2.3.0
	 *
	 * @param   EndToEndTester $I              Tester.
	 * @param   bool|array     $event          Event request.
	 * @param   int            $subscriberID   Kit Subscriber ID.
	 */
	private function seeAppServerEventRequest(EndToEndTester $I, $event, $subscriberID)
	{
		$I->assertIsArray($event);
		$I->assertEquals('POST', $event['method']);
		$I->assertEquals('Bearer ' . $this->siteSecret, $event['authorization']);
		$I->assertEquals($_ENV['WORDPRESS_URL'], $event['body']['site_url']);
		$I->assertEquals((int) $subscriberID, (int) $event['body']['subscriber_id']);
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
