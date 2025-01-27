<?php
/**
 * ConvertKit Order class.
 *
 * @package CKWC
 * @author ConvertKit
 */

/**
 * Subscribes a WooCommerce Order's Customer to ConvertKit Forms, Tags and/or Sequences,
 * based on Product and Plugin settings.
 *
 * Sends Purchase Data to ConvertKit if enabled in the Plugin settings.
 *
 * @package CKWC
 * @author ConvertKit
 */
class CKWC_Order {

	/**
	 * Holds the WooCommerce Integration instance for this Plugin.
	 *
	 * @since   1.4.2
	 *
	 * @var     CKWC_Integration
	 */
	private $integration;

	/**
	 * Holds the ConvertKit API.
	 *
	 * @since   1.4.2
	 *
	 * @var     CKWC_API
	 */
	private $api;

	/**
	 * The Meta Key used to store that the WooCommerce Order
	 * was successfully sent to ConvertKit.
	 *
	 * @since   1.4.4
	 *
	 * @var     string
	 */
	private $purchase_data_sent_meta_key = 'ckwc_purchase_data_sent';

	/**
	 * The Meta Key used to store the ConvertKit Transaction ID
	 * when purchase data is successfully sent to ConvertKit
	 * for a WooCommerce Order.
	 *
	 * @since   1.4.4
	 *
	 * @var     string
	 */
	private $purchase_data_id_meta_key = 'ckwc_purchase_data_id';

