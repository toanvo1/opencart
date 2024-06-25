<?php

use Isv\Common\Helper\TypeConversion;

require_once DIR_SYSTEM . 'library/isv/cybersource_constants.php';

/**
 * eCheck Model file.
 *
 * @author Cybersource
 * @package Front Office
 * @subpackage Model
 */
class ModelExtensionPaymentCybersourceEcheck extends Model {
	/**
	 * Opencart by defualt call this function to get payment information.
	 *
	 * @param string $address
	 * @param int $total
	 *
	 * @return array
	 */
	public function getMethod($address, $total): array {
		$this->load->language('extension/payment/cybersource_echeck');
		$payment_display_data = array();
		if (VAL_ZERO < $total) {
			$payment_display_data = array(
				'code'       => PAYMENT_GATEWAY_ECHECK,
				'title'      => $this->language->get('text_title'),
				'terms'      => VAL_EMPTY,
				'sort_order' => $this->config->get('payment_' . PAYMENT_GATEWAY_ECHECK . '_sort_order')
			);
		}
		return $payment_display_data;
	}

	/**
	 * Prepares order details which is used to store data in order table.
	 *
	 * @param array $response ebc eCheck response
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
		$order_details['payment_action'] = PAYMENT_ACTION_SALE;
		$order_details['currency'] = $payload_data['currency_code'];
		$order_details['order_quantity'] = $total_quantity;
		$order_details['tax_id'] = $tax_id;
		$order_details['amount'] = $payload_data['total'];
		$order_details['date_added'] = CURRENT_DATE;
		return $order_details;
	}

	/**
	 * Insert order details which is got from prepareOrderDetails() to order table.
	 *
	 * @param array $order_details
	 *
	 * @return bool
	 */
	public function insertOrderDetails(array $order_details): bool {
		$return_response = false;
		$this->load->model('extension/payment/cybersource_query');
		if (!empty($order_details)) {
			$query_response = $this->model_extension_payment_cybersource_query->queryInsertOrder($order_details, TABLE_PREFIX_ECHECK);
			if (VAL_NULL != $query_response) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	/**
	 * Payload will get created with necessary data and send that to eCheck end point.
	 *
	 * @param int $account_number account number - mandatory field for eCheck
	 * @param string $account_type account type - mandatory field for eCheck
	 * @param int $account_routing_number routing number - mandatory field for eCheck
	 * @param array $payload_data It will have data that are needed to create payload
	 * @param array $line_items It will have line items details
	 * @param int $order_id order id
	 *
	 * @return array
	 */
	public function getPaymentResponse($account_number, string $account_type, $account_routing_number, array $payload_data, array $line_items, int $order_id): array {
		$this->load->model('extension/payment/cybersource_common');
		$general_configuration = $this->model_extension_payment_cybersource_common->getGeneralConfiguration();
		$session_id = (VAL_ONE == $general_configuration['dfp']) ? TypeConversion::convertDataToType($general_configuration['session_id'], 'string') : VAL_EMPTY;
		$client_reference_info = $this->model_extension_payment_cybersource_common->getClientReferenceInfo($order_id);
		$payload = array(
			"clientReferenceInformation" => $client_reference_info,
			"processingInformation" => array(
				"commerceIndicator" => INTERNET,
				"bankTransferOptions" => array(
					"secCode" => SEC_CODE_WEB
				),
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
				"bank" => array(
					"account" => array(
						"number" => TypeConversion::convertDataToType($account_number, 'string'),
						"type" => TypeConversion::convertDataToType($account_type, 'string')
					),
					"routingNumber" => TypeConversion::convertDataToType($account_routing_number, 'string')
				),
				"paymentType" => array(
					"name" => CHECK
				)
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
}
