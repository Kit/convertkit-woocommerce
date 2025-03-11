<?php

namespace Tests\EndToEnd;

use Tests\Support\EndToEndTester;

/**
 * Tests Plugin activation and deactivation.
 *
 * @since   1.4.2
 */
class ActivateDeactivatePluginCest
{
	/**
	 * Activate the Plugin and confirm a success notification
	 * is displayed with no errors.
	 *
	 * @since   1.4.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testPluginActivationAndDeactivation(EndToEndTester $I)
	{
		$I->activateWooCommerceAndConvertKitPlugins($I);
		$I->deactivateWooCommerceAndConvertKitPlugins($I);
	}

	/**
	 * Activate the Plugin without the WooCommerce Plugin and confirm a success notification
	 * is displayed with no errors.
	 *
	 * @since   1.4.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testPluginActivationAndDeactivationWithoutWooCommerce(EndToEndTester $I)
	{
		$I->activateConvertKitPlugin($I);
		$I->deactivateConvertKitPlugin($I);
	}
}
