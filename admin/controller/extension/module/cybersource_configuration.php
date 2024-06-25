<?php

use Isv\Admin\Controller\Webhook;

require_once DIR_SYSTEM . 'library/isv/cybersource_constants.php';

/**
 * Configuration controller file.
 *
 * @author Cybersource
 * @package Back Office
 * @subpackage Controller
 */
class ControllerExtensionModuleCybersourceConfiguration extends Controller {
	use Webhook;

	private $error_list = array();

	public function index() {
		$data = array();
		$this->load->language('extension/payment/cybersource_common');
		$this->load->language('extension/module/cybersource_configuration');
		$this->load->model('extension/payment/cybersource_common');
		$this->load->model('localisation/order_status');
		$this->load->model('setting/setting');
		$this->document->setTitle($this->language->get('heading_title'));
		$data['error_warning_type'] = ERROR_TYPE_DANGER;
		if (HTTP_METHOD_POST == $this->request->server['REQUEST_METHOD'] && $this->validate()) {
			$environment = ENVIRONMENT_TEST === $this->request->post['module_' . PAYMENT_GATEWAY . '_sandbox'] ? ENVIRONMENT_TEST : ENVIRONMENT_LIVE;
			$new_merchant_id = $this->request->post['module_' . PAYMENT_GATEWAY . '_merchant_id_' . $environment];
			$new_secret_key_id = $this->request->post['module_' . PAYMENT_GATEWAY . '_merchant_key_id_' . $environment];
			$new_secret_key = $this->request->post['module_' . PAYMENT_GATEWAY . '_merchant_secret_key_' . $environment];
			$old_merchant_id = $this->config->get('module_' . PAYMENT_GATEWAY . '_merchant_id_' . $environment);
			$old_secret_key_id = $this->config->get('module_' . PAYMENT_GATEWAY . '_merchant_key_id_' . $environment);
			$old_secret_key = $this->config->get('module_' . PAYMENT_GATEWAY . '_merchant_secret_key_' . $environment);
			$network_token_updates_status = $this->config->get('payment_' . PAYMENT_GATEWAY . '_network_token_updates_status');
			$tokenization_status = $this->config->get('payment_' . PAYMENT_GATEWAY . '_card');
			if ((($new_merchant_id != $old_merchant_id) || ($new_secret_key_id != $old_secret_key_id) || ($new_secret_key != $old_secret_key)) && (isset($network_token_updates_status) && $network_token_updates_status) && (isset($tokenization_status) && $tokenization_status)) {
				$this->config->set('module_' . PAYMENT_GATEWAY . '_merchant_id_' . $environment, $new_merchant_id);
				$this->config->set('module_' . PAYMENT_GATEWAY . '_merchant_key_id_' . $environment, $new_secret_key_id);
				$this->config->set('module_' . PAYMENT_GATEWAY . '_merchant_secret_key_' . $environment, $new_secret_key);
				$event_types = array(TMS_NETWORK_TOKEN_UPDATED);
				$webhook_security_token = $this->config->get('payment_' . PAYMENT_GATEWAY . '_webhook_security_token');
				$response = $this->webhookService($new_merchant_id, PRODUCT_ID_TOKEN_MANAGEMENT, $event_types, $this->request->server['HTTPS'], $webhook_security_token);
				$this->error_list['warning'] = $response ?? VAL_NULL;
				if (isset($this->error_list['warning'])) {
					$this->model_setting_setting->editSettingValue('payment_' . PAYMENT_GATEWAY, 'payment_' . PAYMENT_GATEWAY . '_network_token_updates_status', VAL_ZERO);
				}
			}
			if (isset($this->request->post['module_' . PAYMENT_GATEWAY . '_configuration_status']) && !($this->request->post['module_' . PAYMENT_GATEWAY . '_configuration_status'])) {
				$this->model_setting_setting->editSettingValue('payment_' . PAYMENT_GATEWAY, 'payment_' . PAYMENT_GATEWAY . '_status', VAL_ZERO);
				$this->model_setting_setting->editSettingValue('payment_' . PAYMENT_GATEWAY_ECHECK, 'payment_' . PAYMENT_GATEWAY . '_echeck_status', VAL_ZERO);
				$this->model_setting_setting->editSettingValue('payment_' . PAYMENT_GATEWAY_APPLE_PAY, 'payment_' . PAYMENT_GATEWAY_APPLE_PAY . '_status', VAL_ZERO);
				$this->model_setting_setting->editSettingValue('total_' . PAYMENT_GATEWAY, 'total_' . PAYMENT_GATEWAY . '_status', VAL_ZERO);
			}
			$this->model_setting_setting->editSetting('module_' . PAYMENT_GATEWAY, array_map('trim', $this->request->post));
			$this->model_setting_setting->editSetting('module_' . PAYMENT_CONFIGURATION, $this->request->post);
			if (!isset($this->error_list['warning'])) {
				$this->session->data['success'] = $this->language->get('text_success');
				$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
			} else {
				$data['error_warning_type'] = ERROR_TYPE_WARNING;
			}
		}
		$data['error_warning'] = $this->error_list['warning'] ?? VAL_EMPTY;
		$data['error_merchant_id'] = $this->error_list['merchant_id'] ?? VAL_EMPTY;
		$data['error_merchant_key_id'] = $this->error_list['merchant_key_id'] ?? VAL_EMPTY;
		$data['error_merchant_secret_key'] = $this->error_list['merchant_secret_key'] ?? VAL_EMPTY;
		$data['error_invalid_folder_pbr_path'] = $this->error_list['folder_pbr_path'] ?? VAL_EMPTY;
		$data['error_invalid_folder_trr_path'] = $this->error_list['folder_trr_path'] ?? VAL_EMPTY;
		$data['error_secret_key'] = $this->error_list['secret_key'] ?? VAL_EMPTY;
		$data['error_site_key'] = $this->error_list['site_key'] ?? VAL_EMPTY;
		$data['error_developer_id'] = $this->error_list['developer_id'] ?? VAL_EMPTY;
		$data['breadcrumbs'] = $this->model_extension_payment_cybersource_common->getBreadcrumbsData($this->session->data['user_token'], EXTENSION_TYPE_MODULE, PAYMENT_CONFIGURATION);
		$data['action'] = $this->url->link('extension/module/cybersource_configuration', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);
		$data['module_configuration_status'] = $this->request->post['module_' . PAYMENT_GATEWAY . '_configuration_status'] ?? $this->config->get('module_' . PAYMENT_GATEWAY . '_configuration_status');
		$data['module_transaction_method'] = $this->request->post['module_' . PAYMENT_GATEWAY . '_transaction_method'] ?? $this->config->get('module_' . PAYMENT_GATEWAY . '_transaction_method');
		$data['module_dav_status'] = $this->request->post['module_' . PAYMENT_GATEWAY . '_dav_status'] ?? $this->config->get('module_' . PAYMENT_GATEWAY . '_dav_status');
		$data['module_sandbox'] = $this->request->post['module_' . PAYMENT_GATEWAY . '_sandbox'] ?? $this->config->get('module_' . PAYMENT_GATEWAY . '_sandbox');
		$data['module_merchant_id_test'] = $this->request->post['module_' . PAYMENT_GATEWAY . '_merchant_id_test'] ?? $this->config->get('module_' . PAYMENT_GATEWAY . '_merchant_id_test');
		$data['module_merchant_key_id_test'] = $this->request->post['module_' . PAYMENT_GATEWAY . '_merchant_key_id_test'] ?? $this->config->get('module_' . PAYMENT_GATEWAY . '_merchant_key_id_test');
		$data['module_merchant_secret_key_test'] = $this->request->post['module_' . PAYMENT_GATEWAY . '_merchant_secret_key_test'] ?? $this->config->get('module_' . PAYMENT_GATEWAY . '_merchant_secret_key_test');
		$data['module_merchant_id_live'] = $this->request->post['module_' . PAYMENT_GATEWAY . '_merchant_id_live'] ?? $this->config->get('module_' . PAYMENT_GATEWAY . '_merchant_id_live');
		$data['module_merchant_key_id_live'] = $this->request->post['module_' . PAYMENT_GATEWAY . '_merchant_key_id_live'] ?? $this->config->get('module_' . PAYMENT_GATEWAY . '_merchant_key_id_live');
		$data['module_merchant_secret_key_live'] = $this->request->post['module_' . PAYMENT_GATEWAY . '_merchant_secret_key_live'] ?? $this->config->get('module_' . PAYMENT_GATEWAY . '_merchant_secret_key_live');
		$data['module_fraud_status'] = $this->request->post['module_' . PAYMENT_GATEWAY . '_fraud_status'] ?? $this->config->get('module_' . PAYMENT_GATEWAY . '_fraud_status');
		$data['module_developer_id'] = $this->request->post['module_' . PAYMENT_GATEWAY . '_developer_id'] ?? $this->config->get('module_' . PAYMENT_GATEWAY . '_developer_id');
		$data['module_dfp'] = $this->request->post['module_' . PAYMENT_GATEWAY . '_dfp'] ?? $this->config->get('module_' . PAYMENT_GATEWAY . '_dfp');
		$data['module_recaptcha_status'] = $this->request->post['module_' . PAYMENT_GATEWAY . '_recaptcha_status'] ?? $this->config->get('module_' . PAYMENT_GATEWAY . '_recaptcha_status');
		$data['module_recaptcha_secret_key'] = $this->request->post['module_' . PAYMENT_GATEWAY . '_recaptcha_secret_key'] ?? $this->config->get('module_' . PAYMENT_GATEWAY . '_recaptcha_secret_key');
		$data['module_recaptcha_site_key'] = $this->request->post['module_' . PAYMENT_GATEWAY . '_recaptcha_site_key'] ?? $this->config->get('module_' . PAYMENT_GATEWAY . '_recaptcha_site_key');
		$data['module_enhanced_logs'] = $this->request->post['module_' . PAYMENT_GATEWAY . '_enhanced_logs'] ?? $this->config->get('module_' . PAYMENT_GATEWAY . '_enhanced_logs');
		// Report Configuration
		$data['module_payment_batch_detail_report'] = $this->request->post['module_' . PAYMENT_GATEWAY . '_payment_batch_detail_report'] ?? $this->config->get('module_' . PAYMENT_GATEWAY . '_payment_batch_detail_report');
		$data['module_payment_batch_detail_report_path_test'] = $this->request->post['module_' . PAYMENT_GATEWAY . '_payment_batch_detail_report_path_test'] ?? $this->config->get('module_' . PAYMENT_GATEWAY . '_payment_batch_detail_report_path_test');
		$data['module_payment_batch_detail_report_path_live'] = $this->request->post['module_' . PAYMENT_GATEWAY . '_payment_batch_detail_report_path_live'] ?? $this->config->get('module_' . PAYMENT_GATEWAY . '_payment_batch_detail_report_path_live');
		$data['module_transaction_request_report'] = $this->request->post['module_' . PAYMENT_GATEWAY . '_transaction_request_report'] ?? $this->config->get('module_' . PAYMENT_GATEWAY . '_transaction_request_report');
		$data['module_transaction_request_report_path_test'] = $this->request->post['module_' . PAYMENT_GATEWAY . '_transaction_request_report_path_test'] ?? $this->config->get('module_' . PAYMENT_GATEWAY . '_transaction_request_report_path_test');
		$data['module_transaction_request_report_path_live'] = $this->request->post['module_' . PAYMENT_GATEWAY . '_transaction_request_report_path_live'] ?? $this->config->get('module_' . PAYMENT_GATEWAY . '_transaction_request_report_path_live');
		$data['module_conversion_detail_report'] = $this->request->post['module_' . PAYMENT_GATEWAY . '_conversion_detail_report'] ?? $this->config->get('module_' . PAYMENT_GATEWAY . '_conversion_detail_report');
		// Order Status
		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
		$data['module_auth_status_id'] = $this->request->post['module_' . PAYMENT_GATEWAY . '_auth_status_id'] ?? $this->config->get('module_' . PAYMENT_GATEWAY . '_auth_status_id');
		$data['module_partial_capture_status_id'] = $this->request->post['module_' . PAYMENT_GATEWAY . '_partial_capture_status_id'] ?? $this->config->get('module_' . PAYMENT_GATEWAY . '_partial_capture_status_id');
		$data['module_capture_status_id'] = $this->request->post['module_' . PAYMENT_GATEWAY . '_capture_status_id'] ?? $this->config->get('module_' . PAYMENT_GATEWAY . '_capture_status_id');
		$data['module_payment_error_status_id'] = $this->request->post['module_' . PAYMENT_GATEWAY . '_payment_error_status_id'] ?? $this->config->get('module_' . PAYMENT_GATEWAY . '_payment_error_status_id');
		$data['module_partial_refund_status_id'] = $this->request->post['module_' . PAYMENT_GATEWAY . '_partial_refund_status_id'] ?? $this->config->get('module_' . PAYMENT_GATEWAY . '_partial_refund_status_id');
		$data['module_refund_status_id'] = $this->request->post['module_' . PAYMENT_GATEWAY . '_refund_status_id'] ?? $this->config->get('module_' . PAYMENT_GATEWAY . '_refund_status_id');
		$data['module_refund_error_status_id'] = $this->request->post['module_' . PAYMENT_GATEWAY . '_refund_error_status_id'] ?? $this->config->get('module_' . PAYMENT_GATEWAY . '_refund_error_status_id');
		$data['module_fraud_management_status_id'] = $this->request->post['module_' . PAYMENT_GATEWAY . '_fraud_management_status_id'] ?? $this->config->get('module_' . PAYMENT_GATEWAY . '_fraud_management_status_id');
		$data['module_fraud_reject_status_id'] = $this->request->post['module_' . PAYMENT_GATEWAY . '_fraud_reject_status_id'] ?? $this->config->get('module_' . PAYMENT_GATEWAY . '_fraud_reject_status_id');
		$data['module_auth_reversal_status_id'] = $this->request->post['module_' . PAYMENT_GATEWAY . '_auth_reversal_status_id'] ?? $this->config->get('module_' . PAYMENT_GATEWAY . '_auth_reversal_status_id');
		$data['module_auth_reversal_error_status_id'] = $this->request->post['module_' . PAYMENT_GATEWAY . '_auth_reversal_error_status_id'] ?? $this->config->get('module_' . PAYMENT_GATEWAY . '_auth_reversal_error_status_id');
		$data['module_void_status_id'] = $this->request->post['module_' . PAYMENT_GATEWAY . '_void_status_id'] ?? $this->config->get('module_' . PAYMENT_GATEWAY . '_void_status_id');
		$data['module_partial_void_status_id'] = $this->request->post['module_' . PAYMENT_GATEWAY . '_partial_void_status_id'] ?? $this->config->get('module_' . PAYMENT_GATEWAY . '_partial_void_status_id');
		$data['module_void_error_status_id'] = $this->request->post['module_' . PAYMENT_GATEWAY . '_void_error_status_id'] ?? $this->config->get('module_' . PAYMENT_GATEWAY . '_void_error_status_id');
		$data['payment_gateway'] = PAYMENT_GATEWAY;
		$data['extension_version'] = EXTENSION_VERSION;
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$this->response->setOutput($this->load->view('extension/module/cybersource_configuration', $data));
	}

