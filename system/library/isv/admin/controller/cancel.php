<?php

namespace Isv\Admin\Controller;

use Isv\Admin\Model\Cancel as ModelCancel;

trait Cancel {
	use ModelCancel;

	/**
	 * Common function for cybersource payments(Unified Checkout and Apple Pay) for Auth reversal service.
	 *
	 * @param string $file_name - used to load specified language or model file and it will act as payment method name indicator
	 * @param string $table_name
	 */
	public function cancelService(string $file_name, string $table_name) {
		$response_data = array();
		$this->load->model('extension/payment/cybersource_common');
		$this->load->model('extension/payment/cybersource_query');
		$this->load->language('extension/payment/' . $file_name);
		$this->load->language('extension/payment/cybersource_common');
		$this->load->language('extension/payment/cybersource_logger');
		$this->load->model('sale/order');
		$order_id = $this->request->post['order_id'] ?? VAL_NULL;
		if (!empty($order_id)) {
			$order = $this->model_sale_order->getOrder($order_id);
			try {
				$query_auth_reversal = $this->model_extension_payment_cybersource_query->queryTransactionId($order_id, $table_name . TABLE_AUTH_REVERSAL);
				$auth_reversal_data = (!empty($query_auth_reversal) && VAL_ZERO < $query_auth_reversal->num_rows) ? $query_auth_reversal->row['transaction_id'] : VAL_ZERO;
				if (empty($auth_reversal_data)) {
					$order_details = $this->model_extension_payment_cybersource_common->getOrderDetails($order_id, $table_name . TABLE_ORDER);
					$product_details = $this->model_extension_payment_cybersource_common->getOrderProductDetails($order_id);
					if (!empty($order_details)) {
						$cancel_response = $this->getCancelResponse($order_id, $order_details, $product_details, $file_name);
						if (VAL_NULL != $cancel_response) {
							$cancel_response_array = $this->model_extension_payment_cybersource_common->getResponse($cancel_response['http_code'], $cancel_response['body'], SERVICE_AUTH_REVERSAL, $file_name);
							if (CODE_TWO_ZERO_ONE == $cancel_response['http_code'] && API_STATUS_REVERSED == $cancel_response_array['status']) {
								$this->model_extension_payment_cybersource_common->addOrderHistory($order_id, $this->config->get('module_' . PAYMENT_GATEWAY . '_auth_reversal_status_id'), CANCELLED);
								$prepare_auth_reversal_details = $this->prepareAuthReversalDetails($cancel_response_array, $order_id);
								$is_insertion_success = $this->model_extension_payment_cybersource_query->queryInsertAuthReversalDetails($prepare_auth_reversal_details, $table_name);
								$item_restock = $this->model_extension_payment_cybersource_common->restock($order_id, SERVICE_AUTH_REVERSAL, VAL_NULL, VAL_NULL);
								if (!$is_insertion_success) {
									$response_data['error_flag'] = true;
									$response_data['message'] = $this->language->get('warning_msg_auth_reversal_insertion');
								} else {
									$response_data['error_flag'] = false;
									$response_data['message'] = $this->language->get('success_msg_auth_reversal');
								}
							} else {
								$this->model_extension_payment_cybersource_common->addOrderHistory($order_id, $this->config->get('module_' . PAYMENT_GATEWAY . '_auth_reversal_error_id'), CANCELLED_ERROR);
								$response_data['error_flag'] = true;
								$response_data['message'] = $this->language->get('error_msg_auth_reversal');
								$this->model_extension_payment_cybersource_common->logger('[ControllerExtensionPayment' . $file_name . ']
								[confirmCancel] : Failure Response - ' . $cancel_response['body']);
							}
						} else {
							$response_data['error_flag'] = true;
							$response_data['message'] = $this->language->get('error_msg_auth_reversal');
							$this->model_extension_payment_cybersource_common->logger('[ControllerExtensionPayment' . $file_name . '
							[confirmCancel] : ' . $this->language->get('error_response_info'));
						}
					} else {
						$response_data['error_flag'] = true;
						$response_data['message'] = $this->language->get('error_msg_order_details');
					}
				} else {
					$response_data['error_flag'] = true;
					$response_data['message'] = $this->language->get('warning_msg_cancel_completed');
				}
			} catch (\Exception $e) {
				$response_data['error_flag'] = true;
				$response_data['message'] = $this->language->get('error_msg_order_details');
				$this->model_extension_payment_cybersource_common->logger('[ControllerExtensionPayment ' . $file_name . ']
				[confirmCancel] : ' . $e->getMessage());
			}
		} else {
			$response_data['error_flag'] = true;
			$response_data['message'] = $this->language->get('error_msg_data_not_found');
		}
		$this->response->setOutput(json_encode($response_data));
	}
}