	/**
	 * Constructor
	 *
	 * @since   1.0.0
	 */
	public function __construct() {

		// Fetch integration.
		$this->integration = WP_CKWC_Integration();

		// If the integration isn't enabled, don't load any other actions or filters.
		if ( ! $this->integration->is_enabled() ) {
			return;
		}

		// Subscribe customer's email address to a form, tag or sequence.
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'maybe_subscribe_customer' ), 99999, 1 );
		add_action( 'woocommerce_order_status_changed', array( $this, 'maybe_subscribe_customer' ), 99999, 3 );

		// Send Purchase Data.
		if ( $this->integration->get_option_bool( 'send_purchases' ) ) {
			add_action( 'woocommerce_order_status_changed', array( $this, 'send_purchase_data_action' ), 99999, 3 );
		}

	}

	/**
	 * Subscribe the customer's email address to a ConvertKit Form, Tag or Sequence, if the Order's event
	 * matches the Subscribe Event in this Plugin's Settings.
	 *
	 * @since   1.0.0
	 *
	 * @param   int    $order_id   WooCommerce Order ID.
	 * @param   string $status_old Order's Old Status.
	 * @param   string $status_new Order's New Status.
	 */
	public function maybe_subscribe_customer( $order_id, $status_old = 'new', $status_new = 'pending' ) {

		// Bail if the old and new status are the same i.e. the Order status did not change.
		if ( $status_old === $status_new ) {
			return;
		}

		// Bail if the Subscribe Event doesn't match the Order's new status.
		if ( $this->integration->get_option( 'event' ) !== $status_new ) {
			return;
		}

		// Get WooCommerce Order.
		$order = wc_get_order( $order_id );

		// Bail if the Order does not require that we subscribe the Customer,
		// or the Customer was already subscribed.
		if ( ! $this->should_opt_in_customer( $order ) ) {
			return;
		}

		// If no Order could be fetched, bail.
		if ( ! $order ) {
			return;
		}

		// If configured in the Integration, map Phone, Billing and/or Shipping Addresses to ConvertKit
		// Custom Fields now.
		$fields = $this->custom_field_data( $order );

		// Build an array of Forms, Tags and Sequences to subscribe the Customer to, based on:
		// - the global integration's setting,
		// - any product-specific settings,
		// - any coupon-specific settings.
		$subscriptions = array( $this->integration->get_option( 'subscription' ) );

		// Get product-specific subscription settings.
		foreach ( $order->get_items() as $item ) {
			// Get the WC_Product object.
			$product = wc_get_product( $item['product_id'] );

			// Get the Form, Tag or Sequence for this Product.
			$resource_id = $product->get_meta( 'ckwc_subscription', true );

			/**
			 * Define the Form, Tag or Sequence ID to subscribe the Customer to for the given Product.
			 *
			 * @since   1.4.2
			 *
			 * @param   mixed  $resource_id     Form, Tag or Sequence ID | empty string.
			 * @param   int    $order_id        WooCommerce Order ID.
			 * @param   string $status_old      Order's Old Status.
			 * @param   string $status_new      Order's New Status.
			 * @param   int    $product_id      Product ID.
			 */
			$resource_id = apply_filters( 'convertkit_for_woocommerce_order_maybe_subscribe_customer_resource_id', $resource_id, $order_id, $status_old, $status_new, $product->get_id() );

			// If no resource is specified for this Product, don't add it to the array.
			if ( empty( $resource_id ) ) {
				continue;
			}

			// Add to array of resources to subscribe the Customer to.
			$subscriptions[] = $resource_id;
		}

		// Get coupon-specific subscription settings.
		foreach ( $order->get_coupon_codes() as $coupon_code ) {
			// Get the WC_Coupon object.
			$coupon = new WC_Coupon( $coupon_code );

			// Get the Form, Tag or Sequence for this Coupon.
			$resource_id = $coupon->get_meta( 'ckwc_subscription', true );

			/**
			 * Define the Form, Tag or Sequence ID to subscribe the Customer to for the given Coupon.
			 *
			 * @since   1.5.9
			 *
			 * @param   mixed  $resource_id     Form, Tag or Sequence ID | empty string.
			 * @param   int    $order_id        WooCommerce Order ID.
			 * @param   string $status_old      Order's Old Status.
			 * @param   string $status_new      Order's New Status.
			 * @param   int    $coupon_id       Coupon ID.
			 */
			$resource_id = apply_filters( 'convertkit_for_woocommerce_order_maybe_subscribe_customer_resource_id_coupon', $resource_id, $order_id, $status_old, $status_new, $coupon->get_id() );

			// If no resource is specified for this Coupon, don't add it to the array.
			if ( empty( $resource_id ) ) {
				continue;
			}

			// Add to array of resources to subscribe the Customer to.
			$subscriptions[] = $resource_id;
		}

		// Remove any duplicate Forms, Tags and Sequences.
		$subscriptions = array_filter( array_unique( $subscriptions ) );

		/**
		 * Define the Forms, Tags and/or Sequences to subscribe the Customer to for this Order.
		 *
		 * @since   1.4.2
		 *
		 * @param   array  $subscriptions   Subscriptions (array of Forms, Tags and/or Sequence IDs).
		 * @param   int    $order_id        WooCommerce Order ID.
		 * @param   string $status_old      Order's Old Status.
		 * @param   string $status_new      Order's New Status.
		 */
		$subscriptions = apply_filters( 'convertkit_for_woocommerce_order_maybe_subscribe_customer_subscriptions', $subscriptions, $order_id, $status_old, $status_new );

		// Setup the API.
		$this->api = new CKWC_API(
			CKWC_OAUTH_CLIENT_ID,
			CKWC_OAUTH_CLIENT_REDIRECT_URI,
			$this->integration->get_option( 'access_token' ),
			$this->integration->get_option( 'refresh_token' ),
			$this->integration->get_option_bool( 'debug' )
		);

		// Iterate through each subscription (Form, Tag, Sequence), subscribing the Customer to each.
		foreach ( $subscriptions as $subscription_raw ) {
			list( $subscription['type'], $subscription['id'] ) = explode( ':', $subscription_raw );
			$this->subscribe_customer(
				$subscription['type'],
				(int) $subscription['id'],
				$this->email( $order ),
				$this->name( $order ),
				$order_id,
				$fields
			);
		}

		// The customer was subscribed to ConvertKit, so request a Plugin review.
		// This can safely be called multiple times, as the review request
		// class will ensure once a review request is dismissed by the user,
		// it is never displayed again.
		WP_CKWC()->get_class( 'review_request' )->request_review();

	}

	/**
	 * Subscribe the given email address to the Form, Tag or Sequence, adding an Order Note
	 * for each to the Order.
	 *
	 * @since   1.4.2
	 *
	 * @param   string $resource_type  Resource Type (form|tag|course).
	 * @param   int    $resource_id    Resource ID (Form ID, Tag ID, Sequence ID).
	 * @param   string $email          Email Address.
	 * @param   string $name           Customer Name.
	 * @param   int    $order_id       WooCommerce Order ID.
	 * @param   mixed  $custom_fields  Custom Fields (false | array).
	 * @return  mixed                   WP_Error | array
	 */
	public function subscribe_customer( $resource_type, $resource_id, $email, $name, $order_id, $custom_fields ) {

		// Call API to subscribe the email address to the given Form, Tag or Sequence.
		switch ( $resource_type ) {
			case 'form':
				// Subscribe with inactive state.
				$subscriber = $this->api->create_subscriber( $email, $name, 'inactive', $custom_fields );

				// If an error occured, don't attempt to add the subscriber to the Form, as it won't work.
				if ( is_wp_error( $subscriber ) ) {
					return;
				}

				// For Legacy Forms, a different endpoint is used.
				$forms = new CKWC_Resource_Forms();
				if ( $forms->is_legacy( $resource_id ) ) {
					$result = $this->api->add_subscriber_to_legacy_form( $resource_id, $subscriber['subscriber']['id'] );
					break;
				}

				// Add subscriber to form.
				$result = $this->api->add_subscriber_to_form( $resource_id, $subscriber['subscriber']['id'], home_url() );
				break;

			case 'tag':
				$result = $this->api->tag_subscribe( $resource_id, $email, $name, $custom_fields );
				break;

			case 'sequence':
			case 'course':
				$result = $this->api->sequence_subscribe( $resource_id, $email, $name, $custom_fields );
				break;

			default:
				$result = new WP_Error(
					'convertkit_for_woocommerce_order_subscribe_customer_error',
					sprintf(
						/* translators: Resource Type */
						__( 'Resource type %s is not supported in CKWC_Order::subscribe_customer()', 'woocommerce-convertkit' ),
						$resource_type
					)
				);
				break;
		}

		// If an error occured, bail.
		if ( is_wp_error( $result ) ) {
			wc_create_order_note(
				$order_id,
				$result->get_error_message()
			);
			return;
		}

		// Mark the Customer as being opted in, so future Order status transitions don't opt the Customer in a second time
		// e.g. if the Plugin subscribes the Customer on the 'processing' status, and the Order is then transitioned
		// from processing --> completed --> processing.
		$this->mark_customer_opted_in( $order_id );

		// Create an Order Note so that the Order shows the Customer was subscribed to a Form, Tag or Sequence.
		switch ( $resource_type ) {
			case 'form':
				// Fetch Form from cached resources, so we can output the Form Name in the Order Note.
				$forms = new CKWC_Resource_Forms();
				$form  = $forms->get_by_id( $resource_id );

				// Create Order Note.
				wc_create_order_note(
					$order_id,
					sprintf(
						/* translators: %1$s: Form Name, %2$s: Form ID */
						__( '[Kit] Customer subscribed to the Form: %1$s [%2$s]', 'woocommerce-convertkit' ),
						( $form ? $form['name'] : '' ),
						$resource_id
					)
				);
				break;

			case 'tag':
				// Fetch Tag from cached resources, so we can output the Tag Name in the Order Note.
				$tags = new CKWC_Resource_Tags();
				$tag  = $tags->get_by_id( $resource_id );

				// Create Order Note.
				wc_create_order_note(
					$order_id,
					sprintf(
						/* translators: %1$s: Tag Name, %2$s: Tag ID */
						__( '[Kit] Customer subscribed to the Tag: %1$s [%2$s]', 'woocommerce-convertkit' ),
						( $tag ? $tag['name'] : '' ),
						$resource_id
					)
				);
				break;

			case 'sequence':
			case 'course':
				// Fetch Sequence from cached resources, so we can output the Sequence Name in the Order Note.
				$sequences = new CKWC_Resource_Sequences();
				$sequence  = $sequences->get_by_id( $resource_id );

				// Create Order Note.
				wc_create_order_note(
					$order_id,
					sprintf(
						/* translators: %1$s: Sequence Name, %2$s: Sequence ID */
						__( '[Kit] Customer subscribed to the Sequence: %1$s [%2$s]', 'woocommerce-convertkit' ),
						( $sequence ? $sequence['name'] : '' ),
						$resource_id
					)
				);
				break;
		}

		// Return result.
		return $result;

	}

	/**
	 * Action called when a WooCommerce Order's status is changed, to send purchase
	 * data to ConvertKit for the given WooCommerce Order ID.
	 *
	 * @since   1.6.5
	 *
	 * @param   int    $order_id   WooCommerce Order ID.
	 * @param   string $status_old Order's Old Status.
	 * @param   string $status_new Order's New Status.
	 */
	public function send_purchase_data_action( $order_id, $status_old = 'new', $status_new = 'pending' ) {

		$this->send_purchase_data( $order_id, $status_old, $status_new );

	}

	/**
	 * Send purchase data to ConvertKit for the given WooCommerce Order ID.
	 *
	 * @since   1.4.2
	 *
	 * @param   int    $order_id   WooCommerce Order ID.
	 * @param   string $status_old Order's Old Status.
	 * @param   string $status_new Order's New Status.
	 * @return  mixed               false | WP_Error | array
	 */
	public function send_purchase_data( $order_id, $status_old = 'new', $status_new = 'pending' ) {

		// Bail if the old and new status are the same i.e. the Order status did not change.
		if ( $status_old === $status_new ) {
			return false;
		}

		// Bail if the Purchase Data Event doesn't match the Order's status.
		if ( $this->integration->get_option( 'send_purchases_event' ) !== $status_new ) {
			return false;
		}

		// Get WooCommerce Order.
		$order = wc_get_order( $order_id );

		// If no Order could be fetched, bail.
		if ( ! $order ) {
			return new WP_Error(
				'convertkit_for_woocommerce_error_order_missing',
				sprintf(
					/* translators: WooCommerce Order ID */
					__( 'Order ID #%s could not be found in WooCommerce.', 'woocommerce-convertkit' ),
					$order_id
				)
			);
		}

		// If the Order is a refund, skip it.
		if ( $order instanceof \Automattic\WooCommerce\Admin\Overrides\OrderRefund ) {
			return new WP_Error(
				'convertkit_for_woocommerce_error_order_refund',
				sprintf(
					/* translators: WooCommerce Order ID */
					__( 'Order ID #%s is a refund. Skipping...', 'woocommerce-convertkit' ),
					$order_id
				)
			);
		}

		// If purchase data has already been sent to ConvertKit, don't send any data to ConvertKit.
		// This ensures that we don't unecessarily send data multiple times
		// when the Order's status is transitioned.
		if ( $this->purchase_data_sent( $order_id ) ) {
			return new WP_Error(
				'convertkit_for_woocommerce_error_order_exists',
				sprintf(
					/* translators: WooCommerce Order ID */
					__( 'Order ID #%s has already been sent to Kit.', 'woocommerce-convertkit' ),
					$order_id
				)
			);
		}

		// Build array of Products for the API call.
		$products = array();
		foreach ( $order->get_items() as $item_key => $item ) {
			// If this Order Item's Product could not be found, skip it.
			if ( ! $item->get_product() ) {
				continue;
			}

			// Add Product to array of Products.
			$products[] = array(
				'pid'        => $item->get_product()->get_id(),
				'lid'        => $item_key,
				'name'       => $item->get_name(),
				'sku'        => $item->get_product()->get_sku(),
				'unit_price' => $item->get_product()->get_price(),
				'quantity'   => $item->get_quantity(),
			);
		}

		// If no Products exist, mark purchase data as sent and return.
		if ( ! count( $products ) ) {
			// Mark the purchase data as being sent, so future Order status transitions don't send it again.
			$this->mark_purchase_data_sent( $order, 0 );

			// Add a note to the WooCommerce Order that no purchase data was sent.
			$order->add_order_note( __( '[Kit] Purchase Data skipped, as this Order has no Products', 'woocommerce-convertkit' ) );

			// Return.
			return true;
		}

		// Build API parameters.
		$purchase = array(
			'transaction_id'   => $order->get_order_number(),
			'email_address'    => $order->get_billing_email(),

			// This is deliberate; if the customer has checked the opt in box to subscribe,
			// their name is overwritten by this first_name parameter when purchase data is
			// subsequently sent.  Therefore, we use this classes' name() function to ensure
			// we honor the integration's "Name Format" setting.
			'first_name'       => $this->name( $order ),
			'currency'         => $order->get_currency(),
			'transaction_time' => $order->get_date_created(),
			'subtotal'         => round( floatval( $order->get_subtotal() ), 2 ),
			'tax'              => round( floatval( $order->get_total_tax( 'edit' ) ), 2 ),
			'shipping'         => round( floatval( $order->get_shipping_total( 'edit' ) ), 2 ),
			'discount'         => round( floatval( $order->get_discount_total( 'edit' ) ), 2 ),
			'total'            => round( floatval( $order->get_total( 'edit' ) ), 2 ),
			'status'           => 'paid',
			'products'         => $products,
			'integration'      => 'WooCommerce',
		);

		/**
		 * Define the data to send to the ConvertKit API to create a Purchase in ConvertKit
		 * https://developers.kit.com/#create-a-purchase
		 *
		 * @since   1.4.2
		 *
		 * @param   array  $purchase        Purchase Data.
		 * @param   int    $order_id        WooCommerce Order ID.
		 * @param   string $status_old      Order's Old Status.
		 * @param   string $status_new      Order's New Status.
		 */
		$purchase = apply_filters( 'convertkit_for_woocommerce_order_send_purchase_data', $purchase, $order_id, $status_old, $status_new );

		// Setup the API.
		$this->api = new CKWC_API(
			CKWC_OAUTH_CLIENT_ID,
			CKWC_OAUTH_CLIENT_REDIRECT_URI,
			$this->integration->get_option( 'access_token' ),
			$this->integration->get_option( 'refresh_token' ),
			$this->integration->get_option_bool( 'debug' )
		);

		// Send purchase data to ConvertKit.
		$response = $this->api->create_purchase(
			$purchase['email_address'],
			$purchase['transaction_id'],
			$purchase['products'],
			$purchase['currency'],
			$purchase['first_name'],
			$purchase['status'],
			$purchase['subtotal'],
			$purchase['tax'],
			$purchase['shipping'],
			$purchase['discount'],
			$purchase['total'],
			$purchase['transaction_time']
		);

		// If an error occured sending the purchase data to ConvertKit, add a WooCommerce Order note and bail.
		if ( is_wp_error( $response ) ) {
			$order->add_order_note(
				sprintf(
					/* translators: %1$s: Error Code, %2$s: Error Message */
					__( '[Kit] Send Purchase Data Error: %1$s %2$s', 'woocommerce-convertkit' ),
					$response->get_error_code(),
					$response->get_error_message()
				)
			);

			return $response;
		}

		// Mark the purchase data as being sent, so future Order status transitions don't send it again.
		$this->mark_purchase_data_sent( $order, $response['purchase']['id'] );

		// Add a note to the WooCommerce Order that the purchase data sent successfully.
		$order->add_order_note(
			sprintf(
				/* translators: ConvertKit Purchase ID */
				__( '[Kit] Purchase Data sent successfully: ID [%s]', 'woocommerce-convertkit' ),
				$response['purchase']['id']
			)
		);

		// The customer's purchase data was sent to ConvertKit, so request a Plugin review.
		// This can safely be called multiple times, as the review request
		// class will ensure once a review request is dismissed by the user,
		// it is never displayed again.
		WP_CKWC()->get_class( 'review_request' )->request_review();

		// Check if any custom field data needs to be added to the subscriber.
		$fields = $this->custom_field_data( $order );
		if ( ! count( $fields ) ) {
			return $response;
		}

		// Get subscriber ID by email address.
		$subscriber_id = $this->api->get_subscriber_id( $purchase['email_address'] );

		// If an error occured fetching the subscriber, add a WooCommerce Order note and bail.
		if ( is_wp_error( $subscriber_id ) ) {
			$order->add_order_note(
				sprintf(
					/* translators: %1$s: Error Code, %2$s: Error Message */
					__( '[Kit] Purchase Data: Custom Fields: Get Subscriber Error: %1$s %2$s', 'woocommerce-convertkit' ),
					$subscriber_id->get_error_code(),
					$subscriber_id->get_error_message()
				)
			);

			return $subscriber_id;
		}

		// If no subscriber could be found, add a WooCommerce Order note and bail.
		if ( ! $subscriber_id ) {
			$order->add_order_note(
				sprintf(
					/* translators: %1$s: Error Code, %2$s: Error Message */
					__( '[Kit] Purchase Data: Custom Fields: No subscriber found for email address %s', 'woocommerce-convertkit' ),
					$purchase['email_address']
				)
			);

			return $subscriber_id;
		}

		// Update subscriber with custom field data.
		$response = $this->api->update_subscriber(
			$subscriber_id,
			$purchase['first_name'],
			$purchase['email_address'],
			$fields
		);

		// If an error occured updating the subscriber, add a WooCommerce Order note.
		if ( is_wp_error( $response ) ) {
			$order->add_order_note(
				sprintf(
					/* translators: %1$s: Error Code, %2$s: Error Message */
					__( '[Kit] Purchase Data: Custom Fields: Update Subscriber Error: %1$s %2$s', 'woocommerce-convertkit' ),
					$response->get_error_code(),
					$response->get_error_message()
				)
			);
		}

		// Add a note to the WooCommerce Order that the custom fields data sent successfully.
		$order->add_order_note(
			sprintf(
				/* translators: ConvertKit Subscriber ID */
				__( '[Kit] Purchase Data: Custom Fields sent successfully: Subscriber ID [%s]', 'woocommerce-convertkit' ),
				$subscriber_id
			)
		);

		// Return.
		return $response;

	}

	/**
	 * Returns an array of WooCommerce Orders that have not had their Purchase Data
	 * sent to ConvertKit.
	 *
	 * @since   1.4.3
	 *
	 * @return  mixed   false | array
	 */
	public function get_orders_not_sent_to_convertkit() {

		// Depending on the Purchase Data Event setting, determine the Order statuses that should
		// be included.
		switch ( $this->integration->get_option( 'send_purchases_event' ) ) {
			case 'completed':
				// Only past Orders marked as completed should be included when syncing past Orders.
				// This ensures we don't include Orders created since the Plugin's activation that
				// are marked as Processing and should only be sent once marked as Completed.
				$post_statuses = array(
					'wc-completed',
				);
				break;

			case 'processing':
			default:
				// Any past Orders that are marked as processing or completed should be included when syncing
				// past Orders.
				$post_statuses = array(
					'wc-processing',
					'wc-completed',
				);
				break;
		}

		// Get Orders based on whether HPOS is enabled.
		switch ( get_option( 'woocommerce_custom_orders_table_enabled' ) ) {
			case 'no':
				// Query CPT.
				// We can't use WC_Order_Quey with a meta_query when HPOS is disabled:
				// https://github.com/woocommerce/woocommerce/pull/47457.
				$query = new WP_Query(
					array(
						// Return posts of type `shop_order`.
						'post_type'      => 'shop_order',
						'posts_per_page' => -1,

						// Only include Orders that do not match the Purchase Data Event integration setting.
						'post_status'    => $post_statuses,

						// Only include Orders that do not have a ConvertKit Purchase Data ID.
						'meta_query'     => array(
							array(
								'key'     => 'ckwc_purchase_data_id',
								'compare' => 'NOT EXISTS',
							),
						),

						// Only return Order IDs.
						'fields'         => 'ids',
					)
				);

				// If no Orders exist that have not had their Purchase Data sent to ConvertKit,
				// return false.
				if ( empty( $query->posts ) ) {
					return false;
				}

				// Return the array of Order IDs.
				return $query->posts;

			default:
				// Query HPOS.
				$query = new WC_Order_Query(
					array(
						// Return posts of type `shop_order`.
						'type'       => 'shop_order',
						'limit'      => -1,

						// Only include Orders that do not match the Purchase Data Event integration setting.
						'status'     => $post_statuses,

						// Only include Orders that do not have a ConvertKit Purchase Data ID.
						'meta_query' => array(
							array(
								'key'     => 'ckwc_purchase_data_id',
								'compare' => 'NOT EXISTS',
							),
						),

						// Only return Order IDs.
						'return'     => 'ids',
					)
				);

				// If no Orders exist that have not had their Purchase Data sent to ConvertKit,
				// return false.
				if ( empty( $query->get_orders() ) ) {
					return false;
				}

				// Return the array of Order IDs.
				return $query->get_orders();
		}

	}

	/**
	 * Mark purchase data as being sent to ConvertKit for the given Order ID.
	 *
	 * @since   1.4.2
	 *
	 * @param   WC_Order|WC_Order_Refund $order                         WooCommerce Order.
	 * @param   int                      $convertkit_purchase_data_id   ConvertKit Purchase ID (different from the WooCommerce Order ID, and set by ConvertKit).
	 */
	private function mark_purchase_data_sent( $order, $convertkit_purchase_data_id ) {

		$order->update_meta_data( $this->purchase_data_sent_meta_key, 'yes' );
		$order->update_meta_data( $this->purchase_data_id_meta_key, (string) $convertkit_purchase_data_id );
		$order->save();

	}

	/**
	 * Determines if the given Order has had its purchase data sent to ConvertKit.
	 *
	 * @since   1.4.2
	 *
	 * @param   int $order_id   Order ID.
	 * @return  bool                Purchase Data successfully sent to ConvertKit
	 */
	private function purchase_data_sent( $order_id ) {

		// Get order.
		$order = wc_get_order( $order_id );

		// Return false if Purchase Data Sent Meta Key isn't yes.
		if ( 'yes' !== $order->get_meta( $this->purchase_data_sent_meta_key, true ) ) {
			return false;
		}

		// Return false if Purchase Data ID Meta Key doesn't exist in this Order.
		// This is stored in 1.4.3 and higher, ensuring we have a mapping
		// stored for the WooCommerce Order ID --> ConvertKit Purchase / Transaction ID.
		if ( ! $order->meta_exists( $this->purchase_data_id_meta_key ) ) {
			return false;
		}

		// Purchase data for this Order has previously been sent to ConvertKit.
		return true;

	}

	/**
	 * Mark the Order as having opted in to ConvertKit, so that subsequent status
	 * transitions can check whether the Customer was previously subscribed to
	 * ConvertKit.
	 *
	 * @since   1.4.2
	 *
	 * @param   int $order_id   Order ID.
	 */
	private function mark_customer_opted_in( $order_id ) {

		// Get order.
		$order = wc_get_order( $order_id );

		// Update metadata.
		$order->update_meta_data( 'ckwc_opted_in', 'yes' );
		$order->save();

	}

	/**
	 * Determines if the given Order has the opt in meta value set to 'yes',
	 * and that the Customer has not yet been subscribed to ConvertKit.
	 *
	 * @since   1.4.2
	 *
	 * @param   WC_Order $order   WooCommerce Order.
	 * @return  bool                 Customer can be opted in
	 */
	private function should_opt_in_customer( $order ) {

		// If the Order already opted in the Customer, do not opt them in again.
		if ( 'yes' === $order->get_meta( 'ckwc_opted_in', true ) ) {
			return false;
		}

		// If opt in is anything other than 'yes', do not opt in.
		if ( 'yes' !== $order->get_meta( 'ckwc_opt_in', true ) ) {
			return false;
		}

		// If here, permit opt in.
		$should_opt_in_customer = true;

		/**
		 * Determine if the Customer should be opted in to ConvertKit.
		 * If the Order already opted in the Customer, this filter will not be fired.
		 * If the Order does not permit the Customer be opted in (i.e. they declined at checkout),
		 * this filter will not be fired.
		 *
		 * @since   1.4.4
		 *
		 * @param   bool    $should_opt_in_customer     Should opt in Customer.
		 * @param   int     $order_id                   Order ID.
		 */
		$should_opt_in_customer = apply_filters( 'convertkit_for_woocommerce_order_should_opt_in_customer', $should_opt_in_customer, $order->get_id() );

		// Return.
		return $should_opt_in_customer;

	}

	/**
	 * Returns the customer's email address for the given WooCommerce Order,
	 * immediately before it is sent to ConvertKit when subscribing the Customer
	 * to a Form, Tag or Sequence.
	 *
	 * @since   1.0.0
	 *
	 * @param   WC_Order|WC_Order_Refund $order  Order.
	 * @return  string                              Email Address
	 */
	private function email( $order ) {

		// Get Email.
		$email = $order->get_billing_email();

		/**
		 * Returns the customer's email address for the given WooCommerce Order,
		 * immediately before it is sent to ConvertKit when subscribing the Customer
		 * to a Form, Tag or Sequence.
		 *
		 * @since   1.0.0
		 *
		 * @param   string                      $email  Email Address
		 * @param   WC_Order|WC_Order_Refund    $order  Order
		 */
		$email = apply_filters( 'convertkit_for_woocommerce_email', $email, $order );

		// Return.
		return $email;

	}

	/**
	 * Returns the customer's name for the given WooCommerce Order, based on the Plugin's
	 * Name Format setting (First Name, Last Name or First + Last Name),
	 * immediately before it is sent to ConvertKit when subscribing the Customer
	 * to a Form, Tag or Sequence, or sending Purchase Data.
	 *
	 * @since   1.0.0
	 *
	 * @param   WC_Order|WC_Order_Refund $order  Order.
	 * @return  string                              Email Address
	 */
	private function name( $order ) {

		switch ( $this->integration->get_option( 'name_format' ) ) {

			/**
			 * First Name
			 */
			case 'first':
				$name = $this->first_name( $order );
				break;

			/**
			 * Last Name
			 */
			case 'last':
				$name = $this->last_name( $order );
				break;

			/**
			 * First and Last Name
			 */
			default:
				$name = sprintf( '%s %s', $this->first_name( $order ), $this->last_name( $order ) );
				break;

		}

		/**
		 * Returns the customer's name for the given WooCommerce Order,
		 * immediately before it is sent to ConvertKit when subscribing the Customer
		 * to a Form, Tag or Sequence, or sending Purchase Data.
		 *
		 * @since   1.0.0
		 *
		 * @param   string                      $name   Name
		 * @param   WC_Order|WC_Order_Refund    $order  Order
		 */
		$name = apply_filters( 'convertkit_for_woocommerce_order_name', $name, $order );

		// Return.
		return $name;

	}

	/**
	 * Returns the customer's first name for the given WooCommerce Order,
	 * immediately before it is sent to ConvertKit when subscribing the Customer
	 * to a Form, Tag or Sequence.
	 *
	 * @since   1.0.0
	 *
	 * @param   WC_Order|WC_Order_Refund $order  Order.
	 * @return  string                              Email Address
	 */
	private function first_name( $order ) {

		// Get First Name.
		$first_name = $order->get_billing_first_name();

		/**
		 * Returns the customer's first name for the given WooCommerce Order,
		 * immediately before it is sent to ConvertKit when subscribing the Customer
		 * to a Form, Tag or Sequence.
		 *
		 * @since   1.0.0
		 *
		 * @param   string                      $first_name     First Name
		 * @param   WC_Order|WC_Order_Refund    $order          Order
		 */
		$first_name = apply_filters( 'convertkit_for_woocommerce_first_name', $first_name, $order );

		// Return.
		return $first_name;

	}

	/**
	 * Returns the customer's last name for the given WooCommerce Order,
	 * immediately before it is sent to ConvertKit when subscribing the Customer
	 * to a Form, Tag or Sequence.
	 *
	 * @since   1.0.0
	 *
	 * @param   WC_Order|WC_Order_Refund $order  Order.
	 * @return  string                              Email Address
	 */
	private function last_name( $order ) {

		// Get Last Name.
		$last_name = $order->get_billing_last_name();

		/**
		 * Returns the customer's last name for the given WooCommerce Order,
		 * immediately before it is sent to ConvertKit when subscribing the Customer
		 * to a Form, Tag or Sequence.
		 *
		 * @since   1.0.0
		 *
		 * @param   string                      $last_name      Last Name
		 * @param   WC_Order|WC_Order_Refund    $order          Order
		 */
		$last_name = apply_filters( 'convertkit_for_woocommerce_last_name', $last_name, $order );

		// Return.
		return $last_name;

	}

	/**
	 * Returns an array of ConvertKit Custom Field Key/Value pairs, with values
	 * comprising of Order data based, to be sent to ConvertKit when an Order's
	 * Customer is subscribed via a Form, Tag or Sequence.
	 *
	 * Returns false if no Order data should be stored in ConvertKit Custom Fields.
	 *
	 * @since   1.4.3
	 *
	 * @param   WC_Order|WC_Order_Refund $order  Order.
	 * @return  array
	 */
	private function custom_field_data( $order ) {

		$fields = array();

		// If the name and company name should be excluded from the billing and shipping address
		// fetched using get_formatted_billing_address() / get_formatted_shipping_address(),
		// add filters now.
		if ( $this->integration->get_option_bool( 'custom_field_address_exclude_name' ) ) {
			add_filter( 'woocommerce_order_formatted_billing_address', array( $this, 'remove_name_from_address' ) );
			add_filter( 'woocommerce_order_formatted_shipping_address', array( $this, 'remove_name_from_address' ) );
		}

		if ( $this->integration->get_option( 'custom_field_last_name' ) ) {
			$fields[ $this->integration->get_option( 'custom_field_last_name' ) ] = $order->get_billing_last_name();
		}
		if ( $this->integration->get_option( 'custom_field_phone' ) ) {
			$fields[ $this->integration->get_option( 'custom_field_phone' ) ] = $order->get_billing_phone();
		}
		if ( $this->integration->get_option( 'custom_field_billing_address' ) ) {
			$fields[ $this->integration->get_option( 'custom_field_billing_address' ) ] = str_replace( '<br/>', ', ', $order->get_formatted_billing_address() );
		}
		if ( $this->integration->get_option( 'custom_field_shipping_address' ) ) {
			$fields[ $this->integration->get_option( 'custom_field_shipping_address' ) ] = str_replace( '<br/>', ', ', $order->get_formatted_shipping_address() );
		}
		if ( $this->integration->get_option( 'custom_field_payment_method' ) ) {
			$fields[ $this->integration->get_option( 'custom_field_payment_method' ) ] = $order->get_payment_method();
		}
		if ( $this->integration->get_option( 'custom_field_customer_note' ) ) {
			$fields[ $this->integration->get_option( 'custom_field_customer_note' ) ] = $order->get_customer_note();
		}

		// If the name and company name should be excluded from the billing and shipping address
		// fetched using get_formatted_billing_address() / get_formatted_shipping_address(),
		// remove filters now so these WooCommerce functions work correctly for other Plugins.
		if ( $this->integration->get_option_bool( 'custom_field_address_exclude_name' ) ) {
			remove_filter( 'woocommerce_order_formatted_billing_address', array( $this, 'remove_name_from_address' ) );
			remove_filter( 'woocommerce_order_formatted_shipping_address', array( $this, 'remove_name_from_address' ) );
		}

		/**
		 * Returns an array of ConvertKit Custom Field Key/Value pairs, with values
		 * comprising of Order data based, to be sent to ConvertKit when an Order's
		 * Customer is subscribed via a Form, Tag or Sequence.
		 *
		 * Returns false if no Order data should be stored in ConvertKit Custom Fields.
		 *
		 * @since   1.4.3
		 *
		 * @param   array                       $fields     Custom Field Key/Value pairs (false | array).
		 * @param   WC_Order|WC_Order_Refund    $order      WooCommerce Order.
		 */
		$fields = apply_filters( 'convertkit_for_woocommerce_custom_field_data', $fields, $order );

		return $fields;

	}

	/**
	 * Removes the first name, last name and company name from the WooCommerce Order address,
	 * when calling WC_Order->get_formatted_billing_address() and WC_Order->get_formatted_shipping_address().
	 *
	 * @since   1.8.5
	 *
	 * @param   array $address    Billing or Shipping Address.
	 * @return  array
	 */
	public function remove_name_from_address( $address ) {

		unset( $address['first_name'], $address['last_name'], $address['company_name'] );
		return $address;

	}

}
