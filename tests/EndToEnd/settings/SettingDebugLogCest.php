<?php

namespace Tests\EndToEnd;

use Tests\Support\EndToEndTester;

/**
 * Tests various setting combinations for the "Debug" options.
 *
 * @since   1.4.2
 */
class SettingDebugLogCest
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
		$I->activateWooCommerceAndConvertKitPlugins($I);

		// Enable Integration and define its Access and Refresh Tokens.
		$I->setupConvertKitPlugin($I);

		// Load Settings screen.
		$I->loadConvertKitSettingsScreen($I);
	}

	/**
	 * Test that debug logging works when enabled at
	 * WooCommerce > Settings > Integration > ConvertKit.
	 *
	 * @since   1.4.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testDebugEnabled(EndToEndTester $I)
	{
		// Check "Debug" checkbox.
		$I->checkOption('#woocommerce_ckwc_debug');

		// Save.
		$I->click('Save changes');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm the setting saved.
		$I->seeCheckboxIsChecked('#woocommerce_ckwc_debug');

		// Load WooCommerce's Logs screen.
		$I->amOnAdminPage('admin.php?page=wc-status&tab=logs');

		// Confirm that a ConvertKit Log File exists in the table of logs for today's date.
		$I->seeInSource('<a class="row-title" href="' . $_ENV['TEST_SITE_WP_URL'] . '/wp-admin/admin.php?page=wc-status&amp;tab=logs&amp;view=single_file&amp;file_id=convertkit-' . date('Y-m-d') . '">convertkit</a>');
	}

	/**
	 * Test that debug logging setting is honored when disabled at
	 * WooCommerce > Settings > Integration > ConvertKit.
	 *
	 * @since   1.6.0
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testDebugDisabled(EndToEndTester $I)
	{
		// Uncheck "Debug" checkbox.
		$I->uncheckOption('#woocommerce_ckwc_debug');

		// Save.
		$I->click('Save changes');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm the setting saved.
		$I->dontSeeCheckboxIsChecked('#woocommerce_ckwc_debug');
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
