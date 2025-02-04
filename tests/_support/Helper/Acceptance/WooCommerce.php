<?php
namespace Helper\Acceptance;

/**
 * Helper methods and actions related to WooCommerce,
 * which are then available using $I->{yourFunctionName}.
 *
 * @since   1.0.0
 */
class WooCommerce extends \Codeception\Module
{
	/**
	 * Helper method to setup the WooCommerce Plugin.
	 *
	 * @since   1.0.0
	 *
	 * @param   AcceptanceTester $I     AcceptanceTester.
	 */
	public function setupWooCommercePlugin($I)
	{
		// Set Store in Live mode i.e. not in "Coming Soon" mode.
		$I->haveOptionInDatabase( 'woocommerce_coming_soon', 'no' );

		// Setup Cash on Delivery as Payment Method.
		$I->haveOptionInDatabase(
			'woocommerce_cod_settings',
			[
				'enabled'            => 'yes',
				'title'              => 'Cash on delivery',
				'description'        => 'Pay with cash upon delivery',
				'instructions'       => 'Pay with cash upon delivery',
				'enable_for_methods' => [],
				'enable_for_virtual' => 'yes',
			]
		);

		// Setup Stripe as Payment Method, as it's required for subscription products.
		$I->haveOptionInDatabase(
			'woocommerce_stripe_settings',
			[
				'enabled'                               => 'yes',
				'title'                                 => 'Credit Card (Stripe)',
				'description'                           => 'Pay with your credit card via Stripe.',
				'api_credentials'                       => '',
				'testmode'                              => 'yes',
				'test_publishable_key'                  => $_ENV['STRIPE_TEST_PUBLISHABLE_KEY'],
				'test_secret_key'                       => $_ENV['STRIPE_TEST_SECRET_KEY'],
				'publishable_key'                       => '',
				'secret_key'                            => '',
				'webhook'                               => '',
				'test_webhook_secret'                   => '',
				'webhook_secret'                        => '',
				'inline_cc_form'                        => 'yes', // Required so one iframe is output by Stripe, instead of 3.
				'statement_descriptor'                  => '',
				'capture'                               => 'yes',
				'payment_request'                       => 'no',
				'payment_request_button_type'           => 'buy',
				'payment_request_button_theme'          => 'dark',
				'payment_request_button_locations'      => [
					'checkout',
				],
				'payment_request_button_size'           => 'default',
				'saved_cards'                           => 'no',
				'logging'                               => 'no',
				'upe_checkout_experience_enabled'       => 'yes',
				'title_upe'                             => '',
				'is_short_statement_descriptor_enabled' => 'no',
				'upe_checkout_experience_accepted_payments' => [
					'card',
					'link',
				],
				'short_statement_descriptor'            => 'CK',
				'stripe_upe_payment_method_order'       => [
					'card',
					'alipay',
					'klarna',
					'afterpay_clearpay',
					'eps',
					'bancontact',
					'boleto',
					'ideal',
					'oxxo',
					'sepa_debit',
					'p24',
					'multibanco',
					'link',
					'wechat_pay',
				],
			]
		);
	}

	/**
	 * Helper method to enable HPOS in WooCommerce.
	 *
	 * @since   1.9.3
	 *
	 * @param   AcceptanceTester $I     AcceptanceTester.
	 */
	public function enableWooCommerceHPOS($I)
	{
		$I->haveOptionInDatabase('woocommerce_custom_orders_table_enabled', 'yes');
	}

	/**
	 * Helper method to disable HPOS in WooCommerce.
	 *
	 * @since   1.9.3
	 *
	 * @param   AcceptanceTester $I     AcceptanceTester.
	 */
	public function disableWooCommerceHPOS($I)
	{
		$I->haveOptionInDatabase('woocommerce_custom_orders_table_enabled', 'no');
	}

	/**
	 * Helper method to setup WooCommerce's checkout page to use the
	 * legacy [woocommerce_shortcode].
	 *
	 * @since   1.7.1
	 *
	 * @param   AcceptanceTester $I     AcceptanceTester.
	 */
	public function setupWooCommerceCheckoutShortcode($I)
	{
		// Create Checkout Page using checkout shortcode.
		$pageID = $I->havePageInDatabase(
			[
				'post_title'   => 'Checkout',
				'post_name'    => 'checkout-shortcode',
				'post_content' => '[woocommerce_checkout]',
			]
		);

		// Configure WooCommerce to use this Page as the Checkout Page.
		$I->dontHaveOptionInDatabase('woocommerce_checkout_page_id');
		$I->haveOptionInDatabase('woocommerce_checkout_page_id', $pageID);
	}

