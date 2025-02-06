<h1>Filters</h1><table>
				<thead>
					<tr>
						<th>File</th>
						<th>Filter Name</th>
						<th>Description</th>
					</tr>
				</thead>
				<tbody><tr>
						<td colspan="3">../includes/class-ckwc-checkout.php</td>
					</tr><tr>
						<td>&nbsp;</td>
						<td><a href="#convertkit_for_woocommerce_checkout_add_opt_in_checkbox"><code>convertkit_for_woocommerce_checkout_add_opt_in_checkbox</code></a></td>
						<td>Adds the opt-in checkbox to the checkout's billing or order section, based on the Plugin's settings.</td>
					</tr><tr>
						<td colspan="3">../includes/class-ckwc-order.php</td>
					</tr><tr>
						<td>&nbsp;</td>
						<td><a href="#convertkit_for_woocommerce_order_maybe_subscribe_customer_resource_id"><code>convertkit_for_woocommerce_order_maybe_subscribe_customer_resource_id</code></a></td>
						<td>Define the Form, Tag or Sequence ID to subscribe the Customer to for the given Product.</td>
					</tr><tr>
						<td>&nbsp;</td>
						<td><a href="#convertkit_for_woocommerce_order_maybe_subscribe_customer_resource_id_coupon"><code>convertkit_for_woocommerce_order_maybe_subscribe_customer_resource_id_coupon</code></a></td>
						<td>Define the Form, Tag or Sequence ID to subscribe the Customer to for the given Coupon.</td>
					</tr><tr>
						<td>&nbsp;</td>
						<td><a href="#convertkit_for_woocommerce_order_maybe_subscribe_customer_subscriptions"><code>convertkit_for_woocommerce_order_maybe_subscribe_customer_subscriptions</code></a></td>
						<td>Define the Forms, Tags and/or Sequences to subscribe the Customer to for this Order.</td>
					</tr><tr>
						<td>&nbsp;</td>
						<td><a href="#convertkit_for_woocommerce_order_send_purchase_data"><code>convertkit_for_woocommerce_order_send_purchase_data</code></a></td>
						<td>Define the data to send to the ConvertKit API to create a Purchase in ConvertKit https://developers.kit.com/#create-a-purchase</td>
					</tr><tr>
						<td>&nbsp;</td>
						<td><a href="#convertkit_for_woocommerce_order_should_opt_in_customer"><code>convertkit_for_woocommerce_order_should_opt_in_customer</code></a></td>
						<td>Determine if the Customer should be opted in to ConvertKit. If the Order already opted in the Customer, this filter will not be fired. If the Order does not permit the Customer be opted in (i.e. they declined at checkout), this filter will not be fired.</td>
					</tr><tr>
						<td>&nbsp;</td>
						<td><a href="#convertkit_for_woocommerce_email"><code>convertkit_for_woocommerce_email</code></a></td>
						<td>Returns the customer's email address for the given WooCommerce Order, immediately before it is sent to ConvertKit when subscribing the Customer to a Form, Tag or Sequence.</td>
					</tr><tr>
						<td>&nbsp;</td>
						<td><a href="#convertkit_for_woocommerce_order_name"><code>convertkit_for_woocommerce_order_name</code></a></td>
						<td>Returns the customer's name for the given WooCommerce Order, immediately before it is sent to ConvertKit when subscribing the Customer to a Form, Tag or Sequence, or sending Purchase Data.</td>
					</tr><tr>
						<td>&nbsp;</td>
						<td><a href="#convertkit_for_woocommerce_first_name"><code>convertkit_for_woocommerce_first_name</code></a></td>
						<td>Returns the customer's first name for the given WooCommerce Order, immediately before it is sent to ConvertKit when subscribing the Customer to a Form, Tag or Sequence.</td>
					</tr><tr>
						<td>&nbsp;</td>
						<td><a href="#convertkit_for_woocommerce_last_name"><code>convertkit_for_woocommerce_last_name</code></a></td>
						<td>Returns the customer's last name for the given WooCommerce Order, immediately before it is sent to ConvertKit when subscribing the Customer to a Form, Tag or Sequence.</td>
					</tr><tr>
						<td>&nbsp;</td>
						<td><a href="#convertkit_for_woocommerce_custom_field_data"><code>convertkit_for_woocommerce_custom_field_data</code></a></td>
						<td>Returns an array of ConvertKit Custom Field Key/Value pairs, with values comprising of Order data based, to be sent to ConvertKit when an Order's Customer is subscribed via a Form, Tag or Sequence. Returns false if no Order data should be stored in ConvertKit Custom Fields.</td>
					</tr>
					</tbody>
				</table><h3 id="convertkit_for_woocommerce_checkout_add_opt_in_checkbox">
						convertkit_for_woocommerce_checkout_add_opt_in_checkbox
						<code>includes/class-ckwc-checkout.php::160</code>
					</h3><h4>Overview</h4>
						<p>Adds the opt-in checkbox to the checkout's billing or order section, based on the Plugin's settings.</p><h4>Parameters</h4>
					<table>
						<thead>
							<tr>
								<th>Parameter</th>
								<th>Type</th>
								<th>Description</th>
							</tr>
						</thead>
						<tbody><tr>
							<td>Checkout</td>
							<td>array $fields</td>
							<td>Fields.</td>
						</tr>
						</tbody>
					</table><h4>Usage</h4>
