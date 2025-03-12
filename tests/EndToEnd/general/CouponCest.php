<?php

namespace Tests\EndToEnd;

use Tests\Support\EndToEndTester;

/**
 * Tests that the ConvertKit Form / Tag / Sequence selection works on
 * a WooCommerce Coupon.
 *
 * @since   1.5.9
 */
class CouponCest
{
	/**
	 * Run common actions before running the test functions in this class.
	 *
	 * @since   1.5.9
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function _before(EndToEndTester $I)
	{
		// Activate Plugin.
		$I->activateWooCommerceAndConvertKitPlugins($I);

		// Setup WooCommerce Plugin.
		$I->setupWooCommercePlugin($I);
	}

	/**
	 * Test that the meta box displayed when adding/editing a Coupon does not
	 * output a field, and instead tells the user to configure the integration,
	 * when the integration is disabled.
	 *
	 * @since   1.5.9
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testCouponFieldsWithIntegrationDisabled(EndToEndTester $I)
	{
		// Navigate to Marketing > Coupons > Add New.
		$I->amOnAdminPage('post-new.php?post_type=shop_coupon');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Check that the ConvertKit meta box exists.
		$I->seeElementInDOM('#ckwc');

		// Check that the dropdown field to select a Form, Tag or Sequence is not displayed.
		$I->dontSeeElementInDOM('#ckwc_subscription');

		// Check that a message is displayed telling the user to enable the integration.
		$I->seeInSource('To configure the Kit Form, Tag or Sequence to subscribe Customers to who use this coupon');

		// Check that a link to the Plugin Settings exists.
		$I->seeInSource('<a href="' . $_ENV['WORDPRESS_URL'] . '/wp-admin/admin.php?page=wc-settings&amp;tab=integration&amp;section=ckwc">enable the Kit WooCommerce integration</a>');
	}

	/**
	 * Test that the meta box displayed when adding/editing a Coupon outputs
	 * a <select> field for choosing a Form, Tag or Sequence.
	 *
	 * @since   1.4.3
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testCouponFieldsWithIntegrationEnabled(EndToEndTester $I)
	{
		// Enable Integration and define its Access and Refresh Tokens.
		$I->setupConvertKitPlugin($I);

		// Populate resoruces.
		$I->setupConvertKitPluginResources($I);

		// Navigate to Marketing > Coupons > Add New.
		$I->amOnAdminPage('post-new.php?post_type=shop_coupon');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Check that the ConvertKit meta box exists.
		$I->seeElementInDOM('#ckwc');

		// Check that the dropdown field to select a Form, Tag or Sequence is displayed.
		$I->seeElementInDOM('#ckwc_subscription');

		// Check the order of the resource dropdown are alphabetical.
		$I->checkSelectWithOptionGroupsOptionOrder($I, '#ckwc_subscription');

		// Select Form.
		$I->fillSelect2Field($I, '#select2-ckwc_subscription-container', $_ENV['CONVERTKIT_API_FORM_NAME']);

		// Define Coupon Title, otherwise WooCommerce won't save.
		$I->fillField('post_title', 'Coupon Field Test');

		// Save Coupon.
		$I->click('Publish');

		// Confirm settings saved.
		$I->seeOptionIsSelected('#ckwc_subscription', $_ENV['CONVERTKIT_API_FORM_NAME']);
	}

	/**
	 * Test that the meta box displayed when adding/editing a Coupon does not
	 * output a field, and instead tells the user to configure the integration,
	 * when the integration is enabled but no credentials are specified.
	 *
	 * @since   1.5.9
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testCouponFieldsWithIntegrationEnabledAndNoCredentials(EndToEndTester $I)
	{
		// Enable Integration and define no Access and Refresh Tokens.
		$I->setupConvertKitPlugin(
			$I,
			'', // No Access Token.
			'', // No Refresh Token.
			false, // Don't define a subscribe event.
			false, // Don't subscribe the customer to anything.
			'first', // Name format.
			false, // Don't map custom fields.
			false, // Don't display an opt in.
			'processing' // Send purchase data on 'processing' event.
		);

		// Navigate to Marketing > Coupons > Add New.
		$I->amOnAdminPage('post-new.php?post_type=shop_coupon');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Check that the ConvertKit meta box exists.
		$I->seeElementInDOM('#ckwc');

		// Check that the dropdown field to select a Form, Tag or Sequence is not displayed.
		$I->dontSeeElementInDOM('#ckwc_subscription');

		// Check that a message is displayed telling the user to enable the integration.
		$I->seeInSource('To configure the Kit Form, Tag or Sequence to subscribe Customers to who use this coupon');

		// Check that a link to the Plugin Settings exists.
		$I->seeInSource('<a href="' . $_ENV['WORDPRESS_URL'] . '/wp-admin/admin.php?page=wc-settings&amp;tab=integration&amp;section=ckwc">enable the Kit WooCommerce integration</a>');
	}

	/**
	 * Test that the meta box displayed when adding/editing a Coupon does not
	 * output PHP errors when the integration is enabled with invalid credentials
	 *
	 * @since   1.5.9
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testCouponFieldsWithIntegrationEnabledAndInvalidCredentials(EndToEndTester $I)
	{
		// Enable Integration and define invalid Access and Refresh Tokens.
		$I->setupConvertKitPlugin(
			$I,
			'fakeAccessToken',
			'fakeRefreshToken',
			false, // Don't define a subscribe event.
			false, // Don't subscribe the customer to anything.
			'first', // Name format.
			false, // Don't map custom fields.
			false, // Don't display an opt in.
			'processing' // Send purchase data on 'processing' event.
		);

		// Navigate to Marketing > Coupons > Add New.
		$I->amOnAdminPage('post-new.php?post_type=shop_coupon');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Check that the ConvertKit meta box exists.
		$I->seeElementInDOM('#ckwc');

		// Check that the dropdown field to select a Form, Tag or Sequence is displayed.
		$I->seeElementInDOM('#ckwc_subscription');
	}

	/**
	 * Test that the no Bulk Edit fields are displayed when the integration is not setup.
	 *
	 * @since   1.5.9
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testBulkEditWithIntegrationDisabled(EndToEndTester $I)
	{
		// Programmatically create two Coupons.
		$couponIDs = array(
			$I->havePostInDatabase(
				[
					'post_type'  => 'shop_coupon',
					'post_title' => 'ConvertKit: Coupon: Form: ' . $_ENV['CONVERTKIT_API_FORM_NAME'] . ': Bulk Edit #1',
				]
			),
			$I->havePostInDatabase(
				[
					'post_type'  => 'shop_coupon',
					'post_title' => 'ConvertKit: Coupon: Form: ' . $_ENV['CONVERTKIT_API_FORM_NAME'] . ': Bulk Edit #2',
				]
			),
		);

		// Open Bulk Edit.
		$I->openBulkEdit($I, 'shop_coupon', $couponIDs);

		// Confirm the Bulk Edit field isn't displayed.
		$I->dontSeeElementInDOM('#ckwc-bulk-edit #ckwc_subscription');
	}

	/**
	 * Test that the defined form displays when chosen via
	 * WordPress' Bulk Edit functionality.
	 *
	 * @since   1.5.9
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testBulkEditUsingDefinedForm(EndToEndTester $I)
	{
		// Enable Integration and define its Access and Refresh Tokens.
		$I->setupConvertKitPlugin($I);

		// Populate resoruces.
		$I->setupConvertKitPluginResources($I);

		// Programmatically create two Coupons.
		$couponIDs = array(
			$I->havePostInDatabase(
				[
					'post_type'  => 'shop_coupon',
					'post_title' => 'ConvertKit: Coupon: Form: ' . $_ENV['CONVERTKIT_API_FORM_NAME'] . ': Bulk Edit #1',
				]
			),
			$I->havePostInDatabase(
				[
					'post_type'  => 'shop_coupon',
					'post_title' => 'ConvertKit: Coupon: Form: ' . $_ENV['CONVERTKIT_API_FORM_NAME'] . ': Bulk Edit #2',
				]
			),
		);

		// Bulk Edit the Coupons in the Pages WP_List_Table.
		$I->bulkEdit(
			$I,
			'shop_coupon',
			$couponIDs,
			[
				'ckwc_subscription' => [ 'select', $_ENV['CONVERTKIT_API_FORM_NAME'] ],
			],
			'coupon'
		);

		// Iterate through Coupons to observe expected changes were made to the settings in the database.
		foreach ($couponIDs as $couponID) {
			$I->seePostMetaInDatabase(
				[
					'post_id'    => $couponID,
					'meta_key'   => 'ckwc_subscription',
					'meta_value' => 'form:' . $_ENV['CONVERTKIT_API_FORM_ID'],
				]
			);
		}
	}

	/**
	 * Test that the existing settings are honored and not changed
	 * when the Bulk Edit options are set to 'No Change'.
	 *
	 * @since   1.5.9
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testBulkEditWithNoChanges(EndToEndTester $I)
	{
		// Enable Integration and define its Access and Refresh Tokens.
		$I->setupConvertKitPlugin($I);

		// Populate resoruces.
		$I->setupConvertKitPluginResources($I);

		// Programmatically create two Coupons with a defined form.
		$couponIDs = array(
			$I->havePostInDatabase(
				[
					'post_type'  => 'shop_coupon',
					'post_title' => 'ConvertKit: Coupon: Form: ' . $_ENV['CONVERTKIT_API_FORM_NAME'] . ': Bulk Edit with No Change #1',
					'meta_input' => [
						'ckwc_subscription' => 'form:' . $_ENV['CONVERTKIT_API_FORM_ID'],
					],
				]
			),
			$I->havePostInDatabase(
				[
					'post_type'  => 'shop_coupon',
					'post_title' => 'ConvertKit: Coupon: Form: ' . $_ENV['CONVERTKIT_API_FORM_NAME'] . ': Bulk Edit with No Change #2',
					'meta_input' => [
						'ckwc_subscription' => 'form:' . $_ENV['CONVERTKIT_API_FORM_ID'],
					],
				]
			),
		);

		// Bulk Edit the Coupons in the Coupons WP_List_Table.
		$I->bulkEdit(
			$I,
			'shop_coupon',
			$couponIDs,
			[
				'ckwc_subscription' => [ 'select', '— No Change —' ],
			],
			'coupon'
		);

		// Iterate through Coupons to observe no changes were made to the settings in the database.
		foreach ($couponIDs as $couponID) {
			$I->seePostMetaInDatabase(
				[
					'post_id'    => $couponID,
					'meta_key'   => 'ckwc_subscription',
					'meta_value' => 'form:' . $_ENV['CONVERTKIT_API_FORM_ID'],
				]
			);
		}
	}

	/**
	 * Test that the Bulk Edit fields do not display when a search on a WP_List_Table
	 * returns no results.
	 *
	 * @since   1.5.9
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testBulkEditFieldsHiddenWhenNoCouponsFound(EndToEndTester $I)
	{
		// Enable Integration and define its Access and Refresh Tokens.
		$I->setupConvertKitPlugin($I);

		// Populate resoruces.
		$I->setupConvertKitPluginResources($I);

		// Emulate the user searching for Coupons with a query string that yields no results.
		$I->amOnAdminPage('edit.php?post_type=shop_coupon&s=nothing');

		// Confirm that the Bulk Edit fields do not display.
		$I->dontSeeElement('#ckwc-bulk-edit');
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
