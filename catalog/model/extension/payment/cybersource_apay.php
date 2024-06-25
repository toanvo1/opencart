<?php

use Isv\Common\Helper\TypeConversion;

require_once DIR_SYSTEM . 'library/isv/cybersource_constants.php';

/**
 * Apple pay Model file.
 *
 * @author Cybersource
 * @package Front Office
 * @subpackage Model
 */
class ModelExtensionPaymentCybersourceApay extends Model {
	/**
	 * Opencart by defualt call this function to get payment information.
	 *
	 * @param string $address
	 * @param int $total
	 *
	 * @return array
	 */
	public function getMethod($address, $total): array {
		$this->load->language('extension/payment/cybersource_apay');
		$payment_display_data = array();
		if (VAL_ZERO < $total) {
			$payment_display_data = array(
				'code'       => PAYMENT_GATEWAY_APPLE_PAY,
				'title'      => $this->language->get('text_title'),
				'terms'      => VAL_EMPTY,
				'sort_order' => $this->config->get('payment_' . PAYMENT_GATEWAY_APPLE_PAY . '_sort_order')
			);
		}
		return $payment_display_data;
	}

	/**
	 * Prepares order details which is used to store data in order table.
	 *
	 * @param array $response ebc Apple pay response
	 * @param array $payload_data ebc request payload
	 * @param int $total_quantity total quantity of products that is ordered
	 * @param string $tax_id tax id from tax table
	 *
	 * @return array
	 */
	public function prepareOrderDetails(array $response, array $payload_data, int $total_quantity, string $status, ?string $tax_id): array {
		$order_details = VAL_NULL;
		$order_details['order_id'] = $payload_data['order_id'];
		$order_details['transaction_id'] = $response['transaction_id'];
		$order_details['cybersource_order_status'] = $response['status'];
		$order_details['oc_order_status'] = $status;
		$order_details['payment_action'] = $this->config->get('module_' . PAYMENT_GATEWAY . '_transaction_method');
		$order_details['currency'] = $response['currency'];
		$order_details['order_quantity'] = $total_quantity;
		$order_details['tax_id'] = $tax_id;
		$order_details['amount'] = $response['amount'];
		$order_details['date_added'] = CURRENT_DATE;
		return $order_details;
	}

	/**
	 * Payload will get created with necessary data and send that to Apple pay end point.
	 *
	 * @param int $account_number account number - mandatory field for Apple pay
	 * @param string $account_type account type - mandatory field for Apple pay
	 * @param int $account_routing_number routing number - mandatory field for Apple pay
	 * @param array $payload_data It will have data that are needed to create payload
	 * @param array $line_items It will have line items details
	 * @param int $order_id order id
	 *
	 * @return array
	 */
	public function getPaymentResponse($payment_data, array $payload_data, array $line_items, int $order_id): array {
		$this->load->model('extension/payment/cybersource_common');
		$general_configuration = $this->model_extension_payment_cybersource_common->getGeneralConfiguration();
		$applepay_configuration = $this->model_extension_payment_cybersource_common->getApplePayConfiguration();
		$session_id = (VAL_ONE == $general_configuration['dfp']) ? TypeConversion::convertDataToType($general_configuration['session_id'], 'string') : VAL_EMPTY;
		$client_reference_info = $this->model_extension_payment_cybersource_common->getClientReferenceInfo($order_id);
		$payload = array(
			"clientReferenceInformation" => $client_reference_info,
			"processingInformation" => array(
				"commerceIndicator" => INTERNET,
				"paymentSolution" => PAYMENT_SOLUTION_APPLE_PAY,
				"capture" => TypeConversion::convertDataToType($applepay_configuration['capture'], 'boolean'),
				"actionList" => TypeConversion::convertArrayToType(array($general_configuration['fraud_status']), array('string'))
			),
			"orderInformation" => array(
				"billTo" => array(
					"firstName" => TypeConversion::convertDataToType($payload_data['payment_firstname'], 'string'),
					"lastName" => TypeConversion::convertDataToType($payload_data['payment_lastname'], 'string'),
					"address1" => TypeConversion::convertDataToType($payload_data['payment_address_1'], 'string'),
					"postalCode" => TypeConversion::convertDataToType($payload_data['payment_postcode'], 'string'),
					"locality" => TypeConversion::convertDataToType($payload_data['payment_city'], 'string'),
					"administrativeArea" => TypeConversion::convertDataToType($payload_data['payment_zone_code'], 'string'),
					"country" => TypeConversion::convertDataToType($payload_data['payment_iso_code_2'], 'string'),
					"phoneNumber" => TypeConversion::convertDataToType($payload_data['telephone'], 'string'),
					"email" => TypeConversion::convertDataToType($payload_data['email'], 'string')
				),
				"shipTo" => array(
					"firstName" => TypeConversion::convertDataToType($payload_data['shipping_firstname'], 'string'),
					"lastName" => TypeConversion::convertDataToType($payload_data['shipping_lastname'], 'string'),
					"address1" => TypeConversion::convertDataToType($payload_data['shipping_address_1'], 'string'),
					"postalCode" => TypeConversion::convertDataToType($payload_data['shipping_postcode'], 'string'),
					"locality" => TypeConversion::convertDataToType($payload_data['shipping_city'], 'string'),
					"administrativeArea" => TypeConversion::convertDataToType($payload_data['shipping_zone_code'], 'string'),
					"country" => TypeConversion::convertDataToType($payload_data['shipping_iso_code_2'], 'string'),
					"phoneNumber" => TypeConversion::convertDataToType($payload_data['telephone'], 'string'),
					"email" => TypeConversion::convertDataToType($payload_data['email'], 'string')
				),
				"lineItems" => $line_items,
				"amountDetails" => array(
					"totalAmount" => TypeConversion::convertDataToType($payload_data['total'], 'string'),
					"currency" => TypeConversion::convertDataToType($payload_data['currency_code'], 'string')
				),
			),
			"paymentInformation" => array(
				"fluidData" => array(
					"value" => TypeConversion::convertDataToType($payment_data, 'string')
				),
			),
			"deviceInformation" => array(
				"fingerprintSessionId" => $session_id
			)
		);
		$payload = json_encode($payload);
		$resource = RESOURCE_PTS_V2_PAYMENTS;
		$api_response = $this->model_extension_payment_cybersource_common->serviceProcessor($payload, $resource, true, VAL_EMPTY);
		return $api_response;
	}

