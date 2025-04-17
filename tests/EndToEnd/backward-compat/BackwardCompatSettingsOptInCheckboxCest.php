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

		// Load Settings screen.
		$I->loadConvertKitSettingsScreen($I);
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
