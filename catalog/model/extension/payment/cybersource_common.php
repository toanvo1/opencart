<?php

use Isv\Common\Helper\TypeConversion;

require_once DIR_SYSTEM . 'library/isv/cybersource_constants.php';

/**
 * All the common functions related to Credit card, eCheck and tokenization are in this file.
 *
 * @author Cybersource
 * @package Front Office
 * @subpackage Model
 */
class ModelExtensionPaymentCybersourceCommon extends Model {
	/**
	 * Gives general configurations which are configured in BO.
	 *
	 * @return array
	 */
	public function getGeneralConfiguration(): array {
		$general_configuration = array(
			'request_host'              => ENVIRONMENT_TEST == $this->config->get('module_' . PAYMENT_GATEWAY . '_sandbox') ? REQUEST_HOST_API_TEST : REQUEST_HOST_API_PRODUCTION,
			'merchant_id'               => ENVIRONMENT_TEST == $this->config->get('module_' . PAYMENT_GATEWAY . '_sandbox') ? $this->config->get('module_' . PAYMENT_GATEWAY . '_merchant_id_test') : $this->config->get('module_' . PAYMENT_GATEWAY . '_merchant_id_live'),
			'merchant_key_id'           => ENVIRONMENT_TEST == $this->config->get('module_' . PAYMENT_GATEWAY . '_sandbox') ? $this->config->get('module_' . PAYMENT_GATEWAY . '_merchant_key_id_test') : $this->config->get('module_' . PAYMENT_GATEWAY . '_merchant_key_id_live'),
			'merchant_secret_key'       => ENVIRONMENT_TEST == $this->config->get('module_' . PAYMENT_GATEWAY . '_sandbox') ? $this->config->get('module_' . PAYMENT_GATEWAY . '_merchant_secret_key_test') : $this->config->get('module_' . PAYMENT_GATEWAY . '_merchant_secret_key_live'),
			'sandbox'                   => $this->config->get('module_' . PAYMENT_GATEWAY . '_sandbox'),
			'payment_action'            => $this->config->get('module_' . PAYMENT_GATEWAY . '_transaction_method'),
			'fraud_status'              => VAL_ZERO == $this->config->get('module_' . PAYMENT_GATEWAY . '_fraud_status') ? DECISION_SKIP : VAL_EMPTY,
			'developer_id'              => $this->config->get('module_' . PAYMENT_GATEWAY . '_developer_id'),
			'dfp'                       => $this->config->get('module_' . PAYMENT_GATEWAY . '_dfp'),
			'session_id'                => $this->session->getId(),
			'recaptcha_enabled'         => $this->config->get('module_' . PAYMENT_GATEWAY . '_recaptcha_status'),
			'recaptcha_site_key'        => $this->config->get('module_' . PAYMENT_GATEWAY . '_recaptcha_site_key')
		);
		return $general_configuration;
	}

	/**
	 * Gives credit card configurations which are configured in BO.
	 *
	 * @return array
	 */
	public function getUnifiedCheckoutConfiguration(): array {
		$unifiedcheckout_configuration = array(
			'capture'                     => PAYMENT_ACTION_SALE == $this->config->get('module_' . PAYMENT_GATEWAY . '_transaction_method') ? true : false,
			'payer_auth'                  => $this->config->get('payment_' . PAYMENT_GATEWAY . '_payer_auth'),
			'limit_saved_card_rate'       => $this->config->get('payment_' . PAYMENT_GATEWAY . '_limit_saved_card_rate'),
			'saved_card_limit_frame'      => $this->config->get('payment_' . PAYMENT_GATEWAY . '_saved_card_limit_frame'),
			'saved_card_limit_time_frame' => $this->config->get('payment_' . PAYMENT_GATEWAY . '_saved_card_limit_time_frame'),
			'payer_auth_challenge'        => $this->config->get('payment_' . PAYMENT_GATEWAY . '_payer_auth_challenge'),
			'card'                        => $this->config->get('payment_' . PAYMENT_GATEWAY . '_card'),
			'cardinal_url'                => ENVIRONMENT_TEST == $this->config->get('module_' . PAYMENT_GATEWAY . '_sandbox') ? CARDINAL_CRUISE_TEST_URL : CARDINAL_CRUISE_PRODUCTION_URL,
			'gpay'                        => $this->config->get('payment_' . PAYMENT_GATEWAY . '_gpay_status'),
			'vsrc'                        => $this->config->get('payment_' . PAYMENT_GATEWAY . '_vsrc_status'),
		);
		return $unifiedcheckout_configuration;
	}

	/**
	 * Gives Apple Pay configurations which are configured in BO.
	 *
	 * @return array
	 */
	public function getApplePayConfiguration(): array {
		$sandbox_mode = $this->config->get('module_' . PAYMENT_GATEWAY . '_sandbox');
		$apple_pay_configuration = array(
			'capture'                     => PAYMENT_ACTION_SALE == $this->config->get('module_' . PAYMENT_GATEWAY . '_transaction_method') ? true : false,
			'status'                      => $this->config->get('payment_' . PAYMENT_GATEWAY_APPLE_PAY . '_status'),
			'merchant_id'                 => $this->config->get('payment_' . PAYMENT_GATEWAY_APPLE_PAY . '_merchant_id_' . $sandbox_mode),
			'certificate_path'            => $this->config->get('payment_' . PAYMENT_GATEWAY_APPLE_PAY . '_path_to_certificate_' . $sandbox_mode),
			'key_path'                    => $this->config->get('payment_' . PAYMENT_GATEWAY_APPLE_PAY . '_path_to_key_' . $sandbox_mode)
		);
		return $apple_pay_configuration;
	}

	/**
	 * Gives all the order status which are configured in BO.
	 *
	 * @return array
	 */
	public function getOrderStatusConfiguration(): array {
		$status_configuration = array(
			'authorization'              => $this->config->get('module_' . PAYMENT_GATEWAY . '_auth_status_id'),
			'partial_capture'            => $this->config->get('module_' . PAYMENT_GATEWAY . '_partial_capture_status_id'),
			'capture'                    => $this->config->get('module_' . PAYMENT_GATEWAY . '_capture_status_id'),
			'partial_refund'             => $this->config->get('module_' . PAYMENT_GATEWAY . '_partial_refund_status_id'),
			'refund'                     => $this->config->get('module_' . PAYMENT_GATEWAY . '_refund_status_id'),
			'fraud_management'           => $this->config->get('module_' . PAYMENT_GATEWAY . '_fraud_management_status_id'),
			'fraud_reject'               => $this->config->get('module_' . PAYMENT_GATEWAY . '_fraud_reject_status_id'),
			'auth_reversal'              => $this->config->get('module_' . PAYMENT_GATEWAY . '_auth_reversal_status_id'),
			'partial_void'               => $this->config->get('module_' . PAYMENT_GATEWAY . '_partial_void_status_id'),
			'void'                       => $this->config->get('module_' . PAYMENT_GATEWAY . '_void_status_id'),
			'auth_reversal_error'        => $this->config->get('module_' . PAYMENT_GATEWAY . '_auth_reversal_error_status_id'),
			'payment_error'              => $this->config->get('module_' . PAYMENT_GATEWAY . '_payment_error_status_id'),
			'refund_error'               => $this->config->get('module_' . PAYMENT_GATEWAY . '_refund_error_status_id'),
			'void_error'                 => $this->config->get('module_' . PAYMENT_GATEWAY . '_void_error_status_id')
		);
		return $status_configuration;
	}

	/**
	 * Decodes the JWT token.
	 *
	 * @param string $token
	 *
	 * @return null|object
	 */
	public function decodeToken(string $token): ?object {
		$tokens = explode('.', $token);
		if (3 !== count($tokens)) {
			return null;
		}
		return json_decode(base64_decode($this->convertToBase64($tokens[VAL_ONE])), false, 512, JSON_BIGINT_AS_STRING);
	}

	/**
	 * Convert base64url encoding to standard base64.
	 *
	 * @param string $token
	 *
	 * @return string
	 */
	public function convertToBase64(string $token): string {
		$remainder = strlen($token) % 4;
		if ($remainder) {
			$padding_length = 4 - $remainder;
			$token .= str_repeat('=', $padding_length);
		}
		return strtr($token, HYPEN . UNDER_SCORE, '+' . FORWARD_SLASH);
	}

