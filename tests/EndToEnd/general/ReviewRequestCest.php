<?php

namespace Tests\EndToEnd;

use Tests\Support\EndToEndTester;

/**
 * Tests the ConvertKit Review Notification.
 *
 * @since   1.4.3
 */
class ReviewRequestCest
{
	/**
	 * Run common actions before running the test functions in this class.
	 *
	 * @since   1.4.3
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function _before(EndToEndTester $I)
	{
		// Activate Plugin.
		$I->activateWooCommerceAndConvertKitPlugins($I);

		// Setup WooCommerce Plugin.
		$I->setupWooCommercePlugin($I);

		// Populate resoruces.
		$I->setupConvertKitPluginResources($I);
	}

	/**
	 * Test that the review request is set in the options table when a WooCommerce
	 * Checkout is completed successfully.
	 *
	 * @since   1.4.3
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testReviewRequestOnCheckoutWithOptInEnabled(EndToEndTester $I)
	{
		// Clear options table settings for review request.
		$I->deleteConvertKitReviewRequestOptions($I);

		// Create Product and Checkout for this test.
		$result = $I->wooCommerceCreateProductAndCheckoutWithConfig(
			$I,
			[
				'display_opt_in'           => true,
				'check_opt_in'             => true,
				'plugin_form_tag_sequence' => 'form:' . $_ENV['CONVERTKIT_API_FORM_ID'],
				'subscription_event'       => 'processing',
			]
		);

		// Check that the options table does have a review request set.
		$I->seeOptionInDatabase('convertkit-for-woocommerce-review-request');

		// Check that the option table does not yet have a review dismissed set.
		$I->dontSeeOptionInDatabase('convertkit-for-woocommerce-review-dismissed');
	}

	/**
	 * Test that the review request is not set in the options table when a WooCommerce
	 * Checkout is completed successfully but the customer does not opt in.
	 *
	 * @since   1.4.3
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testReviewRequestOnCheckoutWithOptInDisabled(EndToEndTester $I)
	{
		// Clear options table settings for review request.
		$I->deleteConvertKitReviewRequestOptions($I);

		// Create Product and Checkout for this test.
		$result = $I->wooCommerceCreateProductAndCheckoutWithConfig(
			$I,
			[
				'display_opt_in'           => true,
				'plugin_form_tag_sequence' => 'form:' . $_ENV['CONVERTKIT_API_FORM_ID'],
				'subscription_event'       => 'processing',
			]
		);

		// Check that the options table does not have a review request set.
		$I->dontSeeOptionInDatabase('convertkit-for-woocommerce-review-request');

		// Check that the option table does not yet have a review dismissed set.
		$I->dontSeeOptionInDatabase('convertkit-for-woocommerce-review-dismissed');
	}

	/**
	 * Test that the review request is set in the options table when a WooCommerce
	 * Checkout is completed successfully.
	 *
	 * @since   1.4.3
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testReviewRequestOnCheckoutWithPurchaseDataEnabled(EndToEndTester $I)
	{
		// Clear options table settings for review request.
		$I->deleteConvertKitReviewRequestOptions($I);

		// Create Product and Checkout for this test.
		$result = $I->wooCommerceCreateProductAndCheckoutWithConfig(
			$I,
			[
				'send_purchase_data' => true,
			]
		);

		// Check that the options table does have a review request set.
		$I->seeOptionInDatabase('convertkit-for-woocommerce-review-request');

		// Check that the option table does not yet have a review dismissed set.
		$I->dontSeeOptionInDatabase('convertkit-for-woocommerce-review-dismissed');
	}

	/**
	 * Test that the review request is displayed when the options table entries
	 * have the required values to display the review request notification,
	 * and that when dismissed, it no longer displays on subsequent requests.
	 *
	 * @since   1.4.3
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testReviewRequestNotificationDisplaysAndDismisses(EndToEndTester $I)
	{
		// Clear options table settings for review request.
		$I->deleteConvertKitReviewRequestOptions($I);

		// Set review request option with a timestamp in the past, to emulate
		// the Plugin having set this a few days ago.
		$I->haveOptionInDatabase('convertkit-for-woocommerce-review-request', time() - 3600 );

		// Navigate to a screen in the WordPress Administration.
		$I->amOnAdminPage('index.php');

		// Confirm the review displays.
		$I->seeElementInDOM('div.review-convertkit-for-woocommerce');

		// Confirm links are correct.
		$I->seeInSource('<a href="https://wordpress.org/support/plugin/convertkit-for-woocommerce/reviews/?filter=5#new-post" class="button button-primary" rel="noopener" target="_blank">');
		$I->seeInSource('<a href="https://kit.com/support" class="button" rel="noopener" target="_blank">');

		// Dismiss the review request.
		$I->click('div.review-convertkit-for-woocommerce button.notice-dismiss');

		// Navigate to a screen in the WordPress Administration.
		$I->amOnAdminPage('index.php');

		// Confirm the review notification no longer displays.
		$I->dontSeeElementInDOM('div.review-convertkit-for-woocommerce');
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
		$I->deactivateWooCommerceAndConvertKitPlugins($I);
		$I->resetConvertKitPlugin($I);
	}
}
