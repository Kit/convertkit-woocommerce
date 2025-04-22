<?php

namespace Tests\EndToEnd;

use Tests\Support\EndToEndTester;

/**
 * Tests features that are supported in an older WooCommerce version, 8.4.0
 *
 * @since   1.9.1
 */
class BackwardCompatSettingOptInCheckboxCest
{
	/**
	 * Run common actions before running the test functions in this class.
	 *
	 * @since   1.9.1
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
	}

	/**
	 * Test that the opt in checkbox block is automatically added to the WooCommerce Checkout
	 * block, cannot be removed and links to the integration settings screen.
	 *
	 * @since   1.9.1
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testOptInCheckboxBlockInEditor(EndToEndTester $I)
	{
		// Load Settings screen.
		$I->loadConvertKitSettingsScreen($I);

		// Enable the Opt-In Checkbox option.
		$I->checkOption('#woocommerce_ckwc_display_opt_in');

		// Save.
		$I->click('Save changes');

		// Wait for the page to load.
		$I->waitForElementVisible('div.updated.inline');

		// Get Checkout Page.
		$pageID = $I->grabFromDatabase(
			'wp_posts',
			'ID',
			[
				'post_name' => 'checkout',
			]
		);

		// Edit Checkout Page.
		$I->amOnAdminPage('post.php?post=' . $pageID . '&action=edit');

		// Wait for the page to load.
		$I->waitForElementVisible('body.post-type-page');

		// Close Gutenberg modal.
		$I->maybeCloseGutenbergWelcomeModal($I);

		// Confirm Checkout Block exists in Checkout.
		$I->click('div[data-type="ckwc/opt-in"]');

		// Confirm block cannot be deleted.
		$I->waitForElementVisible('button[aria-label="Locked"]');

		// Confirm Configure Opt In button.
		$I->click('Configure Opt In');

		// Switch to tab that opened.
		$I->switchToNextTab();

		// Confirm this displays the integration settings screen.
		$I->seeCheckboxIsChecked('#woocommerce_ckwc_display_opt_in');
	}

	/**
	 * Test that the Customer is subscribed to Kit when:
	 * - The opt in checkbox is enabled in the integration Settings, and
	 * - The opt in checkbox is checked on the WooCommerce checkout, and
	 * - The Customer purchases a 'Simple' WooCommerce Product, and
	 * - The Customer is subscribed at the point the WooCommerce Order is marked as processing.
	 *
	 * @since   1.9.6
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testOptInWhenCheckedWithFormAndSimpleProduct(EndToEndTester $I)
	{
		// Create Product and Checkout for this test.
		$result = $I->wooCommerceCreateProductAndCheckoutWithConfig(
			$I,
			[
				'display_opt_in'           => true,
				'check_opt_in'             => true,
				'plugin_form_tag_sequence' => 'form:' . $_ENV['CONVERTKIT_API_FORM_ID'],
				'subscription_event'       => 'processing',
				'use_legacy_checkout'      => false,
			]
		);

		// Confirm that the email address was now added to ConvertKit.
		$subscriber = $I->apiCheckSubscriberExists($I, $result['email_address'], 'First');

		// Confirm the subscriber's custom field data is empty, as no Order to Custom Field mapping was specified
		// in the integration's settings.
		$I->apiCustomFieldDataIsEmpty($I, $subscriber);

		// Check that the subscriber has the expected form and referrer value set.
		$I->apiCheckSubscriberHasForm(
			$I,
			$subscriber['id'],
			$_ENV['CONVERTKIT_API_FORM_ID'],
			$_ENV['WORDPRESS_URL']
		);

		// Unsubscribe the email address, so we restore the account back to its previous state.
		$I->apiUnsubscribe($subscriber['id']);

		// Check that the Order's Notes include a note from the Plugin confirming the Customer was subscribed to the Form.
		$I->wooCommerceOrderNoteExists($I, $result['order_id'], 'Customer subscribed to the Form: ' . $_ENV['CONVERTKIT_API_FORM_NAME'] . ' [' . $_ENV['CONVERTKIT_API_FORM_ID'] . ']');
	}



	/**
	 * Deactivate and reset Plugin(s) after each test, if the test passes.
	 * We don't use _after, as this would provide a screenshot of the Plugin
	 * deactivation and not the true test error.
	 *
	 * @since   1.9.1
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function _passed(EndToEndTester $I)
	{
		$I->deactivateWooCommerceAndConvertKitPlugins($I);
		$I->resetConvertKitPlugin($I);
	}
}
