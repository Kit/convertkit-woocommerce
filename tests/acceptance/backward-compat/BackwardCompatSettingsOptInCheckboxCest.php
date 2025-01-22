<?php
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
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function _before(AcceptanceTester $I)
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
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testOptInCheckboxBlockInEditor(AcceptanceTester $I)
	{
		// Enable the Opt-In Checkbox option.
		$I->checkOption('#woocommerce_ckwc_display_opt_in');

		// Save.
		$I->click('Save changes');

		// Edit Checkout Page.
		$pageID = $I->grabFromDatabase(
			'wp_posts',
			'ID',
			[
				'post_name' => 'checkout',
			]
		);
		$I->amOnAdminPage('post.php?post=' . $pageID . '&action=edit');

		// Close Gutenberg modal.
		$I->maybeCloseGutenbergWelcomeModal($I);

		// Confirm Checkout Block exists in Checkout.
		$I->click('div[data-type="ckwc/opt-in"]');

		// Confirm block cannot be deleted.
		$I->click('button.components-dropdown-menu__toggle');
		$I->waitForElementVisible('.components-popover');
		$I->dontSee('Unlock', '.components-popover');
		$I->dontSee('Delete', '.components-popover');

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
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function _passed(AcceptanceTester $I)
	{
		$I->deactivateWooCommerceAndConvertKitPlugins($I);
		$I->resetConvertKitPlugin($I);
	}
}
