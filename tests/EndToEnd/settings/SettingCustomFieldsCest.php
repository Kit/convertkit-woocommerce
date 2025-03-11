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
class SettingCustomFieldsCest
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
		// Activate WooCommerce and ConvertKit Plugins.
		$I->activateWooCommerceAndConvertKitPlugins($I);

		// Enable Integration and define its Access and Refresh Tokens.
		$I->setupConvertKitPlugin($I);

		// Load Settings screen.
		$I->loadConvertKitSettingsScreen($I);
	}

	/**
	 * Test that Custom Field options are saved when selected at
	 * WooCommerce > Settings > Integration > ConvertKit.
	 *
	 * @since   1.4.3
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testCustomFields(EndToEndTester $I)
	{
		// Confirm Custom Fields are in alphabetical ascending order.
		$I->checkSelectCustomFieldOptionOrder(
			$I,
			'#woocommerce_ckwc_custom_field_phone',
			[
				'(Don\'t send or map)',
			]
		);

		// Set Order to Custom Field mappings.
		$I->selectOption('#woocommerce_ckwc_custom_field_last_name', 'Last Name');
		$I->selectOption('#woocommerce_ckwc_custom_field_phone', 'Phone Number');
		$I->selectOption('#woocommerce_ckwc_custom_field_billing_address', 'Billing Address');
		$I->selectOption('#woocommerce_ckwc_custom_field_shipping_address', 'Shipping Address');
		$I->checkOption('#woocommerce_ckwc_custom_field_address_exclude_name');
		$I->selectOption('#woocommerce_ckwc_custom_field_payment_method', 'Payment Method');
		$I->selectOption('#woocommerce_ckwc_custom_field_customer_note', 'Notes');

		// Save.
		$I->click('Save changes');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm the settings saved.
		$I->seeOptionIsSelected('#woocommerce_ckwc_custom_field_last_name', 'Last Name');
		$I->seeOptionIsSelected('#woocommerce_ckwc_custom_field_phone', 'Phone Number');
		$I->seeOptionIsSelected('#woocommerce_ckwc_custom_field_billing_address', 'Billing Address');
		$I->seeOptionIsSelected('#woocommerce_ckwc_custom_field_shipping_address', 'Shipping Address');
		$I->seeCheckboxIsChecked('#woocommerce_ckwc_custom_field_address_exclude_name');
		$I->seeOptionIsSelected('#woocommerce_ckwc_custom_field_payment_method', 'Payment Method');
		$I->seeOptionIsSelected('#woocommerce_ckwc_custom_field_customer_note', 'Notes');
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
