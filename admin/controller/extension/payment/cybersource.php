<?php

use Isv\Admin\Controller\Cancel;
use Isv\Admin\Controller\Capture;
use Isv\Admin\Controller\Cron;
use Isv\Admin\Controller\Order;
use Isv\Admin\Controller\Refund;
use Isv\Admin\Controller\VoidService;
use Isv\Admin\Controller\Webhook;

require_once DIR_SYSTEM . 'library/isv/cybersource_constants.php';

/**
 * Unified Checkout controller file.
 *
 * @author Cybersource
 * @package Back Office
 * @subpackage Controller
 */
class ControllerExtensionPaymentCybersource extends Controller {
	use Order, Capture, Refund, Cancel, VoidService, Cron, Webhook;

	private $error = array();

	public function index() {
		$data = array();
		$data['status'] = FLAG_DISABLE;
		if (!$this->user->isLogged() && isset($this->request->get['user_token']) && ($this->request->get['user_token'] == $this->session->data['user_token'])) {
			$this->response->redirect($this->url->link('common/dashboard', '', true));
		}
		$this->load->language('extension/payment/cybersource');
		$this->load->language('extension/payment/cybersource_common');
		$this->load->model('setting/setting');
		$this->load->model('extension/payment/cybersource_query');
		$this->load->model('extension/payment/cybersource_common');
		$this->load->model('extension/payment/cybersource');
		$this->document->setTitle($this->language->get('heading_title'));
		$data['error_warning_type'] = ERROR_TYPE_DANGER;
		if (HTTP_METHOD_POST == $this->request->server['REQUEST_METHOD'] && $this->validate()) {
			$this->request->post['payment_' . PAYMENT_GATEWAY . '_webhook_security_token'] = $this->config->get('payment_' . PAYMENT_GATEWAY . '_webhook_security_token') ?? hash(ALGORITHM_SHA256, uniqid(bin2hex(random_bytes(64)), VAL_ONE));
			$tokenization_status = $this->request->post['payment_' . PAYMENT_GATEWAY . '_card'];
			$network_token_updates_status = $this->request->post['payment_' . PAYMENT_GATEWAY . '_network_token_updates_status'];
			if ((isset($network_token_updates_status) && $network_token_updates_status) && (isset($tokenization_status) && $tokenization_status)) {
				$merchant_id = ENVIRONMENT_TEST === $this->config->get('module_' . PAYMENT_GATEWAY . '_sandbox') ? $this->config->get('module_' . PAYMENT_GATEWAY . '_merchant_id_test') : $this->config->get('module_' . PAYMENT_GATEWAY . '_merchant_id_live');
				$event_types = array(TMS_NETWORK_TOKEN_UPDATED);
				$webhook_security_token = $this->request->post['payment_' . PAYMENT_GATEWAY . '_webhook_security_token'];
				$response = $this->webhookService($merchant_id, PRODUCT_ID_TOKEN_MANAGEMENT, $event_types, $this->request->server['HTTPS'], $webhook_security_token);
				$this->error['warning'] = $response ?? VAL_NULL;
				if (isset($this->error['warning'])) {
					$this->request->post['payment_' . PAYMENT_GATEWAY . '_network_token_updates_status'] = VAL_ZERO;
				}
			}
			$this->model_setting_setting->editSetting('payment_' . PAYMENT_GATEWAY, array_map('trim', $this->request->post));
			if (!isset($this->error['warning'])) {
				$this->session->data['success'] = $this->language->get('text_success');
				$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
			} else {
				$data['error_warning_type'] = ERROR_TYPE_WARNING;
			}
		}
		$is_payment_configured = $this->model_extension_payment_cybersource_query->queryPaymentConfiguration();
		if ($is_payment_configured) {
			if (!empty($this->config->get('module_' . PAYMENT_GATEWAY . '_configuration_status'))) {
				if ((!empty($this->config->get('module_' . PAYMENT_GATEWAY . '_merchant_secret_key_test')) && !empty($this->config->get('module_' . PAYMENT_GATEWAY . '_merchant_key_id_test')) && !empty($this->config->get('module_' . PAYMENT_GATEWAY . '_merchant_id_test')))
					||  (!empty($this->config->get('module_' . PAYMENT_GATEWAY . '_merchant_secret_key_live')) && !empty($this->config->get('module_' . PAYMENT_GATEWAY . '_merchant_key_id_live')) && !empty($this->config->get('module_' . PAYMENT_GATEWAY . '_merchant_id_live')))) {
					$data['status'] = FLAG_ENABLE;
				}
			}
		}
		$data['error_warning'] = $this->error['warning'] ?? VAL_EMPTY;
		$data['error_saved_card_limit_frame'] = $this->error['saved_card_limit_frame'] ?? VAL_EMPTY;
		$data['error_saved_card_limit_time_frame'] = $this->error['saved_card_limit_time_frame'] ?? VAL_EMPTY;
		$data['error_payment_option_label'] = $this->error['payment_option_label'] ?? VAL_EMPTY;
		$data['error_payment_allowed_card_type'] = $this->error['payment_allowed_card_type'] ?? VAL_EMPTY;
		$data['breadcrumbs'] = $this->model_extension_payment_cybersource_common->getBreadcrumbsData($this->session->data['user_token'], EXTENSION_TYPE_PAYMENT, PAYMENT_GATEWAY);
		$data['action'] = $this->url->link('extension/payment/' . PAYMENT_GATEWAY, 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);
		$payment_configuration = $this->model_setting_setting->getSetting('payment_' . PAYMENT_GATEWAY);
		if (FLAG_ENABLE == $data['status'] && $payment_configuration) {
			$data['payment_sort_order'] = $this->request->post['payment_' . PAYMENT_GATEWAY . '_sort_order'] ?? $this->config->get('payment_' . PAYMENT_GATEWAY . '_sort_order');
			$data['payment_status'] = $this->request->post['payment_' . PAYMENT_GATEWAY . '_status'] ?? $this->config->get('payment_' . PAYMENT_GATEWAY . '_status');
			$data['payment_payer_auth'] = $this->request->post['payment_' . PAYMENT_GATEWAY . '_payer_auth'] ?? $this->config->get('payment_' . PAYMENT_GATEWAY . '_payer_auth');
			$data['payment_card'] = $this->request->post['payment_' . PAYMENT_GATEWAY . '_card'] ?? $this->config->get('payment_' . PAYMENT_GATEWAY . '_card');
			$data['payment_network_token_updates_status'] = $this->request->post['payment_' . PAYMENT_GATEWAY . '_network_token_updates_status'] ?? $this->config->get('payment_' . PAYMENT_GATEWAY . '_network_token_updates_status');
			$data['payment_limit_saved_card_rate'] = $this->request->post['payment_' . PAYMENT_GATEWAY . '_limit_saved_card_rate'] ?? $this->config->get('payment_' . PAYMENT_GATEWAY . '_limit_saved_card_rate');
			$data['payment_saved_card_limit_frame'] = $this->request->post['payment_' . PAYMENT_GATEWAY . '_saved_card_limit_frame'] ?? $this->config->get('payment_' . PAYMENT_GATEWAY . '_saved_card_limit_frame');
			$data['payment_saved_card_limit_time_frame'] = $this->request->post['payment_' . PAYMENT_GATEWAY . '_saved_card_limit_time_frame'] ?? $this->config->get('payment_' . PAYMENT_GATEWAY . '_saved_card_limit_time_frame');
			$data['payment_payer_auth_challenge'] = $this->request->post['payment_' . PAYMENT_GATEWAY . '_payer_auth_challenge'] ?? $this->config->get('payment_' . PAYMENT_GATEWAY . '_payer_auth_challenge');
			$data['payment_gpay_status'] = $this->request->post['payment_' . PAYMENT_GATEWAY . '_gpay_status'] ?? $this->config->get('payment_' . PAYMENT_GATEWAY . '_gpay_status');
			$data['payment_vsrc_status'] = $this->request->post['payment_' . PAYMENT_GATEWAY . '_vsrc_status'] ?? $this->config->get('payment_' . PAYMENT_GATEWAY . '_vsrc_status');
		} else {
			$module_status = array('payment_' . PAYMENT_GATEWAY . '_status' => VAL_ZERO);
			$default_uc_values = $this->model_extension_payment_cybersource->getDefaultUcData();
			$this->model_setting_setting->editSetting('payment_' . PAYMENT_GATEWAY, array_map('trim', array_merge($default_uc_values, $module_status)));
			$payment_configuration = $this->model_setting_setting->getSetting('payment_' . PAYMENT_GATEWAY);
		}
		$data['payment_option_label'] = $this->request->post['payment_' . PAYMENT_GATEWAY . '_payment_option_label'] ?? $payment_configuration['payment_' . PAYMENT_GATEWAY . '_payment_option_label'];
		$allowed_cards = array('visa' => UNIFIED_CHECKOUT_VISA_CARD, 'mastercard' => UNIFIED_CHECKOUT_MASTERCARD_CARD, 'discover' => UNIFIED_CHECKOUT_DISCOVER_CARD, 'amex' => UNIFIED_CHECKOUT_AMEX_CARD, 'jcb' => UNIFIED_CHECKOUT_JCB_CARD, 'dinersclub' => UNIFIED_CHECKOUT_DINERSCLUB_CARD);
		foreach ($allowed_cards as $status_name => $general_name) {
			$data['allowed_cards'][] = array(
				'general_name' => $general_name,
				'status' => $this->request->post['payment_' . PAYMENT_GATEWAY . UNDER_SCORE . $status_name . '_card_status'] ?? $payment_configuration['payment_' . PAYMENT_GATEWAY . UNDER_SCORE . $status_name . '_card_status'],
				'status_name' => $status_name
			);
		}
		$data['payment_gateway'] = PAYMENT_GATEWAY;
		$data['payment_gateway_gpay'] = PAYMENT_GATEWAY . '_gpay';
		$data['payment_gateway_vsrc'] = PAYMENT_GATEWAY . '_vsrc';
		$data['extension_version'] = EXTENSION_VERSION;
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$this->response->setOutput($this->load->view('extension/payment/cybersource', $data));
	}

