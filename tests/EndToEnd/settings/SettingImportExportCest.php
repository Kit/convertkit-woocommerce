<?php

namespace Tests\EndToEnd;

use Tests\Support\EndToEndTester;

/**
 * Tests various setting combinations across the following settings:
 * - Subscribe Event
 * - Display Opt-In Checkbox
 * - Access and Refresh Tokens
 * - Subscription Form
 *
 * @since   1.4.6
 */
class SettingImportExportCest
{
	/**
	 * Run common actions before running the test functions in this class.
	 *
	 * @since   1.4.6
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function _before(EndToEndTester $I)
	{
		// Activate Plugin.
		$I->activateWooCommerceAndConvertKitPlugins($I);

		// Enable Integration and define its Access and Refresh Tokens.
		$I->setupConvertKitPlugin($I);

		// Load Settings screen.
		$I->loadConvertKitSettingsScreen($I);
	}

	/**
	 * Test that the Export Configuration option works.
	 *
	 * @since   1.4.6
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testExportConfiguration(EndToEndTester $I)
	{
		// Click the Export button.
		// This will download the file to $_ENV['WORDPRESS_ROOT_DIR'].
		$I->click('#mainform a#export');

		// Wait 2 seconds for the download to complete.
		sleep(2);

		// Check downloaded file exists and contains some expected information.
		$I->openFile($_ENV['WORDPRESS_ROOT_DIR'] . '/ckwc-export.json');
		$I->seeInThisFile('{"settings":{"enabled":"yes"');
		$I->seeInThisFile('"access_token":"' . $_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN'] . '","refresh_token":"' . $_ENV['CONVERTKIT_OAUTH_REFRESH_TOKEN'] . '",');

		// Delete the file.
		$I->deleteFile($_ENV['WORDPRESS_ROOT_DIR'] . '/ckwc-export.json');
	}

	/**
	 * Test that the Import Configuration option works.
	 *
	 * @since   1.4.6
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testImportConfiguration(EndToEndTester $I)
	{
		// Scroll to Import section.
		$I->scrollTo('#woocommerce_ckwc_import');

		// Select the configuration file at tests/_data/ckwc-export.json to import.
		$I->attachFile('input[name=woocommerce_ckwc_import]', 'ckwc-export.json');

		// Click the Save changes button.
		$I->click('Save changes');

		// Confirm success message displays.
		$I->seeInSource('Configuration imported successfully.');

		// Confirm that the options table contains the fake Access Token and Refresh Token.
		$settings = $I->grabOptionFromDatabase('woocommerce_ckwc_settings');
		$I->assertArrayHasKey('access_token', $settings);
		$I->assertArrayHasKey('refresh_token', $settings);
		$I->assertEquals($settings['access_token'], 'fakeAccessToken');
		$I->assertEquals($settings['refresh_token'], 'fakeRefreshToken');
	}

	/**
	 * Test that the Import Configuration option returns the expected error when an invalid file
	 * is selected.
	 *
	 * @since   1.4.6
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testImportConfigurationWithInvalidFile(EndToEndTester $I)
	{
		// Scroll to Import section.
		$I->scrollTo('#woocommerce_ckwc_import');

		// Select the configuration file at tests/_data/ckwc-export-invalid.json to import.
		$I->attachFile('input[name=woocommerce_ckwc_import]', 'ckwc-export-invalid.json');

		// Click the Save changes button.
		$I->click('Save changes');

		// Confirm error message displays.
		$I->seeInSource('The uploaded configuration file contains no settings.');
	}

	/**
	 * Test that the Import Configuration option returns the expected error when a file
	 * that appears to be JSON is selected, but its content are not JSON.
	 *
	 * @since   1.4.6
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testImportConfigurationWithFakeJSONFile(EndToEndTester $I)
	{
		// Scroll to Import section.
		$I->scrollTo('#woocommerce_ckwc_import');

		// Select the configuration file at tests/_data/ckwc-export-fake.json to import.
		$I->attachFile('input[name=woocommerce_ckwc_import]', 'ckwc-export-fake.json');

		// Click the Save changes button.
		$I->click('Save changes');

		// Confirm error message displays.
		$I->seeInSource('The uploaded configuration file isn\'t valid.');
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
