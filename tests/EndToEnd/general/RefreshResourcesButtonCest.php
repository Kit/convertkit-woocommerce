<?php

namespace Tests\EndToEnd;

use Tests\Support\EndToEndTester;

/**
 * Tests the Refresh Resource button, which are displayed next to settings fields
 * when editing a WooCommerce Product.
 *
 * @since   1.4.8
 */
class RefreshResourcesButtonCest
{
	/**
	 * Run common actions before running the test functions in this class.
	 *
	 * @since   1.4.8
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function _before(EndToEndTester $I)
	{
		// Activate and Setup ConvertKit plugin.
		$I->activateWooCommerceAndConvertKitPlugins($I);
		$I->setupConvertKitPlugin($I);
	}

	/**
	 * Test that the refresh button for works when adding a new Product.
	 *
	 * @since   1.4.8
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testRefreshResourcesOnProduct(EndToEndTester $I)
	{
		// Navigate to Product > Add New.
		$I->amOnAdminPage('post-new.php?post_type=product');

		// Confirm JS is output by the Plugin.
		$I->seeJSEnqueued($I, 'convertkit-woocommerce/resources/backend/js/refresh-resources.js' );

		// Click the refresh button.
		$I->click('button.ckwc-refresh-resources');

		// Wait for button to change its state from disabled.
		$I->waitForElementVisible('button.ckwc-refresh-resources:not(:disabled)');

		// Check the order of the resource dropdown are alphabetical.
		$I->checkSelectWithOptionGroupsOptionOrder($I, '#ckwc_subscription');

		// Change resource to value specified in the .env file, which should now be available.
		// If the expected dropdown value does not exist in the Select2 field, this will fail the test.
		$I->fillSelect2Field($I, '#select2-ckwc_subscription-container', $_ENV['CONVERTKIT_API_FORM_NAME']);
	}

	/**
	 * Test that the refresh buttons for Forms, Sequences and Tags works when Bulk Editing WooCommerce Products.
	 *
	 * @since   1.4.8
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testRefreshResourcesOnBulkEdit(EndToEndTester $I)
	{
		// Programmatically create two Pages.
		$productIDs = array(
			$I->havePostInDatabase(
				[
					'post_type'  => 'product',
					'post_title' => 'ConvertKit: Product: Refresh Resources: Bulk Edit #1',
				]
			),
			$I->havePostInDatabase(
				[
					'post_type'  => 'product',
					'post_title' => 'ConvertKit: Product: Refresh Resources: Bulk Edit #2',
				]
			),
		);

		// Open Bulk Edit form for the Products in the WooCommerce Products WP_List_Table.
		$I->openBulkEdit($I, 'product', $productIDs);

		// Confirm JS is output by the Plugin.
		$I->seeJSEnqueued($I, 'convertkit-woocommerce/resources/backend/js/refresh-resources.js' );

		// Click the refresh button.
		$I->wait(2);
		$I->scrollTo('select[name="comment_status"]');
		$I->click('#ckwc-bulk-edit button.ckwc-refresh-resources');

		// Wait for button to change its state from disabled.
		$I->waitForElementVisible('#ckwc-bulk-edit button.ckwc-refresh-resources:not(:disabled)');

		// Check the order of the resource dropdown are alphabetical.
		$I->checkSelectWithOptionGroupsOptionOrder($I, '#ckwc_subscription');

		// Change resource to value specified in the .env file, which should now be available.
		// If the expected dropdown value does not exist, this will fail the test.
		$I->selectOption('#ckwc_subscription', $_ENV['CONVERTKIT_API_FORM_NAME']);
	}

	/**
	 * Test that the refresh buttons for Forms, Sequences and Tags works when Quick Editing a WooCommerce Product.
	 *
	 * @since   1.4.8
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testRefreshResourcesOnQuickEdit(EndToEndTester $I)
	{
		// Programmatically create a Product.
		$pageID = $I->havePostInDatabase(
			[
				'post_type'  => 'product',
				'post_title' => 'ConvertKit: Product: Refresh Resources: Quick Edit',
			]
		);

		// Open Quick Edit form for the Product in the WooCommerce Products WP_List_Table.
		$I->openQuickEdit($I, 'product', $pageID);

		// Confirm JS is output by the Plugin.
		$I->seeJSEnqueued($I, 'convertkit-woocommerce/resources/backend/js/refresh-resources.js' );

		// Click the refresh button.
		$I->waitForElementVisible('#ckwc-quick-edit button.ckwc-refresh-resources');
		$I->click('#ckwc-quick-edit button.ckwc-refresh-resources');

		// Wait for button to change its state from disabled.
		$I->waitForElementVisible('#ckwc-quick-edit button.ckwc-refresh-resources:not(:disabled)');

		// Check the order of the resource dropdown are alphabetical.
		$I->checkSelectWithOptionGroupsOptionOrder($I, '#ckwc_subscription');

		// Change resource to value specified in the .env file, which should now be available.
		// If the expected dropdown value does not exist, this will fail the test.
		$I->selectOption('#ckwc_subscription', $_ENV['CONVERTKIT_API_FORM_NAME']);
	}

	/**
	 * Test that the refresh button triggers an error message when the AJAX request fails,
	 * or the ConvertKit API returns an error.
	 *
	 * @since   1.4.9
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testRefreshResourcesErrorNotice(EndToEndTester $I)
	{
		// Specify invalid API credentials, so that the AJAX request returns an error.
		$I->haveOptionInDatabase(
			'woocommerce_ckwc_settings',
			[
				'enabled'       => 'yes',
				'access_token'  => 'fakeAccessToken',
				'refresh_token' => 'fakeRefreshToken',
				'debug'         => 'yes',
			]
		);

		// Programmatically create a Product.
		$pageID = $I->havePostInDatabase(
			[
				'post_type'  => 'product',
				'post_title' => 'ConvertKit: Product: Refresh Resources: Quick Edit',
			]
		);

		// Open Quick Edit form for the Product in the WooCommerce Products WP_List_Table.
		$I->openQuickEdit($I, 'product', $pageID);

		// Click the refresh button.
		$I->click('#ckwc-quick-edit button.ckwc-refresh-resources');

		// Wait for button to change its state from disabled.
		$I->waitForElementVisible('#ckwc-quick-edit button.ckwc-refresh-resources:not(:disabled)');

		// Confirm that an error notification is displayed on screen, with the expected error message.
		$I->seeElementInDOM('div.ckwc-error');
		$I->see('The access token is invalid');

		// Confirm that the notice is dismissible.
		$I->click('div.ckwc-error button.notice-dismiss');
		$I->wait(1);
		$I->dontSeeElementInDOM('div.ckwc-error');
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
