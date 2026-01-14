<?php

namespace Tests\EndToEnd;

use Tests\Support\EndToEndTester;

/**
 * Tests various setting combinations for the "Purchases" options.
 *
 * @since   1.4.2
 */
class SettingPurchasesCest
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

		// Enable Integration and define its Access and Refresh Tokens.
		$I->setupConvertKitPlugin($I);

		// Load Settings screen.
		$I->loadConvertKitSettingsScreen($I);
	}

	/**
	 * Test that the Purchase Data option is saved when enabled at
	 * WooCommerce > Settings > Integration > ConvertKit.
	 *
	 * @since   1.4.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testSendPurchaseDataEnabled(EndToEndTester $I)
	{
		// Check "Send purchase data to Kit" checkbox.
		$I->checkOption('#woocommerce_ckwc_send_purchases');

		// Confirm that the Purchase Data Event option is displayed.
		$I->waitForElementVisible('#woocommerce_ckwc_send_purchases_event');

		// Save.
		$I->click('Save changes');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm the setting saved.
		$I->seeCheckboxIsChecked('#woocommerce_ckwc_send_purchases');
		$I->waitForElementVisible('#woocommerce_ckwc_send_purchases_event');
	}

	/**
	 * Test that the Purchase Data option is saved when disabled at
	 * WooCommerce > Settings > Integration > ConvertKit.
	 *
	 * @since   1.4.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testSendPurchaseDataDisabled(EndToEndTester $I)
	{
		// Uncheck "Send purchase data to Kit" checkbox.
		$I->uncheckOption('#woocommerce_ckwc_send_purchases');

		// Confirm that the Purchase Data Event option is not displayed.
		$I->waitForElementNotVisible('#woocommerce_ckwc_send_purchases_event');

		// Save.
		$I->click('Save changes');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm the setting saved.
		$I->dontSeeCheckboxIsChecked('#woocommerce_ckwc_send_purchases');
		$I->waitForElementNotVisible('#woocommerce_ckwc_send_purchases_event');
	}

	/**
	 * Test that the Purchase Data Event option is saved when set to Order Processing at
	 * WooCommerce > Settings > Integration > ConvertKit.
	 *
	 * @since   1.4.5
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testSendPurchaseDataEventOrderProcessing(EndToEndTester $I)
	{
		// Check "Send purchase data to Kit" checkbox.
		$I->checkOption('#woocommerce_ckwc_send_purchases');

		// Set Event = Order Processing.
		$I->selectOption('#woocommerce_ckwc_send_purchases_event', 'Order Processing');

		// Save.
		$I->click('Save changes');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm the setting saved.
		$I->seeCheckboxIsChecked('#woocommerce_ckwc_send_purchases');
		$I->seeOptionIsSelected('#woocommerce_ckwc_send_purchases_event', 'Order Processing');
	}

	/**
	 * Test that the Purchase Data Event option is saved when set to Order Completed at
	 * WooCommerce > Settings > Integration > ConvertKit.
	 *
	 * @since   1.4.5
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testSendPurchaseDataEventOrderCompleted(EndToEndTester $I)
	{
		// Check "Send purchase data to Kit" checkbox.
		$I->checkOption('#woocommerce_ckwc_send_purchases');

		// Set Event = Order Completed.
		$I->selectOption('#woocommerce_ckwc_send_purchases_event', 'Order Completed');

		// Save.
		$I->click('Save changes');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm the setting saved.
		$I->seeCheckboxIsChecked('#woocommerce_ckwc_send_purchases');
		$I->seeOptionIsSelected('#woocommerce_ckwc_send_purchases_event', 'Order Completed');
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
