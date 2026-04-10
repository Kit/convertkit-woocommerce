<?php

namespace Tests\EndToEnd;

use Tests\Support\EndToEndTester;

/**
 * Tests Plugin uninstallation.
 *
 * @since   2.1.3
 */
class UninstallCest
{
	/**
	 * Test that the Plugin's access and refresh tokens are revoked, and all v4 and v3
	 * API credentials are removed from the Plugin's settings when the Plugin is deleted.
	 *
	 * @since   2.1.3
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testPluginDeletionRevokesAndRemovesTokens(EndToEndTester $I)
	{
		// Activate this Plugin.
		$I->activateConvertKitPlugin($I);

		// Generate an access token and refresh token by API key and secret.
		// We don't use the tokens from the environment, as revoking those
		// would result in later tests failing.
		$result = wp_remote_post(
			'https://api.kit.com/wordpress/accounts/oauth_access_token',
			[
				'headers' => [
					'Content-Type' => 'application/json',
				],
				'body'    => wp_json_encode(
					[
						'api_key'     => $_ENV['CONVERTKIT_API_KEY'],
						'api_secret'  => $_ENV['CONVERTKIT_API_SECRET'],
						'client_id'   => $_ENV['CONVERTKIT_OAUTH_CLIENT_ID'],
						'tenant_name' => wp_generate_password( 10, false ), // Random tenant name to produce a token for this request only.
					]
				),
			]
		);
		$tokens = json_decode(wp_remote_retrieve_body($result), true)['oauth'];

		// Store the tokens and API keys in the Plugin's settings.
		$I->setupConvertKitPlugin(
			$I,
			accessToken: $tokens['access_token'],
			refreshToken: $tokens['refresh_token'],
			apiKey: $_ENV['CONVERTKIT_API_KEY'],
			apiSecret: $_ENV['CONVERTKIT_API_SECRET']
		);

		// Deactivate the Plugin.
		$I->deactivateConvertKitPlugin($I);

		// Delete the Plugin.
		$I->deleteKitPlugin($I);

		// Confirm the credentials have been removed from the Plugin's settings.
		$I->wait(3);
		$settings = $I->grabOptionFromDatabase('woocommerce_ckwc_settings');
		$I->assertEmpty($settings['access_token']);
		$I->assertEmpty($settings['refresh_token']);
		$I->assertEmpty($settings['api_key']);
		$I->assertEmpty($settings['api_secret']);

		// Confirm attempting to use the revoked access token no longer works.
		$result = wp_remote_get(
			'https://api.kit.com/v4/account',
			[
				'headers' => [
					'Authorization' => 'Bearer ' . $tokens['access_token'],
				],
			]
		);
		$data   = json_decode(wp_remote_retrieve_body($result), true);
		$I->assertArrayHasKey( 'errors', $data );
		$I->assertEquals( 'The access token was revoked', $data['errors'][0] );

		// Confirm attempting to use the revoked refresh token no longer works.
		$result = wp_remote_post(
			'https://api.kit.com/v4/oauth/token',
			[
				'headers' => [
					'Authorization' => 'Bearer ' . $tokens['access_token'],
				],
				'body'    => [
					'client_id'     => $_ENV['CONVERTKIT_OAUTH_CLIENT_ID'],
					'grant_type'    => 'refresh_token',
					'refresh_token' => $tokens['refresh_token'],
				],
			]
		);
		$data   = json_decode(wp_remote_retrieve_body($result), true);
		$I->assertArrayHasKey( 'error', $data );
		$I->assertEquals( 'invalid_grant', $data['error'] );
	}
}
