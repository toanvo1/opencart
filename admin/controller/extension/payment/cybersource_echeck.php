<?php

use Isv\Admin\Controller\Cron;
use Isv\Admin\Controller\Order;
use Isv\Admin\Controller\Refund;
use Isv\Admin\Controller\VoidService;

require_once DIR_SYSTEM . 'library/isv/cybersource_constants.php';

/**
 * eCheck controller file.
 *
 * @author Cybersource
 * @package Back Office
 * @subpackage Controller
 */
class ControllerExtensionPaymentCybersourceEcheck extends Controller {
	use Order, Refund, VoidService, Cron;

	private $error = array();

	public function index() {
		$data = array();
		$data['status'] = FLAG_DISABLE;
		$this->load->language('extension/payment/cybersource_echeck');
		$this->load->language('extension/payment/cybersource_common');
		$this->load->model('setting/setting');
		$this->load->model('extension/payment/cybersource_common');
		$this->load->model('extension/payment/cybersource_query');
		$this->document->setTitle($this->language->get('heading_title'));
		if (!$this->user->isLogged() && isset($this->request->get['user_token']) && ($this->request->get['user_token'] == $this->session->data['user_token'])) {
			$this->response->redirect($this->url->link('common/dashboard', '', true));
		}
		if ((HTTP_METHOD_POST == $this->request->server['REQUEST_METHOD']) && ($this->validate())) {
			$this->model_setting_setting->editSetting('payment_' . PAYMENT_GATEWAY_ECHECK, array_map('trim', $this->request->post));
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}
		$is_payment_configured = $this->model_extension_payment_cybersource_query->queryPaymentConfiguration();
		if ($is_payment_configured) {
			if (!empty($this->config->get('module_' . PAYMENT_GATEWAY . '_configuration_status'))) {
				if ((!empty($this->config->get('module_' . PAYMENT_GATEWAY . '_merchant_secret_key_test')) && !empty($this->config->get('module_' . PAYMENT_GATEWAY . '_merchant_key_id_test')) && !empty($this->config->get('module_' . PAYMENT_GATEWAY . '_merchant_id_test')))
				|| (!empty($this->config->get('module_' . PAYMENT_GATEWAY . '_merchant_secret_key_live')) && !empty($this->config->get('module_' . PAYMENT_GATEWAY . '_merchant_key_id_live')) && !empty($this->config->get('module_' . PAYMENT_GATEWAY . '_merchant_id_live')))) {
					$data['status'] = FLAG_ENABLE;
				}
			}
		}
		$data['error_warning'] = $this->error['warning'] ?? VAL_EMPTY;
		$data['breadcrumbs'] = $this->model_extension_payment_cybersource_common->getBreadcrumbsData($this->session->data['user_token'], EXTENSION_TYPE_PAYMENT, PAYMENT_GATEWAY_ECHECK);
		$data['action'] = $this->url->link('extension/payment/' . PAYMENT_GATEWAY_ECHECK, 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);
		if (FLAG_ENABLE == $data['status']) {
			$data['payment_status'] = $this->request->post['payment_' . PAYMENT_GATEWAY_ECHECK . '_status'] ?? $this->config->get('payment_' . PAYMENT_GATEWAY_ECHECK . '_status');
			$data['payment_sort_order'] = $this->request->post['payment_' . PAYMENT_GATEWAY_ECHECK . '_sort_order'] ?? $this->config->get('payment_' . PAYMENT_GATEWAY_ECHECK . '_sort_order');
		} else {
			$status_to_zero = array('payment_' . PAYMENT_GATEWAY_ECHECK . '_status' => VAL_ZERO);
			$this->model_setting_setting->editSetting('payment_' . PAYMENT_GATEWAY_ECHECK, $status_to_zero);
		}
		$data['payment_gateway'] = PAYMENT_GATEWAY_ECHECK;
		$data['extension_version'] = EXTENSION_VERSION;
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$this->response->setOutput($this->load->view('extension/payment/cybersource_echeck', $data));
	}

	public function install() {
		$this->load->model('extension/payment/cybersource_echeck');
		$this->model_extension_payment_cybersource_echeck->install();
	}

	public function uninstall() {
		$this->load->model('extension/payment/cybersource_echeck');
		$this->model_extension_payment_cybersource_echeck->uninstall();
	}

	private function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/cybersource_echeck')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		return !$this->error;
	}

	public function order() {
		return $this->getOrderTransactionDetails(PAYMENT_GATEWAY_ECHECK, TABLE_PREFIX_ECHECK);
	}

	public function confirmVoidCapture() {
		$this->voidCaptureService(PAYMENT_GATEWAY_ECHECK, TABLE_PREFIX_ECHECK);
	}

	public function confirmRefund() {
		$this->refundService(PAYMENT_GATEWAY_ECHECK, TABLE_PREFIX_ECHECK);
	}

	public function confirmVoidRefund() {
		$this->voidRefundService(PAYMENT_GATEWAY_ECHECK, TABLE_PREFIX_ECHECK);
	}

	public function cron() {
		$this->cronService(PAYMENT_GATEWAY_ECHECK, TABLE_PREFIX_ECHECK);
	}
}