<pre>
add_filter( 'convertkit_for_woocommerce_checkout_add_opt_in_checkbox', function( $fields ) {
	// ... your code here
	// Return value
	return $fields;
}, 10, 1 );
</pre>
<h3 id="convertkit_for_woocommerce_order_maybe_subscribe_customer_resource_id">
						convertkit_for_woocommerce_order_maybe_subscribe_customer_resource_id
						<code>includes/class-ckwc-order.php::149</code>
					</h3><h4>Overview</h4>
						<p>Define the Form, Tag or Sequence ID to subscribe the Customer to for the given Product.</p><h4>Parameters</h4>
					<table>
						<thead>
							<tr>
								<th>Parameter</th>
								<th>Type</th>
								<th>Description</th>
							</tr>
						</thead>
						<tbody><tr>
							<td>$resource_id</td>
							<td>mixed</td>
							<td>Form, Tag or Sequence ID | empty string.</td>
						</tr><tr>
							<td>$order_id</td>
							<td>int</td>
							<td>WooCommerce Order ID.</td>
						</tr><tr>
							<td>Order's</td>
							<td>string $status_new</td>
							<td>New</td>
						</tr><tr>
							<td>$product_id</td>
							<td>int</td>
							<td>Product ID.</td>
						</tr>
						</tbody>
					</table><h4>Usage</h4>
