<?php

namespace Tests\EndToEnd;

use Tests\Support\EndToEndTester;

/**
 * Tests various setting combinations for the "Display Opt-In Checkbox" and associated
 * options.
 *
 * @since   1.4.2
 */
class SettingOptInCheckboxCest
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
	 * Test that the checkbox doesn't display when the Opt-In Checkbox is disabled at
	 * WooCommerce > Settings > Integration > ConvertKit.
	 *
	 * @since   1.4.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testOptInCheckboxDisabled(EndToEndTester $I)
	{
		// Disable the Opt-In Checkbox option.
		$I->uncheckOption('#woocommerce_ckwc_display_opt_in');

		// Save.
		$I->click('Save changes');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Check the fields honor the changes.
		$I->dontSeeCheckboxIsChecked('#woocommerce_ckwc_display_opt_in');

		// Create Simple Product.
		$productID = $I->wooCommerceCreateSimpleProduct($I);

		// Add Product to Cart and load Checkout.
		$I->wooCommerceCheckoutWithProduct($I, $productID, 'Simple Product');

		// Confirm that the Opt-In checkbox is not displayed on the Checkout screen.
		$I->dontSeeElementInDOM('#ckwc_opt_in');
	}

	/**
	 * Test that the checkbox does display when the Opt-In Checkbox is enabled at
	 * WooCommerce > Settings > Integration > ConvertKit.
	 *
	 * @since   1.4.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testOptInCheckboxEnabled(EndToEndTester $I)
	{
		// Enable the Opt-In Checkbox option.
		$I->checkOption('#woocommerce_ckwc_display_opt_in');

		// Save.
		$I->click('Save changes');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Check the fields remain ticked.
		$I->seeCheckboxIsChecked('#woocommerce_ckwc_display_opt_in');

		// Create Simple Product.
		$productID = $I->wooCommerceCreateSimpleProduct($I);

		// Add Product to Cart and load Checkout.
		$I->wooCommerceCheckoutWithProduct($I, $productID, 'Simple Product');

		// Confirm that the Opt-In checkbox is displayed on the Checkout screen.
		$I->seeElementInDOM('#ckwc_opt_in');

		// Confirm that the label is the default value.
		$I->seeInSource('I want to subscribe to the newsletter');
	}

	/**
	 * Test that the Opt-In Checkbox Label honors the value defined at
	 * WooCommerce > Settings > Integration > ConvertKit.
	 *
	 * @since   1.4.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testOptInCheckboxLabel(EndToEndTester $I)
	{
		// Define the custom label.
		$customLabel = 'Custom Label';

		// Enable the Opt-In Checkbox option.
		$I->checkOption('#woocommerce_ckwc_display_opt_in');

		// Define the Opt-In Checkbox label.
		$I->fillField('#woocommerce_ckwc_opt_in_label', $customLabel);

		// Save.
		$I->click('Save changes');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Check the fields remain ticked.
		$I->seeCheckboxIsChecked('#woocommerce_ckwc_display_opt_in');
		$I->seeInField('#woocommerce_ckwc_opt_in_label', $customLabel);

		// Create Simple Product.
		$productID = $I->wooCommerceCreateSimpleProduct($I);

		// Add Product to Cart and load Checkout.
		$I->wooCommerceCheckoutWithProduct($I, $productID, 'Simple Product');

		// Confirm that the Opt-In checkbox is displayed on the Checkout screen.
		$I->seeElementInDOM('#ckwc_opt_in');

		// Confirm that the label is the custom value.
		$I->seeInSource($customLabel);
	}

	/**
	 * Test that the Opt-In Checkbox is checked by default when this behaviour is enabled at
	 * WooCommerce > Settings > Integration > ConvertKit.
	 *
	 * @since   1.4.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testOptInCheckboxDefaultStatusEnabled(EndToEndTester $I)
	{
		// Enable the Opt-In Checkbox option.
		$I->checkOption('#woocommerce_ckwc_display_opt_in');

		// Set the Opt-In Checkbox Default Status = Checked.
		$I->checkOption('#woocommerce_ckwc_opt_in_status', 'Checked');

		// Save.
		$I->click('Save changes');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Check the fields remain ticked.
		$I->seeCheckboxIsChecked('#woocommerce_ckwc_display_opt_in');
		$I->seeOptionIsSelected('#woocommerce_ckwc_opt_in_status', 'Checked');

		// Create Simple Product.
		$productID = $I->wooCommerceCreateSimpleProduct($I);

		// Add Product to Cart and load Checkout.
		$I->wooCommerceCheckoutWithProduct($I, $productID, 'Simple Product');

		// Confirm that the Opt-In checkbox is displayed on the Checkout screen.
		$I->seeElementInDOM('#ckwc_opt_in');

		// Confirm that the Opt-In checkbox is checked on the Checkout screen.
		$I->seeCheckboxIsChecked('#ckwc_opt_in');
	}

	/**
	 * Test that the Opt-In Checkbox is not checked by default when this behaviour is disabled at
	 * WooCommerce > Settings > Integration > ConvertKit.
	 *
	 * @since   1.4.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testOptInCheckboxDefaultStatusDisabled(EndToEndTester $I)
	{
		// Enable the Opt-In Checkbox option.
		$I->checkOption('#woocommerce_ckwc_display_opt_in');

		// Set the Opt-In Checkbox Default Status = Unchecked.
		$I->selectOption('#woocommerce_ckwc_opt_in_status', 'Unchecked');

		// Save.
		$I->click('Save changes');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Check the fields remain ticked.
		$I->seeCheckboxIsChecked('#woocommerce_ckwc_display_opt_in');
		$I->seeOptionIsSelected('#woocommerce_ckwc_opt_in_status', 'Unchecked');

		// Create Simple Product.
		$productID = $I->wooCommerceCreateSimpleProduct($I);

		// Add Product to Cart and load Checkout.
		$I->wooCommerceCheckoutWithProduct($I, $productID, 'Simple Product');

		// Confirm that the Opt-In checkbox is displayed on the Checkout screen.
		$I->seeElementInDOM('#ckwc_opt_in');

		// Confirm that the Opt-In checkbox is not checked on the Checkout screen.
		$I->dontSeeCheckboxIsChecked('#ckwc_opt_in');
	}

	/**
	 * Test that the Opt-In Checkbox is displayed in the Billing section of the Checkout when defined at
	 * WooCommerce > Settings > Integration > ConvertKit.
	 *
	 * @since   1.4.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testOptInCheckboxDisplayLocationBilling(EndToEndTester $I)
	{
		// Enable the Opt-In Checkbox option.
		$I->checkOption('#woocommerce_ckwc_display_opt_in');

		// Set the Opt-In Checkbox Display Location = Billing.
		$I->selectOption('#woocommerce_ckwc_opt_in_location', 'Billing');

		// Save.
		$I->click('Save changes');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Check the fields remain ticked / selected.
		$I->seeCheckboxIsChecked('#woocommerce_ckwc_display_opt_in');
		$I->seeOptionIsSelected('#woocommerce_ckwc_opt_in_location', 'Billing');

		// Create Simple Product.
		$productID = $I->wooCommerceCreateSimpleProduct($I);

		// Add Product to Cart and load Checkout.
		$I->wooCommerceCheckoutWithProduct($I, $productID, 'Simple Product');

		// Confirm that the Opt-In checkbox is displayed in the Billing section on the Checkout screen.
		$I->seeElementInDOM('.woocommerce-billing-fields #ckwc_opt_in');
	}

	/**
	 * Test that the Opt-In Checkbox is displayed in the Order section of the Checkout when defined at
	 * WooCommerce > Settings > Integration > ConvertKit.
	 *
	 * @since   1.4.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testOptInCheckboxDisplayLocationOrder(EndToEndTester $I)
	{
		// Enable the Opt-In Checkbox option.
		$I->checkOption('#woocommerce_ckwc_display_opt_in');

		// Set the Opt-In Checkbox Display Location = Order.
		$I->selectOption('#woocommerce_ckwc_opt_in_location', 'Order');

		// Save.
		$I->click('Save changes');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Check the fields remain ticked / selected.
		$I->seeCheckboxIsChecked('#woocommerce_ckwc_display_opt_in');
		$I->seeOptionIsSelected('#woocommerce_ckwc_opt_in_location', 'Order');

		// Create Simple Product.
		$productID = $I->wooCommerceCreateSimpleProduct($I);

		// Add Product to Cart and load Checkout.
		$I->wooCommerceCheckoutWithProduct($I, $productID, 'Simple Product');

		// Confirm that the Opt-In checkbox is displayed in the Order section on the Checkout screen.
		$I->seeElementInDOM('.woocommerce-additional-fields #ckwc_opt_in');
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
