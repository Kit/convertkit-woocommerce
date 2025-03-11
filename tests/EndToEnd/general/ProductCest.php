<?php

namespace Tests\EndToEnd;

use Tests\Support\EndToEndTester;

/**
 * Tests that the ConvertKit Form / Tag / Sequence selection works on
 * a WooCommerce Product.
 *
 * @since   1.4.2
 */
class ProductCest
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
		// Activate Plugin.
		$I->activateWooCommerceAndConvertKitPlugins($I);

		// Setup WooCommerce Plugin.
		$I->setupWooCommercePlugin($I);
	}

	/**
	 * Test that the meta box displayed when adding/editing a Product does not
	 * output a field, and instead tells the user to configure the integration,
	 * when the integration is disabled.
	 *
	 * @since   1.4.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testProductFieldsWithIntegrationDisabled(EndToEndTester $I)
	{
		// Navigate to Products > Add New.
		$I->amOnAdminPage('post-new.php?post_type=product');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Check that the ConvertKit meta box exists.
		$I->seeElementInDOM('#ckwc');

		// Check that the dropdown field to select a Form, Tag or Sequence is not displayed.
		$I->dontSeeElementInDOM('#ckwc_subscription');

		// Check that a message is displayed telling the user to enable the integration.
		$I->seeInSource('To configure the Kit Form, Tag or Sequence to subscribe Customers to who use this Product');

		// Check that a link to the Plugin Settings exists.
		$I->seeInSource('<a href="' . $_ENV['TEST_SITE_WP_URL'] . '/wp-admin/admin.php?page=wc-settings&amp;tab=integration&amp;section=ckwc">enable the Kit WooCommerce integration</a>');
	}

	/**
	 * Test that the meta box displayed when adding/editing a Product outputs
	 * a <select> field for choosing a Form, Tag or Sequence.
	 *
	 * @since   1.4.3
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testProductFieldsWithIntegrationEnabled(EndToEndTester $I)
	{
		// Enable Integration and define its Access and Refresh Tokens.
		$I->setupConvertKitPlugin($I);

		// Populate resoruces.
		$I->setupConvertKitPluginResources($I);

		// Navigate to Products > Add New.
		$I->amOnAdminPage('post-new.php?post_type=product');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm CSS and JS is output by the Plugin.
		$I->seeCSSEnqueued($I, 'convertkit-woocommerce/resources/backend/css/select2.css', 'ckwc-admin-select2-css' );
		$I->seeJSEnqueued($I, 'convertkit-woocommerce/resources/backend/js/select2.js' );

		// Check that the ConvertKit meta box exists.
		$I->seeElementInDOM('#ckwc');

		// Check that the dropdown field to select a Form, Tag or Sequence is displayed.
		$I->seeElementInDOM('#ckwc_subscription');

		// Check the order of the resource dropdown are alphabetical.
		$I->checkSelectWithOptionGroupsOptionOrder($I, '#ckwc_subscription');

		// Select Form.
		$I->fillSelect2Field($I, '#select2-ckwc_subscription-container', $_ENV['CONVERTKIT_API_FORM_NAME']);

		// Define Product Title, otherwise WooCommerce won't save.
		$I->fillField('post_title', 'Product Field Test');

		// Save Product.
		$I->click('Publish');

		// Confirm settings saved.
		$I->seeOptionIsSelected('#ckwc_subscription', $_ENV['CONVERTKIT_API_FORM_NAME']);
	}

	/**
	 * Test that the meta box displayed when adding/editing a Product does not
	 * output a field, and instead tells the user to configure the integration,
	 * when the integration is enabled but no credentials are specified.
	 *
	 * @since   1.4.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testProductFieldsWithIntegrationEnabledAndNoCredentials(EndToEndTester $I)
	{
		// Enable Integration and define no Access or Refresh Tokens.
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

		// Navigate to Products > Add New.
		$I->amOnAdminPage('post-new.php?post_type=product');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Check that the ConvertKit meta box exists.
		$I->seeElementInDOM('#ckwc');

		// Check that the dropdown field to select a Form, Tag or Sequence is not displayed.
		$I->dontSeeElementInDOM('#ckwc_subscription');

		// Check that a message is displayed telling the user to enable the integration.
		$I->seeInSource('To configure the Kit Form, Tag or Sequence to subscribe Customers to who use this Product');

		// Check that a link to the Plugin Settings exists.
		$I->seeInSource('<a href="' . $_ENV['TEST_SITE_WP_URL'] . '/wp-admin/admin.php?page=wc-settings&amp;tab=integration&amp;section=ckwc">enable the Kit WooCommerce integration</a>');
	}

	/**
	 * Test that the meta box displayed when adding/editing a Product does not
	 * output PHP errors when the integration is enabled with an invalid credentials.
	 *
	 * @since   1.4.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testProductFieldsWithIntegrationEnabledAndInvalidCredentials(EndToEndTester $I)
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

		// Navigate to Products > Add New.
		$I->amOnAdminPage('post-new.php?post_type=product');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Check that the ConvertKit meta box exists.
		$I->seeElementInDOM('#ckwc');

		// Check that the dropdown field to select a Form, Tag or Sequence is displayed.
		$I->seeElementInDOM('#ckwc_subscription');
	}

	/**
	 * Test that the defined resource (Form, Tag, Sequence) is saved when chosen via
	 * WordPress' Quick Edit functionality.
	 *
	 * @since   1.4.8
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testQuickEditUsingDefinedResource(EndToEndTester $I)
	{
		// Enable Integration and define its Access and Refresh Tokens.
		$I->setupConvertKitPlugin($I);

		// Populate resoruces.
		$I->setupConvertKitPluginResources($I);

		// Programmatically create a Product.
		$productID = $I->havePostInDatabase(
			[
				'post_type'  => 'product',
				'post_title' => 'ConvertKit: Product: Resource: ' . $_ENV['CONVERTKIT_API_FORM_NAME'] . ': Quick Edit',
			]
		);

		// Quick Edit the Product in the Products WP_List_Table.
		$I->quickEdit(
			$I,
			'product',
			$productID,
			[
				'ckwc_subscription' => [ 'select', $_ENV['CONVERTKIT_API_FORM_NAME'] ],
			]
		);

		// Reload the Products admin screen.
		$I->amOnAdminPage('edit.php?post_type=product');

		// Confirm CSS and JS is output by the Plugin.
		$I->seeCSSEnqueued($I, 'convertkit-woocommerce/resources/backend/css/bulk-quick-edit.css', 'ckwc-bulk-quick-edit-css' );
		$I->seeJSEnqueued($I, 'convertkit-woocommerce/resources/backend/js/quick-edit.js' );

		// Confirm that the chosen Resource is the selected option.
		$I->seePostMetaInDatabase(
			[
				'post_id'    => $productID,
				'meta_key'   => 'ckwc_subscription',
				'meta_value' => 'form:' . $_ENV['CONVERTKIT_API_FORM_ID'],
			]
		);
	}

	/**
	 * Test that the no Bulk Edit fields are displayed when the integration is not setup.
	 *
	 * @since   1.4.8
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testBulkEditWithIntegrationDisabled(EndToEndTester $I)
	{
		// Programmatically create two Products.
		$productIDs = array(
			$I->havePostInDatabase(
				[
					'post_type'  => 'product',
					'post_title' => 'ConvertKit: Product: Form: ' . $_ENV['CONVERTKIT_API_FORM_NAME'] . ': Bulk Edit #1',
				]
			),
			$I->havePostInDatabase(
				[
					'post_type'  => 'product',
					'post_title' => 'ConvertKit: Product: Form: ' . $_ENV['CONVERTKIT_API_FORM_NAME'] . ': Bulk Edit #2',
				]
			),
		);

		// Open Bulk Edit.
		$I->openBulkEdit($I, 'product', $productIDs);

		// Confirm the Bulk Edit field isn't displayed.
		$I->dontSeeElementInDOM('#ckwc-bulk-edit #ckwc_subscription');
	}

	/**
	 * Test that the defined resource (Form, Tag, Sequence) is saved when chosen via
	 * WordPress' Bulk Edit functionality.
	 *
	 * @since   1.4.8
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testBulkEditUsingDefinedResource(EndToEndTester $I)
	{
		// Enable Integration and define its Access and Refresh Tokens.
		$I->setupConvertKitPlugin($I);

		// Populate resoruces.
		$I->setupConvertKitPluginResources($I);

		// Programmatically create two PRoducts.
		$productIDs = array(
			$I->havePostInDatabase(
				[
					'post_type'  => 'product',
					'post_title' => 'ConvertKit: Product: Form: ' . $_ENV['CONVERTKIT_API_FORM_NAME'] . ': Bulk Edit #1',
				]
			),
			$I->havePostInDatabase(
				[
					'post_type'  => 'product',
					'post_title' => 'ConvertKit: Product: Form: ' . $_ENV['CONVERTKIT_API_FORM_NAME'] . ': Bulk Edit #2',
				]
			),
		);

		// Bulk Edit the Products in the Pages WP_List_Table.
		$I->bulkEdit(
			$I,
			'product',
			$productIDs,
			[
				'ckwc_subscription' => [ 'select', $_ENV['CONVERTKIT_API_FORM_NAME'] ],
			]
		);

		// Reload the Products admin screen.
		$I->amOnAdminPage('edit.php?post_type=product');

		// Confirm CSS and JS is output by the Plugin.
		$I->seeCSSEnqueued($I, 'convertkit-woocommerce/resources/backend/css/bulk-quick-edit.css', 'ckwc-bulk-quick-edit-css' );
		$I->seeJSEnqueued($I, 'convertkit-woocommerce/resources/backend/js/bulk-edit.js' );

		// Iterate through Products to observe expected changes were made to the settings in the database.
		foreach ($productIDs as $productID) {
			$I->seePostMetaInDatabase(
				[
					'post_id'    => $productID,
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
	 * @since   1.4.8
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testBulkEditWithNoChanges(EndToEndTester $I)
	{
		// Enable Integration and define its Access and Refresh Tokens.
		$I->setupConvertKitPlugin($I);

		// Populate resoruces.
		$I->setupConvertKitPluginResources($I);

		// Programmatically create two Products with a defined form.
		$productIDs = array(
			$I->havePostInDatabase(
				[
					'post_type'  => 'product',
					'post_title' => 'ConvertKit: Product: Form: ' . $_ENV['CONVERTKIT_API_FORM_NAME'] . ': Bulk Edit with No Change #1',
					'meta_input' => [
						'ckwc_subscription' => 'form:' . $_ENV['CONVERTKIT_API_FORM_ID'],
					],
				]
			),
			$I->havePostInDatabase(
				[
					'post_type'  => 'product',
					'post_title' => 'ConvertKit: Page: Form: ' . $_ENV['CONVERTKIT_API_FORM_NAME'] . ': Bulk Edit with No Change #2',
					'meta_input' => [
						'ckwc_subscription' => 'form:' . $_ENV['CONVERTKIT_API_FORM_ID'],
					],
				]
			),
		);

		// Bulk Edit the Products in the Products WP_List_Table.
		$I->bulkEdit(
			$I,
			'product',
			$productIDs,
			[
				'ckwc_subscription' => [ 'select', '— No Change —' ],
			]
		);

		// Iterate through Products to observe no changes were made to the settings in the database.
		foreach ($productIDs as $productID) {
			$I->seePostMetaInDatabase(
				[
					'post_id'    => $productID,
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
	 * @since   1.4.8
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testBulkEditFieldsHiddenWhenNoProductsFound(EndToEndTester $I)
	{
		// Enable Integration and define its Access and Refresh Tokens.
		$I->setupConvertKitPlugin($I);

		// Populate resoruces.
		$I->setupConvertKitPluginResources($I);

		// Emulate the user searching for Products with a query string that yields no results.
		$I->amOnAdminPage('edit.php?post_type=product&s=nothing');

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