	/**
	 * Service call : Gives capture context as response.
	 *
	 * @param bool $zero_dollar_auth
	 *
	 * @return array
	 */
	public function getCaptureContextResponse(bool $zero_dollar_auth): array {
		$this->load->model('localisation/country');
		$this->load->model('checkout/order');

		$country_id = $this->config->get('config_country_id');
		$country = $this->model_localisation_country->getCountry($country_id);
		$url = $this->getUrl();
		$allowed_payment_types = array(UNIFIED_CHECKOUT_PAYMENT_METHOD_CC);
		$selected_cards = $this->getConfiguredCards();
		$data = $this->session->data;

		if ($zero_dollar_auth) {
			$locale = $this->getLocale($data['language']);
			$total_amount = VAL_ZERO_POINT_ZERO_ONE;
			$data['currency_code'] = $data['currency'];
			$data['payment_firstname'] = $this->customer->getFirstName();
			$data['payment_lastname'] = $this->customer->getLastName();
		} else {
			$order_id = $data['order_id'] ?? VAL_ZERO;
			$data = $this->model_checkout_order->getOrder($order_id);
			$locale = $this->getLocale($data['language_code']);
			$total_amount = $this->currency->format($data['total'], $data['currency_code'], false, false);
			$unifiedcheckout_configuration = $this->getUnifiedCheckoutConfiguration();

			if ($unifiedcheckout_configuration['vsrc']) {
				array_push($allowed_payment_types, UNIFIED_CHECKOUT_PAYMENT_METHOD_VSRC);
			}
			if ($unifiedcheckout_configuration['gpay']) {
				array_push($allowed_payment_types, UNIFIED_CHECKOUT_PAYMENT_METHOD_GPAY);
			}
		}

		$payload = array(
			"targetOrigins" => TypeConversion::convertArrayToType(array($url), array('string')),
			"clientVersion" => UNIFIED_CHECKOUT_CLIENT_VERSION,
			"allowedCardNetworks" => TypeConversion::convertArrayToType($selected_cards, array('string')),
			"allowedPaymentTypes" => TypeConversion::convertArrayToType($allowed_payment_types, array('string')),
			"country" => TypeConversion::convertDataToType($country['iso_code_2'], 'string'),
			"locale" => TypeConversion::convertDataToType($locale, 'string'),
			"captureMandate" => array(
				"billingType" => "NONE",
				"requestEmail" => false,
				"requestPhone" => false,
				"requestShipping" => false,
				"showAcceptedNetworkIcons" => true
			),
			"orderInformation" => array(
				"amountDetails" => array(
					"totalAmount" => TypeConversion::convertDataToType($total_amount, 'string'),
					"currency" => TypeConversion::convertDataToType($data['currency_code'], 'string')
				),
				"billTo" => array(
					"firstName" => TypeConversion::convertDataToType($data['payment_firstname'], 'string'),
					"lastName" => TypeConversion::convertDataToType($data['payment_lastname'], 'string'),
				)
			)
		);
		$payload = json_encode($payload);
		$resource = RESOURCE_UNIFIED_CHECKOUT;
		$api_response = $this->serviceProcessor($payload, $resource, true, SERVICE_FLEX_FORM);
		return $api_response;
	}

	/**
	 * Returns configured card details.
	 *
	 * @return array
	 */
	public function getConfiguredCards(): array {
		$allowed_cards = array('visa' => UNIFIED_CHECKOUT_VISA_CARD, 'mastercard' => UNIFIED_CHECKOUT_MASTERCARD_CARD, 'discover' => UNIFIED_CHECKOUT_DISCOVER_CARD, 'amex' => UNIFIED_CHECKOUT_AMEX_CARD, 'jcb' => UNIFIED_CHECKOUT_JCB_CARD, 'dinersclub' => UNIFIED_CHECKOUT_DINERSCLUB_CARD);
		$selected_cards = array();
		foreach ($allowed_cards as $status_name => $general_name) {
			if (VAL_ONE == $this->config->get('payment_' . PAYMENT_GATEWAY . UNDER_SCORE . $status_name . '_card_status')) {
				array_push($selected_cards, $general_name);
			}
		}
		return $selected_cards;
	}

	/**
	 * Return domain name.
	 *
	 * @return string
	 */
	public function getUrl(): string {
		$url = VAL_EMPTY;
		if ($this->request->server['HTTPS']) {
			$url_data = parse_url(HTTPS_SERVER);
		} else {
			$url_data = parse_url(HTTP_SERVER);
		}
		$url = $url_data['scheme'] . "://" . $url_data['host'];
		if (isset($url_data['port'])) {
			$url .= ":" . $url_data['port'];
		}
		return $url;
	}

	/**
	 * Service call : Gives card details from token.
	 *
	 * @param array $token The token will have customer token details
	 *
	 * @return array
	 */
	public function getCardDetailsFromToken(array $token): array {
		$resources = RESOURCE_TMS_V2_CUSTOMERS . $token['customer_token_id'] . RESOURCE_PAYMENT_INSTRUMENTS . $token['payment_instrument_id'];
		$response_data = $this->serviceProcessor(VAL_EMPTY, $resources, true, HTTP_METHOD_GET);
		return $response_data;
	}

	/**
	 * Removes scheme part from the url.
	 *
	 * @param string $url url
	 *
	 * @return string
	 */
	private function removeScheme(string $url): string {
		$disallowed = array(PROTOCOL_HTTP, PROTOCOL_HTTPS);
		foreach ($disallowed as $scheme) {
			if (VAL_ZERO === strpos($url, $scheme)) {
				$url = str_replace($scheme, VAL_EMPTY, $url);
				break;
			}
		}
		return $url;
	}