<pre>
add_filter( 'convertkit_for_woocommerce_order_maybe_subscribe_customer_resource_id', function( $resource_id, $order_id, $status_old, $status_new, $product->get_id( ) {
	// ... your code here
	// Return value
	return $resource_id;
}, 10, 5 );
</pre>
<h3 id="convertkit_for_woocommerce_order_maybe_subscribe_customer_resource_id_coupon">
						convertkit_for_woocommerce_order_maybe_subscribe_customer_resource_id_coupon
						<code>includes/class-ckwc-order.php::179</code>
					</h3><h4>Overview</h4>
						<p>Define the Form, Tag or Sequence ID to subscribe the Customer to for the given Coupon.</p><h4>Parameters</h4>
					<table>
						<thead>
							<tr>
								<th>Parameter</th>
								<th>Type</th>
								<th>Description</th>
							</tr>
						</thead>
						<tbody><tr>
							<td>$resource_id</td>
							<td>mixed</td>
							<td>Form, Tag or Sequence ID | empty string.</td>
						</tr><tr>
							<td>$order_id</td>
							<td>int</td>
							<td>WooCommerce Order ID.</td>
						</tr><tr>
							<td>Order's</td>
							<td>string $status_new</td>
							<td>New</td>
						</tr><tr>
							<td>$coupon_id</td>
							<td>int</td>
							<td>Coupon ID.</td>
						</tr>
						</tbody>
					</table><h4>Usage</h4>
<pre>
add_filter( 'convertkit_for_woocommerce_order_maybe_subscribe_customer_resource_id_coupon', function( $resource_id, $order_id, $status_old, $status_new, $coupon->get_id( ) {
	// ... your code here
	// Return value
	return $resource_id;
}, 10, 5 );
</pre>
<h3 id="convertkit_for_woocommerce_order_maybe_subscribe_customer_subscriptions">
						convertkit_for_woocommerce_order_maybe_subscribe_customer_subscriptions
						<code>includes/class-ckwc-order.php::203</code>
					</h3><h4>Overview</h4>
						<p>Define the Forms, Tags and/or Sequences to subscribe the Customer to for this Order.</p><h4>Parameters</h4>
					<table>
						<thead>
							<tr>
								<th>Parameter</th>
								<th>Type</th>
								<th>Description</th>
							</tr>
						</thead>
						<tbody><tr>
							<td>$subscriptions</td>
							<td>array</td>
							<td>Subscriptions (array of Forms, Tags and/or Sequence IDs).</td>
						</tr><tr>
							<td>$order_id</td>
							<td>int</td>
							<td>WooCommerce Order ID.</td>
						</tr><tr>
							<td>Order's</td>
							<td>string $status_new</td>
							<td>New</td>
						</tr>
						</tbody>
					</table><h4>Usage</h4>
<pre>
add_filter( 'convertkit_for_woocommerce_order_maybe_subscribe_customer_subscriptions', function( $subscriptions, $order_id, $status_old, $status_new ) {
	// ... your code here
	// Return value
	return $subscriptions;
}, 10, 4 );
</pre>
<h3 id="convertkit_for_woocommerce_order_send_purchase_data">
						convertkit_for_woocommerce_order_send_purchase_data
						<code>includes/class-ckwc-order.php::511</code>
					</h3><h4>Overview</h4>
						<p>Define the data to send to the ConvertKit API to create a Purchase in ConvertKit https://developers.kit.com/#create-a-purchase</p><h4>Parameters</h4>
					<table>
						<thead>
							<tr>
								<th>Parameter</th>
								<th>Type</th>
								<th>Description</th>
							</tr>
						</thead>
						<tbody><tr>
							<td>$purchase</td>
							<td>array</td>
							<td>Purchase Data.</td>
						</tr><tr>
							<td>$order_id</td>
							<td>int</td>
							<td>WooCommerce Order ID.</td>
						</tr><tr>
							<td>Order's</td>
							<td>string $status_new</td>
							<td>New</td>
						</tr>
						</tbody>
					</table><h4>Usage</h4>
<pre>
add_filter( 'convertkit_for_woocommerce_order_send_purchase_data', function( $purchase, $order_id, $status_old, $status_new ) {
	// ... your code here
	// Return value
	return $purchase;
}, 10, 4 );
</pre>
<h3 id="convertkit_for_woocommerce_order_should_opt_in_customer">
						convertkit_for_woocommerce_order_should_opt_in_customer
						<code>includes/class-ckwc-order.php::862</code>
					</h3><h4>Overview</h4>
						<p>Determine if the Customer should be opted in to ConvertKit. If the Order already opted in the Customer, this filter will not be fired. If the Order does not permit the Customer be opted in (i.e. they declined at checkout), this filter will not be fired.</p><h4>Parameters</h4>
					<table>
						<thead>
							<tr>
								<th>Parameter</th>
								<th>Type</th>
								<th>Description</th>
							</tr>
						</thead>
						<tbody><tr>
							<td>$should_opt_in_customer</td>
							<td>bool</td>
							<td>Should opt in Customer.</td>
						</tr><tr>
							<td>$order_id</td>
							<td>int</td>
							<td>Order ID.</td>
						</tr>
						</tbody>
					</table><h4>Usage</h4>
<pre>
add_filter( 'convertkit_for_woocommerce_order_should_opt_in_customer', function( $should_opt_in_customer, $order->get_id( ) {
	// ... your code here
	// Return value
	return $should_opt_in_customer;
}, 10, 2 );
</pre>
<h3 id="convertkit_for_woocommerce_email">
						convertkit_for_woocommerce_email
						<code>includes/class-ckwc-order.php::894</code>
					</h3><h4>Overview</h4>
						<p>Returns the customer's email address for the given WooCommerce Order, immediately before it is sent to ConvertKit when subscribing the Customer to a Form, Tag or Sequence.</p><h4>Parameters</h4>
					<table>
						<thead>
							<tr>
								<th>Parameter</th>
								<th>Type</th>
								<th>Description</th>
							</tr>
						</thead>
						<tbody><tr>
							<td>$email</td>
							<td>string</td>
							<td>Email Address</td>
						</tr><tr>
							<td>$order</td>
							<td>WC_Order|WC_Order_Refund</td>
							<td>Order</td>
						</tr>
						</tbody>
					</table><h4>Usage</h4>
<pre>
add_filter( 'convertkit_for_woocommerce_email', function( $email, $order ) {
	// ... your code here
	// Return value
	return $email;
}, 10, 2 );
</pre>
<h3 id="convertkit_for_woocommerce_order_name">
						convertkit_for_woocommerce_order_name
						<code>includes/class-ckwc-order.php::949</code>
					</h3><h4>Overview</h4>
						<p>Returns the customer's name for the given WooCommerce Order, immediately before it is sent to ConvertKit when subscribing the Customer to a Form, Tag or Sequence, or sending Purchase Data.</p><h4>Parameters</h4>
					<table>
						<thead>
							<tr>
								<th>Parameter</th>
								<th>Type</th>
								<th>Description</th>
							</tr>
						</thead>
						<tbody><tr>
							<td>$name</td>
							<td>string</td>
							<td>Name</td>
						</tr><tr>
							<td>$order</td>
							<td>WC_Order|WC_Order_Refund</td>
							<td>Order</td>
						</tr>
						</tbody>
					</table><h4>Usage</h4>
<pre>
add_filter( 'convertkit_for_woocommerce_order_name', function( $name, $order ) {
	// ... your code here
	// Return value
	return $name;
}, 10, 2 );
</pre>
<h3 id="convertkit_for_woocommerce_first_name">
						convertkit_for_woocommerce_first_name
						<code>includes/class-ckwc-order.php::981</code>
					</h3><h4>Overview</h4>
						<p>Returns the customer's first name for the given WooCommerce Order, immediately before it is sent to ConvertKit when subscribing the Customer to a Form, Tag or Sequence.</p><h4>Parameters</h4>
					<table>
						<thead>
							<tr>
								<th>Parameter</th>
								<th>Type</th>
								<th>Description</th>
							</tr>
						</thead>
						<tbody><tr>
							<td>$first_name</td>
							<td>string</td>
							<td>First Name</td>
						</tr><tr>
							<td>$order</td>
							<td>WC_Order|WC_Order_Refund</td>
							<td>Order</td>
						</tr>
						</tbody>
					</table><h4>Usage</h4>
<pre>
add_filter( 'convertkit_for_woocommerce_first_name', function( $first_name, $order ) {
	// ... your code here
	// Return value
	return $first_name;
}, 10, 2 );
</pre>
<h3 id="convertkit_for_woocommerce_last_name">
						convertkit_for_woocommerce_last_name
						<code>includes/class-ckwc-order.php::1013</code>
					</h3><h4>Overview</h4>
						<p>Returns the customer's last name for the given WooCommerce Order, immediately before it is sent to ConvertKit when subscribing the Customer to a Form, Tag or Sequence.</p><h4>Parameters</h4>
					<table>
						<thead>
							<tr>
								<th>Parameter</th>
								<th>Type</th>
								<th>Description</th>
							</tr>
						</thead>
						<tbody><tr>
							<td>$last_name</td>
							<td>string</td>
							<td>Last Name</td>
						</tr><tr>
							<td>$order</td>
							<td>WC_Order|WC_Order_Refund</td>
							<td>Order</td>
						</tr>
						</tbody>
					</table><h4>Usage</h4>
<pre>
add_filter( 'convertkit_for_woocommerce_last_name', function( $last_name, $order ) {
	// ... your code here
	// Return value
	return $last_name;
}, 10, 2 );
</pre>
<h3 id="convertkit_for_woocommerce_custom_field_data">
						convertkit_for_woocommerce_custom_field_data
						<code>includes/class-ckwc-order.php::1083</code>
					</h3><h4>Overview</h4>
						<p>Returns an array of ConvertKit Custom Field Key/Value pairs, with values comprising of Order data based, to be sent to ConvertKit when an Order's Customer is subscribed via a Form, Tag or Sequence. Returns false if no Order data should be stored in ConvertKit Custom Fields.</p><h4>Parameters</h4>
					<table>
						<thead>
							<tr>
								<th>Parameter</th>
								<th>Type</th>
								<th>Description</th>
							</tr>
						</thead>
						<tbody><tr>
							<td>$fields</td>
							<td>array</td>
							<td>Custom Field Key/Value pairs (false | array).</td>
						</tr><tr>
							<td>$order</td>
							<td>WC_Order|WC_Order_Refund</td>
							<td>WooCommerce Order.</td>
						</tr>
						</tbody>
					</table><h4>Usage</h4>
<pre>
add_filter( 'convertkit_for_woocommerce_custom_field_data', function( $fields, $order ) {
	// ... your code here
	// Return value
	return $fields;
}, 10, 2 );
</pre>
<h1>Actions</h1><table>
				<thead>
					<tr>
						<th>File</th>
						<th>Filter Name</th>
						<th>Description</th>
					</tr>
				</thead>
				<tbody><tr>
						<td colspan="3">../includes/class-wp-ckwc.php</td>
					</tr><tr>
						<td>&nbsp;</td>
						<td><a href="#convertkit_for_woocommerce_initialize_admin"><code>convertkit_for_woocommerce_initialize_admin</code></a></td>
						<td></td>
					</tr><tr>
						<td>&nbsp;</td>
						<td><a href="#convertkit_for_woocommerce_initialize_cli"><code>convertkit_for_woocommerce_initialize_cli</code></a></td>
						<td></td>
					</tr><tr>
						<td>&nbsp;</td>
						<td><a href="#convertkit_for_woocommerce_initialize_frontend"><code>convertkit_for_woocommerce_initialize_frontend</code></a></td>
						<td></td>
					</tr><tr>
						<td>&nbsp;</td>
						<td><a href="#convertkit_for_woocommerce_initialize_global"><code>convertkit_for_woocommerce_initialize_global</code></a></td>
						<td></td>
					</tr>
					</tbody>
				</table><h3 id="convertkit_for_woocommerce_initialize_admin">
						convertkit_for_woocommerce_initialize_admin
						<code>includes/class-wp-ckwc.php::173</code>
					</h3><h4>Parameters</h4>
					<table>
						<thead>
							<tr>
								<th>Parameter</th>
								<th>Type</th>
								<th>Description</th>
							</tr>
						</thead>
						<tbody>
						</tbody>
					</table><h4>Usage</h4>
<pre>
do_action( 'convertkit_for_woocommerce_initialize_admin', function(  ) {
	// ... your code here
}, 10, 0 );
</pre>
<h3 id="convertkit_for_woocommerce_initialize_cli">
						convertkit_for_woocommerce_initialize_cli
						<code>includes/class-wp-ckwc.php::220</code>
					</h3><h4>Parameters</h4>
					<table>
						<thead>
							<tr>
								<th>Parameter</th>
								<th>Type</th>
								<th>Description</th>
							</tr>
						</thead>
						<tbody>
						</tbody>
					</table><h4>Usage</h4>
<pre>
do_action( 'convertkit_for_woocommerce_initialize_cli', function(  ) {
	// ... your code here
}, 10, 0 );
</pre>
<h3 id="convertkit_for_woocommerce_initialize_frontend">
						convertkit_for_woocommerce_initialize_frontend
						<code>includes/class-wp-ckwc.php::241</code>
					</h3><h4>Parameters</h4>
					<table>
						<thead>
							<tr>
								<th>Parameter</th>
								<th>Type</th>
								<th>Description</th>
							</tr>
						</thead>
						<tbody>
						</tbody>
					</table><h4>Usage</h4>
<pre>
do_action( 'convertkit_for_woocommerce_initialize_frontend', function(  ) {
	// ... your code here
}, 10, 0 );
</pre>
<h3 id="convertkit_for_woocommerce_initialize_global">
						convertkit_for_woocommerce_initialize_global
						<code>includes/class-wp-ckwc.php::264</code>
					</h3><h4>Parameters</h4>
					<table>
						<thead>
							<tr>
								<th>Parameter</th>
								<th>Type</th>
								<th>Description</th>
							</tr>
						</thead>
						<tbody>
						</tbody>
					</table><h4>Usage</h4>
<pre>
do_action( 'convertkit_for_woocommerce_initialize_global', function(  ) {
	// ... your code here
}, 10, 0 );
</pre>
