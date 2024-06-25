<?php

namespace Isv\Admin\Controller;

use Isv\Admin\Model\Capture as ModelCapture;

trait Capture {
	use ModelCapture;

	/**
	 * Common function for cybersource payments(Unified Checkout and Apple Pay) for capture service.
	 *
	 * @param string $file_name - used to load specified language or model file and it will act as payment method name indicator
	 * @param string $table_name
	 */
	public function captureService(string $file_name, string $table_name) {
		$response_data = array();
		$product_details = array();
		$this->load->model('extension/payment/cybersource_common');
		$this->load->language('extension/payment/' . $file_name);
		$this->load->language('extension/payment/cybersource_common');
		$this->load->language('extension/payment/cybersource_logger');
		$this->load->model('extension/payment/cybersource_query');
		$this->load->model('sale/order');
		$order_id = $this->request->post['order_id'] ?? VAL_NULL;
		if (!empty($order_id)) {
			$order_details = $this->model_extension_payment_cybersource_common->getOrderDetails($order_id, $table_name . TABLE_ORDER);
			$order = $this->model_sale_order->getOrder($order_id);
			try {
				if (!empty($order_id) && !empty($order_details)) {
					$query_total_quantity = $this->model_extension_payment_cybersource_query->queryTotalOrderQuantity($order_id);
					$total_quantity = (VAL_ZERO < $query_total_quantity->num_rows) ? $query_total_quantity->row['quantity'] : VAL_ZERO;
					$query_partial_amount = $this->model_extension_payment_cybersource_query->queryPartialAmount($order_id, $table_name);
					$partial_captured_amount = (VAL_ZERO < $query_partial_amount->num_rows) ? $query_partial_amount->row['amount'] : VAL_ZERO;
					$query_captured_quantity = $this->model_extension_payment_cybersource_query->queryCapturedQuantity($order_id, $table_name);
					$captured_quantity = (VAL_ZERO < $query_captured_quantity->num_rows) ? $query_captured_quantity->row['quantity'] : VAL_ZERO;
					if (empty($partial_captured_amount)) {
						$product_details = $this->model_extension_payment_cybersource_common->getOrderProductDetails($order_id);
					} else {
						$query_product_details = $this->model_extension_payment_cybersource_query->queryCaptureProductDetails($order_id, $table_name);
						$size_of_product_details = $query_product_details->num_rows;
						if (VAL_ZERO < $size_of_product_details) {
							for ($i = VAL_ZERO; $i < $size_of_product_details; $i++) {
								$product_details['' . $i . ''] = array(
									"order_product_id" => $query_product_details->rows['' . $i . '']['order_product_id'],
									"model" => $query_product_details->rows['' . $i . '']['model'],
									"name" => $query_product_details->rows['' . $i . '']['name'],
									"product_id" => $query_product_details->rows['' . $i . '']['product_id'],
									"price" => $query_product_details->rows['' . $i . '']['price'],
									"total" => $query_product_details->rows['' . $i . '']['total'],
									"tax" => $query_product_details->rows['' . $i . '']['tax'],
									"quantity" => $query_product_details->rows['' . $i . '']['capture_quantity']
								);
							}
						}
					}
					if (!empty($total_quantity) || ($partial_captured_amount < $order_details['amount'])) {
						$partial_captured_quantity = $total_quantity - $captured_quantity;
						if (empty($partial_captured_amount)) {
							$capture_response = $this->executeCapture($order_id, $order_details, $product_details, $total_quantity, $file_name, $table_name);
							if ($capture_response['error_flag']) {
								$response_data['error_flag'] = true;
								$response_data['message'] = $capture_response['message'];
							} else {
								$response_data['error_flag'] = false;
								$response_data['message'] = $capture_response['message'];
							}
						} else {
							$order_details['amount'] = $order_details['amount'] - $partial_captured_amount;
							$capture_response = $this->executeCapture($order_id, $order_details, $product_details, $partial_captured_quantity, $file_name, $table_name);
							if ($capture_response['error_flag']) {
								$response_data['error_flag'] = true;
								$response_data['message'] = $capture_response['message'];
							} else {
								$response_data['error_flag'] = false;
								$response_data['message'] = $capture_response['message'];
							}
						}
					} else {
						$response_data['error_flag'] = true;
						$response_data['message'] = $this->language->get('error_msg_order_details');
					}
				} else {
					$response_data['error_flag'] = true;
					$response_data['message'] = $this->language->get('error_msg_order_details');
				}
			} catch (\Exception $e) {
				$response_data['error_flag'] = true;
				$response_data['message'] = $this->language->get('error_msg_order_details');
				$this->model_extension_payment_cybersource_common->logger('[ControllerExtensionPayment' . $file_name . ']
				[confirmCapture] : ' . $e->getMessage());
			}
		} else {
			$response_data['error_flag'] = true;
			$response_data['message'] = $this->language->get('error_msg_data_not_found');
		}
		$this->response->setOutput(json_encode($response_data));
	}

	/**
	 * Common function for cybersource payments(Unified Checkout and Apple Pay) for partial capture service.
	 *
	 * @param string $file_name - used to load specified language or model file and it will act as payment method name indicator
	 * @param string $table_name
	 */
	public function partialCaptureService(string $file_name, string $table_name) {
		$increment_value = VAL_ONE;
		$total_capture_count = VAL_NINETY_NINE;
		$shipping_amount = VAL_ZERO;
		$quantity = VAL_NULL;
		$oc_order_details = VAL_NULL;
		$is_shipping_included = VAL_FLAG_NO;
		$response_data = array();
		$this->load->model('extension/payment/cybersource_common');
		$this->load->language('extension/payment/' . $file_name);
		$this->load->language('extension/payment/cybersource_common');
		$this->load->language('extension/payment/cybersource_logger');
		$this->load->model('sale/order');
		$order_id = $this->request->post['order_id'] ?? VAL_NULL;
		$order = $this->model_sale_order->getOrder($order_id);
		$order_details = $this->model_extension_payment_cybersource_common->getOrderDetails($order_id, $table_name . TABLE_ORDER);
		$product_details = $this->model_extension_payment_cybersource_common->getOrderProductDetails($order_id);
		$shipping_tax = $this->model_extension_payment_cybersource_common->getShippingTax($order_id);
		try {
			if (isset($this->request->post['quantity'])) {
				$quantity_array = $this->request->post['quantity'];
				$i = VAL_ZERO;
				$capture_quantity = VAL_ZERO;
				$capture_amount = VAL_ZERO;
				foreach ($quantity_array as $key => $data) {
					if (VAL_ZERO < $data) {
						$capture_order_product_id = substr($key, strpos($key, UNDER_SCORE) + VAL_ONE);
						$query_product_details = $this->model_extension_payment_cybersource_query->queryPartialCaptureProductDetails($order_id, $capture_order_product_id);
						$quantity[$i] = (int)$data;
						$capture_quantity = $capture_quantity + $quantity[$i];
						if (VAL_NULL != $query_product_details && VAL_ZERO < $query_product_details->num_rows) {
							$oc_order_details['' . $i . ''] = array(
								"order_product_id" => $query_product_details->row['order_product_id'],
								"model" => $query_product_details->row['model'],
								"name" => $query_product_details->row['name'],
								"product_id" => $query_product_details->row['product_id'],
								"quantity" => $query_product_details->row['quantity'],
								"price" => $query_product_details->row['price'],
								"total" => $query_product_details->row['total'],
								"tax" => $query_product_details->row['tax']
							);
							$i++;
						}
					}
				}
				if (isset($this->request->post['shipping_flag']) && VAL_TRUE == $this->request->post['shipping_flag']) {
					$shipping_amount = (float)$this->request->post['shipping_amount'] + $shipping_tax;
					$shipping_price = (float)$this->request->post['shipping_amount'];
					$is_shipping_included = VAL_FLAG_YES;
				} else {
					$shipping_price = VAL_ZERO;
					$shipping_amount = VAL_ZERO;
					$is_shipping_included = VAL_FLAG_NO;
				}
				$capture_amount = $this->getCaptureAmount($oc_order_details, $quantity);
				$query_partial_captured_amount = $this->model_extension_payment_cybersource_query->queryPartialAmount($order_id, $table_name);
				$partial_captured_amount = empty($query_partial_captured_amount->row) ? VAL_ZERO : $query_partial_captured_amount->row['amount'];
				$final_amount = empty($partial_captured_amount) ? $capture_amount : ($capture_amount + $partial_captured_amount);
				$query_seq_count = $this->model_extension_payment_cybersource_query->querySequenceCount($order_id, $table_name);
				$seq_count = (empty($query_seq_count->row) || VAL_ZERO >= $query_seq_count->num_rows) ? VAL_ZERO : $query_seq_count->row['sequence_count'];
				if (VAL_ZERO == strcmp((string)$final_amount, (string)$order_details['amount'])) {
					$query_sequence_value_one = $this->model_extension_payment_cybersource_query->querySequenceCountOrder($order_id, $table_name);
					$sequence_value_one = (empty($query_sequence_value_one) || VAL_ZERO >= $query_sequence_value_one->num_rows) ? VAL_ZERO : $query_sequence_value_one->row['sequence_count'];
					$seq_number = $sequence_value_one + $increment_value;
					$total_capture_count = $seq_number;
				} else {
					if (empty($seq_count)) {
						$seq_number = VAL_ONE;
					} else {
						$query_sequence_value = $this->model_extension_payment_cybersource_query->querySequenceCountLimit($order_id, $table_name);
						$sequence_value = (empty($query_sequence_value)  || VAL_ZERO >= $query_sequence_value->num_rows) ? VAL_ZERO : $query_sequence_value->row['sequence_count'];
						$seq_number = $sequence_value + $increment_value;
					}
					$total_capture_count = VAL_NINETY_NINE;
				}
				$query_captured_quantity = $this->model_extension_payment_cybersource_query->queryCapturedQuantity($order_id, $table_name);
				$captured_quantity = empty($query_captured_quantity->row) ? VAL_ZERO : $query_captured_quantity->row['quantity'];
				if ($order_details['quantity'] == ($captured_quantity + $capture_quantity)) {
					$query_captured_amount = $this->model_extension_payment_cybersource_query->queryPartialAmount($order_id, $table_name);
					$captured_amount = empty($query_captured_amount->row) ? VAL_ZERO : $query_captured_amount->row['amount'];
					$capture_amount = $order_details['amount'] - $captured_amount;
				} else {
					$capture_amount = $this->getCaptureAmount($oc_order_details, $quantity);
					$capture_amount = (VAL_FLAG_YES == $is_shipping_included) ? ($capture_amount + $shipping_amount) : $capture_amount;
				}
				$line_items = $this->getPartialLineItemArray(
					$oc_order_details,
					$quantity,
					$is_shipping_included,
					$shipping_price,
					$shipping_tax
				);
				$partial_capture_response = $this->getPartialCaptureResponse(
					$order_id,
					$total_capture_count,
					$seq_number,
					$line_items,
					$capture_amount,
					$order_details,
					$file_name
				);
				$capture_response_array = $this->model_extension_payment_cybersource_common->getResponse($partial_capture_response['http_code'], $partial_capture_response['body'], SERVICE_CAPTURE, $file_name);
				if ((CODE_TWO_ZERO_ONE == $partial_capture_response['http_code'])
					&& (API_STATUS_PENDING == $capture_response_array['status'])) {
					$size_of_order_details = sizeof($oc_order_details);
					for ($i = VAL_ZERO; $i < $size_of_order_details; $i++) {
						$captured_amount = (($oc_order_details['' . $i . '']['price']) * ($quantity[$i]) + ($oc_order_details['' . $i . '']['tax']) * ($quantity[$i]));
						$order_product_id = $oc_order_details['' . $i . '']['order_product_id'];
						$partial_capture_details = $this->preparePartialCaptureDetails(
							$order_id,
							$capture_response_array,
							$captured_amount,
							$quantity[$i],
							$order_product_id,
							$is_shipping_included,
							$seq_number
						);
						$is_insertion_success = $this->model_extension_payment_cybersource_query->queryInsertPartialCaptureDetails($partial_capture_details, $table_name);
						if (!$is_insertion_success) {
							$response_data['error_flag'] = true;
							$response_data['message'] = $this->language->get('warning_msg_capture_insertion');
						} else {
							$response_data['error_flag'] = false;
							$response_data['message'] = $this->language->get('success_msg_partial_capture');
						}
					}
					if (VAL_FLAG_YES == $is_shipping_included) {
						$partial_capture_details = $this->preparePartialCaptureDetails(
							$order_id,
							$capture_response_array,
							$shipping_amount,
							VAL_ZERO,
							SHIPPING_AND_HANDLING,
							$is_shipping_included,
							$seq_number
						);
						$is_insertion_success = $this->model_extension_payment_cybersource_query->queryInsertPartialCaptureDetails($partial_capture_details, $table_name);
						if (!$is_insertion_success) {
							$response_data['error_flag'] = true;
							$response_data['message'] = $this->language->get('warning_msg_shipping_insertion');
						} else {
							$response_data['error_flag'] = false;
							$response_data['message'] = $this->language->get('success_msg_partial_capture');
						}
					}
					$query_final_amounts = $this->model_extension_payment_cybersource_query->queryPartialAmount($order_id, $table_name);
					$final_amounts = empty($query_final_amounts->row) ? VAL_ZERO : $query_final_amounts->row['amount'];
					if ($order_details['amount'] <= $final_amounts) {
						$query_sequence_value = $this->model_extension_payment_cybersource_query->querySequenceCountId($order_id, $table_name);
						$sequence_value = empty($query_sequence_value->row) ? VAL_ZERO : $query_sequence_value->row['id'];
						$is_updation_success = $this->model_extension_payment_cybersource_query->queryUpdateCaptureStatus($sequence_value, $table_name);
						$this->model_extension_payment_cybersource_common->addOrderHistory($order_id, $this->config->get('module_' . PAYMENT_GATEWAY . '_capture_status_id'), PAYMENT_ACCEPTED);
						if (!$is_updation_success) {
							$response_data['error_flag'] = true;
							$response_data['message'] = $this->language->get('warning_msg_capture_status_update');
						}
					} else {
						$response_data['error_flag'] = false;
						$response_data['message'] = $this->language->get('success_msg_partial_capture');
						$this->model_extension_payment_cybersource_common->addOrderHistory($order_id, $this->config->get('module_' . PAYMENT_GATEWAY . '_partial_capture_status_id'), PARTIAL_CAPTURED);
					}
				} else {
					$this->model_extension_payment_cybersource_common->addOrderHistory($order_id, $this->config->get('module_' . PAYMENT_GATEWAY . '_payment_error_status_id'), PAYMENT_ACCEPTED_ERROR);
					$response_data['error_flag'] = true;
					$response_data['message'] = $this->language->get('error_msg_capture');
					$this->model_extension_payment_cybersource_common->logger('[ControllerExtensionPayment' . $file_name . ']
					[confirmPartialCapture] : Failure Response - ' . $partial_capture_response['body']);
				}
			} else {
				$response_data['error_flag'] = true;
				$response_data['message'] = $this->language->get('error_msg_order_details');
			}
		} catch (\Exception $e) {
			$response_data['error_flag'] = true;
			$response_data['message'] = $this->language->get('error_msg_try_again');
			$this->model_extension_payment_cybersource_common->logger('[ControllerExtensionPayment' . $file_name . ']
			[confirmPartialCapture] : ' . $e->getMessage());
		}
		$this->response->setOutput(json_encode($response_data));
	}
}
