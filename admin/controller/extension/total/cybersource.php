<?php

require_once DIR_SYSTEM . 'library/isv/cybersource_constants.php';

/**
 * Tax controller file.
 *
 * @author Cybersource
 * @package Back Office
 * @subpackage Controller
 */
class ControllerExtensionTotalCybersource extends Controller {
	private $error = array();

	public function index() {
		$data = array();
		$data['status'] = FLAG_DISABLE;
		$this->load->language('extension/total/cybersource');
		$this->load->language('extension/payment/cybersource_common');
		$this->load->model('extension/payment/cybersource_common');
		$this->load->model('setting/setting');
		$this->load->model('extension/payment/cybersource_common');
		$this->load->model('extension/payment/cybersource_query');
		$this->document->setTitle($this->language->get('heading_title'));
		if (!$this->user->isLogged() && isset($this->request->get['user_token']) && ($this->request->get['user_token'] == $this->session->data['user_token'])) {
			$this->response->redirect($this->url->link('common/dashboard', '', true));
		}
		if (HTTP_METHOD_POST == $this->request->server['REQUEST_METHOD'] && $this->validate()) {
			$this->model_setting_setting->editSetting('total_' . PAYMENT_GATEWAY, $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=total', true));
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
		$data['breadcrumbs'] = $this->model_extension_payment_cybersource_common->getBreadcrumbsData($this->session->data['user_token'], EXTENSION_TYPE_TOTAL, PAYMENT_GATEWAY);
		$data['action'] = $this->url->link('extension/total/' . PAYMENT_GATEWAY, 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=total', true);
		if (FLAG_ENABLE == $data['status']) {
			$data['total_status'] = $this->request->post['total_' . PAYMENT_GATEWAY . '_status'] ?? $this->config->get('total_' . PAYMENT_GATEWAY . '_status');
			$data['total_sort_order'] = $this->request->post['total_' . PAYMENT_GATEWAY . '_sort_order'] ?? $this->config->get('total_' . PAYMENT_GATEWAY . '_sort_order');
		} else {
			$module_status = array('total_' . PAYMENT_GATEWAY . '_status' => VAL_ZERO);
			$this->model_setting_setting->editSetting('total_' . PAYMENT_GATEWAY, $module_status);
		}
		$data['payment_gateway'] = PAYMENT_GATEWAY;
		$data['extension_version'] = EXTENSION_VERSION;
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$this->response->setOutput($this->load->view('extension/total/cybersource', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/total/cybersource')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		return !$this->error;
	}
}