	/**
	 * Helper method to setup WooCommerce's checkout page to use the
	 * newer Checkout block.
	 *
	 * @since   1.7.1
	 *
	 * @param   AcceptanceTester $I     AcceptanceTester.
	 */
	public function setupWooCommerceCheckoutBlock($I)
	{
		// Find Checkout Page that contains checkout block.
		$pageID = $I->grabFromDatabase(
			'wp_posts',
			'ID',
			[
				'post_name' => 'checkout',
			]
		);

		// Configure WooCommerce to use the default Checkout Page as this will have the
		// Checkout Block.
		$I->dontHaveOptionInDatabase('woocommerce_checkout_page_id');
		$I->haveOptionInDatabase('woocommerce_checkout_page_id', $pageID);
	}

	/**
	 * Helper method to setup the Custom Order Numbers Plugin.
	 *
	 * @since   1.0.0
	 *
	 * @param   AcceptanceTester $I     AcceptanceTester.
	 */
	public function setupCustomOrderNumbersPlugin($I)
	{
		// Setup WooCommerce Order Number prefix based on the current date and PHP version.
		$I->haveOptionInDatabase('alg_wc_custom_order_numbers_prefix', 'ckwc-' . date( 'Y-m-d-H-i-s' ) . '-php-' . PHP_VERSION_ID . '-');
	}

	/**
	 * Helper method to enable the legacy WooCommerce Checkout method
	 * that uses the [woocommerce_checkout] shortcode.
	 *
	 * @since   1.7.1
	 *
	 * @param   AcceptanceTester $I     AcceptanceTester.
	 */
	public function enableWooCommerceLegacyCheckoutShortcode($I)
	{
		// Create Checkout Page using checkout shortcode, not block.
		$pageID = $I->havePageInDatabase(
			[
				'post_title'   => 'Checkout',
				'post_content' => '[woocommerce_checkout]',
			]
		);

		// Configure WooCommerce to use this Page as the Checkout Page.
		$I->dontHaveOptionInDatabase('woocommerce_checkout_page_id');
		$I->haveOptionInDatabase('woocommerce_checkout_page_id', $pageID);
	}

