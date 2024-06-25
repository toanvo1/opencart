<?php

namespace Isv\Admin\Controller;

use Isv\Admin\Model\Cron as ModelCron;

trait Cron {
	use ModelCron;

	/**
	 * Common function for cybersource payments reporting service.
	 *
	 * @param string $file_name - used to load specified language or model file and it will act as payment method name indicator
	 * @param string $table_name
	 */
	public function cronService(string $file_name, string $table_name) {
		$reports = array();
		$this->load->model('extension/payment/cybersource_common');
		$this->load->language('extension/payment/cybersource_logger');
		if (VAL_ONE == $this->config->get('module_' . PAYMENT_GATEWAY . '_payment_batch_detail_report')) {
			array_push($reports, PAYMENT_BATCH_DETAIL_REPORT);
		}
		if (VAL_ONE == $this->config->get('module_' . PAYMENT_GATEWAY . '_transaction_request_report')) {
			array_push($reports, TRANSACTION_REQUEST_REPORT);
		}
		if (VAL_ONE == $this->config->get('module_' . PAYMENT_GATEWAY . '_conversion_detail_report')) {
			array_push($reports, CONVERSION_DETAILS_REPORT);
		}
		if (!empty($reports)) {
			$report_settings = $this->model_extension_payment_cybersource_common->getReportSettings();
			foreach ($reports as $report_name) {
				if ((TRANSACTION_REQUEST_REPORT == $report_name) || (PAYMENT_BATCH_DETAIL_REPORT == $report_name)) {
					$this->model_extension_payment_cybersource_common->processReport($report_name, $report_settings);
				} elseif (CONVERSION_DETAILS_REPORT == $report_name) {
					$end_time = gmdate(DATE_Y_M_D_TH_I_S, strtotime(REPORT_END_TIME)) . VAL_Z;
					$start_time = gmdate(DATE_Y_M_D_TH_I_S, strtotime(REPORT_START_TIME)) . VAL_Z;
					$report_data_response = $this->model_extension_payment_cybersource_common->getCDReportData($start_time, $end_time);
					if (VAL_NULL != $report_data_response) {
						if (CODE_TWO_ZERO_ZERO == $report_data_response['http_code']) {
							$rows = json_decode($report_data_response['body'], true);
							$this->model_extension_payment_cybersource_common->insertCDReportData($rows['conversionDetails'], $table_name . TABLE_CONVERSION_DETAIL_REPORT);
							$this->getUpdatedStatus($start_time, $end_time, $file_name, $table_name);
						} elseif (CODE_FOUR_ZERO_FOUR == $report_data_response['http_code']) {
							$this->model_extension_payment_cybersource_common->logger('[ControllerExtensionPayment' . $file_name . ']
							[cron] : ' . $this->language->get('error_reports_not_found'));
						} elseif (CODE_FOUR_ZERO_ZERO == $report_data_response['http_code']) {
							$this->model_extension_payment_cybersource_common->logger('[ControllerExtensionPayment' . $file_name . ']
							[cron] : ' . $this->language->get('error_reports_invalid_request'));
						}
					} else {
						$this->model_extension_payment_cybersource_common->logger('[ControllerExtensionPayment' . $file_name . ']
						[cron] : ' . $this->language->get('error_response_info'));
					}
				}
			}
		} else {
			$this->model_extension_payment_cybersource_common->logger('[ControllerExtensionPayment' . $file_name . ']
			[cron] : ' . $this->language->get('error_enable_reporting'));
		}
	}
}