	/**
	 * Returns Apple pay domains as per environment(sandbox/production).
	 *
	 * @return array
	 */
	public function getWhiteListDomainAsPerEnvironment(): array {
		$environment = $this->config->get('module_' . PAYMENT_GATEWAY . '_sandbox');
		if (ENVIRONMENT_TEST === $environment) {
			return $this->getTestingWhiteListedDomain();
		} elseif (ENVIRONMENT_LIVE === $environment) {
			return $this->getProductionWhiteListedDomain();
		}
	}

	/**
	 * Returns Apple pay domains for live/production environment.
	 *
	 * @return array
	 */
	public function getProductionWhiteListedDomain(): array {
		return array(
			'apple-pay-gateway.apple.com',
			'cn-apple-pay-gateway.apple.com',
			'apple-pay-gateway-nc-pod1.apple.com',
			'apple-pay-gateway-nc-pod2.apple.com',
			'apple-pay-gateway-nc-pod3.apple.com',
			'apple-pay-gateway-nc-pod4.apple.com',
			'apple-pay-gateway-nc-pod5.apple.com',
			'apple-pay-gateway-pr-pod1.apple.com',
			'apple-pay-gateway-pr-pod2.apple.com',
			'apple-pay-gateway-pr-pod3.apple.com',
			'apple-pay-gateway-pr-pod4.apple.com',
			'apple-pay-gateway-pr-pod5.apple.com',
			'cn-apple-pay-gateway-sh-pod1.apple.com',
			'cn-apple-pay-gateway-sh-pod2.apple.com',
			'cn-apple-pay-gateway-sh-pod3.apple.com',
			'cn-apple-pay-gateway-tj-pod1.apple.com',
			'cn-apple-pay-gateway-tj-pod2.apple.com',
			'cn-apple-pay-gateway-tj-pod3.apple.com',
		);
	}

	/**
	 * Returns Apple pay domains for test/sandbox environment.
	 *
	 * @return array
	 */
	public function getTestingWhiteListedDomain(): array {
		return array(
			'apple-pay-gateway-cert.apple.com',
			'cn-apple-pay-gateway-cert.apple.com',
		);
	}

	/**
	 * Validates URL and checks for scheme is http or https.
	 *
	 * @param string $url URL which need to be validated
	 *
	 * @return bool
	 */
	public function isValidUrl(string $url): bool {
		$allowed_url_schemes = array('http', 'https');
		$valid_url = false !== filter_var($url, FILTER_VALIDATE_URL);
		if ($valid_url) {
			$scheme = parse_url($url, PHP_URL_SCHEME) ?? VAL_EMPTY;
			return in_array($scheme, $allowed_url_schemes, true);
		}
		return $valid_url;
	}
}
