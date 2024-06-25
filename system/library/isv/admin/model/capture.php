<?php

namespace Isv\Admin\Model;

use Isv\Admin\Model\Common;
use Isv\Common\Helper\TypeConversion;
use Isv\Common\Payload\PaymentProcessingInformation;

trait Capture {
	use Common;

	public function executeCapture(?int $order_id, array $order_details, $product_details, ?int $total_quantity, string $file_name, string $table_name): array {
		$is_shipping_included = VAL_FLAG_NO;
		$is_shipping_captured = false;
		$response_data = array();
		$this->load->model('extension/payment/cybersource_common');
		$this->load->model('extension/payment/cybersource_query');
		$this->load->language('extension/payment/' . $file_name);
		$this->load->language('extension/payment/cybersource_common');
		$this->load->language('extension/payment/cybersource_logger');
		list($shipping_cost, $shipping_tax, $voucher_amount, $coupon_amount) = $this->model_extension_payment_cybersource_common->getShippingCost($order_id);
		$query_capture_data = $this->model_extension_payment_cybersource_query->getShippingOrderProductId($order_id, $table_name);
		$size_of_capture_data = $query_capture_data->num_rows;
		if (VAL_ZERO < $size_of_capture_data) {
			for ($i = VAL_ZERO; $i < $size_of_capture_data; $i++) {
				if (SHIPPING_AND_HANDLING == $query_capture_data->rows['' . $i . '']['order_product_id']) {
					$is_shipping_captured = true;
					break;
				}
			}
		}
		if (VAL_ZERO != $shipping_cost && !$is_shipping_captured) {
			$is_shipping_included = VAL_FLAG_YES;
		}
		$capture_response = $this->getCaptureResponse($order_details, $product_details, $is_shipping_included, $shipping_cost, $shipping_tax, $voucher_amount, $coupon_amount, $order_id, $file_name);
		if (VAL_NULL != $capture_response) {
			$capture_response_array = $this->model_extension_payment_cybersource_common->getResponse($capture_response['http_code'], $capture_response['body'], SERVICE_CAPTURE, $file_name);
			if (CODE_TWO_ZERO_ONE == $capture_response['http_code'] && API_STATUS_PENDING == $capture_response_array['status']) {
				$capture_details = $this->prepareCaptureDetails($order_id, $capture_response_array, $total_quantity, $this->config->get('module_' . PAYMENT_GATEWAY . '_capture_status_id'), $is_shipping_included);
				$is_insertion_success = $this->model_extension_payment_cybersource_query->queryInsertCaptureDetails($capture_details, $table_name);
				$this->model_extension_payment_cybersource_common->addOrderHistory($order_id, $this->config->get('module_' . PAYMENT_GATEWAY . '_capture_status_id'), PAYMENT_ACCEPTED);
				if (!$is_insertion_success) {
					$response_data['error_flag'] = true;
					$response_data['message'] = $this->language->get('warning_msg_capture_insertion');
				} else {
					$response_data['error_flag'] = false;
					$response_data['message'] = $this->language->get('success_msg_capture');
				}
			} else {
				$this->model_extension_payment_cybersource_common->addOrderHistory($order_id, $this->config->get('module_' . PAYMENT_GATEWAY . '_payment_error_status_id'), PAYMENT_ACCEPTED_ERROR);
				$response_data['error_flag'] = true;
				$response_data['message'] = $this->language->get('error_msg_capture');
				$this->model_extension_payment_cybersource_common->logger('[ModelExtensionPayment' . $file_name . ']
				[executeCapture] : Failure Response - ' . $capture_response['body']);
			}
		} else {
			$response_data['error_flag'] = true;
			$response_data['message'] = $this->language->get('error_msg_capture');
			$this->model_extension_payment_cybersource_common->logger('[ModelExtensionPayment' . $file_name . ']
				[executeCapture] : Failure Response - ' . $this->language->get('error_response_info'));
		}
		return $response_data;
	}

	public function prepareCaptureDetails($order_id, $capture_response_array, $quantity, $order_status, $is_shipping_included) {
		$capture_details = VAL_NULL;
		if (!empty($order_id) && !empty($capture_response_array) && !empty($order_status) && !empty($is_shipping_included)) {
			$capture_details['order_id'] = $order_id;
			$capture_details['transaction_id'] = $capture_response_array['transaction_id'];
			$capture_details['cybersource_order_status'] = $capture_response_array['status'];
			$capture_details['oc_order_status'] = $order_status;
			$capture_details['currency'] = $capture_response_array['currency'];
			$capture_details['capture_quantity'] = $quantity;
			$capture_details['amount'] = $capture_response_array['amount'];
			$capture_details['order_product_id'] = '';
			$capture_details['shipping_flag'] = $is_shipping_included;
			$capture_details['sequence_count'] = '';
			$capture_details['void_flag'] = VAL_FLAG_NO;
			$capture_details['refunded_amount'] = VAL_ZERO;
			$capture_details['refunded_quantity'] = VAL_ZERO;
			$capture_details['date_added'] = CURRENT_DATE;
		}
		return $capture_details;
	}

	public function getCaptureResponse(array $order_details, $product_details, string $is_shipping_included, float $shipping_cost, float $shipping_tax, float $voucher_amount, float $coupon_amount, ?int $order_id, string $file_name): array {
		$this->load->model('extension/payment/cybersource_common');
		$this->load->model('sale/order');
		$voucher_details = $this->model_sale_order->getOrderVouchers($order_id);
		$line_items = $this->getLineItemArray($order_id, $product_details, $voucher_details, $shipping_cost, $shipping_tax, $is_shipping_included, $voucher_amount, $coupon_amount);
		$amount = number_format($order_details['amount'], VAL_TWO, '.', '');
		$client_reference_info = $this->model_extension_payment_cybersource_common->getClientReferenceInfo($order_id);
		$payload = array(
			"clientReferenceInformation" => $client_reference_info,
			"orderInformation" => array(
				"amountDetails" => array(
					"totalAmount" => TypeConversion::convertDataToType($amount, 'string'),
					"currency" => TypeConversion::convertDataToType($order_details['currency'], 'string')
				),
				"lineItems" => $line_items
			)
		);
		$payment_processing_information = new PaymentProcessingInformation($this->registry);
		if (PAYMENT_GATEWAY === $file_name || PAYMENT_GATEWAY_APPLE_PAY === $file_name) {
			$payload = $payment_processing_information->paymentSolution($payload, $file_name, $order_id);
		}
		$payload = json_encode($payload);
		$resource = RESOURCE_PTS_V2_PAYMENTS . $order_details['transaction_id'] . RESOURCE_CAPTURES;
		$api_response = $this->model_extension_payment_cybersource_common->serviceProcessor($payload, $resource);
		return $api_response;
	}

	public function getCaptureAmount(?array $oc_order_details, ?array $value): float {
		$capture_amount = VAL_ZERO;
		$size_of_order_details = sizeof($oc_order_details);
		if (VAL_ZERO < $size_of_order_details) {
			for ($i = VAL_ZERO; $i < $size_of_order_details; $i++) {
				$partial_amount = $oc_order_details['' . $i . '']['price'];
				$tax_amount = $oc_order_details['' . $i . '']['tax'];
				$capture_amount = $capture_amount + ($partial_amount * $value[$i]) + ($tax_amount * $value[$i]);
			}
		}
		return $capture_amount;
	}

	public function getPartialCaptureResponse(?int $order_id, int $total_capture_count, int $seq_number, array $line_items, float $capture_amount, array $order_details, string $file_name): array {
		$this->load->model('extension/payment/cybersource_common');
		$client_reference_info = $this->model_extension_payment_cybersource_common->getClientReferenceInfo($order_id);
		$payload = array(
			"clientReferenceInformation" => $client_reference_info,
			"processingInformation" => array(
				"captureOptions" => array(
					"captureSequenceNumber" => TypeConversion::convertDataToType($seq_number, 'integer'),
					"totalCaptureCount" => TypeConversion::convertDataToType($total_capture_count, 'integer')
				)
			),
			"orderInformation" => array(
				"amountDetails" => array(
					"totalAmount" => TypeConversion::convertDataToType($capture_amount, 'string'),
					"currency" => TypeConversion::convertDataToType($order_details['currency'], 'string')
				),
				"lineItems" => $line_items
			)
		);
		$payment_processing_information = new PaymentProcessingInformation($this->registry);
		if (PAYMENT_GATEWAY === $file_name || PAYMENT_GATEWAY_APPLE_PAY === $file_name) {
			$payload = $payment_processing_information->paymentSolution($payload, $file_name, $order_id);
		}
		$payload = json_encode($payload);
		$resource = RESOURCE_PTS_V2_PAYMENTS . $order_details['transaction_id'] . RESOURCE_CAPTURES;
		$api_response = $this->model_extension_payment_cybersource_common->serviceProcessor($payload, $resource);
		return $api_response;
	}

	public function getPartialLineItemArray($oc_order_details, $quantity, $is_shipping_included, $shipping_amount, $shipping_tax) {
		$shipping_include = VAL_ZERO;
		$line_items = array();
		if (!empty($oc_order_details) && !empty($quantity)) {
			for ($i = VAL_ZERO; $i < sizeof($oc_order_details); $i++) {
				$line_items['' . $i . ''] = array(
					"productCode" => PRODUCT_CODE_DEFAULT,
					"productName" => TypeConversion::convertDataToType($oc_order_details['' . $i . '']['name'], 'string'),
					"productSKU" => TypeConversion::convertDataToType($oc_order_details['' . $i . '']['product_id'], 'string'),
					"quantity" => TypeConversion::convertDataToType($quantity[$i], 'integer'),
					"unitPrice" => TypeConversion::convertDataToType($oc_order_details['' . $i . '']['price'], 'string'),
					"taxAmount" => TypeConversion::convertDataToType($oc_order_details['' . $i . '']['tax'], 'string')
				);
				$shipping_include++;
			}
		}
		if (VAL_FLAG_YES == $is_shipping_included && !empty($shipping_amount)) {
			$line_items['' . $shipping_include . ''] = array(
				"productCode" => SHIPPING_AND_HANDLING,
				"productName" => SHIPPING_AND_HANDLING,
				"productSKU" => SHIPPING_AND_HANDLING,
				"quantity" => VAL_ONE,
				"unitPrice" => TypeConversion::convertDataToType($shipping_amount, 'string'),
				"taxAmount" => TypeConversion::convertDataToType($shipping_tax, 'string')
			);
		}
		return $line_items;
	}

	public function preparePartialCaptureDetails($order_id, $capture_response_array, $captured_amount, $quantity, $order_product_id, $is_shipping_included, $seq_number) {
		$capture_details = VAL_NULL;
		if (!empty($order_id)) {
			$capture_details['order_id'] = $order_id;
			$capture_details['transaction_id'] = $capture_response_array['transaction_id'];
			$capture_details['cybersource_order_status'] = $capture_response_array['status'];
			$capture_details['oc_order_status'] = $this->config->get('module_' . PAYMENT_GATEWAY . '_capture_status_id');
			$capture_details['currency'] = $capture_response_array['currency'];
			$capture_details['capture_quantity'] = $quantity;
			$capture_details['amount'] = $captured_amount;
			$capture_details['order_product_id'] = $order_product_id;
			$capture_details['sequence_count'] = $seq_number;
			$capture_details['shipping_flag'] = $is_shipping_included;
			$capture_details['void_flag'] = VAL_FLAG_NO;
			$capture_details['refunded_amount'] = VAL_ZERO;
			$capture_details['refunded_quantity'] = VAL_ZERO;
			$capture_details['date_added'] = CURRENT_DATE;
		}
		return $capture_details;
	}
}
