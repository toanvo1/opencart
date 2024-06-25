<?php

namespace Isv\Admin\Model;

use Isv\Common\Helper\TypeConversion;
use Isv\Common\Payload\PaymentProcessingInformation;

trait Refund {
	public function prepareRefund(?int $order_id, array $quantity, float $shipping_amt, string $is_shipping_included, string $file_name, string $table_name): array {
		$auth_amount = VAL_ZERO;
		$capture_amount = VAL_ZERO;
		$total_quantity = VAL_ZERO;
		$voucher_amount = VAL_ZERO;
		$coupon_amount = VAL_ZERO;
		$amount = VAL_ZERO;
		$refund_response = VAL_NULL;
		$refund_details = VAL_NULL;
		$is_full_capture = false;
		$reward_point_amount_exists = VAL_ZERO;
		$is_store_points_exists = VAL_ZERO;
		$voucher_line_items = array();
		$response_data = array();
		$line_items = array();
		$this->load->model('extension/payment/cybersource_common');
		$this->load->language('extension/payment/' . $file_name);
		$this->load->language('extension/payment/cybersource_common');
		$this->load->language('extension/payment/cybersource_logger');
		$this->load->model('sale/order');
		$product_details = $this->model_extension_payment_cybersource_common->getOrderProductDetails($order_id);
		$query_reward_point = $this->model_extension_payment_cybersource_query->queryRewardPointsAmount($order_id);
		$reward_point_amount = $this->model_extension_payment_cybersource_common->getAbsAmount($query_reward_point);
		if (VAL_NULL != $query_reward_point) {
			$reward_point_amount_exists = VAL_ONE;
		}
		$query_store_points = $this->model_extension_payment_cybersource_query->queryStoreCreditAmount($order_id);
		$store_credit_amount = $this->model_extension_payment_cybersource_common->getAbsAmount($query_store_points);
		if (VAL_NULL != $query_store_points) {
			$is_store_points_exists = VAL_ONE;
		}
		list($shipping_amount, $shipping_tax, $voucher_amount, $coupon_amount) = $this->model_extension_payment_cybersource_common->getShippingCost($order_id);
		try {
			list($auth_amount, $payment_action) = $this->model_extension_payment_cybersource_query->queryRefundAuthAmount($order_id, $table_name . TABLE_ORDER);
			if (PAYMENT_ACTION_SALE == $payment_action) {
				$capture_amount = (float)$auth_amount;
			} else {
				$capture_amount = $this->model_extension_payment_cybersource_query->queryRefundCaptureAmount($order_id, $table_name);
			}
			if (!empty($payment_action) && !empty($auth_amount) && !empty($capture_amount)) {
				$is_full_capture = ($auth_amount == $capture_amount) ? true : false;
				$shipping_cost = (float)$shipping_amt;
				if (($is_full_capture) && (VAL_ZERO != $coupon_amount || VAL_ZERO != $voucher_amount || VAL_ZERO != $reward_point_amount_exists || VAL_ZERO != $is_store_points_exists)) {
					$refund_detail = VAL_NULL;
					$count = VAL_ZERO;
					foreach ($quantity as $key => $value) {
						if (VAL_ZERO < $value) {
							$quantity = (int)$value;
							$total_quantity = $quantity + $total_quantity;
							$refund_order_product_id = substr($key, strpos($key, UNDER_SCORE) + VAL_ONE);
							foreach ($product_details as $product_detail) {
								if ($product_detail['order_product_id'] == $refund_order_product_id) {
									$order_product_id = $product_detail['order_product_id'];
									$unit_price = (float)$product_detail['price'];
									$tax = (float)$product_detail['tax'];
									$amount = ($unit_price + $tax) * $quantity + $amount;
									$line_items = $this->getRefundLineItemArray($order_product_id, $quantity, VAL_FLAG_NO, VAL_ZERO, VAL_ZERO, $product_details);
									$voucher_line_items = array_merge($voucher_line_items, $line_items);
								}
							}
						}
					}
					$refund_response = $this->getRefundData($order_id, $payment_action, $is_full_capture, $order_product_id, $amount - $voucher_amount - $coupon_amount - $reward_point_amount - $store_credit_amount, $total_quantity, $refund_details, $is_shipping_included, $voucher_line_items, $file_name, $table_name);
					if (VAL_ZERO != $shipping_cost) {
						$line_items = $this->getRefundLineItemArray(VAL_NULL, VAL_ONE, $is_shipping_included, $shipping_amount, $shipping_tax, VAL_NULL);
						$refund_response = $this->getRefundData($order_id, $payment_action, $is_full_capture, SHIPPING_AND_HANDLING, $shipping_cost, VAL_ZERO, VAL_NULL, $is_shipping_included, $line_items, $file_name, $table_name);
					}
				} elseif ((PAYMENT_ACTION_SALE == $payment_action) || $is_full_capture  && (VAL_ZERO == $voucher_amount || VAL_ZERO == $coupon_amount || VAL_ZERO == $reward_point_amount_exists || VAL_ZERO == $is_store_points_exists)) {
					$refund_detail = VAL_NULL;
					$count = VAL_ZERO;
					foreach ($quantity as $key => $value) {
						if (VAL_ZERO < $value) {
							$quantity = (int)$value;
							$refund_order_product_id = substr($key, strpos($key, UNDER_SCORE) + VAL_ONE);
							foreach ($product_details as $product_detail) {
								if ($product_detail['order_product_id'] == $refund_order_product_id) {
									$order_product_id = $product_detail['order_product_id'];
									$unit_price = (float)$product_detail['price'];
									$tax = (float)$product_detail['tax'];
									$amount = (float)(($unit_price + $tax) * $quantity);
									$line_items = $this->getRefundLineItemArray($order_product_id, $quantity, VAL_FLAG_NO, VAL_ZERO, VAL_ZERO, $product_details);
									$refund_response = $this->getRefundData($order_id, $payment_action, $is_full_capture, $order_product_id, $amount, $quantity, $refund_details, $is_shipping_included, $line_items, $file_name, $table_name);
								}
							}
						}
					}
					if (VAL_ZERO != $shipping_cost) {
						$line_items = $this->getRefundLineItemArray(VAL_NULL, VAL_ONE, $is_shipping_included, $shipping_amount, $shipping_tax, VAL_NULL);
						$refund_response = $this->getRefundData($order_id, $payment_action, $is_full_capture, SHIPPING_AND_HANDLING, $shipping_cost, VAL_ZERO, VAL_NULL, $is_shipping_included, $line_items, $file_name, $table_name);
					}
				} else {
					$refund_detail = VAL_NULL;
					$unit_price = VAL_ZERO;
					$tax = VAL_ZERO;
					foreach ($quantity as $key => $value) {
						$quantity = (int)$value;
						$refund_order_product_id = substr($key, strpos($key, UNDER_SCORE) + VAL_ONE);
						foreach ($product_details as $product_detail) {
							if ($product_detail['order_product_id'] == $refund_order_product_id) {
								$order_product_id = $product_detail['order_product_id'];
								$unit_price = (float)$product_detail['price'];
								$tax = (float)$product_detail['tax'];
							}
						}
						$amount = (float)($unit_price + $tax) * $quantity;
						if (!empty($quantity)) {
							$line_items = $this->getRefundLineItemArray($order_product_id, $quantity, VAL_FLAG_NO, VAL_ZERO, VAL_ZERO, $product_details);
							$refund_response = $this->getRefundData($order_id, $payment_action, $is_full_capture, $order_product_id, $amount, $quantity, $refund_details, $is_shipping_included, $line_items, $file_name, $table_name);
						}
					}
					if (VAL_ZERO != $shipping_cost) {
						$line_items = $this->getRefundLineItemArray(VAL_NULL, VAL_ONE, $is_shipping_included, $shipping_amount, $shipping_tax, VAL_NULL);
						$refund_response = $this->getRefundData($order_id, $payment_action, $is_full_capture, SHIPPING_AND_HANDLING, $shipping_cost, VAL_ZERO, VAL_NULL, $is_shipping_included, $line_items, $file_name, $table_name);
					}
				}
				$response_data = $refund_response;
			} else {
				$response_data['error_flag'] = true;
				$response_data['message'] = $this->language->get('error_msg_order_details');
			}
		} catch (\Exception $e) {
			$response_data['error_flag'] = true;
			$response_data['message'] = $this->language->get('error_msg_order_details');
			$this->model_extension_payment_cybersource_common->logger('[ModelExtensionPayment' . $file_name . ']
			[prepareRefund] : ' . $e->getMessage());
		}
		return $response_data;
	}

	private function getRefundLineItemArray($order_product_id, int $quantity, string $is_shipping_included, float $shipping_amount, float $shipping_tax, ?array $product_details): array {
		$count = VAL_ZERO;
		$line_items = array();
		if (!empty($order_product_id)) {
			for ($i = VAL_ZERO; $i < sizeof($product_details); $i++) {
				if (VAL_ZERO != $product_details['' . $i . '']['quantity'] && $product_details['' . $i . '']['order_product_id'] == $order_product_id) {
					$line_items['' . $count . ''] = array(
						"productCode" => PRODUCT_CODE_DEFAULT,
						"productName" => TypeConversion::convertDataToType($product_details['' . $i . '']['name'], 'string'),
						"productSKU" => TypeConversion::convertDataToType($product_details['' . $i . '']['product_id'], 'string'),
						"quantity" => TypeConversion::convertDataToType($quantity, 'integer'),
						"unitPrice" => TypeConversion::convertDataToType($product_details['' . $i . '']['price'], 'string'),
						"taxAmount" => TypeConversion::convertDataToType($product_details['' . $i . '']['tax'], 'string')
					);
					$count++;
				}
			}
		}
		if (VAL_FLAG_YES == $is_shipping_included && !empty($shipping_amount)) {
			$line_items['' . $count . ''] = array(
				"productCode" => SHIPPING_AND_HANDLING,
				"productName" => SHIPPING_AND_HANDLING,
				"productSKU" => SHIPPING_AND_HANDLING,
				"quantity" => VAL_ONE,
				"unitPrice" => TypeConversion::convertDataToType($shipping_amount, 'string'),
				"taxAmount" => TypeConversion::convertDataToType($shipping_tax, 'string')
			);
			$count++;
		}
		return $line_items;
	}

	public function getRefundData(?int $order_id, string $payment_action, bool $is_full_capture, $order_product_id, float $amount, int $quantity, $refund_details, string $is_shipping_included, array $line_items, string $file_name, string $table_name): array {
		$refund_response = array();
		$response_data = array();
		$this->load->model('extension/payment/cybersource_common');
		$this->load->language('extension/payment/' . $file_name);
		$this->load->language('extension/payment/cybersource_common');
		$this->load->model('extension/payment/cybersource_query');
		$this->load->model('sale/order');
		if (PAYMENT_ACTION_SALE == $payment_action) {
			$query_capture_data = $this->model_extension_payment_cybersource_query->queryRefundDetailsFromAuth($order_id, $table_name);
			$capture_data = array();
			if (!empty($query_capture_data) && VAL_ZERO < $query_capture_data->num_rows) {
				$capture_data['transaction_id'] = $query_capture_data->row['transaction_id'];
				$capture_data['capture_quantity'] = $query_capture_data->row['order_quantity'];
				$capture_data['refunded_quantity'] = $query_capture_data->row['refunded_quantity'];
				$capture_data['amount'] = $query_capture_data->row['amount'];
				$capture_data['refunded_amount'] = $query_capture_data->row['refunded_amount'];
				$capture_data['currency'] = $query_capture_data->row['currency'];
			}
			if (!empty($capture_data)) {
				$refund_response = $this->doRefund($order_id, $capture_data, $amount, $quantity, $payment_action, $is_shipping_included, $order_product_id, $line_items, $file_name, $table_name);
				if (!$refund_response['error_flag']) {
					$refund_quantity = $capture_data['refunded_quantity'] + $quantity;
					$refund_amount = (float)($capture_data['refunded_amount'] + $amount);
					$is_updation_success = $this->model_extension_payment_cybersource_query->queryUpdateRefundDetails($table_name . TABLE_ORDER, $refund_quantity, $refund_amount, $capture_data['transaction_id']);
					if (!$is_updation_success) {
						$refund_response['error_flag'] = true;
						$refund_response['message'] = $this->language->get('warning_msg_refund_auth_status_update');
					} else {
						$refund_response['error_flag'] = false;
						$refund_response['message'] = $this->language->get('success_msg_refund');
					}
				}
			} else {
				$refund_response['error_flag'] = true;
				$refund_response['message'] = $this->language->get('error_msg_refund');
			}
			$response_data = $refund_response;
		} elseif (PAYMENT_ACTION_SALE != $payment_action && $is_full_capture) {
			$query_capture_data = $this->model_extension_payment_cybersource_query->queryRefundDetailsFromCapture($order_id, $table_name);
			$capture_data = array();
			if (!empty($query_capture_data) && VAL_ZERO < $query_capture_data->num_rows) {
				$capture_data['transaction_id'] = $query_capture_data->row['transaction_id'];
				$capture_data['capture_quantity'] = $query_capture_data->row['capture_quantity'];
				$capture_data['refunded_quantity'] = $query_capture_data->row['refunded_quantity'];
				$capture_data['amount'] = $query_capture_data->row['amount'];
				$capture_data['refunded_amount'] = $query_capture_data->row['refunded_amount'];
				$capture_data['currency'] = $query_capture_data->row['currency'];
			}
			if (!empty($capture_data)) {
				if ($capture_data['capture_quantity'] >= $quantity) {
					$refund_response = $this->doRefund($order_id, $capture_data, $amount, $quantity, $payment_action, $is_shipping_included, $order_product_id, $line_items, $file_name, $table_name);
				} else {
					$refund_response['error_flag'] = true;
					$refund_response['message'] = $this->language->get('error_msg_refund');
				}
				if (!$refund_response['error_flag']) {
					$refund_quantity = $capture_data['refunded_quantity'] + $quantity;
					$refund_amount = (float)($capture_data['refunded_amount'] + $amount);
					$is_updation_success = $this->model_extension_payment_cybersource_query->queryUpdateRefundDetails($table_name . TABLE_CAPTURE, $refund_quantity, $refund_amount, $capture_data['transaction_id']);
					if (!$is_updation_success) {
						$refund_response['error_flag'] = true;
						$refund_response['message'] = $this->language->get('warning_msg_refund_capture_status_update');
					} else {
						$refund_response['error_flag'] = false;
						$refund_response['message'] = $this->language->get('success_msg_refund');
					}
				}
			} else {
				$refund_response['error_flag'] = true;
				$refund_response['message'] = $this->language->get('error_msg_refund');
			}
			$response_data = $refund_response;
		} else {
			$unit_price = VAL_ZERO;
			$query_capture_data = VAL_NULL;
			$captures_data = array();
			$query_capture_data = $this->model_extension_payment_cybersource_query->queryRefundDetails($order_id, $order_product_id, $table_name);
			$size_of_capture_data = $query_capture_data->num_rows;
			if (!empty($query_capture_data) && VAL_ZERO < $size_of_capture_data) {
				for ($i = VAL_ZERO; $i < $size_of_capture_data; $i++) {
					$captures_data['' . $i . ''] = array(
						"transaction_id" => $query_capture_data->rows['' . $i . '']['transaction_id'],
						"capture_quantity" => $query_capture_data->rows['' . $i . '']['capture_quantity'],
						"refunded_quantity" => $query_capture_data->rows['' . $i . '']['refunded_quantity'],
						"amount" => $query_capture_data->rows['' . $i . '']['amount'],
						"refunded_amount" => $query_capture_data->rows['' . $i . '']['refunded_amount'],
						"currency" => $query_capture_data->rows['' . $i . '']['currency']
					);
				}
			}
			if (SHIPPING_AND_HANDLING != $order_product_id || VAL_ZERO != $quantity) {
				$unit_price = $amount / $quantity;
			}
			if (!empty($captures_data)) {
				for ($i = VAL_ZERO; $i < sizeof($captures_data); $i++) {
					$instance_data = array();
					$instance_data['transaction_id'] = $captures_data['' . $i . '']['transaction_id'];
					$instance_data['capture_quantity'] = $captures_data['' . $i . '']['capture_quantity'];
					$instance_data['refunded_quantity'] = $captures_data['' . $i . '']['refunded_quantity'];
					$instance_data['amount'] = $captures_data['' . $i . '']['amount'];
					$instance_data['refunded_amount'] = $captures_data['' . $i . '']['refunded_amount'];
					$instance_data['currency'] = $captures_data['' . $i . '']['currency'];
					$remaining_quantity = $instance_data['capture_quantity'] - $instance_data['refunded_quantity'];

					if (VAL_ZERO == $quantity && VAL_ZERO == $amount) {
						if (SHIPPING_AND_HANDLING != $order_product_id) {
							break;
						}
					}
					if ($remaining_quantity <= $quantity) {
						if (SHIPPING_AND_HANDLING != $order_product_id && VAL_ZERO != $quantity) {
							$refundable_amount = (float)($remaining_quantity * $unit_price);
							$refund_amount = (float)($instance_data['refunded_amount'] + $refundable_amount);
						} else {
							$refundable_amount = (float)$amount;
							$refund_amount = (float)($instance_data['refunded_amount'] + $amount);
						}
						$amount = (float)($amount - $refundable_amount);
						$refund_response = $this->doRefund($order_id, $instance_data, $refundable_amount, $quantity, $payment_action, $is_shipping_included, $order_product_id, $line_items, $file_name, $table_name);
						if (!$refund_response['error_flag']) {
							$quantity = $quantity - $remaining_quantity;
							$is_updation_success = $this->model_extension_payment_cybersource_query->queryUpdateRefundDetailsForCapture($order_product_id, $instance_data['transaction_id'], $instance_data['capture_quantity'], $refund_amount, $table_name);
							if (!$is_updation_success) {
								$refund_response['error_flag'] = true;
								$refund_response['message'] = $this->language->get('warning_msg_refund_capture_status_update');
							} else {
								$refund_response['error_flag'] = false;
								$refund_response['message'] = $this->language->get('success_msg_refund');
							}
						}
					} elseif (VAL_ZERO <= $quantity && $quantity < $remaining_quantity) {
						if (SHIPPING_AND_HANDLING != $order_product_id || VAL_ZERO != $quantity) {
							$refundable_amount = (float)($quantity * $unit_price);
							$refund_amount = (float)($instance_data['refunded_amount'] + $refundable_amount);
						} else {
							$refundable_amount = (float)$amount;
							$refund_amount = (float)($instance_data['refunded_amount'] + $amount);
						}
						$refund_quantity = $instance_data['refunded_quantity'] + $quantity;
						$refund_response = $this->doRefund($order_id, $instance_data, $refundable_amount, $quantity, $payment_action, $is_shipping_included, $order_product_id, $line_items, $file_name, $table_name);
						if (!$refund_response['error_flag']) {
							$is_updation_success = $this->model_extension_payment_cybersource_query->queryUpdateRefundDetailsForCapture($order_product_id, $instance_data['transaction_id'], $refund_quantity, $refund_amount, $table_name);
							if (!$is_updation_success) {
								$refund_response['error_flag'] = true;
								$refund_response['message'] = $this->language->get('warning_msg_refund_capture_status_update');
							} else {
								$refund_response['error_flag'] = false;
								$refund_response['message'] = $this->language->get('success_msg_refund');
							}
							$amount = (float)($amount - $refund_amount);
							$quantity = VAL_ZERO;
						}
					}
				}
			} else {
				$refund_response['error_flag'] = true;
				$refund_response['message'] = $this->language->get('error_msg_refund');
			}
			$response_data = $refund_response;
		}
		return $response_data;
	}

	private function doRefund(?int $order_id, array $captures_data, float $amount, int $quantity, string $payment_action, string $is_shipping_included, $order_product_id, array $line_items, string $file_name, string $table_name): array {
		$capture_amount = VAL_ZERO;
		$refund_amount = VAL_ZERO;
		$response_data = array(
			'message' => VAL_NULL,
			'error_flag' => false,
			'transaction_id' => VAL_NULL
		);
		$oc_order_status = VAL_NULL;
		$refund_response = VAL_NULL;
		$error_flag = false;
		$this->load->model('extension/payment/cybersource_common');
		$this->load->model('extension/payment/cybersource_query');
		$this->load->language('extension/payment/' . $file_name);
		$this->load->language('extension/payment/cybersource_common');
		$this->load->language('extension/payment/cybersource_logger');
		$this->load->model('sale/order');
		$offer_applied = $this->model_extension_payment_cybersource_common->discounts($order_id);
		if (VAL_ZERO != $amount) {
			if (PAYMENT_GATEWAY_ECHECK != $file_name) {
				if ($quantity > ($captures_data['capture_quantity'] - $captures_data['refunded_quantity'])) {
					$quantity = $captures_data['capture_quantity'] - $captures_data['refunded_quantity'];
					$captures_data['capture_quantity'] = $quantity;
				}
			}
			if (VAL_ZERO != $quantity && VAL_ZERO == $offer_applied) {
				$line_items[STRING_VAL_ZERO]['quantity'] = $quantity;
			}
			$refund_response_data = $this->getRefundResponse($order_id, $amount, $captures_data, $line_items, $file_name);
			if (VAL_NULL != $refund_response_data) {
				$refund_response = $this->model_extension_payment_cybersource_common->getResponse($refund_response_data['http_code'], $refund_response_data['body'], SERVICE_REFUND, $file_name);
				if (CODE_TWO_ZERO_ONE == $refund_response_data['http_code'] && API_STATUS_PENDING == $refund_response['status']) {
					$refund_response['quantity'] = $quantity;
					$refund_response['shipping_flag'] = $is_shipping_included;
					$oc_order_status = (int)$this->config->get('module_' . PAYMENT_GATEWAY . '_partial_refund_status_id');
					$custom_status = PARTIAL_REFUNDED;
					$refund_response['oc_order_status'] = $oc_order_status;
					$refund_response['order_product_id'] = $order_product_id;
					if (PAYMENT_GATEWAY_ECHECK == $file_name) {
						$refund_response['amount'] = $amount;
						$refund_response['currency'] = $captures_data['currency'];
					}
					$prepare_refund_details = $this->prepareRefundDetails($order_id, $refund_response);
					$is_insertion_success = $this->model_extension_payment_cybersource_query->queryInsertRefundDetails($prepare_refund_details, $table_name);
					$query_product_id = $this->model_extension_payment_cybersource_query->queryProductIdForRefund($order_id, $order_product_id);
					$product_id = (VAL_ZERO < $query_product_id->num_rows) ? $query_product_id->row['product_id'] : VAL_ZERO;
					$restock_data = array(
						'quantity' => $quantity,
						'order_product_id' => $order_product_id,
						'product_id' => $product_id
					);
					$this->model_extension_payment_cybersource_common->restock($order_id, SERVICE_REFUND, $payment_action, $restock_data);
					if (!$is_insertion_success) {
						$error_flag = true;
						$message = $this->language->get('warning_msg_refund_insertion');
					} else {
						$error_flag = false;
						$message = $this->language->get('success_msg_refund');
					}
					$capture_amount = $this->model_extension_payment_cybersource_query->queryCaptureAmountForRefund($payment_action, $order_id, $table_name);
					$refund_amount = $this->model_extension_payment_cybersource_query->queryRefundAmount($order_id, $table_name);
					if ($refund_amount < $capture_amount) {
						$this->model_extension_payment_cybersource_common->addOrderHistory($order_id, $oc_order_status, $custom_status);
					} elseif ($refund_amount >= $capture_amount) {
						$oc_order_status = (int)$this->config->get('module_' . PAYMENT_GATEWAY . '_refund_status_id');
						$custom_status = REFUNDED;
						$is_updation_success = $this->model_extension_payment_cybersource_query->queryUpdateRefundStatus($order_id, $oc_order_status, $refund_response['transaction_id'], $table_name);
						if (!$is_updation_success) {
							$error_flag = true;
							$message = $this->language->get('warning_msg_refund_status_update');
						}
						$this->model_extension_payment_cybersource_common->addOrderHistory($order_id, $oc_order_status, $custom_status);
					}
					$response_data = array(
						'message' => $message,
						'error_flag' => $error_flag,
						'transaction_id' => $refund_response['transaction_id']
					);
				} else {
					$this->model_extension_payment_cybersource_common->addOrderHistory($order_id, $this->config->get('module_' . PAYMENT_GATEWAY . '_refund_error_status_id'), REFUND_ERROR);
					$response_data = array(
						'message' => $this->language->get('error_msg_refund'),
						'error_flag' => true,
						'transaction_id' => VAL_NULL
					);
					$this->model_extension_payment_cybersource_common->logger('[ModelExtensionPayment ' . $file_name . ']
						[doRefund] : Failure Response - ' . $refund_response_data['body']);
				}
			} else {
				$response_data = array(
					'message' => $this->language->get('error_msg_refund'),
					'error_flag' => true,
					'transaction_id' => VAL_NULL
				);
				$this->model_extension_payment_cybersource_common->logger('[ModelExtensionPayment' . $file_name . ']
					[doRefund] : Failure Response - ' . $this->language->get('error_response_info'));
			}
		}
		return $response_data;
	}

	public function getRefundResponse(?int $order_id, float $refund_amount, array $order_details, array $line_items, string $file_name): array {
		$this->load->model('extension/payment/cybersource_common');
		$refund_amount = number_format($refund_amount, VAL_TWO, '.', '');
		$client_reference_info = $this->model_extension_payment_cybersource_common->getClientReferenceInfo($order_id);
		if (PAYMENT_GATEWAY_ECHECK == $file_name) {
			$payload = array(
				"clientReferenceInformation" => $client_reference_info,
				"paymentInformation" => array(
					"paymentType" => array(
						"name" => CHECK
					)
				),
				"orderInformation" => array(
					"amountDetails" => array(
						"totalAmount" => TypeConversion::convertDataToType($refund_amount, 'string'),
						"currency" => TypeConversion::convertDataToType($order_details['currency'], 'string')
					),
					"lineItems" => $line_items
				)
			);
			$resource = RESOURCE_PTS_V2_PAYMENTS . $order_details['transaction_id'] . RESOURCE_REFUNDS;
		} else {
			$payload = array(
				"clientReferenceInformation" => $client_reference_info,
				"orderInformation" => array(
					"amountDetails" => array(
						"totalAmount" => TypeConversion::convertDataToType($refund_amount, 'string'),
						"currency" => TypeConversion::convertDataToType($order_details['currency'], 'string')
					),
					"lineItems" => $line_items
				)
			);
			$resource = RESOURCE_PTS_V2_CAPTURES . $order_details['transaction_id'] . RESOURCE_REFUNDS;
		}
		$payment_processing_information = new PaymentProcessingInformation($this->registry);
		if (PAYMENT_GATEWAY === $file_name || PAYMENT_GATEWAY_APPLE_PAY === $file_name) {
			$payload = $payment_processing_information->paymentSolution($payload, $file_name, $order_id);
		}
		$payload = json_encode($payload);
		$api_response = $this->model_extension_payment_cybersource_common->serviceProcessor($payload, $resource);
		return $api_response;
	}

	public function prepareRefundDetails(?int $order_id, array $refund_response_array): array {
		$refund_details = array();
		if (!empty($order_id) && !empty($refund_response_array)) {
			$refund_details['order_id'] = $order_id;
			$refund_details['transaction_id'] = $refund_response_array['transaction_id'];
			$refund_details['cybersource_order_status'] = $refund_response_array['status'];
			$refund_details['oc_order_status'] = $refund_response_array['oc_order_status'];
			$refund_details['currency'] = $refund_response_array['currency'];
			$refund_details['refund_quantity'] = $refund_response_array['quantity'];
			$refund_details['amount'] = $refund_response_array['amount'];
			$refund_details['order_product_id'] = $refund_response_array['order_product_id'];
			$refund_details['shipping_flag'] = $refund_response_array['shipping_flag'];
			$refund_details['void_flag'] = VAL_FLAG_NO;
			$refund_details['date_added'] = CURRENT_DATE;
		}
		return $refund_details;
	}
}