	public function install() {
		$this->load->model('setting/event');
		$this->load->language('extension/module/cybersource_configuration');
		$this->load->language('extension/payment/cybersource_common');
		$this->load->model('localisation/order_status');
		$this->load->model('setting/setting');
		$this->load->model('extension/module/cybersource_configuration');
		$data = array();
		$language_id = (int)$this->config->get('config_language_id');
		// Partial Refunded
		$data['order_status'] = array($language_id => array("name" => $this->language->get('text_partial_refunded_status')));
		$partial_refund = $this->model_localisation_order_status->addOrderStatus($data);
		$partial_refund_data = array(PAYMENT_GATEWAY . '_partial_refund_id' => $partial_refund);
		$this->model_setting_setting->editSetting(PAYMENT_GATEWAY . '_partial_refund', $partial_refund_data);
		// Partial Voided
		$data['order_status'] = array($language_id => array("name" => $this->language->get('text_partial_voided_status')));
		$partial_void = $this->model_localisation_order_status->addOrderStatus($data);
		$partial_void_data = array(PAYMENT_GATEWAY . '_partial_void_id' => $partial_void);
		$this->model_setting_setting->editSetting(PAYMENT_GATEWAY . '_partial_void', $partial_void_data);
		// Fraud review
		$data['order_status'] = array($language_id => array("name" => $this->language->get('text_fraud_review_status')));
		$fraud_review = $this->model_localisation_order_status->addOrderStatus($data);
		$fraud_review_data = array(PAYMENT_GATEWAY . '_pending_fraud_review_id' => $fraud_review);
		$this->model_setting_setting->editSetting(PAYMENT_GATEWAY . '_pending_fraud_review', $fraud_review_data);
		// Fraud reject
		$data['order_status'] = array($language_id => array("name" => $this->language->get('text_fraud_reject_status')));
		$fraud_reject = $this->model_localisation_order_status->addOrderStatus($data);
		$fraud_reject_data = array(PAYMENT_GATEWAY . '_pending_fraud_reject_id' => $fraud_reject);
		$this->model_setting_setting->editSetting(PAYMENT_GATEWAY . '_pending_fraud_reject', $fraud_reject_data);
		// Payment error_list
		$data['order_status'] = array($language_id => array("name" => $this->language->get('error_payment')));
		$payment_error = $this->model_localisation_order_status->addOrderStatus($data);
		$payment_error_data = array(PAYMENT_GATEWAY . '_payment_error_id' => $payment_error);
		$this->model_setting_setting->editSetting(PAYMENT_GATEWAY . '_payment_error', $payment_error_data);
		// Void refund error_list
		$data['order_status'] = array($language_id => array("name" => $this->language->get('error_void')));
		$void_error = $this->model_localisation_order_status->addOrderStatus($data);
		$void_error_data = array(PAYMENT_GATEWAY . '_void_error_id' => $void_error);
		$this->model_setting_setting->editSetting(PAYMENT_GATEWAY . '_void_error', $void_error_data);
		// Auth reversal error_list
		$data['order_status'] = array($language_id => array("name" => $this->language->get('error_cancel')));
		$cancel_error = $this->model_localisation_order_status->addOrderStatus($data);
		$cancel_error_data = array(PAYMENT_GATEWAY . '_auth_reversal_error_id' => $cancel_error);
		$this->model_setting_setting->editSetting(PAYMENT_GATEWAY . '_auth_reversal_error', $cancel_error_data);
		// Auth reversal error_list
		$data['order_status'] = array($language_id => array("name" => $this->language->get('error_refund')));
		$refund_error = $this->model_localisation_order_status->addOrderStatus($data);
		$refund_error_data = array(PAYMENT_GATEWAY . '_refund_error_id' => $refund_error);
		$this->model_setting_setting->editSetting(PAYMENT_GATEWAY . '_refund_error', $refund_error_data);
		$this->model_extension_module_cybersource_configuration->install();
		$this->model_extension_module_cybersource_configuration->deleteEvents();
		$this->model_extension_module_cybersource_configuration->addEvents();
		$data['redirect'] = 'extension/extension/module';
		$this->load->controller('marketplace/modification/refresh', $data);
	}

