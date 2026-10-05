<?php

namespace Tests\EndToEnd;

use Tests\Support\EndToEndTester;

/**
 * Tests OAuth connection and disconnection on the settings screen.
 *
 * @since   1.4.2
 */
class SettingOAuthCest
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
	}

	/**
	 * Test that no PHP errors or notices are displayed on the Plugin's Setting screen
	 * and a Connect button is displayed when no credentials exist.
	 *
	 * @since   1.8.0
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testNoCredentials(EndToEndTester $I)
	{
		// Load Settings screen.
		$I->loadConvertKitSettingsScreen($I);

		// Confirm CSS and JS is output by the Plugin.
		$I->seeCSSEnqueued($I, 'convertkit-woocommerce/resources/backend/css/settings.css', 'ckwc-settings-css' );
		$I->seeJSEnqueued($I, 'convertkit-woocommerce/resources/backend/js/integration.js', 'ckwc-integration-js' );

		// Confirm no option is displayed to save changes, as the Plugin isn't authenticated.
		$I->dontSeeElementInDOM('button.woocommerce-save-button');

		// Confirm the Connect button displays.
		$I->see('Connect');
		$I->dontSee('Disconnect');

		// Check that a link to the OAuth auth screen exists.
		$I->seeInSource('<a href="https://app.kit.com/oauth/authorize?client_id=' . $_ENV['CONVERTKIT_OAUTH_CLIENT_ID'] . '&amp;response_type=code&amp;redirect_uri=' . urlencode( $_ENV['KIT_OAUTH_REDIRECT_URI'] ) );

		// Check the state parameter returns to the settings screen, with a nonce.
		$state = $I->apiDecodeStateFromOAuthURL($I->grabAttributeFrom('a[href*="oauth/authorize"]', 'href'));
		$I->assertEquals($_ENV['CONVERTKIT_OAUTH_CLIENT_ID'], $state['client_id']);
		$I->assertStringStartsWith($_ENV['WORDPRESS_URL'] . '/wp-admin/admin.php?', $state['return_to']);
		$I->assertStringContainsString('page=wc-settings&tab=integration&section=ckwc', $state['return_to']);
		$I->assertStringContainsString('nonce=', $state['return_to']);

		// Click the connect button.
		$I->click('Connect');

		// Confirm the ConvertKit hosted OAuth login screen is displayed.
		$I->waitForElementVisible('body.sessions');
		$I->seeInSource('oauth/authorize?client_id=' . $_ENV['CONVERTKIT_OAUTH_CLIENT_ID']);
	}

	/**
	 * Test that no PHP errors or notices are displayed on the Plugin's Setting screen,
	 * and a warning is displayed that the supplied credentials are invalid, when
	 * e.g. the access token has been revoked.
	 *
	 * @since   1.8.0
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testInvalidCredentials(EndToEndTester $I)
	{
		// Setup Plugin.
		$I->setupConvertKitPlugin(
			$I,
			accessToken: 'fakeAccessToken',
			refreshToken: 'fakeRefreshToken'
		);

		// Load Settings screen.
		$I->loadConvertKitSettingsScreen($I);

		// Confirm an error message is displayed confirming that the access token is invalid.
		$I->see('The access token is invalid');

		// Confirm the Connect button displays.
		$I->see('Connect');
		$I->dontSee('Disconnect');
		$I->dontSeeElementInDOM('button.woocommerce-save-button');

		// Navigate to the WordPress Admin.
		$I->amOnAdminPage('index.php');

		// Check that a notice is displayed that the API credentials are invalid.
		$I->seeErrorNotice($I, 'Kit for WooCommerce: Authorization failed. Please connect your Kit account.');
	}

	/**
	 * Test that no PHP errors or notices are displayed on the Plugin's Setting screen,
	 * when valid credentials exist.
	 *
	 * @since   1.8.0
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testValidCredentials(EndToEndTester $I)
	{
		// Setup Plugin.
		$I->setupConvertKitPlugin($I);
		$I->setupConvertKitPluginResources($I);

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

		// Confirm that an expected option can be selected.
		$I->selectOption('#woocommerce_ckwc_subscription', $_ENV['CONVERTKIT_API_FORM_NAME']);

		// Save changes.
		$I->clickSaveChangesButton($I);
	}

	/**
	 * Test that the credentials and resources are deleted on disconnect.
	 *
	 * @since   2.1.3
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testCredentialsAndResourcesAreDeletedOnDisconnect(EndToEndTester $I)
	{
		// Setup Plugin.
		$I->setupConvertKitPlugin($I);

		// Load Settings screen.
		$I->loadConvertKitSettingsScreen($I);

		// Fake the API Key, Access and Refresh Tokens; if we revoke the tokens used for tests, future tests will fail.
		$I->setupConvertKitPlugin(
			$I,
			accessToken: 'fakeAccessToken',
			refreshToken: 'fakeRefreshToken',
			apiKey: 'fakeAPIKey',
			apiSecret: 'fakeAPISecret'
		);

		// Disconnect the Plugin connection to Kit.
		$I->click('Disconnect');

		// Check credentials are removed from the settings.
		$settings = $I->grabOptionFromDatabase('woocommerce_ckwc_settings');
		$I->assertEmpty($settings['access_token']);
		$I->assertEmpty($settings['refresh_token']);
		$I->assertEmpty($settings['token_expires']);
		$I->assertEmpty($settings['api_key']);
		$I->assertEmpty($settings['api_secret']);

		// Check cached resources are removed from the database on disconnection.
		$I->dontSeeOptionInDatabase('ckwc_custom_fields');
		$I->dontSeeOptionInDatabase('ckwc_custom_fields_last_queried');
		$I->dontSeeOptionInDatabase('ckwc_forms');
		$I->dontSeeOptionInDatabase('ckwc_forms_last_queried');
		$I->dontSeeOptionInDatabase('ckwc_sequences');
		$I->dontSeeOptionInDatabase('ckwc_sequences_last_queried');
		$I->dontSeeOptionInDatabase('ckwc_tags');
		$I->dontSeeOptionInDatabase('ckwc_tags_last_queried');

		// Confirm the Connect button displays.
		$I->see('Connect');
		$I->dontSee('Disconnect');
		$I->dontSeeElementInDOM('input#submit');
	}

	/**
	 * Test that an authorization code is not exchanged for an access token when the request
	 * is unauthenticated, as admin-ajax.php runs `admin_init` for logged out requests.
	 *
	 * @since   2.2.1
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testAuthorizationCodeNotExchangedWhenUnauthenticated(EndToEndTester $I)
	{
		// Setup Plugin.
		$I->setupConvertKitPlugin($I);

		// Logout.
		$I->logOut();

		// Attempt to exchange an authorization code without being logged in.
		$I->amOnPage('/wp-admin/admin-ajax.php?action=ckwc&page=wc-settings&tab=integration&section=ckwc&code=fakeAuthorizationCode');
		$I->amOnPage('/wp-admin/admin-ajax.php?action=ckwc&page=wc-settings&tab=integration&section=ckwc&code=fakeAuthorizationCode&nonce=invalid');

		// Confirm the authorization code was not exchanged.
		$I->apiCheckAuthorizationCodeNotExchanged($I);

		// Confirm the credentials were not changed.
		$settings = $I->grabOptionFromDatabase('woocommerce_ckwc_settings');
		$I->assertEquals($_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN'], $settings['access_token']);
		$I->assertEquals($_ENV['CONVERTKIT_OAUTH_REFRESH_TOKEN'], $settings['refresh_token']);
	}

	/**
	 * Test that an authorization code is not exchanged for an access token when an
	 * Administrator loads the settings screen without a valid nonce, such as from a
	 * malicious link.
	 *
	 * @since   2.2.1
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testAuthorizationCodeNotExchangedWithoutNonce(EndToEndTester $I)
	{
		// Setup Plugin.
		$I->setupConvertKitPlugin($I);

		// Attempt to exchange an authorization code without a nonce.
		$I->amOnAdminPage('admin.php?page=wc-settings&tab=integration&section=ckwc&code=fakeAuthorizationCode');

		// Attempt to exchange an authorization code with an invalid nonce.
		$I->amOnAdminPage('admin.php?page=wc-settings&tab=integration&section=ckwc&code=fakeAuthorizationCode&nonce=invalid');

		// Confirm the authorization code was not exchanged.
		$I->apiCheckAuthorizationCodeNotExchanged($I);

		// Confirm the credentials were not changed.
		$settings = $I->grabOptionFromDatabase('woocommerce_ckwc_settings');
		$I->assertEquals($_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN'], $settings['access_token']);
		$I->assertEquals($_ENV['CONVERTKIT_OAUTH_REFRESH_TOKEN'], $settings['refresh_token']);
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
