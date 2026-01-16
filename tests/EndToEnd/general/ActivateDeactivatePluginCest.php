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
	 * is displayed with no errors, and the Action Scheduler action is scheduled
	 * and unscheduled accordingly.
	 *
	 * @since   1.4.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testPluginActivationAndDeactivation(EndToEndTester $I)
	{
		// Activate the Plugin.
		$I->activateWooCommerceAndConvertKitPlugins($I);

		// Confirm the Action Scheduler action is scheduled.
		$I->amOnAdminPage('admin.php?page=wc-status&status=pending&tab=action-scheduler&s=ckwc_abandoned_cart');
		$I->assertEquals('ckwc_abandoned_cart', $I->grabTextFrom('tbody[data-wp-lists="list:action-scheduler"] tr:first-child td.column-hook'));

		// Deactivate the Plugin.
		$I->deactivateConvertKitPlugin($I);

		// Confirm the Action Scheduler action is unscheduled.
		$I->amOnAdminPage('admin.php?page=wc-status&status=pending&tab=action-scheduler&s=ckwc_abandoned_cart');
		$I->waitForElementVisible('tbody[data-wp-lists="list:action-scheduler"] tr.no-items');

		// Deactivate the WooCommerce Plugin.
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