	/**
	 * Service call : Connects to provided end points using cURL and gives response to the calling functions.
	 *
	 * @param string $payload The data that needs to be send to end points are stored in payload
	 * @param string $resource The end point are stored in resource
	 * @param bool $service_header Type of header parameter need to be send in cURL is decided using this parameter
	 * @param string $service The type of cURL service method spcified here
	 *
	 * @return array
	 */
	public function serviceProcessor(string $payload, string $resource, bool $service_header, string $service): array {
		$header_params = array();
		$headers = array();
		$general_configuration = $this->getGeneralConfiguration();
		$host_url = $general_configuration['request_host'];
		$request_host = $this->removeScheme($host_url);
		$url = $host_url . $resource;
		$resource_encode = utf8_encode($resource);
		if (UPDATE_CARD == $service) {
			$method = HTTP_METHOD_PATCH;
		} elseif (HTTP_METHOD_DELETE == $service) {
			$method = HTTP_METHOD_DELETE;
		} elseif (HTTP_METHOD_GET == $service) {
			$method = HTTP_METHOD_GET;
		} else {
			$method = HTTP_POST;
		}
		$date = gmdate(DATE_D_D_M_Y_G_I_S) . DATE_GMT;
		if (!$service_header) {
			$header_params[CONTENT_TYPE_SERVICE_PROCESSOR] = APPLICATION_JSON_CHAR_SET;
		} else {
			if (SERVICE_FLEX_FORM == $service || HTTP_METHOD_GET == $service) {
				$header_params[ACCEPT] = APPLICATION_JSON;
				$header_params[CONTENT_TYPE_SERVICE_PROCESSOR] = APPLICATION_JSON;
			} else {
				$header_params[ACCEPT] = APPLICATION_HAL_JSON_CHAR_SET;
				$header_params[CONTENT_TYPE_SERVICE_PROCESSOR] = APPLICATION_JSON_CHAR_SET;
			}
		}
		foreach ($header_params as $key => $val) {
			$headers[] = "$key: $val";
		}
		$auth_headers = $this->getHttpSignature($resource_encode, $method, $date, $payload, $request_host);
		$header_params = array_merge($headers, $auth_headers);
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		if (HTTP_METHOD_GET == $method) {
			curl_setopt($curl, CURLOPT_HTTPHEADER, $auth_headers);
		} else {
			curl_setopt($curl, CURLOPT_HTTPHEADER, $header_params);
		}
		if (HTTP_POST == $method) {
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
		}
		if (HTTP_METHOD_PATCH == $method) {
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, HTTP_METHOD_TYPE_PATCH);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
		}
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HEADER, VAL_ONE);
		curl_setopt($curl, CURLOPT_VERBOSE, VAL_ZERO);
		curl_setopt($curl, CURLOPT_USERAGENT, MOZILLA_FIVE_ZERO);
		if (HTTP_METHOD_DELETE == $method) {
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
		}
		$response = curl_exec($curl);
		$response = $this->getResponseArray($curl, $response);
		curl_close($curl);
		return $response;
	}

	/**
	 * Gives header parameters, that is required for cURL request.
	 *
	 * @param string $resource_path
	 * @param string $http_method Http method type
	 * @param string $current_date Current time
	 * @param string $payload The data that needs to be send to end points are stored in payload
	 * @param string $request_host
	 *
	 * @return array
	 */
	private function getHttpSignature(string $resource_path, string $http_method, string $current_date, string $payload, string $request_host): array {
		$digest = VAL_EMPTY;
		$general_configuration = $this->getGeneralConfiguration();
		if (HTTP_METHOD_GET == $http_method || HTTP_METHOD_DELETE == $http_method) {
			$signature_string = "host: " . $request_host . "\ndate: " . $current_date . "\nrequest-target: " . $http_method . " " . $resource_path . "\nv-c-merchant-id: " . $general_configuration['merchant_id'];
			$header_string = "host date request-target v-c-merchant-id";
		} else {
			$digest = $this->generateDigest($payload);
			$signature_string = "host: " . $request_host . "\ndate: " . $current_date . "\nrequest-target: " . $http_method . " " . $resource_path . "\ndigest: SHA-256=" . $digest . "\nv-c-merchant-id: " . $general_configuration['merchant_id'];
			$header_string = "host date request-target digest v-c-merchant-id";
		}
		$signature_byte_string = utf8_encode($signature_string);
		$decode_key = base64_decode($general_configuration['merchant_secret_key']);
		$signature = base64_encode(hash_hmac(ALGORITHM_SHA256, $signature_byte_string, $decode_key, true));
		$signature_header = array(
			'keyid="' . $general_configuration['merchant_key_id'] . '"',
			'algorithm="HmacSHA256"',
			'headers="' . $header_string . '"',
			'signature="' . $signature . '"'
		);
		$signature_token = "Signature:" . implode(", ", $signature_header);
		$host = "Host:" . $request_host;
		$vc_merchant = "v-c-merchant-id:" . $general_configuration['merchant_id'];
		$headers = array($vc_merchant, $signature_token, $host, 'Date:' . $current_date);
		if (!(HTTP_METHOD_GET == $http_method || HTTP_METHOD_DELETE == $http_method)) {
			$digest_array = array("Digest: SHA-256=" . $digest);
			$headers = array_merge($headers, $digest_array);
		}
		return $headers;
	}

	/**
	 * Extract and gives response from cURL in array format.
	 *
	 * @param resource|false|CurlHandle $curl
	 * @param string|bool $response
	 *
	 * @return array
	 */
	private function getResponseArray($curl, $response): array {
		$response_array = array(
			'header' => VAL_NULL,
			'body' => VAL_NULL,
			'http_code' => VAL_ZERO
		);
		if (false != $response && false != $curl) {
			$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
			$response_array['header'] = substr($response, VAL_ZERO, $header_size);
			$response_array['body'] = substr($response, $header_size);
			$response_array['http_code'] = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		}
		return $response_array;
	}

	/**
	 * Generates digest for payload.
	 *
	 * @param string $request_payload The data that needs to be send to end points are stored in payload.
	 *
	 * @return string
	 */
	private function generateDigest(string $request_payload): string {
		$utf8_encoded_string = utf8_encode($request_payload);
		$digest_encode = hash(ALGORITHM_SHA256, $utf8_encoded_string, true);
		return base64_encode($digest_encode);
	}

	/**
	 * Generates random digits, which is used in payload when we are not having order id.
	 *
	 * @return string
	 */
	public function generateMerchantRef(): string {
		$random_string = VAL_ZERO;
		$random_string = bin2hex(random_bytes(VAL_TWO));
		return $random_string;
	}

	/**
	 * Calculate shipping cost and call getShippingTax() to gets shipping tax.
	 *
	 * @param int $order_id order id
	 *
	 * @return array
	 */
	public function getShippingCost($order_id) {
		$shipping_cost = VAL_ZERO;
		$shipping_tax = VAL_ZERO;
		$coupon_amount = VAL_ZERO;
		$voucher_amount = VAL_ZERO;
		$reward_point_amount = VAL_ZERO;
		$store_credit_amount = VAL_ZERO;
		$this->load->model('extension/payment/cybersource_query');
		if (VAL_ZERO != $order_id) {
			$query_shipping = $this->model_extension_payment_cybersource_query->queryShippingCost($order_id);
			if (VAL_ZERO < $query_shipping->num_rows) {
				$shipping_cost = $query_shipping->row['shipping_cost'];
			}
			$voucher_amount_temp = $this->model_extension_payment_cybersource_query->queryVoucherAmount($order_id);
			if (!empty($voucher_amount_temp)) {
				$voucher_amount = $this->getAbsAmount($voucher_amount_temp);
			}
			$coupon_amount_temp = $this->model_extension_payment_cybersource_query->queryCouponAmount($order_id);
			if (!empty($coupon_amount_temp)) {
				$coupon_amount = $this->getAbsAmount($coupon_amount_temp);
			}
			$reward_point_amount_temp = $this->model_extension_payment_cybersource_query->queryRewardPointsAmount($order_id);
			if (!empty($reward_point_amount_temp)) {
				$reward_point_amount = $this->getAbsAmount($reward_point_amount_temp);
			}
			$store_credit_amount_temp = $this->model_extension_payment_cybersource_query->queryStoreCreditAmount($order_id);
			if (!empty($store_credit_amount_temp)) {
				$store_credit_amount = $this->getAbsAmount($store_credit_amount_temp);
			}
			$shipping_tax = $this->getShippingTax($order_id);
		}
		return  array($shipping_cost, $shipping_tax, $voucher_amount, $coupon_amount, $reward_point_amount, $store_credit_amount);
	}

	private function getAbsAmount($amount) {
		$abs_amount = VAL_ZERO;
		if (!empty($amount)) {
			$temp = floatval($amount);
			$abs_amount = abs($temp);
		}
		return $abs_amount;
	}

	/**
	 * Gives shipping tax for passed order id.
	 *
	 * @param int $order_id order id
	 *
	 * @return string
	 */
	private function getShippingTax($order_id) {
		$total_tax = VAL_ZERO;
		$shipping_tax_amount = VAL_ZERO;
		$product_details_tax = VAL_ZERO;
		$this->load->model('extension/payment/cybersource_query');
		if (!empty($order_id)) {
			$query_tax_amount = $this->model_extension_payment_cybersource_query->queryTaxAmount($order_id);
			if (VAL_NULL != $query_tax_amount) {
				$total_tax = $query_tax_amount;
			}
			$query_product_tax = $this->model_extension_payment_cybersource_query->queryProductTaxAmount($order_id);
			if (VAL_NULL != $query_product_tax) {
				$product_details_tax = $query_product_tax;
			}
			$shipping_tax_amount = number_format($total_tax - $product_details_tax, VAL_TWO, '.', '');
		}
		return $shipping_tax_amount;
	}

	/**
	 * Service call : Connects to google recaptcha end point using cURL and gives response to the calling functions.
	 *
	 * @param string $token It will have recaptcha token generated in twig
	 * @param string $secret_key It will have recaptcha secret key, which is configured in BO
	 *
	 * @return bool
	 */
	private function getRecaptchaResponse(string $token, string $secret_key): bool {
		$result = false;
		$curl = curl_init();
		$url = GOOGLE_RECAPTCHA_URL;
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HEADER, VAL_ZERO);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, VAL_ONE);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, VAL_ONE);
		curl_setopt($curl, CURLOPT_POST, VAL_ONE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, VAL_ZERO);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, VAL_ZERO);
		curl_setopt($curl, CURLOPT_POSTFIELDS, array(
			'secret' => $secret_key,
			'response' => $token,
			'remoteip' => $_SERVER['REMOTE_ADDR']
		));
		if (VAL_NULL != $curl) {
			$recap_response = curl_exec($curl);
			if (false != $recap_response) {
				$decoded_response = json_decode($recap_response);
				$result = $decoded_response->success ?? false;
			}
			curl_close($curl);
		}
		return $result;
	}

	/**
	 * Generates Cross-site request forgery(csrf) token with provide security over csrf attack.
	 *
	 * @param string $csrf It will csrf token if it exists or null
	 *
	 * @return void Only setting csrf token and time to session.
	 */
	public function generateToken(string $csrf) {
		if (empty($csrf)) {
			$token = bin2hex(random_bytes(VAL_THIRTY_TWO));
		} else {
			$token = $csrf;
		}
		$this->session->data['csrf'] = $token;
		$this->session->data['csrf_time'] = time();
	}

	/**
	 * Gives line items which will be used to payload.
	 *
	 * @param array $product_details It will have product details for perticular order.
	 * @param array $voucher_details It will have voucher details for perticular order.
	 * @param string $voucher_code It will have voucher code it customer uses any voucher.
	 * @param string $coupon_code It will have coupon code it customer uses any coupon.
	 * @param int $order_id order id
	 *
	 * @return array
	 */
	public function getLineItemArray($product_details, $voucher_details, $order_id) {
		$i = VAL_ZERO;
		$sum = VAL_ZERO;
		$line_items = array();
		$this->load->model('extension/total/coupon');
		$this->load->model('extension/total/voucher');
		$sizeof_products = sizeof($product_details);
		for ($i; $i < $sizeof_products; $i++) {
			$line_items['' . $i . ''] = array(
				"productCode" => PRODUCT_CODE_DEFAULT,
				"productName" => TypeConversion::convertDataToType($product_details['' . $i . '']['name'], 'string'),
				"productSKU" => TypeConversion::convertDataToType($product_details['' . $i . '']['product_id'], 'string'),
				"quantity" => TypeConversion::convertDataToType($product_details['' . $i . '']['quantity'], 'integer'),
				"unitPrice" => TypeConversion::convertDataToType($product_details['' . $i . '']['price'], 'string'),
				"taxAmount" => TypeConversion::convertDataToType($product_details['' . $i . '']['tax'], 'string')
			);
			$sum += ($product_details['' . $i . '']['price'] * $product_details['' . $i . '']['quantity']);
		}

		list($shipping_cost, $shipping_tax, $voucher_amount, $coupon_amount, $reward_point_amount, $store_credit_amount) = $this->getShippingCost($order_id);
		if (VAL_NULL != $voucher_amount) {
			$line_items['' . $i++ . ''] = array(
				'productCode' => COUPON,
				'productName' => VOUCHER,
				'productSKU'  => VOUCHER,
				"quantity" => VAL_ONE,
				'unitPrice' => TypeConversion::convertDataToType($voucher_amount, 'string')
			);
		}

		if (VAL_NULL != $reward_point_amount) {
			$line_items['' . $i++ . ''] = array(
				'productCode' => COUPON,
				'productName' => REWARD_POINTS,
				'productSKU'  => REWARD_POINTS,
				"quantity" => VAL_ONE,
				'unitPrice' => TypeConversion::convertDataToType($reward_point_amount, 'string')
			);
		}

		if (VAL_NULL != $store_credit_amount) {
			$line_items['' . $i++ . ''] = array(
				'productCode' => COUPON,
				'productName' => STORE_CREDIT_POINTS,
				'productSKU'  => STORE_CREDIT_POINTS,
				"quantity" => VAL_ONE,
				'unitPrice' => TypeConversion::convertDataToType($store_credit_amount, 'string')
			);
		}

		if (VAL_NULL != $coupon_amount) {
			$line_items['' . $i++ . ''] = array(
				"productCode" => COUPON,
				"productName" => COUPON,
				"productSKU" => COUPON,
				"quantity" => VAL_ONE,
				"unitPrice" => TypeConversion::convertDataToType($coupon_amount, 'string')
			);
		}

		if ($shipping_cost > VAL_ZERO) {
			$line_items['' . $i++ . ''] = array(
				"productCode" => SHIPPING_AND_HANDLING,
				"productName" => SHIPPING_AND_HANDLING,
				"productSKU" => SHIPPING_AND_HANDLING,
				"quantity" => VAL_ONE,
				"unitPrice" => TypeConversion::convertDataToType($shipping_cost, 'string'),
				"taxAmount" => TypeConversion::convertDataToType($shipping_tax, 'string')
			);
		}
		if (!(empty($voucher_details))) {
			for ($j = VAL_ZERO; $j < sizeof($voucher_details); $j++) {
				$line_items['' . $i++ . ''] = array(
					"productCode" => PRODUCT_CODE_GIFT_CERTIFICATE,
					"productName" => GIFT_CERTIFICATE,
					"productSKU" => TypeConversion::convertDataToType($voucher_details['' . $j . '']['description'], 'string'),
					"quantity" => VAL_ONE,
					"unitPrice" => TypeConversion::convertDataToType($voucher_details['' . $j . '']['amount'], 'string')
				);
			}
		}
		return $line_items;
	}

	/**
	 * Generates Device Fingerprint URL.
	 *
	 * @return string|null
	 */
	public function getDfpUrl(): ?string {
		$url = VAL_NULL;
		$general_configuration = $this->getGeneralConfiguration();
		$session_id = $general_configuration['session_id'];
		$sandbox = $general_configuration['sandbox'];
		$merchant_id = $general_configuration['merchant_id'];
		$is_dfp_enabled = $general_configuration['dfp'];
		$org_id = (ENVIRONMENT_TEST == $sandbox) ? ORG_ID_TEST : ORG_ID_LIVE;
		if ($is_dfp_enabled) {
			$location =	RESOURCE_THREAD_MATRIX_URL;
			$url = $location . "/fp/tags?org_id=" . $org_id . "&session_id=" . $merchant_id . $session_id;
		}
		return $url;
	}

	/**
	 * Service call : Connects to address verification end points using cURL and gives response to the calling functions.
	 *
	 * @param int $order_id order id
	 *
	 * @return array
	 */
	public function getDAVResponse(int $order_id): array {
		$this->load->model('checkout/order');
		$payload_data = $this->model_checkout_order->getOrder($order_id);
		$client_reference_info = $this->getClientReferenceInfo($order_id);
		$payload = array(
			"clientReferenceInformation" => $client_reference_info,
			"orderInformation" => array(
				"billTo" => array(
					"address1" => TypeConversion::convertDataToType($payload_data['payment_address_1'], 'string'),
					"address2" => TypeConversion::convertDataToType($payload_data['payment_address_2'], 'string'),
					"postalCode" => TypeConversion::convertDataToType($payload_data['payment_postcode'], 'string'),
					"locality" => TypeConversion::convertDataToType($payload_data['payment_city'], 'string'),
					"administrativeArea" => TypeConversion::convertDataToType($payload_data['payment_zone_code'], 'string'),
					"country" => TypeConversion::convertDataToType($payload_data['payment_iso_code_2'], 'string')
				),
				"shipTo" => array(
					"address1" => TypeConversion::convertDataToType($payload_data['shipping_address_1'], 'string'),
					"address2" => TypeConversion::convertDataToType($payload_data['shipping_address_2'], 'string'),
					"postalCode" => TypeConversion::convertDataToType($payload_data['shipping_postcode'], 'string'),
					"locality" => TypeConversion::convertDataToType($payload_data['shipping_city'], 'string'),
					"administrativeArea" => TypeConversion::convertDataToType($payload_data['shipping_zone_code'], 'string'),
					"country" => TypeConversion::convertDataToType($payload_data['shipping_iso_code_2'], 'string')
				),
			),
		);
		$payload = json_encode($payload);
		$resource = RISKS_V1_ADDRESS_VERIFICATIONS;
		$api_response = $this->serviceProcessor($payload, $resource, true, VAL_EMPTY);
		return array($api_response, $payload_data);
	}

	/**
	 * Extracts response data from delivery address verification response.
	 *
	 * @param array $dav_response The delivery address verification response.
	 *
	 * @return array
	 */
	public function getDAVResponseArray(array $dav_response): array {
		$dav_response_array = array();
		$response_data = VAL_NULL;
		if (VAL_NULL != $dav_response) {
			$this->load->model('extension/payment/cybersource_query');
			$this->load->language('extension/payment/cybersource_common');
			$response_body = $dav_response['body'];
			if (!empty($response_body)) {
				$response_data = json_decode($response_body);
			}
			if (CODE_TWO_ZERO_ONE == $dav_response['http_code']) {
				$status = $response_data->status;
				if (API_STATUS_DECLINED == $status) {
					$dav_response_array['dav_status'] = STATUS_ERROR;
					$dav_response_array['message'] = $response_data->errorInformation->message;
				} elseif (API_STATUS_INVALID_REQUEST == $status) {
					$dav_response_array['dav_status'] = STATUS_ERROR;
					$dav_response_array['message'] = $response_data->message;
				} elseif (API_STATUS_COMPLETED == $status) {
					$dav_response_array['dav_status'] = STATUS_SUCCESS;
					$dav_response_array['address'] = $response_data->addressVerificationInformation->standardAddress->address1->withApartment;
					$dav_response_array['city'] = $response_data->addressVerificationInformation->standardAddress->locality;
					$state_iso_code = $response_data->addressVerificationInformation->standardAddress->administrativeArea;
					$country_code = $response_data->addressVerificationInformation->standardAddress->isoCountry;
					$dav_response_array['postal_code']  = $response_data->addressVerificationInformation->standardAddress->postalCode;
					$dav_response_array['transaction_id'] = $response_data->id;
					$query_country = $this->model_extension_payment_cybersource_query->queryCountryDetails($country_code);
					$country_id = ((VAL_NULL == $query_country) || (VAL_ZERO == $query_country->num_rows)) ? VAL_ZERO : $query_country->row['country_id'];
					$query_state = $this->model_extension_payment_cybersource_query->queryZoneName($state_iso_code, $country_id);
					$dav_response_array['country'] = ((VAL_NULL == $query_country) || (VAL_ZERO == $query_country->num_rows)) ? VAL_NULL : $query_country->row['name'];
					$dav_response_array['state'] = ((VAL_NULL == $query_state) || (VAL_ZERO == $query_state->num_rows)) ? VAL_NULL : $query_state->row['name'];
				}
			} elseif (CODE_FOUR_ZERO_ONE == $dav_response['http_code']) {
				$dav_response_array['message'] = $this->language->get('error_dav_request');
			} elseif (CODE_FIVE_ZERO_TWO == $dav_response['http_code']) {
				$dav_response_array['message'] = $this->language->get('error_dav_server');
			} elseif (CODE_FOUR_ZERO_ZERO == $dav_response['http_code']) {
				$dav_response_array['message'] = $this->language->get('error_dav');
			} else {
				$dav_response_array['message'] = $this->language->get('error_dav_request');
			}
		}
		return $dav_response_array;
	}

	/**
	 * Updates shipping details when user selects recommended address instead of entered address.
	 *
	 * @param string $shipping_address recommended shipping address
	 * @param string $shipping_city recommended shipping city
	 * @param string $shipping_state recommended shipping state
	 * @param string $shipping_country recommended shipping country
	 * @param string $shipping_postal recommended shipping postal
	 * @param int $order_id order id
	 *
	 * @return bool
	 */
	public function updateRecommendedAddress(string $shipping_address, string $shipping_city, string $shipping_state, string $shipping_country, string $shipping_postal, ?int $order_id): bool {
		$return_response = false;
		$shipping_zone_id = VAL_NULL;
		$this->load->model('extension/payment/cybersource_query');
		$query_zone_id = $this->model_extension_payment_cybersource_query->queryZoneIdByName($shipping_state);
		if (VAL_NULL != $query_zone_id) {
			$shipping_zone_id = $query_zone_id->row['zone_id'];
		}
		$query_update_address = $this->model_extension_payment_cybersource_query->queryUpdateOrder($shipping_address, $shipping_city, $shipping_state, $shipping_zone_id, $shipping_country, $shipping_postal, $order_id);
		if (VAL_NULL != $query_update_address) {
			$return_response = $query_update_address;
		}
		return $return_response;
	}

	/**
	 * Unset all the setted session data.
	 *
	 * @return void
	 */
	public function unsetSessionData() {
		unset($this->session->data['csrf_token']);
		unset($this->session->data['csrf_token_time']);
		unset($this->session->data['csrf_time']);
		unset($this->session->data['csrf']);
		unset($this->session->data['card_id']);
		unset($this->session->data['transient_token']);
		unset($this->session->data['security_code']);
		unset($this->session->data['saved_card']);
		unset($this->session->data['reference_id']);
		unset($this->session->data['signed_pareq']);
		unset($this->session->data['auth_transaction_id']);
		unset($this->session->data['enroll_check']);
		unset($this->session->data['uc_payment_method']);
	}

	/**
	 * Prepare delivery address details which is used to store data in DAV table.
	 *
	 * @param array $response Delivery address verification response
	 * @param array $payload_data payload data sended in dav request
	 *
	 * @return array
	 */
	public function prepareDavDetails(array $response, array $payload_data): array {
		$address_details = VAL_NULL;
		$address_details['order_id'] = $payload_data['order_id'];
		$address_details['transaction_id'] = $response['transaction_id'];
		$address_details['recommended_address1'] = $response['address'];
		$address_details['recommended_city'] = $response['city'];
		$address_details['recommended_country'] = $response['country'];
		$address_details['recommended_postal_code'] = $response['postal_code'];
		$address_details['recommended_zone'] = $response['state'];
		$address_details['entered_address1'] = $payload_data['shipping_address_1'];
		$address_details['entered_city'] = $payload_data['shipping_city'];
		$address_details['entered_country'] = $payload_data['shipping_country'];
		$address_details['entered_postal_code'] = $payload_data['shipping_postcode'];
		$address_details['entered_zone'] = $payload_data['shipping_zone'];
		$address_details['status'] = $response['dav_status'];
		$address_details['date_added'] = CURRENT_DATE;
		return $address_details;
	}

	/**
	 * Insert DAV details which is got from insertDavDetails() to DAV table.
	 *
	 * @param array $address_details It will have all the details to be inserted into DAV table.
	 *
	 * @return bool
	 */
	public function insertDavDetails(array $address_details): bool {
		$this->load->model('extension/payment/cybersource_query');
		$return_response = false;
		if (!empty($address_details)) {
			$query_response = $this->model_extension_payment_cybersource_query->queryInsertDavDetails($address_details);
			if (VAL_NULL != $query_response) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	/**
	 * Changes the order status for the perticular order id.
	 *
	 * Here we are using open cart defualt functions by doing some modifications.
	 *
	 * @param int $order_id order id
	 * @param string $order_status_id status which is configured in BO
	 * @param string $custom_status
	 * @param string $comment
	 * @param bool $notify
	 *
	 * @return bool
	 */
	public function addOrderHistory(int $order_id, ?string $order_status_id, string $custom_status, ?string $comment = '', bool $notify = false): bool {
		$query_response = false;
		$this->load->model('checkout/order');
		$this->load->model('account/customer');
		$this->load->model('setting/extension');
		$this->load->model('extension/payment/cybersource_query');
		$order_info = $this->model_checkout_order->getOrder($order_id);
		$current_order_status = (int)$order_info['order_status_id'];
		if ($order_info) {
			$query_update_order = $this->model_extension_payment_cybersource_query->queryUpdateOrderTable($order_status_id, $order_id);
			if (VAL_NULL != $query_update_order && $query_update_order) {
				$query_insert_order = $this->model_extension_payment_cybersource_query->queryInsertOrderHistory($order_id, $order_status_id, $notify, $comment);
				if (VAL_NULL != $query_insert_order && $query_insert_order) {
					$query_response = true;
				}
			}
			$customer_info = $this->model_account_customer->getCustomer($order_info['customer_id']);
			if ($customer_info && $customer_info['safe']) {
				$safe = true;
			} else {
				$safe = false;
			}
			if ((VAL_NULL != $order_status_id) && ($order_status_id != $current_order_status)) {
				$extensions = $this->model_setting_extension->getExtensions('fraud');
				foreach ($extensions as $extension) {
					if ($this->config->get('fraud_' . $extension['code'] . '_status')) {
						$this->load->model('extension/fraud/' . $extension['code']);
						if (property_exists($this->{'model_extension_fraud_' . $extension['code']}, 'check')) {
							$fraud_status_id = $this->{'model_extension_fraud_' . $extension['code']}->check($order_info);
							if ($fraud_status_id) {
								$order_status_id = $fraud_status_id;
							}
						}
					}
				}
			}
			if ((VAL_NULL != $order_status_id) && ($order_status_id != $current_order_status)) {
				$order_totals = $this->model_checkout_order->getOrderTotals($order_id);
				foreach ($order_totals as $order_total) {
					$this->load->model('extension/total/' . $order_total['code']);
					if (property_exists($this->{'model_extension_total_' . $order_total['code']}, 'confirm')) {
						$fraud_status_id = $this->{'model_extension_total_' . $order_total['code']}->confirm($order_info, $order_total);
						if ($fraud_status_id) {
							$order_status_id = $fraud_status_id;
						}
					}
				}
				$order_products = $this->model_checkout_order->getOrderProducts($order_id);
				foreach ($order_products as $order_product) {
					$query_update_product = $this->model_extension_payment_cybersource_query->queryUpdateProductDeductStock($order_product);
					if (VAL_NULL != $query_update_product && $query_update_product) {
						$order_options = $this->model_checkout_order->getOrderOptions($order_id, $order_product['order_product_id']);
						foreach ($order_options as $order_option) {
							$query_update_product_option = $this->model_extension_payment_cybersource_query->queryUpdateProductOptionDeductStock($order_product, $order_option);
							if (VAL_NULL != $query_update_product_option) {
								$return_response = $query_update_product_option;
							}
						}
					}
				}
				if ($order_info['affiliate_id'] && $this->config->get('config_affiliate_auto')) {
					if (!$this->model_account_customer->getTotalTransactionsByOrderId($order_id)) {
						$this->model_account_customer->addTransaction($order_info['affiliate_id'], $this->language->get('text_order_id') . ' #' . $order_id, $order_info['commission'], $order_id);
					}
				}
			}
			$query_insert_status = $this->model_extension_payment_cybersource_query->queryInsertOrderStatus($custom_status, $order_id);
			if (VAL_NULL != $query_insert_status) {
				$query_response = $query_insert_status;
			}
			$this->cache->delete('product');
			return $query_response;
		}
	}

	/**
	 * Common recaptcha function for credit card, eCheck and Tokenization.
	 *
	 * @param string $payment_method based on payment method we will assign url.
	 * @param array $request_data request data which is passed from twig
	 *
	 * @return array
	 */
	public function recaptchaCommon(string $payment_method, array $request_data): array {
		$recaptcha_required = VAL_FLAG_NO;
		$saved_card = VAL_FLAG_NO;
		$response_data = array();
		$this->load->language('extension/credit_card/cybersource');
		$recaptcha_token = $request_data['recaptcha_token'];
		if (PAYMENT_GATEWAY == $payment_method) {
			if (PAYMENT_METHOD_NAME_CC == $this->session->data['uc_payment_method']) {
				$saved_card = $request_data['saved_card'];
				$save_card_check = $request_data['save_card_check'];
			}
			$url = OPENCART_INDEX_PATH . EXTENSION_PAYMENT_GATEWAY . '/confirmFlexData';
		} elseif (PAYMENT_GATEWAY_ECHECK == $payment_method) {
			$url = OPENCART_INDEX_PATH . 'extension/payment/cybersource_echeck/confirm';
		} elseif (PAYMENT_GATEWAY_APPLE_PAY == $payment_method) {
			$url = OPENCART_INDEX_PATH . 'extension/payment/cybersource_apay/confirmPerformValidation';
		} else {
			$url = OPENCART_INDEX_PATH . 'extension/credit_card/cybersource/confirm';
		}
		$csrf = $request_data['csrf'];
		$time = $request_data['time'];
		if (!$recaptcha_token) {
			$response_data['error_flag'] = true;
			$response_data['message'] = $this->language->get('error_recaptcha_load');
		} else {
			if (NO_TOKEN == $recaptcha_token) {
				if (VAL_ONE == $this->config->get('module_' . PAYMENT_GATEWAY . '_recaptcha_status')) {
					$recaptcha_required = VAL_FLAG_NO;
				} else {
					$response_data['error'] = false;
					$response_data[STATUS_SUCCESS] = $url;
					$recaptcha_required = VAL_FLAG_YES;
				}
			} else {
				$recaptcha_secret_key = $this->config->get('module_' . PAYMENT_GATEWAY . '_recaptcha_secret_key');
				$response = $this->getRecaptchaResponse($recaptcha_token, $recaptcha_secret_key);
			}
			if (VAL_FLAG_YES == $recaptcha_required || (isset($response) && $response)) {
				$response_data['error'] = false;
				$response_data[STATUS_SUCCESS] = $url;
				$this->session->data['csrf_token'] = $csrf;
				$this->session->data['csrf_token_time'] = $time;
				if (PAYMENT_GATEWAY == $payment_method && PAYMENT_METHOD_NAME_CC == $this->session->data['uc_payment_method']) {
					if (VAL_FLAG_YES == $saved_card) {
						$this->session->data['recaptcha_success'] = VAL_FLAG_YES;
						$card_id = $request_data['card_id'];
						$sec_code = $request_data['sec_code'];

						$this->session->data['saved_card'] = $saved_card;
						$this->session->data['card_id'] = $card_id;
						$this->session->data['security_code'] = $sec_code;
					} else {
						$this->session->data['save_card_check'] = $save_card_check;
					}
				}
			} else {
				$response_data['error'] = true;
				$response_data['error_warning'] = $this->language->get('error_recaptcha_failed');
			}
		}
		return $response_data;
	}

	/**
	 * Prepares Tax details array which is used to store data in tax table.
	 *
	 * @param object $tax_response_array Tax response
	 *
	 * @return array
	 */
	public function prepareTaxDetails(object $tax_response_array): array {
		$taxable_amount = VAL_ZERO;
		$tax_details = VAL_NULL;
		if (property_exists($tax_response_array->orderInformation, 'taxableAmount')) {
			$taxable_amount = $tax_response_array->orderInformation->taxableAmount;
		}
		$tax_details['tax_id'] = $tax_response_array->clientReferenceInformation->code;
		$tax_details['transaction_id'] = $tax_response_array->id;
		$tax_details['taxable_amount'] = $taxable_amount;
		$tax_details['tax_amount'] = $tax_response_array->orderInformation->taxAmount;
		$tax_details['total_amount'] = $tax_response_array->orderInformation->amountDetails->totalAmount;
		$tax_details['currency'] = $tax_response_array->orderInformation->amountDetails->currency;
		$tax_details['status'] = $tax_response_array->status;
		$tax_details['date_added'] = CURRENT_DATE;
		return $tax_details;
	}

	/**
	 * Insert Tax details which is got from prepareTaxDetails() to Tax table.
	 *
	 * @param array $tax_details
	 *
	 * @return bool
	 */
	public function insertTaxDetails(array $tax_details): bool {
		$query_insert_tax_details = false;
		$this->load->model('extension/payment/cybersource_query');
		if (!empty($tax_details)) {
			$query_response = $this->model_extension_payment_cybersource_query->queryInsertTaxDetails($tax_details);
			if (VAL_NULL != $query_response) {
				$query_insert_tax_details = $query_response;
			}
		}
		return $query_insert_tax_details;
	}

	/**
	 * Gives customer token id from tokenization table if exists, else NULL.
	 *
	 * @param int $customer_id customer id
	 *
	 * @return string|null
	 */
	public function getCustomerTokenId(?int $customer_id): ?string {
		$customer_token_id = VAL_NULL;
		$this->load->model('extension/payment/cybersource_query');
		if (VAL_NULL != $customer_id) {
			$query_customer_token_id = $this->model_extension_payment_cybersource_query->queryCustomerTokenId($customer_id);
			if (VAL_NULL != $query_customer_token_id) {
				$customer_token_id = ($query_customer_token_id->num_rows > VAL_ZERO) ? $query_customer_token_id->row['customer_token_id'] : VAL_NULL;
			}
		}
		return $customer_token_id;
	}

	/**
	 * Based on response from ebc, status and custom status will be returned.
	 *
	 * @param array $response ebc response for credit card or echeck
	 *
	 * @return array
	 */
	public function getOrderStatus(array $response): array {
		$status = VAL_NULL;
		$custom_status = VAL_NULL;
		$general_configuration = $this->getGeneralConfiguration();
		$status_configuration = $this->getOrderStatusConfiguration();

		if (API_STATUS_PENDING == $response['status']) {
			$status = $status_configuration['capture'];
			$custom_status = PAYMENT_ACCEPTED;
		} elseif ((API_STATUS_DECLINED == $response['status']) || (API_STATUS_PENDING_REVIEW == $response['status'])) {
			$status = $status_configuration['fraud_management'];
			$custom_status = FRAUD_MANAGEMENT;
		} elseif (API_STATUS_AUTHORIZED == $response['status'] && (PAYMENT_ACTION_AUTHORIZE == $general_configuration['payment_action'])) {
			$status = $status_configuration['authorization'];
			$custom_status = AWAITING_PAYMENT;
		} elseif ((API_STATUS_AUTHORIZED == $response['status']) && (PAYMENT_ACTION_SALE == $general_configuration['payment_action'])) {
			$status = $status_configuration['capture'];
			$custom_status = PAYMENT_ACCEPTED;
		} elseif (API_STATUS_AUTHORIZED_PENDING_REVIEW == $response['status']) {
			$status = $status_configuration['fraud_management'];
			$custom_status = FRAUD_MANAGEMENT;
		} elseif (API_STATUS_AUTHORIZED_RISK_DECLINED == $response['status']) {
			$status = $status_configuration['fraud_reject'];
			$custom_status = REJECTED;
		}
		return array($status, $custom_status);
	}

	/**
	 * Extract amount and currency from response body.
	 *
	 * @param mixed $http_code http code from response
	 * @param string $http_body http body from response
	 * @param string $service service type
	 *
	 * @return array
	 */
	public function getResponse($http_code, ?string $http_body, string $service): array {
		$response_array = array(
			'status' => VAL_NULL,
			'amount' => VAL_NULL,
			'currency' => VAL_NULL,
			'transaction_id' => VAL_NULL,
			'default_state' => VAL_NULL,
			'customer_token_id' => VAL_NULL,
			'reason' => VAL_NULL
		);
		$response_data = VAL_NULL;
		if (!empty($http_body)) {
			$response_data = json_decode($http_body);
			if (!(empty($response_data->status))) {
				$response_array['status'] = $response_data->status;
			}
			if (!(empty($response_data->id))) {
				$response_array['transaction_id'] = $response_data->id;
			}
			if (!(empty($response_data->errorInformation->reason))) {
				$response_array['reason'] = $response_data->errorInformation->reason;
			}
		}

		if (CODE_TWO_ZERO_ONE == $http_code) {
			if ((PAYMENT_GATEWAY_ECHECK == $service) && (API_STATUS_PENDING == $response_array['status'])) {
				$response_array['amount'] = $response_data->orderInformation->amountDetails->totalAmount;
				$response_array['currency'] = $response_data->orderInformation->amountDetails->currency;
			} elseif ((PAYMENT_GATEWAY_ECHECK == $service) && (API_STATUS_PROCESSOR_ERROR == $response_data->errorInformation->reason)) {
				$response_array['status'] = API_STATUS_PROCESSOR_ERROR;
			} elseif ((SERVICE_AUTH == $service) && ((API_STATUS_AUTHORIZED == $response_array['status'])
				|| (API_STATUS_AUTHORIZED_RISK_DECLINED == $response_array['status'])
				|| (API_STATUS_AUTHORIZED_PENDING_REVIEW == $response_array['status']))) {
				$response_array['amount'] = $response_data->orderInformation->amountDetails->authorizedAmount;
				$response_array['currency'] = $response_data->orderInformation->amountDetails->currency;
			} elseif ((SERVICE_AUTH_REVERSAL == $service) && (API_STATUS_REVERSED == $response_array['status'])) {
				$response_array['amount'] = $response_data->reversalAmountDetails->reversedAmount;
				$response_array['currency'] = $response_data->reversalAmountDetails->currency;
			}
		}
		return $response_array;
	}

	/**
	 * Front office cancel button will be disabled or enabled is decided in this function.
	 *
	 * @param int $order_id order id
	 *
	 * @return string
	 */
	public function cancel(int $order_id): string {
		$this->load->language('extension/payment/cybersource_common');
		$this->load->model('extension/payment/cybersource_query');
		$cancel_button_status = FLAG_DISABLE;
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('account/account', '', true);
			$this->response->redirect($this->url->link('account/login', '', true));
		}
		$this->load->model('checkout/order');
		$csrf = $this->getCSRF();
		$order_details = $this->model_checkout_order->getOrder($order_id);
		$payment_code = $order_details['payment_code'];
		if (PAYMENT_GATEWAY === $payment_code || PAYMENT_GATEWAY_APPLE_PAY === $payment_code) {
			$table_prefix = (PAYMENT_GATEWAY == $payment_code) ? TABLE_PREFIX_UNIFIED_CHECKOUT : TABLE_PREFIX_APPLE_PAY;
			$gift_vouchers = $this->model_checkout_order->getOrderVouchers($order_id);
			$is_gift_certificate_exists = empty($gift_vouchers) ? VAL_ZERO : VAL_ONE;
			$query_extension_status = $this->model_extension_payment_cybersource_query->queryExtensionId($order_id);
			$extension_id = VAL_NULL != $query_extension_status ? (VAL_ZERO < $query_extension_status->num_rows ? $query_extension_status->num_rows : VAL_ZERO) : VAL_ZERO;
			$query_data_exists = $this->model_extension_payment_cybersource_query->queryDataExists($order_id, $table_prefix);
			$transaction_id = VAL_NULL != $query_data_exists ? (VAL_ZERO < $query_data_exists->num_rows ? $query_data_exists->num_rows : VAL_ZERO) : VAL_ZERO;

			if (VAL_ZERO != $transaction_id && VAL_ZERO < $order_id && VAL_ZERO < $extension_id  &&  (PAYMENT_GATEWAY == $order_details['payment_code']) || PAYMENT_GATEWAY_APPLE_PAY == $order_details['payment_code']) {
				$query_current_status = $this->model_extension_payment_cybersource_query->queryStatus($order_id);
				$current_status = VAL_NULL != $query_current_status ? ($query_current_status->num_rows > VAL_ZERO ? $query_current_status->row['cybersource_order_status'] : VAL_NULL) : VAL_NULL;
				$query_payment_action = $this->model_extension_payment_cybersource_query->getPaymentAction($order_id, $csrf, $table_prefix);
				$payment_action = VAL_NULL != $query_payment_action ? (VAL_ZERO < $query_payment_action->num_rows ? $query_payment_action->row['payment_action'] : VAL_NULL) : VAL_NULL;
				$query_capture_row_count = $this->model_extension_payment_cybersource_query->queryCaptureCount($order_id, $table_prefix);
				$capture_row_count = VAL_NULL != $query_capture_row_count ? ($query_capture_row_count->num_rows > VAL_ZERO ? $query_capture_row_count->row['capture_count'] : VAL_ZERO) : VAL_ZERO;
				$query_auth_reversed_row_count = $this->model_extension_payment_cybersource_query->queryAuthRevCount($order_id, $table_prefix);
				$auth_reversed_row_count = VAL_NULL != $query_auth_reversed_row_count ? ($query_auth_reversed_row_count->num_rows > VAL_ZERO ? $query_auth_reversed_row_count->row['auth_rev_count'] : VAL_ZERO) : VAL_ZERO;
				if (VAL_ZERO == (int)$capture_row_count && VAL_ZERO == (int)$auth_reversed_row_count &&  PAYMENT_ACTION_SALE != $payment_action  &&  FRAUD_MANAGEMENT != $current_status && VAL_ZERO == $is_gift_certificate_exists && REJECTED != $current_status) {
					$cancel_button_status = FLAG_ENABLE;
				}
			}
		}
		return $cancel_button_status;
	}

	/**
	 * Front office return order button will be enabled or disbaled is decided using this function.
	 *
	 * @param int $order_id order id
	 *
	 * @return bool
	 */
	public function getReturnData(int $order_id): array {
		$return_flag = false;
		$this->load->model('extension/payment/cybersource_query');
		$query_payment_code = $this->model_extension_payment_cybersource_query->queryPaymentCode($order_id);
		$csrf = $this->getCSRF();
		$payment_code = VAL_NULL != $query_payment_code ? (VAL_ZERO < $query_payment_code->num_rows ? $query_payment_code->row['payment_code'] : VAL_ZERO) : VAL_ZERO;
		if (PAYMENT_GATEWAY == $payment_code || PAYMENT_GATEWAY_APPLE_PAY == $payment_code) {
			$table_prefix = (PAYMENT_GATEWAY == $payment_code) ? TABLE_PREFIX_UNIFIED_CHECKOUT : TABLE_PREFIX_APPLE_PAY;
			$query_payment_action = $this->model_extension_payment_cybersource_query->getPaymentAction($order_id, $csrf, $table_prefix);
			$payment_action = VAL_NULL != $query_payment_action ? (VAL_ZERO < $query_payment_action->num_rows ? $query_payment_action->row['payment_action'] : VAL_NULL) : VAL_NULL;
			$query_order_quantity = $this->model_extension_payment_cybersource_query->queryProductQuantity($order_id);
			$order_quantity = VAL_NULL != $query_order_quantity ? (VAL_ZERO < $query_order_quantity->num_rows ? $query_order_quantity->row['quantity'] : VAL_ZERO) : VAL_ZERO;
			$query_capture_quantity = $this->model_extension_payment_cybersource_query->queryCaptureQuantity($order_id, $table_prefix);
			$capture_quantity = VAL_NULL != $query_capture_quantity ? (VAL_ZERO < $query_capture_quantity->num_rows ? $query_capture_quantity->row['quantity'] : VAL_ZERO) : VAL_ZERO;
			if ($order_quantity != $capture_quantity && PAYMENT_ACTION_AUTHORIZE == $payment_action) {
				$return_flag = true;
			}
		}
		return array("return_flag" => $return_flag, "payment_code" => $payment_code);
	}

	public function getCSRF() {
		$this->load->model('extension/payment/cybersource_common');
		$csrf = $this->session->data['csrf'] ?? VAL_EMPTY;
		$this->model_extension_payment_cybersource_common->generateToken($csrf);
		return $this->session->data['csrf'] ?? VAL_EMPTY;
	}

	/**
	 * Gives client reference part which is used in payload.
	 *
	 * @param int $order_id order id
	 *
	 * @return array
	 */
	public function getClientReferenceInfo($order_id): array {
		$client_reference_payload = array(
			"code" => TypeConversion::convertDataToType($order_id, 'string'),
			"partner" => array(
				"developerId" => TypeConversion::convertDataToType($this->config->get('module_' . PAYMENT_GATEWAY . '_developer_id'), 'string'),
				"solutionId" => CODE_PARTNER_SOLUTION_ID
			)
		);
		return $client_reference_payload;
	}

	/**
	 * Formating dav recommended and entered address data to display according to open cart standards.
	 *
	 * @param array $payload_data payload data which is sended to ebc
	 * @param array $dav_response_array It will have dav response
	 *
	 * @return array
	 */
	public function getDavAddressFormat(array $payload_data, array $dav_response_array): array {
		$format = $payload_data['shipping_address_format'] ? $payload_data['shipping_address_format'] : '{firstname} {lastname}' . "\n" . '{company}' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city} {postcode}' . "\n" . '{zone}' . "\n" . '{country}';
		$find = array(
			'{firstname}',
			'{lastname}',
			'{company}',
			'{address_1}',
			'{address_2}',
			'{city}',
			'{postcode}',
			'{zone}',
			'{zone_code}',
			'{country}'
		);
		$address1 = $payload_data['shipping_address_1'] ?? VAL_NULL;
		$address2 = $payload_data['shipping_address_2'] ?? VAL_NULL;
		$address = $address1 . ' ' . $address2;
		$replace = array(
			'firstname' => $payload_data['shipping_firstname'] ?? VAL_NULL,
			'lastname'  => $payload_data['shipping_lastname'] ?? VAL_NULL,
			'company'   => VAL_NULL,
			'address_1' => $address,
			'address_2' => VAL_NULL,
			'city'      => $payload_data['shipping_city'] ?? VAL_NULL,
			'postcode'  => $payload_data['shipping_postcode'] ?? VAL_NULL,
			'zone'      => $payload_data['shipping_zone'] ?? VAL_NULL,
			'zone_code' => $payload_data['shipping_zone_code'] ?? VAL_NULL,
			'country'   => $payload_data['shipping_country'] ?? VAL_NULL
		);
		$entered_address = array(
			'address'  		=> str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', trim(str_replace($find, $replace, $format))))
		);
		$replace = array(
			'firstname' => $payload_data['shipping_firstname'] ?? VAL_NULL,
			'lastname'  => $payload_data['shipping_lastname'] ?? VAL_NULL,
			'company'   => VAL_NULL,
			'address_1' => $dav_response_array['address'] ?? VAL_NULL,
			'address_2' => VAL_NULL,
			'city'      => $dav_response_array['city'] ?? VAL_NULL,
			'postcode'  => $dav_response_array['postal_code'] ?? VAL_NULL,
			'zone'      => $dav_response_array['state'] ?? VAL_NULL,
			'zone_code' => $dav_response_array['zone_code'] ?? VAL_NULL,
			'country'   => $dav_response_array['country'] ?? VAL_NULL
		);
		$recommended_address = array(
			'address'       => str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', trim(str_replace($find, $replace, $format))))
		);
		return array($entered_address, $recommended_address);
	}

	/**
	 * DAV Common function for credit card and eCheck.
	 *
	 * Call DAV service and based on response we will change the twig part.
	 *
	 * @param int $order_id order id
	 * @param string $csrf generated csrf token
	 *
	 * @return array
	 */
	public function davCommon(int $order_id, string $csrf): array {
		$this->load->language('extension/payment/cybersource_loggers');
		$data['error_address'] = VAL_EMPTY;
		$data['address_match'] = VAL_ZERO;
		$general_configuration = $this->getGeneralConfiguration();
		if ($this->cart->hasShipping() && $this->config->get('module_' . PAYMENT_GATEWAY . '_dav_status')) {
			list($api_response, $payload_data) = $this->getDAVResponse($order_id);
			$dav_response_array = $this->getDAVResponseArray($api_response);
			if (CODE_TWO_ZERO_ONE == $api_response['http_code']) {
				if (STATUS_SUCCESS == $dav_response_array['dav_status']) {
					if (VAL_NULL == $dav_response_array['state']) {
						$dav_response_array['state'] = $payload_data['shipping_zone'];
					}
					$address_details = $this->prepareDavDetails($dav_response_array, $payload_data);
					$result = $this->insertDavDetails($address_details);
					if (!$result) {
						$this->logger('[ModelExtensionPaymentCybersourceCommon][davCommon]:' . $this->language->get('error_dav_table_insertion'));
					}
					if (
						$dav_response_array['address'] == $payload_data['shipping_address_1']
						&& $dav_response_array['city'] == $payload_data['shipping_city']
						&& $dav_response_array['country'] == $payload_data['shipping_country']
						&& $dav_response_array['postal_code'] == $payload_data['shipping_postcode']
					) {
						$data['address_match'] = VAL_ONE;
					} else {
						list($data['entered_address_format'], $data['recommended_address_format']) = $this->getDavAddressFormat($payload_data, $dav_response_array);
						$data['recommended_address'] = $dav_response_array['address'];
						$data['recommended_city'] = $dav_response_array['city'];
						$data['recommended_state'] = $dav_response_array['state'];
						$data['recommended_country'] = $dav_response_array['country'];
						$data['recommended_postal_code'] = $dav_response_array['postal_code'];

						$data['entered_address'] = $payload_data['shipping_address_1'];
						$data['entered_city'] = $payload_data['shipping_city'];
						$data['entered_state'] = $payload_data['shipping_zone'];
						$data['entered_country'] = $payload_data['shipping_country'];
						$data['entered_postal_code'] = $payload_data['shipping_postcode'];
					}
				} elseif (STATUS_ERROR == $dav_response_array['dav_status']) {
					$data['error_address'] = $dav_response_array['message'];
				}
			} else {
				$data['error_address'] = $dav_response_array['message'];
			}
		} else {
			$data['address_match'] = VAL_ONE;
		}
		$this->generateToken($csrf);
		$data['recaptcha_enabled'] = $general_configuration['recaptcha_enabled'];
		$data['recaptcha_site_key'] = $general_configuration['recaptcha_site_key'];
		$data['dfp_url'] = $this->getDfpUrl();
		return $data;
	}

	/**
	 * Logs the data to cyberource.log file. defualt path = system\storage\logs or inside storage confifured path.
	 *
	 * @param string $message data that we want ro log
	 *
	 * @return void
	 */
	public function logger(string $message) {
		if ($this->config->get('module_' . PAYMENT_GATEWAY . '_enhanced_logs')) {
			$log = new Log('cybersource.log');
			$log->write($message);
		}
	}

	/**
	 * Returns redirect url based on parameter value.
	 *
	 * @param string $log_msg message we want to log
	 * @param string $url page name where we want to redirect
	 *
	 * @return string
	 */
	public function getReturnUrl(string $log_msg, string $url): string {
		if (VAL_ZERO != strcmp(STATUS_SUCCESS, $url)) {
			$this->logger($log_msg);
		}
		return $this->url->link('checkout/' . $url);
	}

	/**
	 * Converts language code to <ISO 639 language code>_<ISO 3166 region code> format.
	 * Eg:- 1) de-de ===> de_DE
	 * 2) en-gb ====> en_GB
	 * 3) ar ====> ar_.
	 *
	 * @param string $language_code
	 *
	 * @return string
	 */
	public function getLocale(string $language_code): string {
		$locale_array = explode(HYPEN, $language_code);
		$locale = $locale_array[VAL_ZERO] . UNDER_SCORE . strtoupper($locale_array[VAL_ONE] ?? VAL_EMPTY);
		return $locale;
	}
}