	public function uninstall() {
		$this->load->model('localisation/order_status');
		$this->load->model('setting/setting');
		$this->load->model('extension/module/cybersource_configuration');
		// Partial Refund
		$partial_refund = $this->model_setting_setting->getSetting(PAYMENT_GATEWAY . '_partial_refund');
		$this->model_localisation_order_status->deleteOrderStatus($partial_refund[PAYMENT_GATEWAY . '_partial_refund_id']);
		$this->model_setting_setting->deleteSetting(PAYMENT_GATEWAY . '_partial_refund');
		// Partial Void
		$partial_void = $this->model_setting_setting->getSetting(PAYMENT_GATEWAY . '_partial_void');
		$this->model_localisation_order_status->deleteOrderStatus($partial_void[PAYMENT_GATEWAY . '_partial_void_id']);
		$this->model_setting_setting->deleteSetting(PAYMENT_GATEWAY . '_partial_void');
		// Fraud Review
		$fraud_review = $this->model_setting_setting->getSetting(PAYMENT_GATEWAY . '_pending_fraud_review');
		$this->model_localisation_order_status->deleteOrderStatus($fraud_review[PAYMENT_GATEWAY . '_pending_fraud_review_id']);
		$this->model_setting_setting->deleteSetting(PAYMENT_GATEWAY . '_pending_fraud_review');
		// Fraud Reject
		$fraud_reject = $this->model_setting_setting->getSetting(PAYMENT_GATEWAY . '_pending_fraud_reject');
		$this->model_localisation_order_status->deleteOrderStatus($fraud_reject[PAYMENT_GATEWAY . '_pending_fraud_reject_id']);
		$this->model_setting_setting->deleteSetting(PAYMENT_GATEWAY . '_pending_fraud_reject');
		// Payment error_list
		$payment_error = $this->model_setting_setting->getSetting(PAYMENT_GATEWAY . '_payment_error');
		$this->model_localisation_order_status->deleteOrderStatus($payment_error[PAYMENT_GATEWAY . '_payment_error_id']);
		$this->model_setting_setting->deleteSetting(PAYMENT_GATEWAY . '_payment_error');
		// Void error_list
		$void_error = $this->model_setting_setting->getSetting(PAYMENT_GATEWAY . '_void_error');
		$this->model_localisation_order_status->deleteOrderStatus($void_error[PAYMENT_GATEWAY . '_void_error_id']);
		$this->model_setting_setting->deleteSetting(PAYMENT_GATEWAY . '_void_error');
		// Cancel error_list
		$cancel_error = $this->model_setting_setting->getSetting(PAYMENT_GATEWAY . '_auth_reversal_error');
		$this->model_localisation_order_status->deleteOrderStatus($cancel_error[PAYMENT_GATEWAY . '_auth_reversal_error_id']);
		$this->model_setting_setting->deleteSetting(PAYMENT_GATEWAY . '_auth_reversal_error');
		// Refund error_list
		$refund_error = $this->model_setting_setting->getSetting(PAYMENT_GATEWAY . '_refund_error');
		$this->model_localisation_order_status->deleteOrderStatus($refund_error[PAYMENT_GATEWAY . '_refund_error_id']);
		$this->model_setting_setting->deleteSetting(PAYMENT_GATEWAY . '_refund_error');
		// Cancel Reject
		$this->model_setting_setting->deleteSetting('payment_' . PAYMENT_GATEWAY);
		$this->model_setting_setting->deleteSetting('payment_' . PAYMENT_GATEWAY_ECHECK);
		$this->model_setting_setting->deleteSetting('payment_' . PAYMENT_GATEWAY_APPLE_PAY);
		$this->model_setting_setting->deleteSetting('module_' . PAYMENT_GATEWAY);
		$this->model_setting_setting->deleteSetting('total_' . PAYMENT_GATEWAY);
		$this->model_extension_module_cybersource_configuration->uninstall();
		$this->model_extension_module_cybersource_configuration->deleteEvents();
		$data['redirect'] = 'extension/extension/module';
		$this->load->controller('marketplace/modification/refresh', $data);
	}