	public function install() {
		$this->load->model('extension/payment/cybersource');
		$this->load->model('setting/setting');
		$this->model_extension_payment_cybersource->install();
		$default_uc_values = $this->model_extension_payment_cybersource->getDefaultUcData();
		$this->model_setting_setting->editSetting('payment_' . PAYMENT_GATEWAY, $default_uc_values);
	}

	public function uninstall() {
		$this->load->model('extension/payment/cybersource');
		$this->model_extension_payment_cybersource->uninstall();
	}

	private function validate() {
		$this->load->language('extension/payment/cybersource');
		$this->load->language('extension/payment/cybersource_common');
		if (!$this->user->hasPermission('modify', 'extension/payment/cybersource')) {
			$this->error['warning'] = $this->language->get('error_permission');
		} else {
			if (VAL_ONE == $this->request->post['payment_' . PAYMENT_GATEWAY . '_card']) {
				if (VAL_ONE == $this->request->post['payment_' . PAYMENT_GATEWAY . '_limit_saved_card_rate']) {
					if ((preg_match(REGEX_ONLY_NUM, trim($this->request->post['payment_' . PAYMENT_GATEWAY . '_saved_card_limit_frame']))
					|| !trim($this->request->post['payment_' . PAYMENT_GATEWAY . '_saved_card_limit_frame']))
					|| (VAL_ZERO > $this->request->post['payment_' . PAYMENT_GATEWAY . '_saved_card_limit_frame'])) {
						$this->error['saved_card_limit_frame'] = $this->language->get('error_saved_card_limit_frame');
					}
					if ((preg_match(REGEX_ONLY_NUM, trim($this->request->post['payment_' . PAYMENT_GATEWAY . '_saved_card_limit_time_frame']))
					|| !trim($this->request->post['payment_' . PAYMENT_GATEWAY . '_saved_card_limit_time_frame']))
					|| ((VAL_ZERO > $this->request->post['payment_' . PAYMENT_GATEWAY . '_saved_card_limit_time_frame'])
					|| (VAL_TWENTY_FOUR < $this->request->post['payment_' . PAYMENT_GATEWAY . '_saved_card_limit_time_frame']))) {
						$this->error['saved_card_limit_time_frame'] = $this->language->get('error_saved_card_limit_time_frame');
					}
				}
			}
			if (!trim($this->request->post['payment_' . PAYMENT_GATEWAY . '_payment_option_label'])) {
				$this->error['payment_option_label'] = $this->language->get('error_payment_option_label');
			}
			if (!preg_grep(REGEX_CARD_STATUS, array_keys(array_map('trim', $this->request->post)))) {
				$this->error['payment_allowed_card_type'] = $this->language->get('error_allowed_card_types');
			}
		}
		return !$this->error;
	}