	/**
	 * Helper method to:
	 * - configure the Plugin's opt in, subscribe event and purchase options,
	 * - create a WooCommerce Product (simple|virtual|zero|subscription)
	 * - log out as the WordPress Administrator
	 * - add the WooCommerce Product to the cart
	 * - complete checkout
	 *
	 * This is quite a monolithic function, however this flow is used across 20+ tests,
	 * so it's better to have the code here than in every single test.
	 *
	 * @since   1.9.6
	 *
	 * @param   AcceptanceTester $I                          AcceptanceTester.
	 * @param   bool|array       $options {
	 *           Optional. An array of settings.
	 *
	 *     @type string $product_type               WooCommerce Product Type (simple|virtual|zero|subscription).
	 *     @type string $display_opt_in             Display Opt In on Checkout.
	 *     @type string $check_opt_in               Check Opt In checkbox on Checkout.
	 *     @type string $plugin_form_tag_sequence   Plugin Setting for Form, Tag or Sequence to subscribe the Customer to.
	 *     @type string $subscription_event         Subscription event setting.
	 *     @type string $send_purchase_data         Send WooCommerce Order data to ConvertKit Purchase Data API.
	 *     @type string $product_form_tag_sequence  Product Setting for Form, Tag or Sequence to subscribe the Customer to.
	 *     @type string $custom_fields              Map WooCommerce fields to ConvertKit Custom Fields.
	 *     @type string $name_format                Name format.
	 *     @type string $coupon_form_tag_sequence   Coupon Setting for Form, Tag or Sequence to subscribe the Customer to.
	 *     @type string $use_legacy_checkout        Use Legacy Checkout Shortcode.
	 * }
	 */
	public function wooCommerceCreateProductAndCheckoutWithConfig($I, $options = false)
	{
		// Define default options.
		$defaults = [
			'product_type'              => 'simple',
			'display_opt_in'            => false,
			'check_opt_in'              => false,
			'plugin_form_tag_sequence'  => false,
			'subscription_event'        => false,
			'send_purchase_data'        => false,
			'product_form_tag_sequence' => false,
			'custom_fields'             => false,
			'exclude_name_from_address' => false,
			'name_format'               => 'first',
			'coupon_form_tag_sequence'  => false,
			'use_legacy_checkout'       => true,
		];

		// If supplied options are an array, merge them with the defaults.
		if (is_array($options)) {
			$options = array_merge($defaults, $options);
		} else {
			$options = $defaults;
		}

		// Setup ConvertKit for WooCommerce Plugin.
		$I->setupConvertKitPlugin(
			$I,
			$_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN'],
			$_ENV['CONVERTKIT_OAUTH_REFRESH_TOKEN'],
			$options['subscription_event'],
			$options['plugin_form_tag_sequence'],
			$options['name_format'],
			$options['custom_fields'],
			$options['display_opt_in'],
			( ( $options['send_purchase_data'] === true ) ? 'processing' : $options['send_purchase_data'] ),
			$options['exclude_name_from_address']
		);

		// Create Product.
		switch ($options['product_type']) {
			case 'zero':
				$productName   = 'Zero Value Product';
				$paymentMethod = 'cod';
				$productID     = $I->wooCommerceCreateZeroValueProduct($I, $options['product_form_tag_sequence']);
				break;

			case 'virtual':
				$productName   = 'Virtual Product';
				$paymentMethod = 'cod';
				$productID     = $I->wooCommerceCreateVirtualProduct($I, $options['product_form_tag_sequence']);
				break;

			case 'subscription':
				$productName   = 'Subscription Product';
				$paymentMethod = 'stripe';
				$productID     = $I->wooCommerceCreateSubscriptionProduct($I, $options['product_form_tag_sequence']);
				break;

			case 'simple':
				$productName   = 'Simple Product';
				$paymentMethod = 'cod';
				$productID     = $I->wooCommerceCreateSimpleProduct($I, $options['product_form_tag_sequence']);
				break;
		}

		// Create Coupon.
		if ($options['coupon_form_tag_sequence']) {
			$couponID = $I->wooCommerceCreateCoupon($I, '20off', $options['coupon_form_tag_sequence']);
		}

		// Define Email Address for this Test.
		$emailAddress = $I->generateEmailAddress();

		// Logout as the WordPress Administrator.
		$I->logOut();

		// Add Product to Cart and load Checkout.
		$I->wooCommerceCheckoutWithProduct($I, $productID, $productName, $emailAddress, $paymentMethod, $options['use_legacy_checkout']);

		// Apply Coupon Code.
		if (isset($couponID)) {
			$I->click('a.showcoupon');
			$I->waitForElementNotVisible('.blockOverlay');
			$I->waitForElementVisible('input#coupon_code');
			$I->fillField('input#coupon_code', '20off');
			$I->click('Apply coupon');

			$I->waitForText('Coupon code applied successfully.', 5, '.is-success');
		}

		// Determine field ID for opt-in checkbox.
		$optInCheckboxFieldID = ( $options['use_legacy_checkout'] ? '#ckwc_opt_in' : '#billing-ckwc-opt-in' );

		// Handle Opt-In Checkbox.
		if ($options['display_opt_in']) {
			if ($options['check_opt_in']) {
				$I->checkOption($optInCheckboxFieldID);
			} else {
				$I->uncheckOption($optInCheckboxFieldID);
			}
		} else {
			$I->dontSeeElement($optInCheckboxFieldID);
		}

		// Click Place order button.
		switch ($options['use_legacy_checkout']) {
			case true:
				$I->waitForElementNotVisible('.blockOverlay');
				$I->scrollTo('#place_order');
				$I->click('#place_order');
				break;

			case false:
				$I->click('button.wc-block-components-checkout-place-order-button');

				// WooCommerce has a bug where clicking the Place Order button the first time doesn't do anything.
				// This can be reproduced without the ConvertKit for WooCommerce Plugin active, so it's not a conflict.
				// However, it doesn't always happen, so check if the button is still visible, and click it again if so.
				try {
					$I->wait(2);
					$I->seeElement('button.wc-block-components-checkout-place-order-button');
					$I->click('button.wc-block-components-checkout-place-order-button');
				} catch (\Exception $e) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
					// Don't throw an error. Continue through the assertions.
				}
				break;
		}

		// Confirm order received is displayed.
		// WooCommerce changed the default wording between 5.x and 6.x, so perform
		// a few checks to be certain.
		$I->waitForElement('body.woocommerce-order-received', 30);
		$I->seeInSource('Order');
		$I->seeInSource('received');
		$I->seeInSource('Order details</h3>');

