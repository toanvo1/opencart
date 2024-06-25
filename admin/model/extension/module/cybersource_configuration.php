<?php

require_once DIR_SYSTEM . 'library/isv/cybersource_constants.php';

/**
 * Configuration Model file.
 *
 * @author Cybersource
 * @package Back Office
 * @subpackage Model
 */
class ModelExtensionModuleCybersourceConfiguration extends Model {
	public function install() {
		$table_prefix = TABLE_PREFIX_UNIFIED_CHECKOUT;
		$this->load->model('extension/payment/cybersource_query');
		$this->model_extension_payment_cybersource_query->queryCreateTaxTable($table_prefix);
		$this->model_extension_payment_cybersource_query->queryCreateDavTable($table_prefix);
		$this->model_extension_payment_cybersource_query->queryCreateOrderStatusTable($table_prefix);
		// modification reset automatically
		$this->model_extension_payment_cybersource_query->queryUpdateModification(VAL_ONE);
		$this->createReportDownloadDirectory();
	}

	public function uninstall() {
		$table_prefix = TABLE_PREFIX_UNIFIED_CHECKOUT;
		$this->load->model('extension/payment/cybersource_query');
		$this->model_extension_payment_cybersource_query->queryDropTable($table_prefix . TABLE_TAX);
		$this->model_extension_payment_cybersource_query->queryDropTable($table_prefix . TABLE_DAV);
		$this->model_extension_payment_cybersource_query->queryDropTable($table_prefix . TABLE_ORDER_STATUS);
		// modification reset automatically
		$this->model_extension_payment_cybersource_query->queryUpdateModification(VAL_ZERO);
	}

	public function deleteEvents() {
		$this->load->model('setting/event');
		$this->model_setting_event->deleteEventByCode(PAYMENT_GATEWAY . '_mail_order');
		$this->model_setting_event->deleteEventByCode(PAYMENT_GATEWAY . '_mail_order_alert');
	}

	public function addEvents() {
		$this->load->model('setting/event');
		$this->model_setting_event->addEvent(PAYMENT_GATEWAY . '_mail_order', 'catalog/model/extension/payment/cybersource_common/addOrderHistory/before', 'mail/order');
		$this->model_setting_event->addEvent(PAYMENT_GATEWAY . '_mail_order_alert', 'catalog/model/extension/payment/cybersource_common/addOrderHistory/before', 'mail/order/alert');
		$this->model_setting_event->addEvent(PAYMENT_GATEWAY_APPLE_PAY, 'catalog/controller/checkout/checkout/before', 'extension/payment/cybersource_apay/buttonHiding');
	}

	public function createReportDownloadDirectory() {
		if (!file_exists(REPORT_TEST_DIR)) {
			$this->createReportDirectory(REPORT_TEST_DIR);
		}
		if (!file_exists(REPORT_LIVE_DIR)) {
			$this->createReportDirectory(REPORT_LIVE_DIR);
		}
	}

	public function createReportDirectory(string $environment) {
		$this->load->model('extension/payment/cybersource_common');
		$this->load->language('extension/payment/cybersource_logger');
		if (!empty($environment)) {
			$is_directory_created = mkdir($environment, VAL_DIR_CREATE_PERMISSION, true) ? true : false;
			if (!$is_directory_created) {
				$this->model_extension_payment_cybersource_common->logger("[ModelExtensionModuleCybersourceConfiguration]
				[createReportDirectory]" . $this->language->get('error_create_report_directory'));
			}
		}
	}
}
