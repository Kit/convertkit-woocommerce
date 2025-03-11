<?php

namespace Tests\EndToEnd;

use Tests\Support\EndToEndTester;

/**
 * Tests for any output errors on a clean installation and activation,
 * with no Plugin configuration.
 *
 * @since   1.4.2
 */
class AnyErrorsOnBlankInstallCest
{
	/**
	 * Check that no PHP errors or notices are displayed at WooCommerce > Settings > Integration > ConvertKit, when the Plugin is activated
	 * and not configured.
	 *
	 * @since   1.4.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testSettingsScreen(EndToEndTester $I)
	{
		// Activate Plugin.
		$I->activateWooCommerceAndConvertKitPlugins($I);

		// Go to the Plugin's Settings > General Screen.
		$I->loadConvertKitSettingsScreen($I);
	}

	/**
	 * Check that no errors are displayed on Products > Add New, when the Plugin is activated
	 * and not configured.
	 *
	 * @since   1.4.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testAddNewProduct(EndToEndTester $I)
	{
		// Activate Plugin.
		$I->activateWooCommerceAndConvertKitPlugins($I);

		// Navigate to Products > Add New.
		$I->amOnAdminPage('post-new.php?post_type=product');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);
	}

	/**
	 * Deactivate and reset Plugin(s) after each test, if the test passes.
	 * We don't use _after, as this would provide a screenshot of the Plugin
	 * deactivation and not the true test error.
	 *
	 * @since   1.4.8
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function _passed(EndToEndTester $I)
	{
		$I->deactivateWooCommerceAndConvertKitPlugins($I);
		$I->resetConvertKitPlugin($I);
	}
}