	public function order() {
		return $this->getOrderTransactionDetails(PAYMENT_GATEWAY, TABLE_PREFIX_UNIFIED_CHECKOUT);
	}

	public function confirmCapture() {
		$this->captureService(PAYMENT_GATEWAY, TABLE_PREFIX_UNIFIED_CHECKOUT);
	}

	public function confirmCancel() {
		$this->cancelService(PAYMENT_GATEWAY, TABLE_PREFIX_UNIFIED_CHECKOUT);
	}

	public function confirmPartialCapture() {
		$this->partialCaptureService(PAYMENT_GATEWAY, TABLE_PREFIX_UNIFIED_CHECKOUT);
	}

	public function confirmVoidCapture() {
		$this->voidCaptureService(PAYMENT_GATEWAY, TABLE_PREFIX_UNIFIED_CHECKOUT);
	}

	public function confirmRefund() {
		$this->refundService(PAYMENT_GATEWAY, TABLE_PREFIX_UNIFIED_CHECKOUT);
	}

	public function confirmVoidRefund() {
		$this->voidRefundService(PAYMENT_GATEWAY, TABLE_PREFIX_UNIFIED_CHECKOUT);
	}

	public function cron() {
		$this->cronService(PAYMENT_GATEWAY, TABLE_PREFIX_UNIFIED_CHECKOUT);
	}
}
