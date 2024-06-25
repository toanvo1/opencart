<?php

use Isv\Admin\Controller\Cancel;
use Isv\Admin\Controller\Capture;
use Isv\Admin\Controller\Cron;
use Isv\Admin\Controller\Order;
use Isv\Admin\Controller\Refund;
use Isv\Admin\Controller\VoidService;

require_once DIR_SYSTEM . 'library/isv/cybersource_constants.php';

/**
 * Apple Pay controller file.
 *
 * @author Cybersource
 * @package Back Office
 * @subpackage Controller
 */
class ControllerExtensionPaymentCybersourceApay extends Controller {
	use Order, Capture, Refund, Cancel, VoidService, Cron;

	private $error = array();

	public function index() {
		$data = array();
		$data['status'] = FLAG_DISABLE;
		$sandbox_mode = $this->config->get('module_' . PAYMENT_GATEWAY . '_sandbox');
		$this->load->language('extension/payment/cybersource_apay');
		$this->load->language('extension/payment/cybersource_common');
		$this->load->model('setting/setting');
		$this->load->model('extension/payment/cybersource_common');
		$this->load->model('extension/payment/cybersource_query');
		$this->document->setTitle($this->language->get('heading_title'));
		if (!$this->user->isLogged() && isset($this->request->get['user_token']) && ($this->request->get['user_token'] == $this->session->data['user_token'])) {
			$this->response->redirect($this->url->link('common/dashboard', '', true));
		}
		if ((HTTP_METHOD_POST == $this->request->server['REQUEST_METHOD']) && ($this->validate())) {
			$this->model_setting_setting->editSetting('payment_' . PAYMENT_GATEWAY_APPLE_PAY, array_map('trim', $this->request->post));
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}
		$is_payment_configured = $this->model_extension_payment_cybersource_query->queryPaymentConfiguration();
		if ($is_payment_configured) {
			if (!empty($this->config->get('module_' . PAYMENT_GATEWAY . '_configuration_status'))) {
				if ((!empty($this->config->get('module_' . PAYMENT_GATEWAY . '_merchant_secret_key_test')) && !empty($this->config->get('module_' . PAYMENT_GATEWAY . '_merchant_key_id_test')) && !empty($this->config->get('module_' . PAYMENT_GATEWAY . '_merchant_id_test')))
					|| (!empty($this->config->get('module_' . PAYMENT_GATEWAY . '_merchant_secret_key_live')) && !empty($this->config->get('module_' . PAYMENT_GATEWAY . '_merchant_key_id_live')) && !empty($this->config->get('module_' . PAYMENT_GATEWAY . '_merchant_id_live')))
				) {
					$data['status'] = FLAG_ENABLE;
				}
			}
		}
		$data['error_warning'] = $this->error['warning'] ?? VAL_EMPTY;
		$data['error_apay_merchant_id'] = $this->error['apay_merchant_id'] ?? VAL_EMPTY;
		$data['error_apay_path_to_certificate'] = $this->error['apay_path_to_certificate'] ?? VAL_EMPTY;
		$data['error_apay_path_to_key'] = $this->error['apay_path_to_key'] ?? VAL_EMPTY;
		$data['error_apay_store_name'] = $this->error['apay_store_name'] ?? VAL_EMPTY;
		$data['breadcrumbs'] = $this->model_extension_payment_cybersource_common->getBreadcrumbsData($this->session->data['user_token'], EXTENSION_TYPE_PAYMENT, PAYMENT_GATEWAY_APPLE_PAY);
		$data['action'] = $this->url->link('extension/payment/' . PAYMENT_GATEWAY_APPLE_PAY, 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);
		if (FLAG_ENABLE == $data['status']) {
			$data['payment_status'] = $this->request->post['payment_' . PAYMENT_GATEWAY_APPLE_PAY . '_status'] ?? $this->config->get('payment_' . PAYMENT_GATEWAY_APPLE_PAY . '_status');
			$data['payment_sort_order'] = $this->request->post['payment_' . PAYMENT_GATEWAY_APPLE_PAY . '_sort_order'] ?? $this->config->get('payment_' . PAYMENT_GATEWAY_APPLE_PAY . '_sort_order');
			$data['payment_apay_merchant_id_' . $sandbox_mode] = $this->request->post['payment_' . PAYMENT_GATEWAY_APPLE_PAY . '_merchant_id_' . $sandbox_mode] ?? $this->config->get('payment_' . PAYMENT_GATEWAY_APPLE_PAY . '_merchant_id_' . $sandbox_mode);
			$data['payment_apay_path_to_certificate_' . $sandbox_mode] = $this->request->post['payment_' . PAYMENT_GATEWAY_APPLE_PAY . '_path_to_certificate_' . $sandbox_mode] ?? $this->config->get('payment_' . PAYMENT_GATEWAY_APPLE_PAY . '_path_to_certificate_' . $sandbox_mode);
			$data['payment_apay_path_to_key_' . $sandbox_mode] = $this->request->post['payment_' . PAYMENT_GATEWAY_APPLE_PAY . '_path_to_key_' . $sandbox_mode] ?? $this->config->get('payment_' . PAYMENT_GATEWAY_APPLE_PAY . '_path_to_key_' . $sandbox_mode);
			$data['payment_apay_store_name'] = $this->request->post['payment_' . PAYMENT_GATEWAY_APPLE_PAY . '_store_name'] ?? $this->config->get('payment_' . PAYMENT_GATEWAY_APPLE_PAY . '_store_name');
		} else {
			$status_to_zero = array('payment_' . PAYMENT_GATEWAY_APPLE_PAY . '_status' => VAL_ZERO);
			$this->model_setting_setting->editSetting('payment_' . PAYMENT_GATEWAY_APPLE_PAY, $status_to_zero);
		}
		$data['sandbox_mode'] = $sandbox_mode;
		$data['payment_gateway'] = PAYMENT_GATEWAY_APPLE_PAY;
		$data['extension_version'] = EXTENSION_VERSION;
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$this->response->setOutput($this->load->view('extension/payment/cybersource_apay', $data));
	}

