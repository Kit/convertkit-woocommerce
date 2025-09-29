=== Kit (formerly ConvertKit) for WooCommerce ===
Contributors: nathanbarry, growdev, travisnorthcutt, convertkit
Donate link: https://kit.com
Tags: email, marketing, embed form, convertkit, capture
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.1
Stable tag: 2.0.1
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Integrates WooCommerce with Kit allowing customers to be automatically sent to your Kit account.

== Description ==

[Kit](https://kit.com) makes it easy to capture more leads and sell more products by easily embedding email capture forms anywhere.

This Plugin integrates WooCommerce with Kit, allowing customers and purchase data to be automatically sent to your Kit account.

Full plugin documentation is located [here](https://help.kit.com/en/articles/2502554-woocommerce-integration).

== Installation ==

1. Upload the `convertkit-for-woocommerce` folder to the `/wp-content/plugins/` directory
2. Active the Kit for WooCommerce plugin through the 'Plugins' menu in WordPress

== Configuration ==

1. Configure the plugin by navigating to WooCommerce > Settings > in the WordPress Administration Menu, then click the Integration tab > Kit
2. Enable the integration
3. Enter your [API Key](https://app.kit.com/account_settings/advanced_settings) and API Secret, clicking Save changes
4. Choose the Subscription Form, Tag or Sequence to subscribe Customers to when they complete the WooCommerce Checkout
5. Configure other settings as necessary, depending on your requirements
6. (Optional) Configure which Sequence, Form or Tag to subscribe a Customer to when editing individual WooCommerce Products

== Frequently asked questions ==

= Does this plugin require a paid service? =

No. You must first have an account on kit.com, but you do not have to use a paid plan!

== Screenshots ==

1. Settings page
2. Checkout page with added checkbox

== Changelog ==

### 2.0.1 2025-09-29
* Added: Refresh resources button: show loading indicator until refresh is complete
* Updated: WordPress Coding Standards for JS and CSS
* Updated: Use WordPress Libraries 2.1.0

### 2.0.0 2025-08-12
* Fix: Settings: Removed white border below settings panels

### 1.9.9 2025-06-30
* Fix: Schedule refresh token cron event relative to time(), not token's `created_at`

### 1.9.8 2025-06-23
* Fix: Automatically refresh Access Token when expired

### 1.9.7 2025-05-07
* Updated: Require PHP 7.1 or greater
* Updated: Use WordPress Libraries 2.0.9

### 1.9.6 2025-04-24
* Fix: Checkout: PHP Fatal error when using WooCommerce 8.4.0 or older opt-in checkout block

### 1.9.5 2025-04-15
* Added: Custom Fields: Address Format: Option to format Billing and Shipping Address stored in Kit Custom Fields
* Removed: Custom Fields: Option to exclude name from Billing and Shipping Address. Use above setting instead
* Updated: Use WordPress Libraries 2.0.8

### 1.9.4 2025-03-25
* Fix: Remove `load_plugin_textdomain` call, as it's not needed since WordPress 4.6
* Fix: Issue client ID, site-specific v4 API Access and Refresh Token when upgrading from < 1.8.0
* Updated: Use WordPress Libraries 2.0.7

### 1.9.3 2025-02-06
* Fix: Sync Past Orders: Exclude orders with an invalid email address

### 1.9.2 2025-01-31
* Fix: Sync Past Orders: Exclude refund orders, to prevent 500 Internal Server errors

### 1.9.1 2025-01-23
* Added: Populate the referrer property when the Plugin is configured to subscribe an email address to a Kit Form
* Added: Register opt-in checkbox using new `woocommerce_register_additional_checkout_field method`
* Updated: ConvertKit WordPress Libraries to 2.0.6

### 1.9.0 2024-11-18
* Fix: Purchase Data: Sync Past Orders when High-Performance Order Storage is disabled

### 1.8.9 2024-11-13
* Added: OAuth: Issue site-specific Access and Refresh Token when using the same Kit account on multiple WordPress sites
* Updated: ConvertKit WordPress Libraries to 2.0.5

### 1.8.8 2024-10-08
* Fix: Kit branding tweaks and secondary button colors

### 1.8.7 2024-10-01
* Updated: Changed branding to Kit
* Updated: Kit WordPress Libraries to 2.0.4

### 1.8.6 2024-09-27
* Updated: ConvertKit WordPress Libraries to 2.0.3

### 1.8.5 2024-09-23
* Added: Custom Fields: Option to exclude name from Billing and Shipping Address

### 1.8.4 2024-09-13
* Added: Map WooCommerce Order Data (Phone, Billing Address etc) to ConvertKit Subscriber's Custom Fields when sending purchase data 
* Updated: ConvertKit WordPress Libraries to 2.0.2
* Fix: Don't automatically refresh tokens on non-production sites

### 1.8.3 2024-08-15
* Fix: Set subscriber to `inactive` when a ConvertKit Form is specified, honoring the Form's opt in setting.

### 1.8.2 2024-07-25
* Fix: `ckwc_purchase_data_id` would not be populated on Order when "Send purchase data to ConvertKit" enabled
* Fix: Change `update_option` parameter from `yes` to `true`

### 1.8.1 2024-07-18
* Updated: ConvertKit WordPress Libraries to 2.0.1

### 1.8.0 2024-07-04
* Added: Use ConvertKit v4 API and OAuth
* Updated: ConvertKit WordPress Libraries to 2.0.0

### 1.7.3 2024-03-12
* Updated: ConvertKit WordPress Libraries to 1.4.2

### 1.7.2 2024-01-16
* Updated: ConvertKit WordPress Libraries to 1.4.1

### 1.7.1 2023-12-22
* Added: Checkout: Opt in checkbox block is automatically added when WooCommerce Checkout Block is used, honoring settings at `WooCommerce > Settings > Integration > ConvertKit`
* Added: Purchase Data: CLI: `wp ckwc-sync-past-orders` command can be used to send past WooCommerce Orders to ConvertKit.  Use `wp ckwc-sync-past-orders --limit=100` to send a specific number of WooCommerce Orders 

### 1.7.0 2023-11-09
* Updated: ConvertKit WordPress Libraries to 1.4.0

### 1.6.9 2023-10-05
* Updated: ConvertKit WordPress Libraries to 1.3.9

### 1.6.8 2023-08-31
* Updated: WordPress Coding Standards
* Updated: ConvertKit WordPress Libraries to 1.3.8

### 1.6.7 2023-07-17
* Updated: ConvertKit WordPress Libraries to 1.3.7

### 1.6.6 2023-07-11
* Added: Support for High-Performance Order Storage

### 1.6.5 2023-06-13
* Updated: ConvertKit WordPress Libraries to 1.3.6

### 1.6.4 2023-05-15
* Fix: Remove double forwardslash on JS and CSS assets

### 1.6.3 2023-05-04
* Added: Settings: Options to Map WooCommerce Order Data's Last Name to ConvertKit Custom Field

### 1.6.2 2023-04-20
* Fix: Honor Name Format when a customer both opts in and Purchase Data setting is enabled

### 1.6.1 2023-04-06
* Updated: ConvertKit WordPress Libraries to 1.3.4

### 1.6.0 2023-03-30
* Updated: Tested with WordPress 6.2
* Updated: Tested with WooCommerce 7.5.1

### 1.5.9 2023-02-23
* Added: WooCommerce Coupons: Option to specify Form, Tag or Sequence to subscribe customer to if a specific coupon is applied at checkout

### 1.5.8 2023-02-14
* Fix: WooCommerce Products: Refresh Sequences, Forms and Tags when refresh button clicked

### 1.5.7 2023-02-02
* Fix: Settings: Subscription: List ConvertKit Sequences, Forms and Tags in alphabetical order
* Fix: WooCommerce Products: List ConvertKit Sequences, Forms and Tags in alphabetical order

### 1.5.6 2023-01-16
* Updated: ConvertKit WordPress Libraries to 1.3.0

### 1.5.5 2023-01-05
* Fix: PHP Warning: Trying to access array offset on value of type null

### 1.5.4 2022-12-12
* Fix: iThemes Sync: Error when attempting to update WordPress Plugins or Themes using iThemes Sync when ConvertKit for WooCommerce active.

### 1.5.3 2022-12-07
* Fix: Bulk & Quick Edit: Improve layout of ConvertKit settings on desktop and mobile
* Fix: Products: Improve layout of ConvertKit settings on desktop and mobile

### 1.5.2 2022-11-21
* Fix: Remove unused `admin_init` call

### 1.5.1 2022-10-25
* Updated: ConvertKit WordPress Libraries to 1.2.1

### 1.5.0 2022-09-07
* Development: Moved /lib folder to managed repository

### 1.4.9 2022-08-15
* Added: Refresh button: Show error notification when refreshing fails

### 1.4.8 2022-08-04
* Added: Bulk and Quick Edit Subscription when viewing list of WooCommerce Products
* Added: Refresh button for Subscription field when editing a WooCommerce Product, to fetch latest data from ConvertKit account
* Fix: Performance: Don't perform API requests on every WordPress Administration screen when no Forms, Sequences or Tags exist

### 1.4.7 2022-06-23
* Fix: Type checks and consistent return types on API class
* Fix: Performance: Improved caching of Forms, Tags and Sequences to prevent API timeouts and slow loading in the WordPress Administration

### 1.4.6 2022-04-19
* Added: Settings: Import and Export configuration

### 1.4.5 2022-03-17
* Added: Settings: Purchase Data: Option to specify when purchase data should be sent to ConvertKit based on WooCommerce Order status 

### 1.4.4 2022-03-10
* Fix: Include Name when subscribing to a Tag or Sequence
* Fix: Settings: Change Subscribe Event's "Order Created" label to "Order Pending payment", to reflect WooCommerce Order's status labels
* Fix: WooCommerce Subscriptions: Don't resubscribe customer when a WooCommerce Order created is for a subscription renewal

### 1.4.3 2022-02-14
* Added: Settings: Options to Map WooCommerce Order Data (Phone, Billing Address etc) to ConvertKit Custom Fields
* Added: Select2 dropdown for Forms, Tags and Sequence selection with search functionality for improved UX.

### 1.4.2 2022-01-28
* Added: Testing and compatibility for WooCommerce 6.1
* Added: PHP 8.x compatibility
* Added: Developers: Action and filter hooks.  See https://github.com/ConvertKit/convertkit-woocommerce/blob/main/ACTIONS-FILTERS.md
* Added: Localization and .pot file for translators
* Fix: Settings: Only show conditional settings if other settings enabled/disabled
* Fix: Settings: API Key and Secret: Don't need to save settings twice for API Key and Secret to work
* Fix: Settings: Improved setting descriptions
* Fix: Settings: Improved order and layout of settings to be more logical
* Fix: Settings: Subscription: Renamed Courses to Sequences on Subscription dropdown option
* Fix: Settings: Purchase Data: If enabled, always send purchase data to ConvertKit, regardless of how the order is created or the payment method used
* Fix: Settings: Purchase Data: Once purchase data is sent to ConvertKit, don't keep sending it when e.g. the order's status changes
* Fix: WooCommerce: Order: Don't subscribe a Customer a second time if an existing Order's status changes back to the Plugin's Subscribe Event
* Fix: WooCommerce: Products: Renamed Courses to Sequences on Subscription dropdown option
* Fix: WooCommerce: Checkout: Improved performance by not requesting ConvertKit Forms, Tags and Sequences from the API when not needed
* Fix: Performance: Cache Forms, Tags and Sequences from ConvertKit account for longer than 5 minutes, to prevent API timeouts and slow loading in the WordPress Administration

### 1.4.1  2020-06-06
* Protect against missing products on order items

### 1.4.0  2020-04-03
* Add support for sending manual orders to ConvertKit

### 1.3.0  2019-12-20
* Add support for sending cash on delivery and check payment purchase info to ConvertKit

### 1.2.0  2019-07-15
* Improve plugin's translation readiness
* Make plugin settings link work with other WooCommerce addons
* Correct product metabox title
* Display tags/forms/sequences alphabetically
* Add ability to force-refresh subscription options
* Add integration name to purchase API requests

### 1.1.0  2019-03-15
* Add WooCommerce order note when a customer is subscribed to ConvertKit

### 1.0.6  2018-09-10
* Fix for cart item_id being sent to ConvertKit's API instead of product_id. This was causing product purchases to be seen as unique instead of grouped.

### 1.0.5  2018-07-24
* Added ability for WooCommerce purchase data to be sent to Seva's API
* See: http://developers.convertkit.com/#purchases
* Updated Installation and Configuration sections of this readme.

### 1.0.4  2017-08-09
* Verified compatibility with WooCommerce 3.1
* Code cleanup.

### 1.0.3
* Added Settings plugin link.
* Added a setting to allow admin to decide if First Name, Last Name, or both are sent to CK's 'name' field.

### 1.0.2
* Added logger to help debug connectivity issues.

### 1.0.1
* Don't use API when setting transient options

### 1.0.0
* Initial release

== Upgrade notice ==

