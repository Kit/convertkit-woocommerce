<?php

namespace Tests\EndToEnd;

use Tests\Support\EndToEndTester;

/**
 * Tests various setting combinations for the "Name Format" option.
 *
 * @since   1.4.2
 */
class SettingNameFormatCest
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

		// Setup WooCommerce Plugin.
		$I->setupWooCommercePlugin($I);

		// Enable Integration and define its Access and Refresh Tokens.
		$I->setupConvertKitPlugin($I);

		// Load Settings screen.
		$I->loadConvertKitSettingsScreen($I);
	}

	/**
	 * Test that the Billing First Name option is saved when selected at
	 * WooCommerce > Settings > Integration > ConvertKit, and honored
	 * when submitted to ConvertKit.
	 *
	 * @since   1.4.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testNameFormatBillingFirstName(EndToEndTester $I)
	{
		// Set Name Format = Billing First Name.
		$I->selectOption('#woocommerce_ckwc_name_format', 'Billing First Name');

		// Save.
		$I->click('Save changes');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm the setting saved.
		$I->seeOptionIsSelected('#woocommerce_ckwc_name_format', 'Billing First Name');

		// Create Product and Checkout for this test.
		$result = $I->wooCommerceCreateProductAndCheckoutWithConfig(
			$I,
			[
				'display_opt_in'           => true,
				'check_opt_in'             => true,
				'plugin_form_tag_sequence' => 'form:' . $_ENV['CONVERTKIT_API_FORM_ID'],
				'subscription_event'       => 'pending',
			]
		);

		// Confirm that the email address was now added to ConvertKit with a name.
		$subscriber = $I->apiCheckSubscriberExists($I, $result['email_address'], 'First');

		// Unsubscribe the email address, so we restore the account back to its previous state.
		$I->apiUnsubscribe($subscriber['id']);
	}

	/**
	 * Test that the Billing Last Name option is saved when selected at
	 * WooCommerce > Settings > Integration > ConvertKit, and honored
	 * when submitted to ConvertKit.
	 *
	 * @since   1.4.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testNameFormatBillingLastName(EndToEndTester $I)
	{
		// Set Name Format = Billing Last Name.
		$I->selectOption('#woocommerce_ckwc_name_format', 'Billing Last Name');

		// Save.
		$I->click('Save changes');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm the setting saved.
		$I->seeOptionIsSelected('#woocommerce_ckwc_name_format', 'Billing Last Name');

		// Create Product and Checkout for this test.
		$result = $I->wooCommerceCreateProductAndCheckoutWithConfig(
			$I,
			[
				'display_opt_in'           => true,
				'check_opt_in'             => true,
				'plugin_form_tag_sequence' => 'form:' . $_ENV['CONVERTKIT_API_FORM_ID'],
				'subscription_event'       => 'pending',
				'name_format'              => 'last',
			]
		);

		// Confirm that the email address was now added to ConvertKit.
		$subscriber = $I->apiCheckSubscriberExists($I, $result['email_address'], 'Last');

		// Unsubscribe the email address, so we restore the account back to its previous state.
		$I->apiUnsubscribe($subscriber['id']);
	}

	/**
	 * Test that the Billing First Name + Billing Last Name option is saved when selected at
	 * WooCommerce > Settings > Integration > ConvertKit, and honored
	 * when submitted to ConvertKit.
	 *
	 * @since   1.4.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testNameFormatBillingFirstNameAndLastName(EndToEndTester $I)
	{
		// Set Name Format = Billing First Name.
		$I->selectOption('#woocommerce_ckwc_name_format', 'Billing First Name + Billing Last Name');

		// Save.
		$I->click('Save changes');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm the setting saved.
		$I->seeOptionIsSelected('#woocommerce_ckwc_name_format', 'Billing First Name + Billing Last Name');

		// Create Product and Checkout for this test.
		$result = $I->wooCommerceCreateProductAndCheckoutWithConfig(
			$I,
			[
				'display_opt_in'           => true,
				'check_opt_in'             => true,
				'plugin_form_tag_sequence' => 'form:' . $_ENV['CONVERTKIT_API_FORM_ID'],
				'subscription_event'       => 'pending',
				'name_format'              => 'both',
			]
		);

		// Confirm that the email address was now added to ConvertKit.
		$subscriber = $I->apiCheckSubscriberExists($I, $result['email_address'], 'First Last');

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
		$I->deactivateWooCommerceAndConvertKitPlugins($I);
		$I->resetConvertKitPlugin($I);
	}
}
