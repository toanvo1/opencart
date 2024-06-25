<?php

namespace Isv\Admin\Controller;

use Isv\Admin\Model\VoidService as ModelVoidService;

trait VoidService {
	use ModelVoidService;

	/**
	 * Common function for cybersource payments for void capture service.
	 *
	 * @param string $file_name - used to load specified language or model file and it will act as payment method name indicator
	 * @param string $table_name
	 */
	public function voidCaptureService(string $file_name, string $table_name) {
		$response_data = array();
		$this->load->model('extension/payment/cybersource_common');
		$this->load->model('extension/payment/cybersource_query');
		$this->load->language('extension/payment/' . $file_name);
		$this->load->language('extension/payment/cybersource_common');
		$this->load->language('extension/payment/cybersource_logger');
		$this->load->model('sale/order');
		$order_id = $this->request->post['order_id'] ?? VAL_NULL;
		$order = $this->model_sale_order->getOrder($order_id);
		try {
			$query_payment_action = $this->model_extension_payment_cybersource_query->getPaymentAction($order_id, $table_name . TABLE_ORDER, $this->session->data['user_token']);
			$payment_action = (empty($query_payment_action) && VAL_ZERO > $query_payment_action->num_rows) ? VAL_EMPTY : $query_payment_action->row['payment_action'];
			if (!empty($payment_action)) {
				if (PAYMENT_ACTION_SALE == $payment_action) {
					$order_details = $this->model_extension_payment_cybersource_common->getOrderDetails($order_id, $table_name . TABLE_ORDER);
					if (!empty($order_details)) {
						$void_response = $this->model_extension_payment_cybersource_common->getVoidCaptureResponse($order_id, $order_details['transaction_id']);
						if (VAL_NULL != $void_response) {
							$void_response_array = $this->model_extension_payment_cybersource_common->getResponse($void_response['http_code'], $void_response['body'], SERVICE_VOID, $file_name);
							if (CODE_TWO_ZERO_ONE == $void_response['http_code'] && API_STATUS_VOIDED == $void_response_array['status']) {
								$prepare_void_capture_details = $this->prepareVoidCaptureDetails($order_id, $void_response_array);
								$is_insertion_success = $this->model_extension_payment_cybersource_query->queryInsertVoidCaptureDetails($prepare_void_capture_details, $table_name);
								if (!$is_insertion_success) {
									$response_data['error_flag'] = true;
									$response_data['message'] = $this->language->get('warning_msg_void_capture');
								} else {
									$response_data['error_flag'] = false;
									$response_data['message'] = $this->language->get('success_msg_void_capture');
								}
								$this->model_extension_payment_cybersource_common->addOrderHistory($order_id, $this->config->get('module_' . PAYMENT_GATEWAY . '_void_status_id'), VOID);
								$item_restock = $this->model_extension_payment_cybersource_common->restock($order_id, SERVICE_VOID_CAPTURE, $payment_action, VAL_NULL);
							} else {
								$this->model_extension_payment_cybersource_common->addOrderHistory($order_id, $this->config->get('module_' . PAYMENT_GATEWAY . '_void_error_status_id'), VOID_ERROR);
								$response_data['error_flag'] = true;
								$response_data['message'] = $this->language->get('error_msg_void_capture');
								$this->model_extension_payment_cybersource_common->logger('[ControllerExtensionPayment' . $file_name . ']
								[confirmVoidCapture] : Failure Response - ' . $void_response['body']);
							}
						} else {
							$response_data['error_flag'] = true;
							$response_data['message'] = $this->language->get('error_msg_void_capture');
							$this->model_extension_payment_cybersource_common->logger('[ControllerExtensionPayment' . $file_name . ']
							[confirmVoidCapture] : ' . $this->language->get('error_response_info'));
						}
					} else {
						$response_data['error_flag'] = true;
						$response_data['message'] = $this->language->get('error_msg_order_details');
					}
				} else {
					$query_capture_details = $this->getCaptureDetails($order_id, VAL_EMPTY, $table_name);
					$i = VAL_ZERO;
					$capture_transaction_ids = array();
					$size_capture_details = $query_capture_details->num_rows;
					if (VAL_ZERO < $size_capture_details) {
						for ($i = VAL_ZERO; $i < $size_capture_details; $i++) {
							$capture_transaction_ids['' . $i . ''] = array(
								"transaction_id" => $query_capture_details->rows['' . $i . '']['transaction_id']
							);
						}
					}
					if (!empty($capture_transaction_ids)) {
						foreach ($capture_transaction_ids as $capture_transaction_id) {
							$transaction_id = $capture_transaction_id['transaction_id'];
							$void_response = $this->model_extension_payment_cybersource_common->getVoidCaptureResponse($order_id, $transaction_id);
							if (VAL_NULL != $void_response) {
								$void_response_array = $this->model_extension_payment_cybersource_common->getResponse($void_response['http_code'], $void_response['body'], SERVICE_VOID, $file_name);
								if (CODE_TWO_ZERO_ONE == $void_response['http_code'] && API_STATUS_VOIDED == $void_response_array['status']) {
									$prepare_void_capture_details = $this->prepareVoidCaptureDetails($order_id, $void_response_array);
									$is_insertion_success = $this->model_extension_payment_cybersource_query->queryInsertVoidCaptureDetails($prepare_void_capture_details, $table_name);
									if (!$is_insertion_success) {
										$response_data['error_flag'] = true;
										$response_data['message'] = $this->language->get('warning_msg_void_capture');
									}
									$query_capture_details = $this->getCaptureDetails($order_id, $transaction_id, $table_name);
									$size_of_capture_details = $query_capture_details->num_rows;
									if (VAL_ZERO < $size_of_capture_details) {
										for ($i = VAL_ZERO; $i < $size_of_capture_details; $i++) {
											if (!empty($query_capture_details->rows['' . $i . '']['order_product_id'])) {
												$query_product_id = $this->getProductId($order_id, $query_capture_details->rows['' . $i . '']['order_product_id'], $table_name);
												$product_id = empty($query_product_id->row) ? VAL_ZERO : $query_product_id->row['product_id'];
												$restock_data = array(
													'quantity' => $query_capture_details->rows['' . $i . '']['capture_quantity'],
													'order_product_id' => $query_capture_details->rows['' . $i . '']['order_product_id'],
													'product_id' => $product_id
												);
												$restock = $this->model_extension_payment_cybersource_common->restock($order_id, SERVICE_VOID_CAPTURE, $payment_action, $restock_data);
											} else {
												$query_product_details = $this->getProductId($order_id, VAL_NULL, $table_name);
												$size_of_product_details = $query_product_details->num_rows;
												if (VAL_ZERO < $size_of_product_details) {
													for ($j = VAL_ZERO; $j < $size_of_product_details; $j++) {
														$restock_data = array(
															'quantity' => $query_product_details->rows['' . $j . '']['quantity'],
															'order_product_id' => $query_product_details->rows['' . $j . '']['order_product_id'],
															'product_id' => $query_product_details->rows['' . $j . '']['product_id']
														);
														$restock = $this->model_extension_payment_cybersource_common->restock($order_id, SERVICE_VOID_CAPTURE, $payment_action, $restock_data);
													}
												}
											}
										}
									} else {
										$response_data['error_flag'] = true;
										$response_data['message'] = $this->language->get('error_msg_order_details');
									}
									$is_updation_success = $this->model_extension_payment_cybersource_query->queryUpdateVoidCaptureFlag($capture_transaction_id['transaction_id'], $table_name);
									if (!$is_updation_success) {
										$response_data['error_flag'] = true;
										$response_data['message'] = $this->language->get('warning_msg_void_capture_status_update');
									} else {
										$response_data['error_flag'] = false;
										$response_data['message'] = $this->language->get('success_msg_void_capture');
									}
									$query_final_capture_amount = $this->model_extension_payment_cybersource_query->queryVoidPartialAmount($order_id, $table_name . TABLE_CAPTURE);
									$final_capture_amount = empty($query_final_capture_amount->row) ? VAL_ZERO : (float)$query_final_capture_amount->row['amount'];
									$query_authorized_amount = $this->model_extension_payment_cybersource_common->getOrderDetails($order_id, $table_name . TABLE_ORDER);
									$authorized_amount = empty($query_authorized_amount) ? VAL_ZERO : (float)$query_authorized_amount['amount'];
									if ($authorized_amount == $final_capture_amount) {
										$query_sequence_value = $this->model_extension_payment_cybersource_query->queryVoidId($order_id, $table_name . TABLE_VOID_CAPTURE);
										$sequence_value = empty($query_sequence_value->row) ? VAL_ZERO : $query_sequence_value->row['id'];
										$is_updation_success = $this->model_extension_payment_cybersource_query->queryUpdateVoidCaptureStatus($sequence_value, $table_name);
										if (!$is_updation_success) {
											$response_data['error_flag'] = true;
											$response_data['message'] = $this->language->get('warning_msg_void_capture_status_update');
										}
										$this->model_extension_payment_cybersource_common->addOrderHistory($order_id, $this->config->get('module_' . PAYMENT_GATEWAY . '_void_status_id'), VOID);
										$response_data['error_flag'] = false;
										$response_data['message'] = $this->language->get('success_msg_void_capture');
									} else {
										$this->model_extension_payment_cybersource_common->addOrderHistory($order_id, $this->config->get('module_' . PAYMENT_GATEWAY . '_partial_void_status_id'), VOID_PARTIAL);
										$response_data['error_flag'] = false;
										$response_data['message'] = $this->language->get('success_msg_void_capture');
									}
								} else {
									$this->model_extension_payment_cybersource_common->addOrderHistory($order_id, $this->config->get('module_' . PAYMENT_GATEWAY . '_void_error_status_id'), VOID_ERROR);
									$response_data['error_flag'] = true;
									$response_data['message'] = $this->language->get('error_msg_void_capture');
									$this->model_extension_payment_cybersource_common->logger('[ControllerExtensionPayment' . $file_name . ']
									[confirmVoidCapture] : Failure Response - ' . $void_response['body']);
								}
							} else {
								$response_data['error_flag'] = true;
								$response_data['message'] = $this->language->get('error_msg_void_capture');
								$this->model_extension_payment_cybersource_common->logger('[ControllerExtensionPayment' . $file_name . ']
								[confirmVoidCapture] : ' . $this->language->get('error_response_info'));
							}
						}
					} else {
						$response_data['error_flag'] = true;
						$response_data['message'] = $this->language->get('error_msg_order_details');
					}
				}
			} else {
				$response_data['error_flag'] = true;
				$response_data['message'] = $this->language->get('error_msg_try_again');
				$this->model_extension_payment_cybersource_common->logger('[ControllerExtensionPayment' . $file_name . ']
				[confirmVoidCapture] : ' . $this->language->get('error_data_not_found'));
			}
		} catch (\Exception $e) {
			$response_data['error_flag'] = true;
			$response_data['message'] = $this->language->get('error_msg_try_again');
			$this->model_extension_payment_cybersource_common->logger('[ControllerExtensionPayment' . $file_name . ']
			[confirmVoidCapture] : ' . $e->getMessage());
		}
		$this->response->setOutput(json_encode($response_data));
	}

	/**
	 * Common function for cybersource payments for void refund service.
	 *
	 * @param string $file_name - used to load specified language or model file and it will act as payment method name indicator
	 * @param string $table_name
	 */
	public function voidRefundService(string $file_name, string $table_name) {
		$response_data = array();
		$transaction_ids = array();
		$this->load->model('extension/payment/cybersource_common');
		$this->load->model('extension/payment/cybersource_query');
		$this->load->language('extension/payment/' . $file_name);
		$this->load->language('extension/payment/cybersource_common');
		$this->load->language('extension/payment/cybersource_logger');
		$this->load->model('sale/order');
		$order_id = $this->request->post['order_id'] ?? VAL_NULL;
		$order = $this->model_sale_order->getOrder($order_id);
		$order_details = $this->model_extension_payment_cybersource_common->getOrderDetails($order_id, $table_name . TABLE_ORDER);
		try {
			$query_payment_action = $this->model_extension_payment_cybersource_query->getPaymentAction($order_id, $table_name . TABLE_ORDER, $this->session->data['user_token']);
			$payment_action = (empty($query_payment_action) && VAL_ZERO > $query_payment_action->num_rows) ? VAL_EMPTY : $query_payment_action->row['payment_action'];
			if (!empty($payment_action) && !empty($order_details)) {
				$query_transaction_ids = $this->model_extension_payment_cybersource_query->queryVoidRefundIds($order_id, $table_name . TABLE_REFUND);
				if (VAL_NULL != $query_transaction_ids) {
					$size_of_transaction_ids = $query_transaction_ids->num_rows;
					if (VAL_ZERO < $size_of_transaction_ids) {
						for ($i = VAL_ZERO; $i < $size_of_transaction_ids; $i++) {
							$transaction_ids['' . $i . ''] = array(
								"transaction_id" => $query_transaction_ids->rows['' . $i . '']['transaction_id']
							);
						}
					}
				}
				foreach ($transaction_ids as $transaction_id) {
					$void_response = $this->model_extension_payment_cybersource_common->getVoidRefundResponse($order_id, $transaction_id['transaction_id']);
					if (VAL_NULL != $void_response) {
						$void_response_array = $this->model_extension_payment_cybersource_common->getResponse($void_response['http_code'], $void_response['body'], SERVICE_VOID, $file_name);
						if (CODE_TWO_ZERO_ONE == $void_response['http_code'] && API_STATUS_VOIDED == $void_response_array['status']) {
							$prepare_void_refund_details = $this->prepareVoidRefundDetails($order_id, $void_response_array);
							$is_insertion_success = $this->model_extension_payment_cybersource_query->queryInsertVoidRefundDetails($prepare_void_refund_details, $table_name);
							if (!$is_insertion_success) {
								$response_data['error_flag'] = true;
								$response_data['message'] = $this->language->get('warning_msg_void_refund_insertion');
							} else {
								$response_data['error_flag'] = false;
								$response_data['message'] = $this->language->get('success_msg_void_refund');
							}
							$is_updation_success = $this->model_extension_payment_cybersource_query->queryUpdateVoidRefundFlag($transaction_id['transaction_id'], $table_name . TABLE_REFUND);
							if (!$is_updation_success) {
								$response_data['error_flag'] = true;
								$response_data['message'] = $this->language->get('warning_msg_void_refund_status_update');
							}
							$query_refund_total_amount = $this->model_extension_payment_cybersource_query->queryVoidPartialAmount($order_id, $table_name . TABLE_REFUND);
							$refund_total_amount = empty($query_refund_total_amount->row) ? VAL_ZERO : (float)$query_refund_total_amount->row['amount'];
							if (PAYMENT_ACTION_SALE == $payment_action) {
								$captured_total = $order_details['amount'];
							} else {
								$query_captured_total = $this->model_extension_payment_cybersource_query->queryPartialAmount($order_id, $table_name);
								$captured_total = empty($query_captured_total->row) ? VAL_ZERO : (float)$query_captured_total->row['amount'];
							}
							if ($refund_total_amount >= $captured_total) {
								$is_updation_success = $this->model_extension_payment_cybersource_common->updateVoidRefundStatus($order_id, $table_name . TABLE_VOID_REFUND);
								$this->model_extension_payment_cybersource_common->addOrderHistory($order_id, (int)$this->config->get('module_' . PAYMENT_GATEWAY . '_void_status_id'), VOID);
								$response_data['error_flag'] = false;
								$response_data['message'] = $this->language->get('success_msg_refund_cancelled');
								if (!$is_updation_success) {
									$response_data['error_flag'] = true;
									$response_data['message'] = $this->language->get('warning_msg_void_refund_status_update');
								}
							} else {
								$this->model_extension_payment_cybersource_common->addOrderHistory($order_id, (int)$this->config->get('module_' . PAYMENT_GATEWAY . '_partial_void_status_id'), VOID_PARTIAL);
								$response_data['error_flag'] = false;
								$response_data['message'] = $this->language->get('success_msg_partial_void_refund');
							}
						} else {
							$this->model_extension_payment_cybersource_common->addOrderHistory($order_id, (int)$this->config->get('module_' . PAYMENT_GATEWAY . '_void_error_status_id'), VOID_ERROR);
							$response_data['error_flag'] = true;
							$response_data['message'] = $this->language->get('error_msg_void_refund');
							$this->model_extension_payment_cybersource_common->logger('[ControllerExtensionPayment' . $file_name . ']
							[confirmVoidRefund] : Failure Response - ' . $void_response['body']);
						}
					} else {
						$response_data['error_flag'] = true;
						$response_data['message'] = $this->language->get('error_msg_order_details');
						$this->model_extension_payment_cybersource_common->logger('[ControllerExtensionPayment' . $file_name . ']
						[confirmVoidRefund] : ' . $this->language->get('error_response_info'));
					}
				}
			} else {
				$response_data['error_flag'] = true;
				$response_data['message'] = $this->language->get('error_msg_order_details');
			}
		} catch (\Exception $e) {
			$response_data['error_flag'] = true;
			$response_data['message'] = $this->language->get('error_msg_try_again');
			$this->model_extension_payment_cybersource_common->logger('[ControllerExtensionPayment' . $file_name . ']
			[confirmVoidRefund] : ' . $e->getMessage());
		}
		$this->response->setOutput(json_encode($response_data));
	}
}
