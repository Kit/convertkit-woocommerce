<?php

namespace Tests\EndToEnd;

use Tests\Support\EndToEndTester;

/**
 * Tests for the Enable/Disable option on the WooCommerce Integration.
 *
 * @since   1.4.2
 */
class SettingEnabledDisabledCest
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
	}

	/**
	 * Test that the integration doesn't perform any expected actions when disabled at
	 * WooCommerce > Settings > Integration > ConvertKit, and that WooCommerce Checkout
	 * works as expected.
	 *
	 * @since   1.4.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testIntegrationWhenDisabled(EndToEndTester $I)
	{
		// Setup WooCommerce Plugin.
		$I->setupWooCommercePlugin($I);

		// Create Simple Product.
		$productID = $I->wooCommerceCreateSimpleProduct($I);

		// Add Product to Cart and load Checkout.
		$I->wooCommerceCheckoutWithProduct($I, $productID, 'Simple Product');

		// Click Place order button.
		$I->waitForElementNotVisible('.blockOverlay');
		$I->scrollTo('#order_review_heading');
		$I->click('#place_order');

		// Wait until JS completes and redirects.
		$I->waitForElement('.woocommerce-order-received', 10);

		// Check that no WooCommerce, PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);
	}

	/**
	 * Test that the enabled setting is honored when checked at
	 * WooCommerce > Settings > Integration > ConvertKit.
	 *
	 * @since   1.6.0
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testEnabled(EndToEndTester $I)
	{
		// Setup Plugin.
		$I->haveOptionInDatabase(
			'woocommerce_ckwc_settings',
			[
				'enabled'       => 'no',
				'access_token'  => $_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN'],
				'refresh_token' => $_ENV['CONVERTKIT_OAUTH_REFRESH_TOKEN'],
			]
		);

		// Load Settings screen.
		$I->loadConvertKitSettingsScreen($I);

		// Check "Enabled" checkbox.
		$I->checkOption('#woocommerce_ckwc_enabled');

		// Save.
		$I->click('Save changes');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm the setting saved.
		$I->seeCheckboxIsChecked('#woocommerce_ckwc_enabled');
	}

	/**
	 * Test that the enabled setting is honored when unchecked at
	 * WooCommerce > Settings > Integration > ConvertKit.
	 *
	 * @since   1.6.0
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testDisabled(EndToEndTester $I)
	{
		// Setup Plugin.
		$I->haveOptionInDatabase(
			'woocommerce_ckwc_settings',
			[
				'enabled'       => 'yes',
				'access_token'  => $_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN'],
				'refresh_token' => $_ENV['CONVERTKIT_OAUTH_REFRESH_TOKEN'],
			]
		);

		// Load Settings screen.
		$I->loadConvertKitSettingsScreen($I);

		// Uncheck "Enabled" checkbox.
		$I->uncheckOption('#woocommerce_ckwc_enabled');

		// Save.
		$I->click('Save changes');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm the setting saved.
		$I->dontSeeCheckboxIsChecked('#woocommerce_ckwc_enabled');
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