	public function install() {
		$this->load->model('extension/payment/cybersource_apay');
		$this->model_extension_payment_cybersource_apay->install();
	}

	public function uninstall() {
		$this->load->model('extension/payment/cybersource_apay');
		$this->model_extension_payment_cybersource_apay->uninstall();
	}

	private function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/cybersource_apay')) {
			$this->error['warning'] = $this->language->get('error_permission');
		} else {
			$sandbox_mode = $this->config->get('module_' . PAYMENT_GATEWAY . '_sandbox');
			if (VAL_ONE == $this->request->post['payment_' . PAYMENT_GATEWAY_APPLE_PAY . '_status']) {
				if (!trim($this->request->post['payment_' . PAYMENT_GATEWAY_APPLE_PAY . '_merchant_id_' . $sandbox_mode])) {
					$this->error['apay_merchant_id'] = $this->language->get('error_apay_merchant_id');
				}
				if (!trim($this->request->post['payment_' . PAYMENT_GATEWAY_APPLE_PAY . '_path_to_certificate_' . $sandbox_mode])) {
					$this->error['apay_path_to_certificate'] = $this->language->get('error_apay_path_to_certificate');
				}
				if (!trim($this->request->post['payment_' . PAYMENT_GATEWAY_APPLE_PAY . '_path_to_key_' . $sandbox_mode])) {
					$this->error['apay_path_to_key'] = $this->language->get('error_apay_path_to_key');
				}
				if (trim($this->request->post['payment_' . PAYMENT_GATEWAY_APPLE_PAY . '_path_to_certificate_' . $sandbox_mode]) && !preg_match(REGEX_APAY_CERTIFICATE_PATH, trim($this->request->post['payment_' . PAYMENT_GATEWAY_APPLE_PAY . '_path_to_certificate_' . $sandbox_mode]))) {
					$this->error['apay_path_to_certificate'] = $this->language->get('error_invalid_apay_path_to_certificate');
				}
				if (trim($this->request->post['payment_' . PAYMENT_GATEWAY_APPLE_PAY . '_path_to_key_' . $sandbox_mode]) && !preg_match(REGEX_APAY_KEY_PATH, trim($this->request->post['payment_' . PAYMENT_GATEWAY_APPLE_PAY . '_path_to_key_' . $sandbox_mode]))) {
					$this->error['apay_path_to_key'] = $this->language->get('error_invalid_apay_path_to_key');
				}
			}
			if (!trim($this->request->post['payment_' . PAYMENT_GATEWAY_APPLE_PAY . '_store_name'])) {
				$this->error['apay_store_name'] = $this->language->get('error_apay_store_name');
			} elseif (strlen(trim($this->request->post['payment_' . PAYMENT_GATEWAY_APPLE_PAY . '_store_name'])) > 64) {
				$this->error['apay_store_name'] = $this->language->get('error_apay_store_name_length');
			}
		}
		return !$this->error;
	}

	public function order() {
		return $this->getOrderTransactionDetails(PAYMENT_GATEWAY_APPLE_PAY, TABLE_PREFIX_APPLE_PAY);
	}

	public function confirmCapture() {
		$this->captureService(PAYMENT_GATEWAY_APPLE_PAY, TABLE_PREFIX_APPLE_PAY);
	}

	public function confirmCancel() {
		$this->cancelService(PAYMENT_GATEWAY_APPLE_PAY, TABLE_PREFIX_APPLE_PAY);
	}

	public function confirmPartialCapture() {
		$this->partialCaptureService(PAYMENT_GATEWAY_APPLE_PAY, TABLE_PREFIX_APPLE_PAY);
	}

	public function confirmVoidCapture() {
		$this->voidCaptureService(PAYMENT_GATEWAY_APPLE_PAY, TABLE_PREFIX_APPLE_PAY);
	}

	public function confirmRefund() {
		$this->refundService(PAYMENT_GATEWAY_APPLE_PAY, TABLE_PREFIX_APPLE_PAY);
	}

	public function confirmVoidRefund() {
		$this->voidRefundService(PAYMENT_GATEWAY_APPLE_PAY, TABLE_PREFIX_APPLE_PAY);
	}

	public function cron() {
		$this->cronService(PAYMENT_GATEWAY_APPLE_PAY, TABLE_PREFIX_APPLE_PAY);
	}
}
