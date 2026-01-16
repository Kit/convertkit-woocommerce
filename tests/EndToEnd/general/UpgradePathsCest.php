<?php

namespace Tests\EndToEnd;

use Tests\Support\EndToEndTester;

/**
 * Tests edge cases and upgrade routines when upgrading between specific ConvertKit Plugin versions.
 *
 * @since   1.8.0
 */
class UpgradePathsCest
{
	/**
	 * Tests that an Access Token and Refresh Token are obtained using an API Key and Secret
	 * when upgrading to 1.8.0 or later.
	 *
	 * @since   1.8.0
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testGetAccessTokenByAPIKeyAndSecret(EndToEndTester $I)
	{
		// Setup Plugin's settings with an API Key and Secret.
		$I->haveOptionInDatabase(
			'woocommerce_ckwc_settings',
			[
				'api_key'    => $_ENV['CONVERTKIT_API_KEY'],
				'api_secret' => $_ENV['CONVERTKIT_API_SECRET'],
				'enabled'    => 'yes',
			]
		);

		// Define an installation version older than 1.8.0.
		$I->haveOptionInDatabase('ckwc_version', '1.4.0');

		// Activate the Plugin, as if we just upgraded to 1.8.0 or higher.
		$I->activateWooCommerceAndConvertKitPlugins($I);

		// Confirm the options table now contains an Access Token and Refresh Token.
		$settings = $I->grabOptionFromDatabase('woocommerce_ckwc_settings');
		$I->assertArrayHasKey('access_token', $settings);
		$I->assertArrayHasKey('refresh_token', $settings);
		$I->assertArrayHasKey('token_expires', $settings);

		// Confirm the API Key and Secret are retained, in case we need them in the future.
		$I->assertArrayHasKey('api_key', $settings);
		$I->assertArrayHasKey('api_secret', $settings);
		$I->assertEquals($settings['api_key'], $_ENV['CONVERTKIT_API_KEY']);
		$I->assertEquals($settings['api_secret'], $_ENV['CONVERTKIT_API_SECRET']);

		// Load Settings screen.
		$I->loadConvertKitSettingsScreen($I);

		// Confirm the Disconnect and Save Changes buttons display.
		$I->see('Disconnect');
		$I->seeElementInDOM('button.woocommerce-save-button');

		// Enable the Integration.
		$I->checkOption('#woocommerce_ckwc_enabled');

		// Confirm that the Subscription dropdown option is displayed.
		$I->seeElement('#woocommerce_ckwc_subscription');

		// Check the order of the resource dropdown are alphabetical.
		$I->checkSelectWithOptionGroupsOptionOrder($I, '#woocommerce_ckwc_subscription');

		// Save changes (avoids a JS alert box which would prevent other tests from running due to changes made on screen).
		$I->click('Save changes');

		// Wait for settings to save.
		$I->waitForElementVisible('div.updated.inline');
		$I->see('Your settings have been saved.');
	}

	/**
	 * Test that the `Exclude Name and Address` setting is migrated to the `Address Format` setting
	 * when upgrading to 1.9.5 or later.
	 *
	 * @since   1.9.5
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testMigrateExcludeNameAndAddressSettingToAddressFormat(EndToEndTester $I)
	{
		// Setup Plugin's settings with the `Exclude Name and Address` setting enabled.
		$I->haveOptionInDatabase(
			'woocommerce_ckwc_settings',
			[
				'enabled'                           => 'yes',
				'access_token'                      => $_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN'],
				'refresh_token'                     => $_ENV['CONVERTKIT_OAUTH_REFRESH_TOKEN'],
				'custom_field_address_exclude_name' => 'yes',
			]
		);

		// Define an installation version older than 1.9.5.
		$I->haveOptionInDatabase('ckwc_version', '1.4.0');

		// Activate the Plugin, as if we just upgraded to 1.8.0 or higher.
		$I->activateWooCommerceAndConvertKitPlugins($I);

		// Confirm the options table now contains the address format without Name and Company Name.
		$settings = $I->grabOptionFromDatabase('woocommerce_ckwc_settings');
		$I->assertArrayHasKey('custom_field_address_format', $settings);
		$I->assertEquals(array( 'address_1', 'address_2', 'city', 'state', 'postcode' ), $settings['custom_field_address_format']);
	}

	/**
	 * Test that the Abandoned Cart action is scheduled when upgrading to 2.0.5 or later.
	 *
	 * @since   2.0.5
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testAbandonedCartActionScheduled(EndToEndTester $I)
	{
		// Activate the Plugin.
		$I->activateWooCommerceAndConvertKitPlugins($I);

		// Setup Plugin's settings.
		$I->haveOptionInDatabase(
			'woocommerce_ckwc_settings',
			[
				'access_token'  => $_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN'],
				'refresh_token' => $_ENV['CONVERTKIT_OAUTH_REFRESH_TOKEN'],
				'enabled'       => 'yes',
			]
		);

		// Define an installation version older than 2.0.5.
		$I->haveOptionInDatabase('ckwc_version', '2.0.4');

		// Remove the Action Scheduler action, as it will have been added on activation of 2.0.5 or higher.
		$I->dontHaveInDatabase(
			'actionscheduler_actions',
			[
				'hook' => 'ckwc_abandoned_cart',
			]
		);

		// Navigate to the Plugins screen, as if the Plugin just upgraded to 2.0.5 or higher.
		$I->amOnPluginsPage();

		// Confirm the Action Scheduler action is scheduled.
		$I->amOnAdminPage('admin.php?page=wc-status&status=pending&tab=action-scheduler&s=ckwc_abandoned_cart');
		$I->assertEquals('ckwc_abandoned_cart', $I->grabTextFrom('tbody[data-wp-lists="list:action-scheduler"] tr:first-child td.column-hook'));
	}

	/**
	 * Deactivate and reset Plugin(s) after each test, if the test passes.
	 * We don't use _after, as this would provide a screenshot of the Plugin
	 * deactivation and not the true test error.
	 *
	 * @since   1.8.0
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function _passed(EndToEndTester $I)
	{
		$I->deactivateWooCommerceAndConvertKitPlugins($I);
		$I->resetConvertKitPlugin($I);
	}
}