	private function validate() {
		$this->load->model('extension/module/cybersource_configuration');
		if (!$this->user->hasPermission('modify', 'extension/module/cybersource_configuration')) {
			$this->error_list['warning'] = $this->language->get('error_permission');
		} else {
			if (ENVIRONMENT_TEST == $this->request->post['module_' . PAYMENT_GATEWAY . '_sandbox']) {
				$this->getMerchantData(ENVIRONMENT_TEST);
			} elseif (ENVIRONMENT_LIVE == $this->request->post['module_' . PAYMENT_GATEWAY . '_sandbox']) {
				$this->getMerchantData(ENVIRONMENT_LIVE);
			}
			if (preg_match(REGEX_ONLY_NUM, trim($this->request->post['module_' . PAYMENT_GATEWAY . '_developer_id']))) {
				$this->error_list['developer_id'] = $this->language->get('error_invalid_developer_id');
			}
			if (VAL_ONE == $this->request->post['module_' . PAYMENT_GATEWAY . '_recaptcha_status'] && !trim($this->request->post['module_' . PAYMENT_GATEWAY . '_recaptcha_secret_key'])) {
				$this->error_list['secret_key'] = $this->language->get('error_secret_key');
			}
			if (VAL_ONE == $this->request->post['module_' . PAYMENT_GATEWAY . '_recaptcha_status'] && !trim($this->request->post['module_' . PAYMENT_GATEWAY . '_recaptcha_site_key'])) {
				$this->error_list['site_key'] = $this->language->get('error_site_key');
			}
		}
		if ($this->error_list && !isset($this->error_list['warning'])) {
			$this->error_list['warning'] = $this->language->get('error_config_form');
		}
		return !$this->error_list;
	}

