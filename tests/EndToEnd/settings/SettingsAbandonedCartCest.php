<?php

namespace Tests\EndToEnd;

use Tests\Support\EndToEndTester;

/**
 * Tests various setting combinations for the "Purchases" options.
 *
 * @since   2.0.5
 */
class SettingsAbandonedCartCest
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

		// Enable Integration and define its Access and Refresh Tokens.
		$I->setupConvertKitPlugin($I);

		// Load Settings screen.
		$I->loadConvertKitSettingsScreen($I);
	}

	/**
	 * Test that the Abandoned Cart option is saved when enabled at
	 * WooCommerce > Settings > Integration > ConvertKit.
	 *
	 * @since   2.0.5
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testAbandonedCartEnabled(EndToEndTester $I)
	{
		// Check "Abandoned Cart" checkbox.
		$I->checkOption('#woocommerce_ckwc_abandoned_cart');

		// Confirm the Abandoned Cart options are displayed.
		$I->waitForElementVisible('#woocommerce_ckwc_abandoned_cart_timeout');
		$I->waitForElementVisible('#woocommerce_ckwc_abandoned_cart_subscription');

		// Save.
		$I->click('Save changes');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm the setting saved.
		$I->seeCheckboxIsChecked('#woocommerce_ckwc_abandoned_cart');
		$I->waitForElementVisible('#woocommerce_ckwc_abandoned_cart_timeout');
		$I->waitForElementVisible('#woocommerce_ckwc_abandoned_cart_subscription');
	}

	/**
	 * Test that the Abandoned Cart option is saved when disabled at
	 * WooCommerce > Settings > Integration > ConvertKit.
	 *
	 * @since   2.0.5
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testAbandonedCartDisabled(EndToEndTester $I)
	{
		// Check "Abandoned Cart" checkbox.
		$I->checkOption('#woocommerce_ckwc_abandoned_cart');

		// Uncheck "Abandoned Cart" checkbox.
		$I->uncheckOption('#woocommerce_ckwc_abandoned_cart');

		// Confirm the Abandoned Cart options are not displayed.
		$I->waitForElementNotVisible('#woocommerce_ckwc_abandoned_cart_timeout');
		$I->waitForElementNotVisible('#woocommerce_ckwc_abandoned_cart_subscription');

		// Save.
		$I->click('Save changes');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm the setting saved.
		$I->dontSeeCheckboxIsChecked('#woocommerce_ckwc_abandoned_cart');
		$I->waitForElementNotVisible('#woocommerce_ckwc_abandoned_cart_timeout');
		$I->waitForElementNotVisible('#woocommerce_ckwc_abandoned_cart_subscription');
	}

	/**
	 * Test that the Abandoned Cart Timeout option is saved when set to 10 minutes at
	 * WooCommerce > Settings > Integration > ConvertKit.
	 *
	 * @since   2.0.5
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testAbandonedCartTimeoutSetting(EndToEndTester $I)
	{
		// Check "Abandoned Cart" checkbox.
		$I->checkOption('#woocommerce_ckwc_abandoned_cart');

		// Set Abandoned Cart Timeout = 10 minutes.
		$I->fillField('#woocommerce_ckwc_abandoned_cart_timeout', '10');

		// Save.
		$I->click('Save changes');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm the settings saved.
		$I->seeCheckboxIsChecked('#woocommerce_ckwc_abandoned_cart');
		$I->seeInField('#woocommerce_ckwc_abandoned_cart_timeout', '10');
	}

	/**
	 * Test that the Abandoned Cart Timeout option is saved when set to 10 minutes at
	 * WooCommerce > Settings > Integration > ConvertKit.
	 *
	 * @since   2.0.5
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testAbandonedCartSubscriptionSetting(EndToEndTester $I)
	{
		// Check "Abandoned Cart" checkbox.
		$I->checkOption('#woocommerce_ckwc_abandoned_cart');

		// Set Abandoned Cart Subscription to a Sequence.
		$I->selectOption('#woocommerce_ckwc_abandoned_cart_subscription', $_ENV['CONVERTKIT_API_SEQUENCE_NAME']);

		// Save.
		$I->click('Save changes');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm the settings saved.
		$I->seeCheckboxIsChecked('#woocommerce_ckwc_abandoned_cart');
		$I->seeOptionIsSelected('#woocommerce_ckwc_abandoned_cart_subscription', $_ENV['CONVERTKIT_API_SEQUENCE_NAME']);
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
