<?php

namespace Isv\Admin\Model;

use Isv\Admin\Model\Common;
use Isv\Common\Helper\TypeConversion;
use Isv\Common\Payload\PaymentProcessingInformation;

trait Cancel {
	use Common;

	public function getCancelResponse(?int $order_id, array $order_details, $product_details, string $file_name): array {
		$is_shipping_included = VAL_FLAG_NO;
		$this->load->model('extension/payment/cybersource_common');
		$this->load->model('sale/order');
		$voucher_details = $this->model_sale_order->getOrderVouchers($order_id);
		list($shipping_cost, $shipping_tax, $voucher_amount, $coupon_amount) = $this->model_extension_payment_cybersource_common->getShippingCost($order_id);
		if (VAL_ZERO < $shipping_cost) {
			$is_shipping_included = VAL_FLAG_YES;
		}
		$line_items = $this->getLineItemArray($order_id, $product_details, $voucher_details, $shipping_cost, $shipping_tax, $is_shipping_included, $voucher_amount, $coupon_amount);
		$client_reference_info = $this->model_extension_payment_cybersource_common->getClientReferenceInfo($order_id);
		$payload = array(
			"clientReferenceInformation" => $client_reference_info,
			"reversalInformation" => array(
				"amountDetails" => array(
					"totalAmount" => TypeConversion::convertDataToType($order_details['amount'], 'string')
				)
			),
			"orderInformation" => array(
				"lineItems" => $line_items
			)
		);
		$payment_processing_information = new PaymentProcessingInformation($this->registry);
		if (PAYMENT_GATEWAY === $file_name || PAYMENT_GATEWAY_APPLE_PAY === $file_name) {
			$payload = $payment_processing_information->paymentSolution($payload, $file_name, $order_id);
		}
		$payload = json_encode($payload);
		$resource = RESOURCE_PTS_V2_PAYMENTS . $order_details['transaction_id'] . RESOURCE_REVERSALS;
		$api_response = $this->model_extension_payment_cybersource_common->serviceProcessor($payload, $resource);
		return $api_response;
	}

	public function prepareAuthReversalDetails(array $auth_reversal_response_array, ?int $order_id): array {
		$auth_reversal_details = array();
		if (!empty($auth_reversal_response_array) && !empty($order_id)) {
			$auth_reversal_details['order_id'] = $order_id;
			$auth_reversal_details['transaction_id'] = $auth_reversal_response_array['transaction_id'];
			$auth_reversal_details['cybersource_order_status'] = $auth_reversal_response_array['status'];
			$auth_reversal_details['oc_order_status'] = $this->config->get('payment_cybersource_auth_reversal_status_id');
			$auth_reversal_details['currency'] = $auth_reversal_response_array['currency'];
			$auth_reversal_details['amount'] = $auth_reversal_response_array['amount'];
			$auth_reversal_details['date_added'] = CURRENT_DATE;
		}
		return $auth_reversal_details;
	}
}
