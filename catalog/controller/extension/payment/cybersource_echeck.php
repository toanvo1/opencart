<?php

require_once DIR_SYSTEM . 'library/isv/cybersource_constants.php';

/**
 * The functions related to echeck are in this file.
 *
 * @author Cybersource
 * @package Front Office
 * @subpackage Controller
 */
class ControllerExtensionPaymentCybersourceEcheck extends Controller {
	/**
	 * This function will be called when customer selects echeck as payment method.
	 *
	 * All the things that are loaded in echeck payment section are returned from this function.
	 */
	public function index() {
		$this->load->language('extension/payment/cybersource_echeck');
		$this->load->language('extension/payment/cybersource_common');
		$this->load->model('extension/payment/cybersource_common');
		$order_id = $this->session->data['order_id'] ?? VAL_ZERO;
		$csrf = $this->session->data['csrf'] ?? VAL_EMPTY;
		$data = $this->model_extension_payment_cybersource_common->davCommon($order_id, $csrf);
		$data['csrf_token_data'] = $this->session->data['csrf'] ?? VAL_NULL;
		$data['time_data'] = $this->session->data['csrf_time'] ?? VAL_NULL;
		return $this->load->view('extension/payment/cybersource_echeck', $data);
	}

	/**
	 * This function will be called when customer clicks confirm order button from echeck payment ui section(internally called from recaptcha common function).
	 */
	public function confirm() {
		if ((!$this->customer->isLogged()) && (!$this->config->get('config_checkout_guest'))) {
			$this->session->data['redirect'] = $this->url->link('checkout/checkout', '', true);
			$response_data['redirect'] = $this->url->link('account/login');
		} else {
			$response_data = array();
			$api_response = array();
			$total_quantity = VAL_ZERO;
			$this->load->language('extension/payment/cybersource_echeck');
			$this->load->language('extension/payment/cybersource_loggers');
			$this->load->model('checkout/order');
			$this->load->model('extension/payment/cybersource_common');
			$this->load->model('extension/payment/cybersource_echeck');
			$dav_address = $this->request->post['dav_address'] ?? VAL_NULL;
			$order_id = $this->session->data['order_id'] ?? VAL_NULL;
			$shipping_address = $this->request->post['shipping_address'] ?? VAL_NULL;
			$shipping_city = $this->request->post['shipping_city'] ?? VAL_NULL;
			$shipping_state = $this->request->post['shipping_state'] ?? VAL_NULL;
			$shipping_country = $this->request->post['shipping_country'] ?? VAL_NULL;
			$shipping_postal = $this->request->post['shipping_postal'] ?? VAL_NULL;
			if (VAL_ONE == $dav_address) {
				$this->model_extension_payment_cybersource_common->updateRecommendedAddress($shipping_address, $shipping_city, $shipping_state, $shipping_country, $shipping_postal, $order_id);
			}
			$account_number = $this->request->post['account_number'] ?? VAL_NULL;
			$account_type = $this->request->post['account_type'] ?? VAL_NULL;
			$account_routing_number = $this->request->post['account_routing_number'] ?? VAL_NULL;
			$payload_data = $this->model_checkout_order->getOrder($order_id);
			$payload_data['currency_code'] = $this->config->get('config_currency');
			$product_details = $this->model_checkout_order->getOrderProducts($order_id);
			$voucher_details = $this->model_checkout_order->getOrderVouchers($order_id);
			$line_items = $this->model_extension_payment_cybersource_common->getLineItemArray($product_details, $voucher_details, $order_id);
			foreach ($product_details as $product) {
				$total_quantity += $product['quantity'];
			}
			$csrf_token = $this->session->data['csrf_token'] ?? VAL_NULL;
			$tax_id = $this->session->data['tax_id'] ?? VAL_NULL;
			$csrf = $this->session->data['csrf'] ?? VAL_NULL;
			$csrf_token_time = $this->session->data['csrf_token_time'] ?? VAL_NULL;
			if ($csrf_token) {
				if (!(VAL_SIX_ZERO_ZERO < (time() - $csrf_token_time))) {
					if (VAL_ZERO == strcmp($csrf_token, $csrf)) {
						try {
							$api_response = $this->model_extension_payment_cybersource_echeck->getPaymentResponse(trim($account_number), trim($account_type), trim($account_routing_number), $payload_data, $line_items, $order_id);
							if (VAL_NULL != $api_response) {
								$response_http_code = $api_response['http_code'];
								$response_http_body = $api_response['body'];
								$payment_response_array = $this->model_extension_payment_cybersource_common->getResponse($response_http_code, $response_http_body, PAYMENT_GATEWAY_ECHECK);
								list($status, $custom_status) = $this->model_extension_payment_cybersource_common->getOrderStatus($payment_response_array);
								if (CODE_TWO_ZERO_ONE == $response_http_code && (API_STATUS_PENDING == $payment_response_array['status']
								|| API_STATUS_PENDING_REVIEW == $payment_response_array['status']) && API_STATUS_PROCESSOR_ERROR != $payment_response_array['status']) {
									$order_result = $this->model_extension_payment_cybersource_common->addOrderHistory($order_id, $status, $custom_status, VAL_NULL, true);
									if ($order_result) {
										$order_details = $this->model_extension_payment_cybersource_echeck->prepareOrderDetails($payment_response_array, $payload_data, $total_quantity, $status, $tax_id);
										$is_insertion_success = $this->model_extension_payment_cybersource_echeck->insertOrderDetails($order_details);
										if ($is_insertion_success) {
											$response_data['redirect'] = $this->model_extension_payment_cybersource_common->getReturnUrl(VAL_EMPTY, STATUS_SUCCESS);
										} else {
											$response_data['redirect'] = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersourceEcheck][confirm]:' . $this->language->get('error_order_table_insertion'), STATUS_FAILURE);
										}
									} else {
										$response_data['redirect'] = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersourceEcheck][confirm]:' . $this->language->get('error_history_table_update'), STATUS_FAILURE);
									}
								} else {
									$response_data['redirect'] = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersourceEcheck][confirm]:' . $response_http_body, STATUS_FAILURE);
								}
							} else {
								$response_data['redirect'] = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersourceEcheck][confirm]:' . $this->language->get('error_response_info'), STATUS_FAILURE);
							}
						} catch (Exception $e) {
							$response_data['redirect'] = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersourceEcheck][confirm]:' . $this->language->get('error_exception'), STATUS_FAILURE);
						}
					} else {
						$response_data['redirect'] = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersourceEcheck][confirm]:' . $this->language->get('error_csrf_token_expired'), STATUS_CHECKOUT);
					}
				} else {
					$response_data['redirect'] = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersourceEcheck][confirm]:' . $this->language->get('error_csrf_token_validation_failed'), STATUS_CHECKOUT);
				}
			} else {
				$response_data['redirect'] = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersourceEcheck][confirm]:' . $this->language->get('error_csrf_token_missing'), STATUS_CHECKOUT);
			}
		}
		$this->model_extension_payment_cybersource_common->unsetSessionData();
		$this->response->addHeader(CONTENT_TYPE);
		$this->response->setOutput(json_encode($response_data));
	}

	/**
	 * This function will be called when customer clicks confrim order button from echeck payment ui section.
	 */
	public function confirmPaymentRecaptcha() {
		if ((!$this->customer->isLogged()) && (!$this->config->get('config_checkout_guest'))) {
			$this->session->data['redirect'] = $this->url->link('checkout/checkout', '', true);
			$response_data['redirect'] = $this->url->link('account/login');
		} else {
			$this->load->model('extension/payment/cybersource_common');
			$request_data['recaptcha_token'] = $this->request->post['token'] ?? VAL_NULL;
			$request_data['csrf'] = $this->request->post['csrf'] ?? VAL_NULL;
			$request_data['time'] = $this->request->post['time'] ?? VAL_NULL;
			$response_data = $this->model_extension_payment_cybersource_common->recaptchaCommon(PAYMENT_GATEWAY_ECHECK, $request_data);
		}
		$this->response->addHeader(CONTENT_TYPE);
		$this->response->setOutput(json_encode($response_data));
	}
}
