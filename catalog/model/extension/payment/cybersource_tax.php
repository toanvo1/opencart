<?php

use Isv\Common\Helper\TypeConversion;

require_once DIR_SYSTEM . 'library/isv/cybersource_constants.php';

/**
 * The functions related to tax service are in this file.
 *
 * @author Cybersource
 * @package Front Office
 * @subpackage Model
 */
class ModelExtensionPaymentCybersourceTax extends Model {
	/**
	 * Gives line items which will be used to payload.
	 *
	 * @param array $product_details It will have product details for perticular order.
	 *
	 * @return array
	 */
	private function getLineItemArray(array $product_details): array {
		$this->load->language('extension/payment/cybersource');
		$line_items = array();
		$size_of_roducts = sizeof($product_details);
		for ($i = VAL_ZERO; $i < $size_of_roducts; $i++) {
			$line_items['' . $i . ''] = array(
				"productCode" => PRODUCT_CODE_DEFAULT,
				"productName" => TypeConversion::convertDataToType($product_details['' . $i . '']['name'], 'string'),
				"productSKU" => TypeConversion::convertDataToType($product_details['' . $i . '']['product_id'], 'string'),
				"quantity" => TypeConversion::convertDataToType($product_details['' . $i . '']['quantity'], 'integer'),
				"unitPrice" => TypeConversion::convertDataToType($product_details['' . $i . '']['price'], 'string')
			);
		}
		if ($this->cart->hasShipping()) {
			$shipping_cost = $this->session->data['shipping_method']['cost'];
			if (VAL_ZERO < $shipping_cost) {
				$line_items['' . $i++ . ''] = array(
					"productCode" => SHIPPING_AND_HANDLING,
					"productName" => SHIPPING_AND_HANDLING,
					"productSKU" => SHIPPING_AND_HANDLING,
					"quantity" => VAL_ONE,
					"unitPrice" => TypeConversion::convertDataToType($shipping_cost, 'string')
				);
			}
		}
		return $line_items;
	}

	/**
	 * Creates payload and send request to tax end point.
	 *
	 * @param array $payload_data Contains data used for payload creation.
	 *
	 * @return array
	 */
	private function getTaxResponse(array $payload_data): array {
		$payload_data['payment_zone_code'] = VAL_EMPTY;
		$payload_data['shipping_zone_code'] = VAL_EMPTY;
		$this->load->model('extension/payment/cybersource_common');
		$this->load->model('extension/payment/cybersource_query');
		$line_items = $this->getLineItemArray($payload_data['products']);
		$payment_zone_query = $this->model_extension_payment_cybersource_query->queryZoneCode($payload_data['payment_zone_id']);
		if (VAL_NULL != $payment_zone_query) {
			$payload_data['payment_zone_code'] = (VAL_ZERO < $payment_zone_query->num_rows) ? $payment_zone_query->row['code'] : VAL_EMPTY;
		}
		$payment_country_query = $this->model_extension_payment_cybersource_query->queryIsoCode($payload_data['payment_country_id']);
		$payload_data['payment_iso_code_2'] = (VAL_NULL != $payment_country_query && VAL_ZERO < $payment_country_query->num_rows) ? $payment_country_query->row['iso_code_2'] : VAL_EMPTY;
		$shipping_zone_query = $this->model_extension_payment_cybersource_query->queryZoneCode($payload_data['shipping_zone_id']);
		if (VAL_NULL != $shipping_zone_query) {
			$payload_data['shipping_zone_code']  = (VAL_ZERO < $shipping_zone_query->num_rows) ? $shipping_zone_query->row['code'] : VAL_EMPTY;
		}
		$shipping_country_query = $this->model_extension_payment_cybersource_query->queryIsoCode($payload_data['shipping_country_id']);
		$payload_data['shipping_iso_code_2'] = (VAL_NULL != $shipping_country_query && VAL_ZERO < $shipping_country_query->num_rows) ? $shipping_country_query->row['iso_code_2'] : VAL_EMPTY;
		$merchant_ref = $this->model_extension_payment_cybersource_common->generateMerchantRef();
		$client_reference_info = $this->model_extension_payment_cybersource_common->getClientReferenceInfo($merchant_ref);
		$payload = array(
			"clientReferenceInformation" => $client_reference_info,
			"orderInformation" => array(
				"billTo" => array(
					"address1" => TypeConversion::convertDataToType($payload_data['payment_address_1'], 'string'),
					"postalCode" => TypeConversion::convertDataToType($payload_data['payment_postcode'], 'string'),
					"locality" => TypeConversion::convertDataToType($payload_data['payment_city'], 'string'),
					"administrativeArea" => TypeConversion::convertDataToType($payload_data['payment_zone_code'], 'string'),
					"country" => TypeConversion::convertDataToType($payload_data['payment_iso_code_2'], 'string'),
				),
				"shipTo" => array(
					"address1" => TypeConversion::convertDataToType($payload_data['shipping_address_1'], 'string'),
					"postalCode" => TypeConversion::convertDataToType($payload_data['shipping_postcode'], 'string'),
					"locality" => TypeConversion::convertDataToType($payload_data['shipping_city'], 'string'),
					"administrativeArea" => TypeConversion::convertDataToType($payload_data['shipping_zone_code'], 'string'),
					"country" => TypeConversion::convertDataToType($payload_data['shipping_iso_code_2'], 'string'),
				),
				"lineItems" => $line_items,
				"amountDetails" => array(
					"currency" => TypeConversion::convertDataToType($payload_data['currency_code'], 'string')
				),
			)
		);
		$payload = json_encode($payload);
		$resource = RESOURCE_TAX;
		$api_response = $this->model_extension_payment_cybersource_common->serviceProcessor($payload, $resource, true, VAL_EMPTY);
		return $api_response;
	}

