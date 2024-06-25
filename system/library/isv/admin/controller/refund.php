<?php

namespace Isv\Admin\Controller;

use Isv\Admin\Model\Refund as ModelRefund;

trait Refund {
	use ModelRefund;

	/**
	 * Common function for cybersource payments for refund service.
	 *
	 * @param string $file_name - used to load specified language or model file and it will act as payment method name indicator
	 * @param string $table_name
	 */
	public function refundService(string $file_name, string $table_name) {
		$shipping_cost = VAL_ZERO;
		$status = VAL_EMPTY;
		$query_data = VAL_EMPTY;
		$is_shipping_included = VAL_FLAG_NO;
		$response_data = array();
		$quantity = array();
		$this->load->model('extension/payment/cybersource_common');
		$this->load->model('extension/payment/cybersource_query');
		$this->load->language('extension/payment/' . $file_name);
		$this->load->language('extension/payment/cybersource_common');
		$this->load->language('extension/payment/cybersource_logger');
		$this->load->model('sale/order');
		$order_id = $this->request->post['order_id'] ?? VAL_NULL;
		list($shipping_amount, $shipping_tax, $voucher_amount, $coupon_amount) = $this->model_extension_payment_cybersource_common->getShippingCost($order_id);
		if (isset($this->request->post['shipping_flag']) && VAL_TRUE == $this->request->post['shipping_flag']) {
			$is_shipping_included = VAL_FLAG_YES;
			$shipping_cost = (float)($shipping_amount + $shipping_tax);
		}
		if (isset($this->request->post['flag'])) {
			$status = $this->request->post['flag'];
		}
		$products = $this->model_sale_order->getOrderProducts($order_id);
		if (STATUS_STD_REFUND == $status) {
			$query_payment_action = $this->model_extension_payment_cybersource_query->getPaymentAction($order_id, $table_name . TABLE_ORDER, $this->session->data['user_token']);
			$payment_action = (empty($query_payment_action) && VAL_ZERO > $query_payment_action->num_rows) ? VAL_ZERO : $query_payment_action->row['payment_action'];
			$query_refund_details = $this->model_extension_payment_cybersource_query->queryTransactionId($order_id, $table_name . TABLE_REFUND);
			if (!empty($query_refund_details) && VAL_ZERO == $query_refund_details->num_rows && PAYMENT_ACTION_SALE == $payment_action) {
				foreach ($products as $product) {
					$quantity['quantity_' . $product['order_product_id']] = $product['quantity'];
				}
			} else {
				if (PAYMENT_ACTION_SALE == $payment_action) {
					$query_refund_details = $this->model_extension_payment_cybersource_query->queryRefundQuantity($order_id, $table_name . TABLE_REFUND);
					$size_of_refund_details = $query_refund_details->num_rows;
					for ($i = VAL_ZERO; $i < $size_of_refund_details; $i++) {
						if (!empty($query_refund_details->rows['' . $i . '']['order_product_id']) && SHIPPING_AND_HANDLING != $query_refund_details->rows['' . $i . '']['order_product_id']) {
							$query_data = $this->model_extension_payment_cybersource_query->queryOrderProductQuantity($query_refund_details->rows['' . $i . '']['order_product_id']);
							$quantity['quantity_' . $query_refund_details->rows['' . $i . '']['order_product_id']] = $query_data->row['quantity'] - $query_refund_details->rows['' . $i . '']['refund_quantity'];
						}
					}
					foreach ($products as $product) {
						if (!array_key_exists("quantity_" . $product['order_product_id'], $quantity)) {
							$quantity['quantity_' . $product['order_product_id']] = $product['quantity'];
						}
					}
				} else {
					$query_capture_details = $this->model_extension_payment_cybersource_query->queryCaptureDetail($order_id, $table_name);
					$size_of_capture_details = $query_capture_details->num_rows;
					for ($i = VAL_ZERO; $i < $size_of_capture_details; $i++) {
						if (!empty($query_capture_details->rows['' . $i . '']['order_product_id'])) {
							$quantity['quantity_' . $query_capture_details->rows['' . $i . '']['order_product_id']] = $query_capture_details->rows['' . $i . '']['capture_quantity'];
						}
					}
					foreach ($products as $product) {
						if (array_key_exists("quantity_" . $product['order_product_id'], $quantity)) {
							$quantity['quantity_' . $product['order_product_id']] = $product['quantity'] - $quantity['quantity_' . $product['order_product_id']];
						} else {
							$quantity['quantity_' . $product['order_product_id']] = $product['quantity'];
						}
					}
					$query_refund_quantity = $this->model_extension_payment_cybersource_query->queryRefundQuantity($order_id, $table_name . TABLE_REFUND);
					$size_of_refund_quantity = $query_refund_quantity->num_rows;
					for ($i = VAL_ZERO; $i < $size_of_refund_quantity; $i++) {
						if (array_key_exists("quantity_" . $query_refund_quantity->rows['' . $i . '']['order_product_id'], $quantity)) {
							if ($quantity['quantity_' . $query_refund_quantity->rows['' . $i . '']['order_product_id']] - $query_refund_quantity->rows['' . $i . '']['refund_quantity'] <= VAL_ZERO) {
								unset($quantity['quantity_' . $query_refund_quantity->rows['' . $i . '']['order_product_id']]);
							} else {
								$quantity['quantity_' . $query_refund_quantity->rows['' . $i . '']['order_product_id']] = $quantity['quantity_' . $query_refund_quantity->rows['' . $i . '']['order_product_id']] - $query_refund_quantity->rows['' . $i . '']['refund_quantity'];
							}
						}
					}
				}
			}
		}
		if (STATUS_STD_REFUND != $status) {
			if (isset($this->request->post['quantity'])) {
				$quantity = $this->request->post['quantity'];
			} else {
				$response_data['error_flag'] = true;
				$response_data['message'] = $this->language->get('error_null_data');
				$quantity = VAL_ZERO;
			}
		}
		if (!empty($quantity)) {
			$response_data = $this->prepareRefund($order_id, $quantity, $shipping_cost, $is_shipping_included, $file_name, $table_name);
		}
		$this->response->setOutput(json_encode($response_data));
	}
}
