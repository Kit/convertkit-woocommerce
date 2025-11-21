<?php
namespace Tests\Support\Helper;

/**
 * Helper methods and actions related to the ConvertKit API,
 * which are then available using $I->{yourFunctionName}.
 *
 * @since   1.4.2
 */
class KitAPI extends \Codeception\Module
{
	/**
	 * Returns an encoded `state` parameter compatible with OAuth.
	 *
	 * @since   2.5.0
	 *
	 * @param   string $returnTo   Return URL.
	 * @param   string $clientID   OAuth Client ID.
	 * @return  string
	 */
	public function apiEncodeState($returnTo, $clientID)
	{
		$str = json_encode( // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
			array(
				'return_to' => $returnTo,
				'client_id' => $clientID,
			)
		);

		// Encode to Base64 string.
		$str = base64_encode( $str ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions

		// Convert Base64 to Base64URL by replacing “+” with “-” and “/” with “_”.
		$str = strtr( $str, '+/', '-_' );

		// Remove padding character from the end of line.
		$str = rtrim( $str, '=' );

		return $str;
	}

	/**
	 * Check the given email address exists as a subscriber on ConvertKit.
	 *
	 * @param   EndToEndTester $I             EndToEndTester.
	 * @param   string         $emailAddress   Email Address.
	 * @param   mixed          $firstName      Name (false = don't check name matches).
	 * @return  array                           Subscriber
	 */
	public function apiCheckSubscriberExists($I, $emailAddress, $firstName = false)
	{
		// Wait a second to ensure the subscriber has been created.
		$I->wait(1);

		// Run request.
		$results = $this->apiRequest(
			'subscribers',
			'GET',
			[
				'email_address'       => $emailAddress,
				'include_total_count' => true,

				// Check all subscriber states.
				'status'              => 'all',
			]
		);

		// Check at least one subscriber was returned and it matches the email address.
		$I->assertGreaterThan(0, $results['pagination']['total_count']);
		$I->assertEquals($emailAddress, $results['subscribers'][0]['email_address']);

		// If defined, check that the name matches for the subscriber.
		if ($firstName) {
			$I->assertEquals($firstName, $results['subscribers'][0]['first_name']);
		}

		return $results['subscribers'][0];
	}

	/**
	 * Check the given email address does not exists as a subscriber.
	 *
	 * @param   EndToEndTester $I             EndToEndTester.
	 * @param   string         $emailAddress   Email Address.
	 */
	public function apiCheckSubscriberDoesNotExist($I, $emailAddress)
	{
		// Run request.
		$results = $this->apiRequest(
			'subscribers',
			'GET',
			[
				'email_address'       => $emailAddress,
				'include_total_count' => true,
			]
		);

		// Check no subscribers are returned by this request.
		$I->assertEquals(0, $results['pagination']['total_count']);
	}

	/**
	 * Check the given subscriber ID has been assigned to the given form ID.
	 *
	 * @since   1.9.1
	 *
	 * @param   EndToEndTester $I             EndToEndTester.
	 * @param   int            $subscriberID  Subscriber ID.
	 * @param   int            $formID        Form ID.
	 * @param   string         $referrer      Referrer.
	 */
	public function apiCheckSubscriberHasForm($I, $subscriberID, $formID, $referrer = false)
	{
		// Run request.
		$results = $this->apiRequest(
			'forms/' . $formID . '/subscribers',
			'GET',
			[
				// Check all subscriber states.
				'status'   => 'all',
				'per_page' => 20,
			]
		);

		// Iterate through subscribers.
		$subscriberHasForm = false;
		foreach ($results['subscribers'] as $subscriber) {
			if ($subscriber['id'] === $subscriberID) {
				$subscriberHasForm = true;
				break;
			}
		}

		// Assert if the subscriber has the form.
		$this->assertTrue($subscriberHasForm);

		// If a referrer is specified, assert it matches the subscriber's referrer now.
		if ($referrer) {
			$I->assertEquals($subscriber['referrer'], $referrer);
		}
	}

	/**
	 * Check the given order ID exists as a purchase on ConvertKit.
	 *
	 * @param   EndToEndTester $I             EndToEndTester.
	 * @param   int            $orderID        Order ID.
	 * @param   string         $emailAddress   Email Address.
	 * @param   int            $productID      Product ID.
	 * @return  int                              ConvertKit ID.
	 */
	public function apiCheckPurchaseExists($I, $orderID, $emailAddress, $productID)
	{
		// Run request.
		$purchase = $this->apiExtractPurchaseFromPurchases($this->apiGetPurchases(), $orderID);

		// Check data returned for this Order ID.
		$I->assertIsArray($purchase);
		$I->assertEquals($orderID, $purchase['transaction_id']);
		$I->assertEquals($emailAddress, $purchase['email_address']);

		// Iterate through the array of products, to find a pid matching the Product ID.
		$productExistsInPurchase = false;
		foreach ($purchase['products'] as $product) {
			if ($productID === (int) $product['pid']) {
				$productExistsInPurchase = true;
				break;
			}
		}

		// Check that the Product exists in the purchase data.
		$I->assertTrue($productExistsInPurchase);

		// Return the ConvertKit ID.
		return $purchase['id'];
	}

	/**
	 * Check the given order ID does not exist as a purchase on ConvertKit.
	 *
	 * @param   EndToEndTester $I             EndToEndTester.
	 * @param   int            $orderID        Order ID.
	 * @param   string         $emailAddress   Email Address.
	 */
	public function apiCheckPurchaseDoesNotExist($I, $orderID, $emailAddress)
	{
		// Run request.
		$purchase = $this->apiExtractPurchaseFromPurchases($this->apiGetPurchases(), $orderID);

		// Check data not returned for this Order ID.
		// We check the email address, because each test will reset, meaning the Order ID will match that
		// of a previous test, and therefore the API will return data from an existing test.
		$I->assertIsArray($purchase);
		$I->assertNotEquals($emailAddress, $purchase['email_address']);
	}

	/**
	 * Returns a Purchase from the /purchases API endpoint based on the given Order ID (transaction_id).
	 *
	 * We cannot use /purchases/{id} as {id} is the ConvertKit ID, not the WooCommerce Order ID (which
	 * is stored in the transaction_id).
	 *
	 * @param   array $purchases  Purchases Data.
	 * @param   int   $orderID    Order ID.
	 * @return  array
	 */
	private function apiExtractPurchaseFromPurchases($purchases, $orderID)
	{
		// Bail if no purchases exist.
		if ( ! isset($purchases)) {
			return [
				'id'            => 0,
				'order_id'      => 0,
				'email_address' => 'no',
			];
		}

		// Iterate through purchases to find one where the transaction ID matches the order ID.
		foreach ($purchases as $purchase) {
			// Skip if order ID does not match.
			if ($purchase['transaction_id'] !== $orderID) {
				continue;
			}

			return $purchase;
		}

		// No purchase exists with the given order ID. Return a blank array.
		return [
			'id'            => 0,
			'order_id'      => 0,
			'email_address' => 'no',
		];
	}

	/**
	 * Returns the first 50 purchases from the API.
	 *
	 * @return  array
	 */
	public function apiGetPurchases()
	{
		$purchases = $this->apiRequest('purchases', 'GET');
		return $purchases['purchases'];
	}

	/**
	 * Unsubscribes the given subscriber ID. Useful for clearing the API
	 * between tests.
	 *
	 * @param   int $id Subscriber ID.
	 */
	public function apiUnsubscribe($id)
	{
		// Run request.
		$this->apiRequest('subscribers/' . $id . '/unsubscribe', 'POST');
	}

	/**
	 * Check the subscriber array's custom field data is valid.
	 *
	 * @param   EndToEndTester $I                         EndToEndTester.
	 * @param   array          $subscriber                Subscriber from API.
	 * @param   bool|array     $addressFields             Expected fields in billing address (false = all fields).
	 */
	public function apiCustomFieldDataIsValid($I, $subscriber, $addressFields = false)
	{
		// The default address data used for all tests.
		$address = array(
			'first_name' => 'First',
			'last_name'  => 'Last',
			'company'    => 'Company',
			'address_1'  => 'Address Line 1',
			'address_2'  => 'Address Line 2',
			'city'       => 'City',
			'state'      => 'CA',
			'postcode'   => '12345',
			'country'    => 'United States (US)',
		);

		// If no address fields are specified, build the expected address based on the integration's default setting.
		if ( ! $addressFields ) {
			$addressFields = array( 'name', 'address_1', 'city', 'state', 'postcode', 'country' );
		}

		// Build address array.
		$address = array_intersect_key( $address, array_flip( $addressFields ) );

		// WooCommerce has no comma between the state and postcode on addresses, so remove it.
		$addressString = implode(', ', $address);
		$addressString = str_replace('CA, 12345', 'CA 12345', $addressString);

		// Check the subscriber's custom field data is valid.
		$I->assertEquals($subscriber['fields']['last_name'], 'Last');
		$I->assertEquals($subscriber['fields']['phone_number'], '6159684594');
		$I->assertEquals($subscriber['fields']['billing_address'], $addressString);
		$I->assertEquals($subscriber['fields']['payment_method'], 'cod');
		$I->assertEquals($subscriber['fields']['notes'], 'Notes');
	}

	/**
	 * Check the subscriber array's custom field data is empty.
	 *
	 * @param   EndToEndTester $I             EndToEndTester.
	 * @param   array          $subscriber     Subscriber from API.
	 */
	public function apiCustomFieldDataIsEmpty($I, $subscriber)
	{
		$I->assertEquals($subscriber['fields']['last_name'], '');
		$I->assertEquals($subscriber['fields']['phone_number'], '');
		$I->assertEquals($subscriber['fields']['billing_address'], '');
		$I->assertEquals($subscriber['fields']['shipping_address'], '');
		$I->assertEquals($subscriber['fields']['payment_method'], '');
		$I->assertEquals($subscriber['fields']['notes'], '');
	}

	/**
	 * Sends a request to the ConvertKit API, typically used to read an endpoint to confirm
	 * that data in an Acceptance Test was added/edited/deleted successfully.
	 *
	 * @param   string $endpoint   Endpoint.
	 * @param   string $method     Method (GET|POST|PUT).
	 * @param   array  $params     Endpoint Parameters.
	 */
	public function apiRequest($endpoint, $method = 'GET', $params = array())
	{
		// Send request.
		$client = new \GuzzleHttp\Client();
		switch ($method) {
			case 'GET':
				$result = $client->request(
					$method,
					'https://api.kit.com/v4/' . $endpoint . '?' . http_build_query($params),
					[
						'headers' => [
							'Authorization' => 'Bearer ' . $_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN'],
							'timeout'       => 5,
						],
					]
				);
				break;

			default:
				$result = $client->request(
					$method,
					'https://api.kit.com/v4/' . $endpoint,
					[
						'headers' => [
							'Accept'        => 'application/json',
							'Content-Type'  => 'application/json; charset=utf-8',
							'Authorization' => 'Bearer ' . $_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN'],
							'timeout'       => 5,
						],
						'body'    => (string) json_encode($params), // phpcs:ignore WordPress.WP.AlternativeFunctions
					]
				);
				break;
		}

		// Return JSON decoded response.
		return json_decode($result->getBody()->getContents(), true);
	}
}
