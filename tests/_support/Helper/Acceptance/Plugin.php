<?php
namespace Helper\Acceptance;

/**
 * Helper methods and actions related to the ConvertKit Plugin,
 * which are then available using $I->{yourFunctionName}.
 *
 * @since   1.9.6
 */
class Plugin extends \Codeception\Module
{
	/**
	 * Helper method to activate the ConvertKit Plugin, checking
	 * it activated and no errors were output.
	 *
	 * @since   1.9.6
	 *
	 * @param   AcceptanceTester $I Acceptance Tester.
	 */
	public function activateConvertKitPlugin($I)
	{
		$I->activateThirdPartyPlugin($I, 'convertkit-for-woocommerce');
	}

	/**
	 * Helper method to deactivate the ConvertKit Plugin, checking
	 * it activated and no errors were output.
	 *
	 * @since   1.9.6
	 *
	 * @param   AcceptanceTester $I Acceptance Tester.
	 */
	public function deactivateConvertKitPlugin($I)
	{
		$I->deactivateThirdPartyPlugin($I, 'convertkit-for-woocommerce');
	}

	/**
	 * Helper method to activate the following Plugins:
	 * - WooCommerce
	 * - WooCommerce Stripe Gateway
	 * - ConvertKit for WooCommerce
	 *
	 * @since   1.0.0
	 *
	 * @param   AcceptanceTester $I Acceptance Tester.
	 */
	public function activateWooCommerceAndConvertKitPlugins($I)
	{
		// Activate ConvertKit Plugin.
		$I->activateConvertKitPlugin($I);

		// Activate WooCommerce Plugin.
		$I->activateThirdPartyPlugin($I, 'woocommerce');

		// Activate WooCommerce Stripe Gateway Plugin.
		$I->activateThirdPartyPlugin($I, 'woocommerce-gateway-stripe');

		// Flush Permalinks by visiting Settings > Permalinks, so that newly registered Post Types e.g.
		// WooCommerce Products work.
		$I->amOnAdminPage('options-permalink.php');
	}

	/**
	 * Helper method to deactivate the following Plugins:
	 * - WooCommerce
	 * - WooCommerce Stripe Gateway
	 * - ConvertKit for WooCommerce
	 *
	 * @since   1.0.0
	 *
	 * @param   AcceptanceTester $I Acceptance Tester.
	 */
	public function deactivateWooCommerceAndConvertKitPlugins($I)
	{
		$I->deactivateThirdPartyPlugin($I, 'convertkit-for-woocommerce');

		// Deactivate WooCommerce Stripe Gateway before WooCommerce, to prevent WooCommerce throwing a fatal error.
		$I->deactivateThirdPartyPlugin($I, 'woocommerce-gateway-stripe');
		$I->deactivateThirdPartyPlugin($I, 'woocommerce');
	}

	/**
	 * Helper method to setup the Plugin's API Key and Secret.
	 *
	 * @since   1.9.6
	 *
	 * @param   AcceptanceTester $I          Acceptance Tester.
	 * @param   mixed            $apiKey     API Key (if specified, used instead of CONVERTKIT_API_KEY).
	 * @param   mixed            $apiSecret  API Secret (if specified, used instead of CONVERTKIT_API_SECRET).
	 */
	public function setupConvertKitPlugin($I, $apiKey = false, $apiSecret = false)
	{
		// Determine API Key and Secret to use.
		$convertKitAPIKey    = ( $apiKey !== false ? $apiKey : $_ENV['CONVERTKIT_API_KEY'] );
		$convertKitAPISecret = ( $apiSecret !== false ? $apiSecret : $_ENV['CONVERTKIT_API_SECRET'] );

		// Go to the Plugin's Settings Screen.
		$I->loadConvertKitSettingsScreen($I);

		// Enable the Integration.
		$I->checkOption('#woocommerce_ckwc_enabled');

		// Complete API Fields.
		$I->fillField('woocommerce_ckwc_api_key', $convertKitAPIKey);
		$I->fillField('woocommerce_ckwc_api_secret', $convertKitAPISecret);

		// Click the Save Changes button.
		$I->click('Save changes');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Check the value of the fields match the inputs provided.
		$I->seeCheckboxIsChecked('#woocommerce_ckwc_enabled');
		$I->seeInField('woocommerce_ckwc_api_key', $convertKitAPIKey);
		$I->seeInField('woocommerce_ckwc_api_secret', $convertKitAPISecret);
	}

	/**
	 * Helper method to reset the ConvertKit Plugin settings, as if it's a clean installation.
	 *
	 * @since   1.9.6.7
	 *
	 * @param   AcceptanceTester $I Acceptance Tester.
	 */
	public function resetConvertKitPlugin($I)
	{
		// Plugin Settings.
		$I->dontHaveOptionInDatabase('woocommerce_ckwc_settings');

		// Resources.
		$I->dontHaveOptionInDatabase('ckwc_custom_fields');
		$I->dontHaveOptionInDatabase('ckwc_forms');
		$I->dontHaveOptionInDatabase('ckwc_sequences');
		$I->dontHaveOptionInDatabase('ckwc_tags');

		// Review Request.
		$I->dontHaveOptionInDatabase('convertkit-for-woocommerce-review-request');
		$I->dontHaveOptionInDatabase('convertkit-for-woocommerce-review-dismissed');
	}

	/**
	 * Helper method to delete option table rows for review requests.
	 * Useful for resetting the review state between tests.
	 *
	 * @since   1.4.3
	 *
	 * @param   AcceptanceTester $I Acceptance Tester.
	 */
	public function deleteConvertKitReviewRequestOptions($I)
	{
		$I->dontHaveOptionInDatabase('convertkit-for-woocommerce-review-request');
		$I->dontHaveOptionInDatabase('convertkit-for-woocommerce-review-dismissed');
	}

	/**
	 * Helper method to load the WooCommerce > Settings > Integration > ConvertKit screen.
	 *
	 * @since   1.0.0
	 *
	 * @param   AcceptanceTester $I Acceptance Tester.
	 */
	public function loadConvertKitSettingsScreen($I)
	{
		$I->amOnAdminPage('admin.php?page=wc-settings&tab=integration&section=ckwc');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);
	}
}