	public function getMerchantData($environment) {
		if (!trim($this->request->post['module_' . PAYMENT_GATEWAY . '_merchant_id_' . $environment])) {
			$this->error_list['merchant_id'] = $this->language->get('error_merchant_id');
		}
		if (preg_match(REGEX_ALNUM, trim($this->request->post['module_' . PAYMENT_GATEWAY . '_merchant_id_' . $environment]))) {
			$this->error_list['merchant_id'] = $this->language->get('error_invalid_merchant_id');
		}
		if (!trim($this->request->post['module_' . PAYMENT_GATEWAY . '_merchant_key_id_' . $environment])) {
			$this->error_list['merchant_key_id'] = $this->language->get('error_merchant_key_id');
		}
		if (!trim($this->request->post['module_' . PAYMENT_GATEWAY . '_merchant_secret_key_' . $environment])) {
			$this->error_list['merchant_secret_key'] = $this->language->get('error_merchant_secret_key');
		}
		if ($this->request->post['module_' . PAYMENT_GATEWAY . '_transaction_request_report_path_' . $environment]) {
			if (!preg_match(REGEX_FOLDER_NAME, trim($this->request->post['module_' . PAYMENT_GATEWAY . '_transaction_request_report_path_' . $environment]))) {
				$this->error_list['folder_trr_path'] = $this->language->get('error_invalid_folder_path');
			}
		}
		if ($this->request->post['module_' . PAYMENT_GATEWAY . '_payment_batch_detail_report_path_' . $environment]) {
			if (!preg_match(REGEX_FOLDER_NAME, trim($this->request->post['module_' . PAYMENT_GATEWAY . '_payment_batch_detail_report_path_' . $environment]))) {
				$this->error_list['folder_pbr_path'] = $this->language->get('error_invalid_folder_path');
			}
		}
	}
}
