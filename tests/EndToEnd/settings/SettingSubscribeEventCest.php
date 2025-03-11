<?php

namespace Tests\EndToEnd;

use Tests\Support\EndToEndTester;

/**
 * Tests various setting combinations across the following settings:
 * - Subscribe Event
 * - Display Opt-In Checkbox
 * - Access and Refresh Tokens
 * - Subscription Form
 *
 * @since   1.4.2
 */
class SettingSubscribeEventCest
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
	 * Test that the Order Pending payment option is saved when selected at
	 * WooCommerce > Settings > Integration > ConvertKit.
	 *
	 * @since   1.4.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testOrderPendingPaymentWithoutOptInCheckbox(EndToEndTester $I)
	{
		// Set Subscribe Event = Order Order Pending payment.
		$I->selectOption('#woocommerce_ckwc_event', 'Order Pending payment');

		// Save.
		$I->click('Save changes');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm the setting saved.
		$I->seeOptionIsSelected('#woocommerce_ckwc_event', 'Order Pending payment');
	}
	/**
	 * Test that the Order Processing option is saved when selected at
	 * WooCommerce > Settings > Integration > ConvertKit.
	 *
	 * @since   1.4.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testOrderProcessing(EndToEndTester $I)
	{
		// Set Subscribe Event = Order Processing.
		$I->selectOption('#woocommerce_ckwc_event', 'Order Processing');

		// Save.
		$I->click('Save changes');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm the setting saved.
		$I->seeOptionIsSelected('#woocommerce_ckwc_event', 'Order Processing');
	}

	/**
	 * Test that the Order Completed option is saved when selected at
	 * WooCommerce > Settings > Integration > ConvertKit.
	 *
	 * @since   1.4.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testOrderCompleted(EndToEndTester $I)
	{
		// Set Subscribe Event = Order Completed.
		$I->selectOption('#woocommerce_ckwc_event', 'Order Completed');

		// Save.
		$I->click('Save changes');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm the setting saved.
		$I->seeOptionIsSelected('#woocommerce_ckwc_event', 'Order Completed');
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