	/**
	 * Calulates the tax amount got from the tax response and pass that to calling function.
	 *
	 * @param array $total
	 * @param array $order_data
	 *
	 * @return array
	 */
	public function getTotal(array $total, array $order_data): array {
		$i = VAL_ZERO;
		$tax_total = VAL_ZERO;
		$response_data = VAL_NULL;
		$api_response['http_code'] = VAL_ZERO;
		$product_data = VAL_NULL;
		$this->load->model('extension/payment/cybersource_common');
		$this->load->language('extension/payment/cybersource_common');
		$this->load->model('localisation/currency');
		if (!empty($order_data['products'])) {
			$order_data['currency_code'] = $this->config->get('config_currency');
			$currency_info = $this->model_localisation_currency->getCurrencyByCode($order_data['currency_code']);
			$api_response = $this->getTaxResponse($order_data);
			if (VAL_NULL != $api_response) {
				$response_body = $api_response['body'];
				if (!empty($response_body)) {
					$response_data = json_decode($response_body);
				}
				if (CODE_TWO_ZERO_ONE == $api_response['http_code']) {
					$prepare_tax_details = $this->model_extension_payment_cybersource_common->prepareTaxDetails($response_data);
					$this->model_extension_payment_cybersource_common->insertTaxDetails($prepare_tax_details);
					$this->session->data['tax_id'] = $response_data->clientReferenceInformation->code;

					foreach ($this->cart->getProducts() as $product) {
						$option_data = array();
						$product_data['products'][] = array(
							'product_id' => $product['product_id'],
							'name'       => $product['name'],
							'model'      => $product['model'],
							'option'     => $option_data,
							'download'   => $product['download'],
							'quantity'   => $product['quantity'],
							'subtract'   => $product['subtract'],
							'price'      => $product['price'],
							'total'      => $product['total'],
							'tax'        => bcdiv($response_data->orderInformation->lineItems[$i]->taxAmount, $product['quantity'], $currency_info['decimal_place']),
							'reward'     => $product['reward']
						);
						$tax_total += (bcdiv($response_data->orderInformation->lineItems[$i]->taxAmount, $product['quantity'], $currency_info['decimal_place']) * $product['quantity']);
						$i++;
					}
					if ($i < sizeof($response_data->orderInformation->lineItems)) {
						$tax_total += $response_data->orderInformation->lineItems[$i]->taxAmount;
					}
					$total['total'] += $tax_total;
					$total['totals'][] = array(
						'code'       => PAYMENT_GATEWAY,
						'title'      => $this->language->get('text_tax_title'),
						'value'      => $tax_total,
						'sort_order' => $this->config->get('total_cybersource_sort_order')
					);
				}
			}
		}
		return array($response_data, $api_response['http_code'], $product_data);
	}
}
