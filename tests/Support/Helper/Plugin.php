<?php
namespace Tests\Support\Helper;

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
	 * @param   EndToEndTester $I Acceptance Tester.
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
	 * @param   EndToEndTester $I Acceptance Tester.
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
	 * @param   EndToEndTester $I Acceptance Tester.
	 */
	public function activateWooCommerceAndConvertKitPlugins($I)
	{
		// Activate Disable _load_textdomain notice.
		$I->activateThirdPartyPlugin($I, 'disable-_load_textdomain_just_in_time-doing_it_wrong-notice');

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
	 * @param   EndToEndTester $I Acceptance Tester.
	 */
	public function deactivateWooCommerceAndConvertKitPlugins($I)
	{
		$I->deactivateThirdPartyPlugin($I, 'convertkit-for-woocommerce');

		// Deactivate WooCommerce Stripe Gateway before WooCommerce, to prevent WooCommerce throwing a fatal error.
		$I->deactivateThirdPartyPlugin($I, 'woocommerce-gateway-stripe');
		$I->deactivateThirdPartyPlugin($I, 'woocommerce');

		$I->deactivateThirdPartyPlugin($I, 'disable-_load_textdomain_just_in_time-doing_it_wrong-notice');
	}

	/**
	 * Helper method to setup the Plugin.
	 *
	 * @since   1.6.0
	 *
	 * @param   EndToEndTester $I                      Acceptance Tester.
	 * @param   bool|string    $accessToken            Access Token (if specified, used instead of CONVERTKIT_OAUTH_ACCESS_TOKEN).
	 * @param   bool|string    $refreshToken           Refresh Token (if specified, used instead of CONVERTKIT_OAUTH_REFRESH_TOKEN).
	 * @param   string         $subscriptionEvent      Subscribe Event.
	 * @param   bool|string    $subscription           Form, Tag or Sequence to subscribe customer to.
	 * @param   string         $nameFormat             Name Format.
	 * @param   bool           $mapCustomFields        Map Order data to Custom Fields.
	 * @param   bool           $displayOptIn           Display Opt-In Checkbox.
	 * @param   bool           $sendPurchaseDataEvent  Send Purchase Data to ConvertKit on Order Event.
	 * @param   bool           $excludeNameFromAddress Exclude name from billing and shipping addresses.
	 */
	public function setupConvertKitPlugin(
		$I,
		$accessToken = false,
		$refreshToken = false,
		$subscriptionEvent = 'pending',
		$subscription = false,
		$nameFormat = 'first',
		$mapCustomFields = false,
		$displayOptIn = false,
		$sendPurchaseDataEvent = false,
		$excludeNameFromAddress = false
	) {
		// Define Plugin's settings.
		$I->haveOptionInDatabase(
			'woocommerce_ckwc_settings',
			[
				'enabled'                           => 'yes',
				'access_token'                      => ( $accessToken !== false ? $accessToken : $_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN'] ),
				'refresh_token'                     => ( $refreshToken !== false ? $refreshToken : $_ENV['CONVERTKIT_OAUTH_REFRESH_TOKEN'] ),
				'event'                             => $subscriptionEvent,
				'subscription'                      => ( $subscription ? $subscription : '' ),
				'name_format'                       => $nameFormat,

				// Custom Field mappings.
				'custom_field_last_name'            => ( $mapCustomFields ? 'last_name' : '' ),
				'custom_field_phone'                => ( $mapCustomFields ? 'phone_number' : '' ),
				'custom_field_billing_address'      => ( $mapCustomFields ? 'billing_address' : '' ),
				'custom_field_shipping_address'     => ( $mapCustomFields ? 'shipping_address' : '' ),
				'custom_field_payment_method'       => ( $mapCustomFields ? 'payment_method' : '' ),
				'custom_field_customer_note'        => ( $mapCustomFields ? 'notes' : '' ),
				'custom_field_address_exclude_name' => ( $excludeNameFromAddress ? 'yes' : 'no' ),

				// Opt-In Checkbox.
				'display_opt_in'                    => ( $displayOptIn ? 'yes' : 'no' ),
				'opt_in_label'                      => 'I want to subscribe to the newsletter',
				'opt_in_status'                     => 'checked',
				'opt_in_location'                   => 'billing',

				// Purchase Data.
				'send_purchases'                    => ( $sendPurchaseDataEvent ? 'yes' : 'no' ),
				'send_purchases_event'              => ( $sendPurchaseDataEvent ? $sendPurchaseDataEvent : '' ),

				// Debug.
				'debug'                             => 'yes',
			]
		);
	}

	/**
	 * Helper method to define cached Resources (Forms, Sequences and Tags),
	 * directly into the database, instead of querying the API for them via the Resource classes.
	 *
	 * This can safely be done for Acceptance tests, as WPUnit tests ensure that
	 * caching Resources from calls made to the API work and store data in the expected
	 * structure.
	 *
	 * Defining cached Resources here reduces the number of API calls made for each test,
	 * reducing the likelihood of hitting a rate limit due to running tests in parallel.
	 *
	 * Resources are deliberately not in order, to emulate how the data might not always
	 * be in alphabetical / published order from the API.
	 *
	 * @since   1.6.0
	 *
	 * @param   EndToEndTester $I              EndToEndTester.
	 */
	public function setupConvertKitPluginResources($I)
	{
		// Define Custom Fields.
		$I->haveOptionInDatabase(
			'ckwc_custom_fields',
			[
				276271 => [
					'id'    => 276271,
					'name'  => 'ck_field_276271_phone_number',
					'key'   => 'phone_number',
					'label' => 'Phone Number',
				],
				276273 => [
					'id'    => 276273,
					'name'  => 'ck_field_276273_billing_address',
					'key'   => 'billing_address',
					'label' => 'Billing Address',
				],
				276295 => [
					'id'    => 276295,
					'name'  => 'ck_field_276295_payment_method',
					'key'   => 'payment_method',
					'label' => 'Payment Method',
				],
				264073 => [
					'id'    => 264073,
					'name'  => 'ck_field_264073_last_name',
					'key'   => 'last_name',
					'label' => 'Last Name',
				],
				321150 => [
					'id'    => 321150,
					'name'  => 'ck_field_321150_test',
					'key'   => 'test',
					'label' => 'Test',
				],
				276272 => [
					'id'    => 276272,
					'name'  => 'ck_field_276272_shipping_address',
					'key'   => 'shipping_address',
					'label' => 'Shipping Address',
				],
				258240 => [
					'id'    => 258240,
					'name'  => 'ck_field_258240_notes',
					'key'   => 'notes',
					'label' => 'Notes',
				],
			]
		);

		// Define Forms.
		$I->haveOptionInDatabase(
			'ckwc_forms',
			[
				3003590 => [
					'id'         => 3003590,
					'name'       => 'Third Party Integrations Form',
					'created_at' => '2022-02-17T15:05:31.000Z',
					'type'       => 'embed',
					'format'     => 'inline',
					'embed_js'   => 'https://cheerful-architect-3237.ck.page/71cbcc4042/index.js',
					'embed_url'  => 'https://cheerful-architect-3237.ck.page/71cbcc4042',
					'archived'   => false,
					'uid'        => '71cbcc4042',
				],
				2780977 => [
					'id'         => 2780977,
					'name'       => 'Modal Form',
					'created_at' => '2021-11-17T04:22:06.000Z',
					'type'       => 'embed',
					'format'     => 'modal',
					'embed_js'   => 'https://cheerful-architect-3237.ck.page/397e876257/index.js',
					'embed_url'  => 'https://cheerful-architect-3237.ck.page/397e876257',
					'archived'   => false,
					'uid'        => '397e876257',
				],
				2780979 => [
					'id'         => 2780979,
					'name'       => 'Slide In Form',
					'created_at' => '2021-11-17T04:22:24.000Z',
					'type'       => 'embed',
					'format'     => 'slide in',
					'embed_js'   => 'https://cheerful-architect-3237.ck.page/e0d65bed9d/index.js',
					'embed_url'  => 'https://cheerful-architect-3237.ck.page/e0d65bed9d',
					'archived'   => false,
					'uid'        => 'e0d65bed9d',
				],
				2765139 => [
					'id'         => 2765139,
					'name'       => 'Page Form',
					'created_at' => '2021-11-11T15:30:40.000Z',
					'type'       => 'embed',
					'format'     => 'inline',
					'embed_js'   => 'https://cheerful-architect-3237.ck.page/85629c512d/index.js',
					'embed_url'  => 'https://cheerful-architect-3237.ck.page/85629c512d',
					'archived'   => false,
					'uid'        => '85629c512d',
				],
				470099  => [
					'id'                  => 470099,
					'name'                => 'Legacy Form',
					'created_at'          => null,
					'type'                => 'embed',
					'url'                 => 'https://app.kit.com/landing_pages/470099',
					'embed_js'            => 'https://api.kit.com/api/v3/forms/470099.js?api_key=' . $_ENV['CONVERTKIT_API_KEY'],
					'embed_url'           => 'https://api.kit.com/api/v3/forms/470099.html?api_key=' . $_ENV['CONVERTKIT_API_KEY'],
					'title'               => 'Join the newsletter',
					'description'         => '<p>Subscribe to get our latest content by email.</p>',
					'sign_up_button_text' => 'Subscribe',
					'success_message'     => 'Success! Now check your email to confirm your subscription.',
					'archived'            => false,
				],
				2780980 => [
					'id'         => 2780980,
					'name'       => 'Sticky Bar Form',
					'created_at' => '2021-11-17T04:22:42.000Z',
					'type'       => 'embed',
					'format'     => 'sticky bar',
					'embed_js'   => 'https://cheerful-architect-3237.ck.page/9f5c601482/index.js',
					'embed_url'  => 'https://cheerful-architect-3237.ck.page/9f5c601482',
					'archived'   => false,
					'uid'        => '9f5c601482',
				],
				3437554 => [
					'id'         => 3437554,
					'name'       => 'AAA Test',
					'created_at' => '2022-07-15T15:06:32.000Z',
					'type'       => 'embed',
					'format'     => 'inline',
					'embed_js'   => 'https://cheerful-architect-3237.ck.page/3bb15822a2/index.js',
					'embed_url'  => 'https://cheerful-architect-3237.ck.page/3bb15822a2',
					'archived'   => false,
					'uid'        => '3bb15822a2',
				],
				2765149 => [
					'id'         => 2765149,
					'name'       => 'WooCommerce Product Form',
					'created_at' => '2021-11-11T15:32:54.000Z',
					'type'       => 'embed',
					'format'     => 'inline',
					'embed_js'   => 'https://cheerful-architect-3237.ck.page/7e238f3920/index.js',
					'embed_url'  => 'https://cheerful-architect-3237.ck.page/7e238f3920',
					'archived'   => false,
					'uid'        => '7e238f3920',
				],
			]
		);

		// Define Sequences.
		$I->haveOptionInDatabase(
			'ckwc_sequences',
			[
				1030824 => [
					'id'         => 1030824,
					'name'       => 'WordPress Sequence',
					'hold'       => false,
					'repeat'     => false,
					'created_at' => '2022-01-04T13:00:15.000Z',
				],
				1341993 => [
					'id'         => 1341993,
					'name'       => 'Another Sequence',
					'hold'       => false,
					'repeat'     => false,
					'created_at' => '2023-01-30T17:25:54.000Z',
				],
			]
		);

		// Define Tags.
		$I->haveOptionInDatabase(
			'ckwc_tags',
			[
				2744672 => [
					'id'         => 2744672,
					'name'       => 'wordpress',
					'created_at' => '2021-11-11T19:30:06.000Z',
				],
				2907192 => [
					'id'         => 2907192,
					'name'       => 'gravityforms-tag-1',
					'created_at' => '2022-02-02T14:06:32.000Z',
				],
				3748541 => [
					'id'         => 3748541,
					'name'       => 'wpforms',
					'created_at' => '2023-03-29T12:32:38.000Z',
				],
				2907193 => [
					'id'         => 2907193,
					'name'       => 'gravityforms-tag-2',
					'created_at' => '2022-02-02T14:06:38.000Z',
				],
			]
		);

		// Define last queried to now for all resources, so they're not automatically immediately refreshed by the Plugin's logic.
		$I->haveOptionInDatabase( 'ckwc_custom_fields_last_queried', strtotime( 'now' ) );
		$I->haveOptionInDatabase( 'ckwc_forms_last_queried', strtotime( 'now' ) );
		$I->haveOptionInDatabase( 'ckwc_sequences_last_queried', strtotime( 'now' ) );
		$I->haveOptionInDatabase( 'ckwc_tags_last_queried', strtotime( 'now' ) );
	}

	/**
	 * Helper method to define cached Resources (Forms, Landing Pages, Posts, Products and Tags),
	 * directly into the database, instead of querying the API for them via the Resource classes
	 * as if the ConvertKit account is new and has no resources defined in ConvertKit.
	 *
	 * @since   2.0.7
	 *
	 * @param   EndToEndTester $I              EndToEndTester.
	 */
	public function setupConvertKitPluginResourcesNoData($I)
	{
		// Define Custom Fields.
		$I->haveOptionInDatabase(
			'ckwc_custom_fields',
			[]
		);

		// Define Forms.
		$I->haveOptionInDatabase(
			'ckwc_forms',
			[]
		);

		// Define Sequences.
		$I->haveOptionInDatabase(
			'ckwc_sequences',
			[]
		);

		// Define Tags.
		$I->haveOptionInDatabase(
			'ckwc_tags',
			[]
		);

		// Define last queried to now for all resources, so they're not automatically immediately refreshed by the Plugin's logic.
		$I->haveOptionInDatabase( 'ckwc_custom_fields_last_queried', strtotime( 'now' ) );
		$I->haveOptionInDatabase( 'ckwc_forms_last_queried', strtotime( 'now' ) );
		$I->haveOptionInDatabase( 'ckwc_sequences_last_queried', strtotime( 'now' ) );
		$I->haveOptionInDatabase( 'ckwc_tags_last_queried', strtotime( 'now' ) );
	}

	/**
	 * Helper method to reset the ConvertKit Plugin settings, as if it's a clean installation.
	 *
	 * @since   1.9.6.7
	 *
	 * @param   EndToEndTester $I Acceptance Tester.
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
	 * @param   EndToEndTester $I Acceptance Tester.
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
	 * @param   EndToEndTester $I Acceptance Tester.
	 */
	public function loadConvertKitSettingsScreen($I)
	{
		$I->amOnAdminPage('admin.php?page=wc-settings&tab=integration&section=ckwc');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);
	}

	/**
	 * Helper method to determine the order of <option> values for the given select element
	 * and values when <optgroup> is used within a <select>.
	 *
	 * @since   1.5.7
	 *
	 * @param   EndToEndTester $I             EndToEndTester.
	 * @param   string         $selectElement <select> element.
	 */
	public function checkSelectWithOptionGroupsOptionOrder($I, $selectElement)
	{
		// Define options.
		$options = [
			'ckwc-sequences' => [ // <optgroup> ID.
				'Another Sequence', // First item.
				'WordPress Sequence', // Last item.
			],
			'ckwc-forms'     => [ // <optgroup> ID.
				'AAA Test', // First item.
				'WooCommerce Product Form', // Last item.
			],
			'ckwc-tags'      => [ // <optgroup> ID.
				'gravityforms-tag-1', // First item.
				'wpforms', // Last item.
			],
		];

		// Confirm ordering.
		foreach ( $options as $optgroup => $values ) {
			foreach ( $values as $i => $value ) {
				// Define the applicable CSS selector.
				if ( $i === 0 ) {
					$nth = 'first-child';
				} elseif ( $i + 1 === count( $values ) ) {
					$nth = 'last-child';
				} else {
					$nth = 'nth-child(' . ( $i + 1 ) . ')';
				}

				$I->assertEquals(
					$I->grabTextFrom('select' . $selectElement . ' optgroup#' . $optgroup . ' option:' . $nth),
					$value
				);
			}
		}
	}

	/**
	 * Helper method to determine that the order of the Form resources in the given
	 * select element are in the expected alphabetical order.
	 *
	 * @since   1.5.7
	 *
	 * @param   EndToEndTester $I                 EndToEndTester.
	 * @param   string         $selectElement     <select> element.
	 * @param   bool|array     $prependOptions    Option elements that should appear before the resources.
	 */
	public function checkSelectCustomFieldOptionOrder($I, $selectElement, $prependOptions = false)
	{
		// Define options.
		$options = [
			'Billing Address', // First item.
			'Test', // Last item.
		];

		// Prepend options, such as 'Default' and 'None' to the options, if required.
		if ( $prependOptions ) {
			$options = array_merge( $prependOptions, $options );
		}

		// Check order.
		$I->checkSelectOptionOrder($I, $selectElement, $options);
	}

	/**
	 * Helper method to determine the order of <option> values for the given select element
	 * and values.
	 *
	 * @since   1.5.7
	 *
	 * @param   EndToEndTester $I             EndToEndTester.
	 * @param   string         $selectElement <select> element.
	 * @param   array          $values        <option> values.
	 */
	public function checkSelectOptionOrder($I, $selectElement, $values)
	{
		foreach ( $values as $i => $value ) {
			// Define the applicable CSS selector.
			if ( $i === 0 ) {
				$nth = 'first-child';
			} elseif ( $i + 1 === count( $values ) ) {
				$nth = 'last-child';
			} else {
				$nth = 'nth-child(' . ( $i + 1 ) . ')';
			}

			$I->assertEquals(
				$I->grabTextFrom('select' . $selectElement . ' option:' . $nth),
				$value
			);
		}
	}
}
