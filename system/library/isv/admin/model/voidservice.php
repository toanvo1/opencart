<?php

namespace Isv\Admin\Model;

trait VoidService {
	public function prepareVoidCaptureDetails(?int $order_id, array $void_capture_array): array {
		$void_capture_details = array();
		if (!empty($order_id) && !empty($void_capture_array)) {
			$void_capture_details['order_id'] = $order_id;
			$void_capture_details['transaction_id'] = $void_capture_array['transaction_id'];
			$void_capture_details['cybersource_order_status'] = $void_capture_array['status'];
			$void_capture_details['oc_order_status'] = $this->config->get('module_' . PAYMENT_GATEWAY . '_void_status_id');
			$void_capture_details['currency'] = $void_capture_array['currency'];
			$void_capture_details['amount'] = $void_capture_array['amount'];
			$void_capture_details['date_added'] = CURRENT_DATE;
		}
		return $void_capture_details;
	}

	public function getCaptureDetails(?int $order_id, ?string $transaction_id, string $table_name): ?object {
		$query_capture_details = null;
		$this->load->model('extension/payment/cybersource_query');
		if (!empty($order_id)) {
			if (!empty($transaction_id)) {
				$query_response = $this->model_extension_payment_cybersource_query->queryCaptureDetails($order_id, $transaction_id, $table_name);
				if (VAL_NULL != $query_response) {
					$query_capture_details = $query_response;
				}
			} else {
				$query_response = $this->model_extension_payment_cybersource_query->queryCaptureTransactionId($order_id, $table_name);
				if (VAL_NULL != $query_response) {
					$query_capture_details = $query_response;
				}
			}
		}
		return $query_capture_details;
	}

	public function getProductId(?int $order_id, ?string $order_product_id, string $table_name): ?object {
		$query_product_id = null;
		$this->load->model('extension/payment/cybersource_query');
		if (!empty($order_id)) {
			if (!empty($order_product_id)) {
				$query_response = $this->model_extension_payment_cybersource_query->queryOrderProductId($order_id, $order_product_id);
				if (VAL_NULL != $query_response) {
					$query_product_id = $query_response;
				}
			} else {
				$query_response = $this->model_extension_payment_cybersource_query->queryOrderProductDetails($order_id, $table_name);
				if (VAL_NULL != $query_response) {
					$query_product_id = $query_response;
				}
			}
		}
		return $query_product_id;
	}

	public function prepareVoidRefundDetails(?int $order_id, array $void_refund_array): array {
		$void_refund_details = array();
		if (!empty($order_id) && !empty($void_refund_array)) {
			$void_refund_details['order_id'] = $order_id;
			$void_refund_details['transaction_id'] = $void_refund_array['transaction_id'];
			$void_refund_details['cybersource_order_status'] = $void_refund_array['status'];
			$void_refund_details['oc_order_status'] = $this->config->get('module_' . PAYMENT_GATEWAY . '_void_status_id');
			$void_refund_details['currency'] = $void_refund_array['currency'];
			$void_refund_details['amount'] = $void_refund_array['amount'];
			$void_refund_details['date_added'] = CURRENT_DATE;
		}
		return $void_refund_details;
	}
}
