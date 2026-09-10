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
	 * Installs the Kit API recorder mu-plugin, and clears any previously recorded
	 * requests, before each test runs.
	 *
	 * @since   2.2.0
	 *
	 * @param   \Codeception\TestInterface $test   Test.
	 */
	public function _before(\Codeception\TestInterface $test) // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
	{
		$this->getModule('lucatume\WPBrowser\Module\WPFilesystem')->haveMuPlugin(
			'kit-api-recorder.php',
			(string) file_get_contents(__DIR__ . '/../mu-plugins/kit-api-recorder.php') // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		);

		$this->getModule('lucatume\WPBrowser\Module\WPDb')->haveOptionInDatabase('kit_api_log', []);
	}

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
	 * Returns the Kit API requests the Plugin made during this test, optionally
	 * filtered by method, path and email address.
	 *
	 * @since   2.2.0
	 *
	 * @param   EndToEndTester $I              EndToEndTester.
	 * @param   bool|string    $method         HTTP method (GET,POST,PUT,DELETE).
	 * @param   bool|string    $path           Request path, excluding the API version e.g. `subscribers`.
	 * @param   bool|string    $emailAddress   Email address in the request body.
	 * @return  array
	 */
	public function grabKitAPIRequests($I, $method = false, $path = false, $emailAddress = false)
	{
		$log = $I->grabOptionFromDatabase('kit_api_log');

		if ( ! is_array($log)) {
			return [];
		}

		return array_values(
			array_filter(
				$log,
				function ($request) use ($method, $path, $emailAddress) {
					if ($method && $request['method'] !== $method) {
						return false;
					}
					if ($path && $request['path'] !== $path) {
						return false;
					}
					if ($emailAddress && ( ! array_key_exists('email_address', $request['body']) || $request['body']['email_address'] !== $emailAddress )) {
						return false;
					}

					return true;
				}
			)
		);
	}

	/**
	 * Returns the first Kit API request the Plugin made during this test that matches
	 * the given method, path and email address, waiting for it to be made.
	 *
	 * @since   2.2.0
	 *
	 * @param   EndToEndTester $I              EndToEndTester.
	 * @param   string         $method         HTTP method (GET,POST,PUT,DELETE).
	 * @param   string         $path           Request path, excluding the API version e.g. `subscribers`.
	 * @param   bool|string    $emailAddress   Email address in the request body.
	 * @return  bool|array
	 */
	public function grabKitAPIRequest($I, $method, $path, $emailAddress = false)
	{
		// The request is made by WordPress when the order is placed or its status changes,
		// which may not have completed when this is called.
		return $this->retryUntil(
			function () use ($I, $method, $path, $emailAddress) {
				$requests = $this->grabKitAPIRequests($I, $method, $path, $emailAddress);

				return count($requests) ? $requests[0] : false;
			},
			10,
			1
		);
	}

	/**
	 * Returns the Kit API request the Plugin made that resulted in a subscriber existing
	 * for the given email address, waiting for it to be made.
	 *
	 * The Plugin creates a subscriber with a POST request, updates an existing subscriber
	 * with a PUT request, and Kit creates a subscriber when the Plugin sends purchase data,
	 * so all three are checked.
	 *
	 * @since   2.2.0
	 *
	 * @param   EndToEndTester $I              EndToEndTester.
	 * @param   string         $emailAddress   Email Address.
	 * @return  bool|array
	 */
	public function grabKitAPISubscriberRequest($I, $emailAddress)
	{
		return $this->retryUntil(
			function () use ($I, $emailAddress) {
				// Check if the Plugin created the subscriber.
				$requests = $this->grabKitAPIRequests($I, 'POST', 'subscribers', $emailAddress);
				if (count($requests)) {
					return $requests[0];
				}

				// Check if the Plugin updated an existing subscriber with this email address.
				$requests = array_filter(
					$this->grabKitAPIRequests($I, 'PUT', false, $emailAddress),
					function ($request) {
						return strpos($request['path'], 'subscribers/') === 0;
					}
				);

				// Use the most recent update request, as a subscriber may be updated more than once.
				if (count($requests)) {
					return end($requests);
				}

				// Check if the Plugin sent purchase data, which subscribes the email address.
				$requests = $this->grabKitAPIRequests($I, 'POST', 'purchases', $emailAddress);

				return count($requests) ? $requests[0] : false;
			},
			10,
			1
		);
	}

	/**
	 * Returns the subscriber ID for the given Kit API request the Plugin made.
	 *
	 * @since   2.2.0
	 *
	 * @param   array $request  Kit API request.
	 * @return  int
	 */
	private function grabKitAPISubscriberID($request)
	{
		// The subscriber was created or updated by the Plugin, so the response contains the subscriber.
		if ($request['path'] !== 'purchases') {
			return $request['response']['subscriber']['id'];
		}

		// Kit created the subscriber when the Plugin sent purchase data.
		// Fetch the purchase by its ID, which includes the subscriber ID.
		$results = $this->apiRequest('purchases/' . $request['response']['purchase']['id'], 'GET');

		return $results['purchase']['subscriber_id'];
	}

	/**
	 * Returns the Kit API request the Plugin made that sent purchase data for the given
	 * Order ID, waiting for it to be made.
	 *
	 * @since   2.2.0
	 *
	 * @param   EndToEndTester $I         EndToEndTester.
	 * @param   int            $orderID   Order ID.
	 * @return  bool|array
	 */
	public function grabKitAPIPurchaseRequest($I, $orderID)
	{
		return $this->retryUntil(
			function () use ($I, $orderID) {
				foreach ($this->grabKitAPIRequests($I, 'POST', 'purchases') as $request) {
					if ( ! array_key_exists('transaction_id', $request['body'])) {
						continue;
					}

					// Compare as strings, as the Order ID may be a string when a third party
					// Plugin defines custom order numbers.
					if ((string) $request['body']['transaction_id'] === (string) $orderID) {
						return $request;
					}
				}

				return false;
			},
			10,
			1
		);
	}

	/**
	 * Check the given email address exists as a subscriber on ConvertKit.
	 *
	 * The Plugin's request that resulted in the subscriber is used to determine the subscriber ID,
	 * as querying the API by email address is subject to eventual consistency. Querying by
	 * subscriber ID returns strongly consistent results.
	 *
	 * @see     https://developers.kit.com/api-reference/eventual-consistency
	 *
	 * @param   EndToEndTester $I             EndToEndTester.
	 * @param   string         $emailAddress   Email Address.
	 * @param   mixed          $firstName      Name (false = don't check name matches).
	 * @return  array                           Subscriber
	 */
	public function apiCheckSubscriberExists($I, $emailAddress, $firstName = false)
	{
		// Get the request the Plugin made that resulted in the subscriber.
		$request = $this->grabKitAPISubscriberRequest($I, $emailAddress);

		// Check the Plugin sent a request that subscribes the email address.
		$I->assertNotFalse(
			$request,
			sprintf('The Plugin did not send a request that subscribes %s.', $emailAddress)
		);
		$I->assertLessThan(
			300,
			$request['code'],
			sprintf('The API returned a %s response when the Plugin subscribed %s.', $request['code'], $emailAddress)
		);

		// Fetch the subscriber by their ID, which returns strongly consistent results.
		$results = $this->apiRequest('subscribers/' . $this->grabKitAPISubscriberID($request), 'GET');

		// Check the subscriber matches the email address.
		$I->assertEquals($emailAddress, $results['subscriber']['email_address']);

		// If defined, check that the name matches for the subscriber.
		if ($firstName) {
			$I->assertEquals($firstName, $results['subscriber']['first_name']);
		}

		return $results['subscriber'];
	}

	/**
	 * Check the given email address does not exists as a subscriber.
	 *
	 * This deliberately queries the API by email address, and deliberately does not specify a
	 * status, so that only active subscribers are returned. Tests that subscribe an email
	 * address, unsubscribe it and then confirm the Plugin does not resubscribe it depend on
	 * this behaviour.
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
		// Wait for the subscriber to be assigned to the form, as list endpoints are eventually consistent.
		$subscriber = $this->retryUntil(
			function () use ($subscriberID, $formID) {
				$results = $this->apiRequest(
					'forms/' . $formID . '/subscribers',
					'GET',
					[
						// Check all subscriber states.
						'status' => 'all',
					]
				);

				// Return the subscriber only if they're assigned to the form, so
				// retryUntil() will keep trying otherwise.
				foreach ($results['subscribers'] as $subscriber) {
					if ( (int) $subscriber['id'] === (int) $subscriberID) {
						return $subscriber;
					}
				}

				return false;
			}
		);

		// Assert the subscriber has the form.
		$I->assertNotFalse(
			$subscriber,
			sprintf('Subscriber %s was not assigned to Form %s in time.', $subscriberID, $formID)
		);

		// If a referrer is specified, assert it matches the subscriber's referrer now.
		if ($referrer) {
			$I->assertEquals($subscriber['referrer'], $referrer);
		}
	}

	/**
	 * Check the given subscriber ID has been assigned to the given tag ID.
	 *
	 * @param   EndToEndTester $I             EndToEndTester.
	 * @param   int            $subscriberID  Subscriber ID.
	 * @param   int            $tagID         Tag ID.
	 */
	public function apiCheckSubscriberHasTag($I, $subscriberID, $tagID)
	{
		// Wait for the tag to be assigned to the subscriber, as list endpoints are eventually consistent.
		$tag = $this->retryUntil(
			function () use ($subscriberID, $tagID) {
				$results = $this->apiRequest(
					'subscribers/' . $subscriberID . '/tags',
					'GET'
				);

				// Return the tag only if it's assigned to the subscriber, so
				// retryUntil() will keep trying otherwise.
				foreach ($results['tags'] as $tag) {
					if ( (int) $tag['id'] === (int) $tagID) {
						return $tag;
					}
				}

				return false;
			}
		);

		// Assert the subscriber has the tag.
		$I->assertNotFalse(
			$tag,
			sprintf('Subscriber %s was not assigned Tag %s in time.', $subscriberID, $tagID)
		);
	}

	/**
	 * Check the given subscriber ID has no tags assigned.
	 *
	 * @param   EndToEndTester $I             EndToEndTester.
	 * @param   int            $subscriberID  Subscriber ID.
	 */
	public function apiCheckSubscriberHasNoTags($I, $subscriberID)
	{
		// Wait for the subscriber to have no tags, as list endpoints are eventually consistent.
		$result = $this->retryUntil(
			function () use ($subscriberID) {
				$results = $this->apiRequest(
					'subscribers/' . $subscriberID . '/tags',
					'GET'
				);

				// Return the tags only if none are assigned, so retryUntil() will keep trying otherwise.
				// The result is wrapped in an array, as an empty array is falsy.
				return count($results['tags']) === 0 ? array( 'tags' => $results['tags'] ) : false;
			}
		);

		// Assert the subscriber has no tags.
		$I->assertNotFalse(
			$result,
			sprintf('Subscriber %s still has tags assigned.', $subscriberID)
		);
	}

	/**
	 * Check the given order ID exists as a purchase on ConvertKit.
	 *
	 * The Plugin's request to send the purchase data is used to determine the purchase ID,
	 * as the purchases list endpoint is subject to eventual consistency. Querying by
	 * purchase ID returns strongly consistent results.
	 *
	 * @see     https://developers.kit.com/api-reference/eventual-consistency
	 *
	 * @param   EndToEndTester $I             EndToEndTester.
	 * @param   int            $orderID        Order ID.
	 * @param   string         $emailAddress   Email Address.
	 * @param   int            $productID      Product ID.
	 * @return  int                              ConvertKit ID.
	 */
	public function apiCheckPurchaseExists($I, $orderID, $emailAddress, $productID)
	{
		// Get the request the Plugin made to send the purchase data.
		$request = $this->grabKitAPIPurchaseRequest($I, $orderID);

		// Check the Plugin sent the purchase data.
		$I->assertNotFalse(
			$request,
			sprintf('The Plugin did not send purchase data for Order %s.', $orderID)
		);
		$I->assertLessThan(
			300,
			$request['code'],
			sprintf('The API returned a %s response when the Plugin sent purchase data for Order %s.', $request['code'], $orderID)
		);

		// Fetch the purchase by its ID, which returns strongly consistent results.
		$results  = $this->apiRequest('purchases/' . $request['response']['purchase']['id'], 'GET');
		$purchase = $results['purchase'];

		// Check data returned for this Order ID.
		$I->assertEquals($orderID, $purchase['transaction_id']);
		$I->assertEquals($emailAddress, $purchase['email_address']);

		// Iterate through the array of products, to find a pid matching the Product ID.
		$productExistsInPurchase = false;
		foreach ($purchase['products'] as $product) {
			if ( (int) $productID === (int) $product['pid']) {
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
	 * The Plugin's requests are inspected, instead of querying the purchases list endpoint,
	 * as that endpoint is subject to eventual consistency and is capped at the most recent
	 * purchases, either of which would return no purchase even when the Plugin sent one.
	 *
	 * @see     https://developers.kit.com/api-reference/eventual-consistency
	 *
	 * @param   EndToEndTester $I             EndToEndTester.
	 * @param   int            $orderID        Order ID.
	 * @param   string         $emailAddress   Email Address.
	 */
	public function apiCheckPurchaseDoesNotExist($I, $orderID, $emailAddress) // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
	{
		// Get any requests the Plugin made to send purchase data for this Order ID.
		$requests = array_filter(
			$this->grabKitAPIRequests($I, 'POST', 'purchases'),
			function ($request) use ($orderID) {
				if ( ! array_key_exists('transaction_id', $request['body'])) {
					return false;
				}

				// Compare as strings, as the Order ID may be a string when a third party
				// Plugin defines custom order numbers.
				return (string) $request['body']['transaction_id'] === (string) $orderID;
			}
		);

		// Check the Plugin did not send the purchase data.
		$I->assertCount(
			0,
			$requests,
			sprintf('The Plugin sent purchase data for Order %s.', $orderID)
		);
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
	 * Returns the subscriber for the given subscriber ID once their custom field data
	 * matches the given data.
	 *
	 * The Plugin updates the subscriber with custom field data after sending purchase data,
	 * so the subscriber may not have the data when this is first called.
	 *
	 * @since   2.2.0
	 *
	 * @param   int   $subscriberID  Subscriber ID.
	 * @param   array $fields        Custom Field key/value pairs to check.
	 * @return  bool|array
	 */
	private function grabSubscriberWithFields($subscriberID, $fields)
	{
		return $this->retryUntil(
			function () use ($subscriberID, $fields) {
				$results = $this->apiRequest('subscribers/' . $subscriberID, 'GET');

				// Return the subscriber only if every custom field matches, so
				// retryUntil() will keep trying otherwise.
				foreach ($fields as $key => $value) {
					if ( ! array_key_exists($key, $results['subscriber']['fields'])) {
						return false;
					}

					// Compare loosely, as an unset custom field is returned as null, which the
					// assertions treat as matching an empty string.
					if ($results['subscriber']['fields'][ $key ] != $value) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
						return false;
					}
				}

				return $results['subscriber'];
			}
		);
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
		if ( ! $addressFields) {
			$addressFields = array( 'name', 'address_1', 'city', 'state', 'postcode', 'country' );
		}

		// Build address array.
		$address = array_intersect_key( $address, array_flip( $addressFields ) );

		// WooCommerce has no comma between the state and postcode on addresses, so remove it.
		$addressString = implode(', ', $address);
		$addressString = str_replace('CA, 12345', 'CA 12345', $addressString);

		// Define the expected custom field data.
		$fields = array(
			'last_name'       => 'Last',
			'phone_number'    => '6159684594',
			'billing_address' => $addressString,
			'payment_method'  => 'cod',
			'notes'           => 'Notes',
		);

		// Wait for the subscriber to have the custom field data.
		$subscriberID = $subscriber['id'];
		$subscriber   = $this->grabSubscriberWithFields($subscriberID, $fields);

		// Re-fetch the subscriber if the data never matched, so the assertions below report which
		// custom field is incorrect.
		if ($subscriber === false) {
			$results    = $this->apiRequest('subscribers/' . $subscriberID, 'GET');
			$subscriber = $results['subscriber'];
		}

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
		// Define the expected custom field data.
		$fields = array(
			'last_name'        => '',
			'phone_number'     => '',
			'billing_address'  => '',
			'shipping_address' => '',
			'payment_method'   => '',
			'notes'            => '',
		);

		// Confirm the subscriber's custom field data is empty.
		$subscriberID = $subscriber['id'];
		$subscriber   = $this->grabSubscriberWithFields($subscriberID, $fields);

		// Re-fetch the subscriber if the data never matched, so the assertions below report which
		// custom field is incorrect.
		if ($subscriber === false) {
			$results    = $this->apiRequest('subscribers/' . $subscriberID, 'GET');
			$subscriber = $results['subscriber'];
		}

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
						],
						'timeout' => 5,
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
						],
						'timeout' => 5,
						'body'    => (string) json_encode($params), // phpcs:ignore WordPress.WP.AlternativeFunctions
					]
				);
				break;
		}

		// Return JSON decoded response.
		return json_decode($result->getBody()->getContents(), true);
	}

	/**
	 * Repeatedly invokes the given callback until it returns a truthy value, or
	 * the maximum number of attempts is reached.
	 *
	 * Use this to wrap API checks that can be flaky due to ingestion lag at
	 * Kit's end (e.g. a subscriber assigned to a form isn't always immediately
	 * returned by the `forms/{id}/subscribers` endpoint).
	 *
	 * @since   2.1.5
	 *
	 * @param   callable $callback   Callback to invoke. Should return the value
	 *                                to use, or false/null to indicate the
	 *                                check has not yet succeeded.
	 * @param   int      $attempts   Maximum number of attempts.
	 * @param   int      $delay      Seconds to wait between attempts.
	 * @return  mixed                The truthy value returned by $callback, or
	 *                                false if all attempts are exhausted.
	 */
	private function retryUntil(callable $callback, $attempts = 4, $delay = 3)
	{
		for ($i = 0; $i < $attempts; $i++) {
			$result = $callback();
			if ($result) {
				return $result;
			}

			// Don't sleep after the final attempt.
			if ($i < $attempts - 1) {
				sleep($delay);
			}
		}

		return false;
	}
}
