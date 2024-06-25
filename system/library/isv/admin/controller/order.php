<?php

namespace Isv\Admin\Controller;

trait Order {
	/**
	 * Common function for getting Order Management transaction details UI.
	 *
	 * @param string $file_name - used to load specified language or model file and it will act as payment method name indicator
	 * @param string $table_name
	 *
	 * @return string
	 */
	public function getOrderTransactionDetails(string $file_name, string $table_name) {
		$data = array();
		$this->load->model('extension/payment/cybersource_common');
		$this->load->model('extension/payment/cybersource_query');
		$this->load->language('extension/payment/' . $file_name);
		$this->load->language('extension/payment/cybersource_common');
		$order_id = $this->request->get['order_id'] ?? VAL_NULL;
		if (VAL_NULL != $order_id) {
			// Authorization Transaction ID's
			$query_auth_transaction_id = $this->model_extension_payment_cybersource_query->queryTransactionId($order_id, $table_name . TABLE_ORDER);
			if (!empty($query_auth_transaction_id) && VAL_ZERO != $query_auth_transaction_id->num_rows) {
				$data['error_status'] = false;
				$query_payment_action = $this->model_extension_payment_cybersource_query->getPaymentAction($order_id, $table_name . TABLE_ORDER, $this->session->data['user_token']);
				$data['transaction_id'] = (!empty($query_auth_transaction_id) && VAL_ZERO < $query_auth_transaction_id->num_rows) ? $query_auth_transaction_id->row['transaction_id'] : VAL_EMPTY;
				$data['payment_action'] = (!empty($query_payment_action) && VAL_ZERO < $query_payment_action->num_rows) ? $query_payment_action->row['payment_action'] : VAL_EMPTY;
				if (PAYMENT_GATEWAY_ECHECK == $file_name) {
					$data['capture_status'] = FLAG_DISABLE;
					$data['auth_reversal_status'] = FLAG_DISABLE;
				} else {
					// Capture Transaction ID's
					$capture_details = $this->model_extension_payment_cybersource_common->getTransactionDetails($order_id, TABLE_CAPTURE, $table_name . TABLE_CAPTURE);
					$data = array_merge($data, $capture_details);
					// Auth Reversal Transaction ID's
					$auth_reversal_details = $this->model_extension_payment_cybersource_common->getTransactionDetails($order_id, TABLE_AUTH_REVERSAL, $table_name . TABLE_AUTH_REVERSAL);
					$data = array_merge($data, $auth_reversal_details);
				}
				// Void Capture Transaction ID's
				$void_capture_details = $this->model_extension_payment_cybersource_common->getTransactionDetails($order_id, TABLE_VOID_CAPTURE, $table_name . TABLE_VOID_CAPTURE);
				$data = array_merge($data, $void_capture_details);
				// Refund Transaction ID's
				$refund_details = $this->model_extension_payment_cybersource_common->getTransactionDetails($order_id, TABLE_REFUND, $table_name . TABLE_REFUND);
				$data = array_merge($data, $refund_details);
				// Void Refund Transaction ID's
				$void_refund_details = $this->model_extension_payment_cybersource_common->getTransactionDetails($order_id, TABLE_VOID_REFUND, $table_name . TABLE_VOID_REFUND);
				$data = array_merge($data, $void_refund_details);
				// Payment Method
				if (PAYMENT_GATEWAY == $file_name) {
					$query_transaction_details = $this->model_extension_payment_cybersource_query->queryUcPaymentMethod($order_id, $table_name . TABLE_ORDER);
					$data['uc_payment_method'] = $query_transaction_details->row['payment_method'];
				}
			} else {
				$data['error_status'] = true;
				$data['error_warning'] = $this->language->get('error_transaction_not_found');
			}
		} else {
			$data['error_status'] = true;
			$data['error_warning'] = $this->language->get('error_transaction_not_found');
		}
		return $this->load->view('extension/payment/cybersource_order', $data);
	}
}
