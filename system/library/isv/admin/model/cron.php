<?php

namespace Isv\Admin\Model;

trait Cron {
	public function getUpdatedStatus(string $start_time, string $end_time, string $file_name, string $table_name) {
		$this->load->model('extension/payment/cybersource_query');
		$this->load->language('extension/payment/cybersource_logger');
		try {
			$query_reports_data = $this->model_extension_payment_cybersource_query->queryNewDecision($start_time, $end_time, $table_name);
			if (!empty($query_reports_data)) {
				foreach ($query_reports_data->rows as $row) {
					$query_payment_action = $this->model_extension_payment_cybersource_query->queryPaymentActionforDM($row['request_id'], $table_name);
					if (VAL_NULL != $query_payment_action && VAL_ZERO != $query_payment_action->num_rows) {
						$auth_status_id = $this->config->get('module_' . PAYMENT_GATEWAY . '_auth_status_id');
						$capture_status_id = $this->config->get('module_' . PAYMENT_GATEWAY . '_capture_status_id');
						foreach ($query_payment_action->rows as $query_payment_action_row) {
							$action = $query_payment_action_row['payment_action'];
						}
						if (PAYMENT_ACTION_AUTHORIZE == $action) {
							$this->updateStatus($row, $auth_status_id, $action, $file_name, $table_name);
						} elseif (PAYMENT_ACTION_SALE == $action) {
							$this->updateStatus($row, $capture_status_id, $action, $file_name, $table_name);
						}
					}
				}
			} else {
				$this->model_extension_payment_cybersource_common->logger('[ModelExtensionPayment' . $file_name . ']
				[getUpdatedStatus] : ' . $this->language->get('error_CDR_data_not_found'));
			}
		} catch (\Exception $e) {
			$this->model_extension_payment_cybersource_common->logger('[ModelExtensionPayment' . $file_name . ']
			[getUpdatedStatus] : ' . $this->language->get('error_CDR_update_status'));
		}
	}

	public function updateStatus(array $row, string $new_status, string $action, string $file_name, string $table_name) {
		$status = VAL_NULL;
		$oc_order_status = VAL_NULL;
		$custom_status = VAL_NULL;
		$this->load->model('extension/payment/cybersource_query');
		$this->load->model('extension/payment/cybersource_common');
		$this->load->language('extension/payment/cybersource_logger');
		if (DECISION_ACCEPT == $row['new_decision']) {
			$oc_order_status = (int)$new_status;
			$status = API_STATUS_AUTHORIZED;
			if (PAYMENT_ACTION_AUTHORIZE == $action) {
				$custom_status = AWAITING_PAYMENT;
			} elseif (PAYMENT_ACTION_SALE == $action) {
				$custom_status = PAYMENT_ACCEPTED;
			}
		} elseif (DECISION_REJECT == $row['new_decision']) {
			$oc_order_status = $this->config->get('module_' . PAYMENT_GATEWAY . '_auth_reversal_status_id');
			$custom_status = CANCELLED;
			$status = API_STATUS_DECLINED;
		}
		try {
			$is_updation_success = $this->model_extension_payment_cybersource_query->queryUpdateOrderStatus($status, $oc_order_status, $row['request_id'], $table_name);
			if (VAL_NULL != $is_updation_success) {
				$query_order_id = $this->model_extension_payment_cybersource_query->queryOrderId($row['request_id'], $table_name);
				if (!empty($query_order_id)) {
					foreach ($query_order_id->rows as $query_order_id_row) {
						$order_id = $query_order_id_row['order_id'];
					}
					$this->model_extension_payment_cybersource_common->addOrderHistory($order_id, $oc_order_status, $custom_status, VAL_NULL, true);
					if (DECISION_REJECT == $row['new_decision']) {
						$item_restock = $this->model_extension_payment_cybersource_common->restock($order_id, SERVICE_AUTH_REVERSAL, $action, VAL_NULL);
					}
				} else {
					$this->model_extension_payment_cybersource_common->logger('[ModelExtensionPayment' . $file_name . ']
					[UpdateStatus] : ' . $this->language->get('error_order_data_not_found'));
				}
			}
		} catch (\Exception $e) {
			$this->model_extension_payment_cybersource_common->logger('[ModelExtensionPayment' . $file_name . ']
			[UpdateStatus] : ' . $this->language->get('error_CDR_update_status'));
		}
	}
}