		// Return data.
		return [
			'email_address'   => $emailAddress,
			'product_id'      => $productID,
			'order_id'        => $I->grabTextFrom('ul.wc-block-order-confirmation-summary-list li:first-child span.wc-block-order-confirmation-summary-list-item__value'),
			'subscription_id' => ( ( $options['product_type'] === 'subscription' ) ? (int) filter_var($I->grabTextFrom('.woocommerce-orders-table__cell-order-number a'), FILTER_SANITIZE_NUMBER_INT) : 0 ),
		];
	}

	/**
	 * Changes the order status for the given Order ID to the given Order Status.
	 *
	 * @since   1.9.6
	 *
	 * @param   AcceptanceTester $I              Acceptance Tester.
	 * @param   int              $orderID        WooCommerce Order ID.
	 * @param   string           $orderStatus    Order Status.
	 */
	public function wooCommerceChangeOrderStatus($I, $orderID, $orderStatus)
	{
		// We perform the order status change by editing the Order as a WordPress Administrator would,
		// so that WooCommerce triggers its actions and filters that our integration hooks into.
		$I->loginAsAdmin();

		// If the Order ID contains dashes, it's prefixed by the Custom Order Numbers Plugin.
		if (strpos($orderID, '-') !== false) {
			$orderIDParts = explode('-', $orderID);
			$orderID      = $orderIDParts[ count($orderIDParts) - 1 ];
		}

		$I->amOnAdminPage('post.php?post=' . $orderID . '&action=edit');
		$I->submitForm(
			'div.wrap > form',
			[
				'order_status' => $orderStatus,
			]
		);
	}

	/**
	 * Refunds the given Order ID.
	 *
	 * @since   1.9.2
	 *
	 * @param   AcceptanceTester $I              Acceptance Tester.
	 * @param   int              $orderID        WooCommerce Order ID.
	 * @param   int              $amount         Amount to refund.
	 */
	public function wooCommerceRefundOrder($I, $orderID, $amount)
	{
		// We perform the order status change by editing the Order as a WordPress Administrator would,
		// so that WooCommerce triggers its actions and filters that our integration hooks into.
		$I->loginAsAdmin();

		// If the Order ID contains dashes, it's prefixed by the Custom Order Numbers Plugin.
		if (strpos($orderID, '-') !== false) {
			$orderIDParts = explode('-', $orderID);
			$orderID      = $orderIDParts[ count($orderIDParts) - 1 ];
		}

		// Load order edit screen.
		$I->amOnAdminPage('post.php?post=' . $orderID . '&action=edit');

		// Refund the entire order.
		$I->click('button.refund-items');
		$I->waitForElementVisible('input#refund_amount');
		$I->fillField('input#refund_amount', $amount);
		$I->click('button.do-manual-refund');
		$I->acceptPopup();

		// Wait for confirmation.
		$I->waitForElementVisible('abbr.refund_by');
	}

	/**
	 * Refunds the given Order ID.
	 *
	 * @since   1.9.3
	 *
	 * @param   AcceptanceTester $I                 Acceptance Tester.
	 * @param   int|string       $orderID           WooCommerce Order ID.
	 * @param   string           $newEmailAddress   New Email Address.
	 */
	public function wooCommerceChangeOrderEmailAddress($I, $orderID, $newEmailAddress = '')
	{
		// We perform the order status change by editing the Order as a WordPress Administrator would,
		// so that WooCommerce triggers its actions and filters that our integration hooks into.
		$I->loginAsAdmin();

		// If the Order ID contains dashes, it's prefixed by the Custom Order Numbers Plugin.
		if (strpos($orderID, '-') !== false) {
			$orderIDParts = explode('-', $orderID);
			$orderID      = $orderIDParts[ count($orderIDParts) - 1 ];
		}

		// Load order edit screen.
		$I->amOnAdminPage('post.php?post=' . $orderID . '&action=edit');

		// Change the email address.
		$I->click('a.edit_address:first-child');
		$I->waitForElementVisible('#_billing_email');

		// Update email address.
		$I->submitForm(
			'div.wrap > form',
			[
				'_billing_email' => $newEmailAddress,
			]
		);
	}

	/**
	 * Creates a 'Simple product' in WooCommerce that can be used for tests.
	 *
	 * @since   1.0.0
	 *
	 * @param   AcceptanceTester $I                      Acceptance Tester.
	 * @param   mixed            $productFormTagSequence Product Setting for Form, Tag or Sequence to subscribe the Customer to.
	 * @return  int                                         Product ID
	 */
	public function wooCommerceCreateSimpleProduct($I, $productFormTagSequence = false)
	{
		return $I->havePostInDatabase(
			[
				'post_type'    => 'product',
				'post_status'  => 'publish',
				'post_name'    => 'simple-product',
				'post_title'   => 'Simple Product',
				'post_content' => 'Simple Product Content',
				'meta_input'   => [
					'_backorders'        => 'no',
					'_download_expiry'   => -1,
					'_download_limit'    => -1,
					'_downloadable'      => 'no',
					'_manage_stock'      => 'no',
					'_price'             => 10,
					'_product_version'   => '6.3.0',
					'_regular_price'     => 10,
					'_sold_individually' => 'no',
					'_stock'             => null,
					'_stock_status'      => 'instock',
					'_tax_class'         => '',
					'_tax_status'        => 'taxable',
					'_virtual'           => 'no',
					'_wc_average_rating' => 0,
					'_wc_review_count'   => 0,

					// ConvertKit Integration Form/Tag/Sequence.
					'ckwc_subscription'  => ( $productFormTagSequence ? $productFormTagSequence : '' ),
				],
			]
		);
	}

	/**
	 * Creates a 'Simple product' in WooCommerce that is set to be 'Virtual', that can be used for tests.
	 *
	 * @since   1.0.0
	 *
	 * @param   AcceptanceTester $I                      Acceptance Tester.
	 * @param   mixed            $productFormTagSequence Product Setting for Form, Tag or Sequence to subscribe the Customer to.
	 * @return  int                                         Product ID
	 */
	public function wooCommerceCreateVirtualProduct($I, $productFormTagSequence = false)
	{
		return $I->havePostInDatabase(
			[
				'post_type'    => 'product',
				'post_status'  => 'publish',
				'post_name'    => 'virtual-product',
				'post_title'   => 'Virtual Product',
				'post_content' => 'Virtual Product Content',
				'meta_input'   => [
					'_backorders'        => 'no',
					'_download_expiry'   => -1,
					'_download_limit'    => -1,
					'_downloadable'      => 'no',
					'_manage_stock'      => 'no',
					'_price'             => 10,
					'_product_version'   => '6.3.0',
					'_regular_price'     => 10,
					'_sold_individually' => 'no',
					'_stock'             => null,
					'_stock_status'      => 'instock',
					'_tax_class'         => '',
					'_tax_status'        => 'taxable',
					'_virtual'           => 'yes',
					'_wc_average_rating' => 0,
					'_wc_review_count'   => 0,

					// ConvertKit Integration Form/Tag/Sequence.
					'ckwc_subscription'  => ( $productFormTagSequence ? $productFormTagSequence : '' ),
				],
			]
		);
	}

	/**
	 * Creates a 'Subscription product' in WooCommerce that can be used for tests, which
	 * is set to renew daily.
	 *
	 * @since   1.4.4
	 *
	 * @param   AcceptanceTester $I                      Acceptance Tester.
	 * @param   mixed            $productFormTagSequence Product Setting for Form, Tag or Sequence to subscribe the Customer to.
	 * @return  int                                         Product ID
	 */
	public function wooCommerceCreateSubscriptionProduct($I, $productFormTagSequence = false)
	{
		return $I->havePostInDatabase(
			[
				'post_type'    => 'product',
				'post_status'  => 'publish',
				'post_name'    => 'subscription-product',
				'post_title'   => 'Subscription Product',
				'post_content' => 'Subscription Product Content',
				'meta_input'   => [
					'_backorders'                     => 'no',
					'_download_expiry'                => -1,
					'_download_limit'                 => -1,
					'_downloadable'                   => 'yes',
					'_manage_stock'                   => 'no',
					'_price'                          => 10,
					'_product_version'                => '6.2.0',
					'_regular_price'                  => 10,
					'_sold_individually'              => 'no',
					'_stock'                          => null,
					'_stock_status'                   => 'instock',
					'_subscription_length'            => 0,
					'_subscription_limit'             => 'no',
					'_subscription_one_time_shipping' => 'no',
					'_subscription_payment_sync_date' => 0,
					'_subscription_period'            => 'day',
					'_subscription_period_interval'   => 1,
					'_subscription_price'             => 10,
					'_subscription_sign_up_fee'       => 0,
					'_subscription_trial_length'      => 0,
					'_subscription_trial_period'      => 'day',
					'_tax_class'                      => '',
					'_tax_status'                     => 'taxable',
					'_virtual'                        => 'yes',
					'_wc_average_rating'              => 0,
					'_wc_review_count'                => 0,

					// ConvertKit Integration Form/Tag/Sequence.
					'ckwc_subscription'               => ( $productFormTagSequence ? $productFormTagSequence : '' ),
				],
				'tax_input'    => [
					[ 'product_type' => 'subscription' ],
				],
			]
		);
	}

	/**
	 * Creates a zero value 'Simple product' in WooCommerce that can be used for tests.
	 *
	 * @since   1.0.0
	 *
	 * @param   AcceptanceTester $I                      Acceptance Tester.
	 * @param   mixed            $productFormTagSequence Product Setting for Form, Tag or Sequence to subscribe the Customer to.
	 * @return  int                                         Product ID
	 */
	public function wooCommerceCreateZeroValueProduct($I, $productFormTagSequence = false)
	{
		return $I->havePostInDatabase(
			[
				'post_type'    => 'product',
				'post_status'  => 'publish',
				'post_name'    => 'zero-value-product',
				'post_title'   => 'Zero Value Product',
				'post_content' => 'Zero Value Product Content',
				'meta_input'   => [
					'_backorders'        => 'no',
					'_download_expiry'   => -1,
					'_download_limit'    => -1,
					'_downloadable'      => 'no',
					'_manage_stock'      => 'no',
					'_price'             => 0,
					'_product_version'   => '6.3.0',
					'_regular_price'     => 0,
					'_sold_individually' => 'no',
					'_stock'             => null,
					'_stock_status'      => 'instock',
					'_tax_class'         => '',
					'_tax_status'        => 'taxable',
					'_virtual'           => 'no',
					'_wc_average_rating' => 0,
					'_wc_review_count'   => 0,

					// ConvertKit Integration Form/Tag/Sequence.
					'ckwc_subscription'  => ( $productFormTagSequence ? $productFormTagSequence : '' ),
				],
			]
		);
	}

	/**
	 * Creates a Coupon in WooCommerce that can be used for tests.
	 *
	 * @since   1.5.9
	 *
	 * @param   AcceptanceTester $I                      Acceptance Tester.
	 * @param   string           $couponCode             Couponn Code.
	 * @param   mixed            $couponFormTagSequence  Coupon Setting for Form, Tag or Sequence to subscribe the Customer to.
	 * @return  int                                      Coupon ID
	 */
	public function wooCommerceCreateCoupon($I, $couponCode, $couponFormTagSequence = false)
	{
		return $I->havePostInDatabase(
			[
				'post_type'    => 'shop_coupon',
				'post_status'  => 'publish',
				'post_name'    => $couponCode,
				'post_title'   => $couponCode,
				'post_content' => $couponCode,
				'meta_input'   => [
					// Create a 20% off coupon. The amount doesn't matter for tests.
					'discount_type'          => 'percent',
					'coupon_amount'          => 20,
					'individual_use'         => 'no',
					'usage_limit'            => 0,
					'usage_limit_per_user'   => 0,
					'limit_usage_to_x_items' => 0,
					'usage_count'            => 0,
					'date_expires'           => null,
					'free_shipping'          => 'no',
					'exclude_sales_items'    => 'no',

					// ConvertKit Integration Form/Tag/Sequence.
					'ckwc_subscription'      => ( $couponFormTagSequence ? $couponFormTagSequence : '' ),
				],
			]
		);
	}

	/**
	 * Adds the given Product ID to the Cart, loading the Checkout screen
	 * and prefilling the standard WooCommerce Billing Fields.
	 *
	 * @since   1.0.0
	 *
	 * @param   AcceptanceTester $I              AcceptanceTester.
	 * @param   string           $productID      Product ID.
	 * @param   string           $productName    Product Name.
	 * @param   string           $emailAddress   Email Address (wordpress@convertkit.com).
	 * @param   string           $paymentMethod  Payment Method (cod|stripe).
	 * @param   bool             $useLegacyCheckout Use Legacy Checkout Shortcode.
	 */
	public function wooCommerceCheckoutWithProduct($I, $productID, $productName, $emailAddress = 'wordpress@convertkit.com', $paymentMethod = 'cod', $useLegacyCheckout = true)
	{
		// Enable legacy or block Checkout Page.
		if ($useLegacyCheckout) {
			$I->setupWooCommerceCheckoutShortcode($I);
		} else {
			$I->setupWooCommerceCheckoutBlock($I);
		}

		// Logout as the WordPress Administrator.
		$I->logOut();

		// Load the Product on the frontend site.
		$I->amOnPage('/?p=' . $productID);

		// Check that no WooCommerce, PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Add Product to Cart.
		$I->click('button[name=add-to-cart]');

		// View Cart.
		$I->click('a.wc-forward');

		// Check that no WooCommerce, PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Check that the Product exists in the Cart.
		$I->seeInSource($productName);

		// Proceed to Checkout.
		$I->click('Proceed to Checkout');

		// Wait for the Checkout to load.
		$I->waitForElementVisible('body.woocommerce-checkout');

		// Check that no WooCommerce, PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		$I->wait(3);

		// Complete Billing Details.
		switch ($useLegacyCheckout) {
			// Legacy Checkout Shortcode.
			case true:
				$I->fillField('#billing_first_name', 'First');
				$I->fillField('#billing_last_name', 'Last');
				$I->fillField('#billing_address_1', 'Address Line 1');
				$I->fillField('#billing_city', 'City');
				$I->fillField('#billing_postcode', '12345');
				$I->fillField('#billing_phone', '6159684594');
				$I->fillField('#billing_email', $emailAddress);
				$I->fillField('#order_comments', 'Notes');
				break;

			// Checkout Block.
			case false:
				$I->fillField('#billing-first_name', 'First');
				$I->fillField('#billing-last_name', 'Last');
				$I->fillField('#billing-address_1', 'Address Line 1');
				$I->fillField('#billing-city', 'City');
				$I->fillField('#billing-postcode', '12345');
				$I->fillField('#billing-phone', '6159684594');
				$I->fillField('#email', $emailAddress);
				$I->checkOption('.wc-block-checkout__add-note input.wc-block-components-checkbox__input');
				$I->fillField('.wc-block-components-textarea', 'Notes');
				break;
		}

		// Depending on the payment method required, complete some fields.
		switch ($paymentMethod) {
			/**
			 * Card
			 */
			case 'stripe':
				// Complete Credit Card Details.
				$I->switchToIFrame('iframe[name^="__privateStripeFrame"]'); // Switch to Stripe iFrame.
				$I->fillField('number', '4242424242424242');
				$I->fillfield('expiry', '01/26');
				$I->fillField('cvc', '123');
				$I->switchToIFrame(); // Switch back to main window.
				break;

			/**
			 * COD
			 */
			default:
				// COD is selected by default, so no need to click anything.
				break;
		}
	}

	/**
	 * Creates an Order as if the user were creating an Order through the WordPress Administration
	 * interface.
	 *
	 * @since   1.0.0
	 *
	 * @param   AcceptanceTester $I              Acceptance Tester.
	 * @param   int              $productID      Product ID.
	 * @param   string           $productName    Product Name.
	 * @param   string           $orderStatus    Order Status.
	 * @param   string           $paymentMethod  Payment Method.
	 * @return  int                                 Order ID
	 */
	public function wooCommerceCreateManualOrder($I, $productID, $productName, $orderStatus, $paymentMethod)
	{
		// Login as Administrator.
		$I->loginAsAdmin();

		// Define Email Address for this Manual Order.
		$emailAddress = $I->generateEmailAddress();

		// Create User for this Manual Order.
		$userID = $I->haveUserInDatabase(
			'test',
			'subscriber',
			[
				'user_email' => $emailAddress,
			]
		);

		// Load New Order screen.
		$I->amOnAdminPage('post-new.php?post_type=shop_order');

		// Check that no WooCommerce, PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Define Order Status.
		$I->selectOption('#order_status', $orderStatus);

		// Define User and Payment Method.
		$I->fillSelect2Field($I, '#select2-customer_user-container', $emailAddress, 'aria-owns');
		$I->selectOption('#_payment_method', $paymentMethod);

		// Add Product.
		$I->click('button.add-line-item');
		$I->click('button.add-order-item');
		$I->fillSelect2Field($I, '.wc-backbone-modal-content .select2-selection__rendered', $productName, 'aria-owns');
		$I->click('#btn-ok');

		// Create Order.
		$I->executeJS('window.scrollTo(0,0);');
		$I->click('button.save_order');

		// Check that no WooCommerce, PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Determine the Order ID.
		$orderID = $I->grabTextFrom('h2.woocommerce-order-data__heading');
		$orderID = str_replace('Order #', '', $orderID);
		$orderID = str_replace('details', '', $orderID);
		$orderID = trim($orderID);

		// Return.
		return [
			'email_address' => $emailAddress,
			'product_id'    => $productID,
			'order_id'      => $orderID,
		];
	}

	/**
	 * Check the given Order ID contains an Order Note with the given text.
	 *
	 * @since   1.4.2
	 *
	 * @param   AcceptanceTester $I             AcceptanceTester.
	 * @param   int              $orderID       Order ID.
	 * @param   string           $noteText      Order Note Text.
	 */
	public function wooCommerceOrderNoteExists($I, $orderID, $noteText)
	{
		// Logout.
		$I->logOut();

		// Login as Administrator.
		$I->loginAsAdmin();

		// If the Order ID contains dashes, it's prefixed by the Custom Order Numbers Plugin.
		if (strpos($orderID, '-') !== false) {
			$orderIDParts = explode('-', $orderID);
			$orderID      = $orderIDParts[ count($orderIDParts) - 1 ];
		}

		// Load Edit Order screen.
		$I->amOnAdminPage('post.php?post=' . $orderID . '&action=edit');

		// Confirm note text exists.
		$I->seeInSource($noteText);
	}

	/**
	 * Check the given Order ID does not contain an Order Note with the given text.
	 *
	 * @since   1.4.2
	 *
	 * @param   AcceptanceTester $I             AcceptanceTester.
	 * @param   int              $orderID       Order ID.
	 * @param   string           $noteText      Order Note Text.
	 */
	public function wooCommerceOrderNoteDoesNotExist($I, $orderID, $noteText)
	{
		// Login as Administrator.
		$I->loginAsAdmin();

		// If the Order ID contains dashes, it's prefixed by the Custom Order Numbers Plugin.
		if (strpos($orderID, '-') !== false) {
			$orderIDParts = explode('-', $orderID);
			$orderID      = $orderIDParts[ count($orderIDParts) - 1 ];
		}

		// Load Edit Order screen.
		$I->amOnAdminPage('post.php?post=' . $orderID . '&action=edit');

		// Confirm note text does not exist.
		$I->dontSeeInSource($noteText);
	}

	/**
	 * Check the given Order ID has the given meta key and value pair.
	 *
	 * @since   1.6.6
	 *
	 * @param   AcceptanceTester $I             AcceptanceTester.
	 * @param   int              $orderID       Order ID.
	 * @param   string           $metaKey       Meta Key.
	 * @param   string           $metaValue     Meta Value.
	 * @param   bool             $hposEnabled   If HPOS is enabled.
	 */
	public function wooCommerceOrderMetaKeyAndValueExist($I, $orderID, $metaKey, $metaValue, $hposEnabled = false)
	{
		// If the $orderID isn't numeric, the Custom Order Number Prefix Plugin has prefixed the Order ID
		// to make it unique for tests run in parallel.
		// Extract the true order ID.
		if ( ! is_numeric( $orderID ) ) {
			$orderIDParts = explode( '-', $orderID );
			$orderID      = $orderIDParts[ count($orderIDParts) - 1 ];
		}

		// If HPOS is enabled, check the wp_wc_orders_meta table instead, as the Post
		// Meta isn't used.
		if ( ! $hposEnabled) {
			$I->seePostMetaInDatabase(
				[
					'post_id'    => $orderID,
					'meta_key'   => $metaKey,
					'meta_value' => $metaValue,
				]
			);
		} else {
			$I->seeInDatabase(
				'wp_wc_orders_meta',
				[
					'order_id'   => $orderID,
					'meta_key'   => $metaKey,
					'meta_value' => $metaValue,
				]
			);
		}
	}

	/**
	 * Helper method to programmatically create an Order based on a Product.
	 *
	 * @since   1.7.1
	 *
	 * @param   AcceptanceTester $I             AcceptanceTester.
	 * @param   int              $productID     Product ID.
	 * @param   int              $userID        User ID.
	 * @return  int              $orderID
	 */
	public function wooCommerceOrderCreate($I, $productID, $userID)
	{
		// Create Order.
		$orderID = rand(1, 5000); // phpcs:ignore WordPress.WP.AlternativeFunctions

		$I->haveInDatabase(
			'wp_wc_orders',
			[
				'id'                   => $orderID,
				'status'               => 'wc-processing',
				'currency'             => 'USD',
				'type'                 => 'shop_order',
				'total_amount'         => '10.00000000',
				'customer_id'          => 1,
				'billing_email'        => '',
				'date_created_gmt'     => date('Y-m-d H:i:s'),
				'date_updated_gmt'     => date('Y-m-d H:i:s'),
				'payment_method'       => 'cod',
				'payment_method_title' => 'Cash on delivery',
			]
		);
		$I->haveInDatabase(
			'wp_wc_order_product_lookup',
			[
				'order_item_id'         => rand(1, 5000), // phpcs:ignore WordPress.WP.AlternativeFunctions
				'order_id'              => $orderID,
				'product_id'            => $productID,
				'customer_id'           => $userID,
				'product_net_revenue'   => 10,
				'product_gross_revenue' => 10,
			]
		);

		return $orderID;
	}

	/**
	 * Helper method to delete the given meta key from the wp_posts and wp_wc_orders tables
	 * for a given WooComemrce Order.
	 *
	 * @since   1.6.6
	 *
	 * @param   AcceptanceTester $I             AcceptanceTester.
	 * @param   int              $orderID       Order ID.
	 * @param   string           $metaKey       Meta Key.
	 * @param   bool             $hposEnabled   If HPOS is enabled.
	 */
	public function wooCommerceOrderDeleteMeta($I, $orderID, $metaKey, $hposEnabled = false)
	{
		// If HPOS is enabled, check the wp_wc_orders_meta table instead, as the Post
		// Meta isn't used.
		if ( ! $hposEnabled) {
			$I->dontHavePostMetaInDatabase(
				[
					'post_id'  => $orderID,
					'meta_key' => $metaKey,
				]
			);
		} else {
			$I->dontHaveInDatabase(
				'wp_wc_orders_meta',
				[
					'order_id' => $orderID,
					'meta_key' => $metaKey,
				]
			);
		}
	}

	/**
	 * Helper method to delete all orders from the wp_posts and wp_wc_orders tables.
	 *
	 * @since   1.6.6
	 *
	 * @param   AcceptanceTester $I             AcceptanceTester.
	 */
	public function wooCommerceDeleteAllOrders($I)
	{
		// Delete from wp_posts.
		$I->dontHavePostInDatabase([ 'post_type' => 'shop_order' ]);

		// Delete from wp_wc_orders and wp_wc_orders_meta HPOS tables.
		$I->dontHaveInDatabase('wp_wc_orders', [ 'parent_order_id' => 0 ]);
		$I->dontHaveInDatabase('wp_wc_orders_meta', [ 'id >=', '0' ]);
	}
}
