<?php

require_once DIR_SYSTEM . 'library/isv/cybersource_constants.php';

/**
 * The functions related to Apple pay are in this file.
 *
 * @author Cybersource
 * @package Front Office
 * @subpackage Controller
 */
class ControllerExtensionPaymentCybersourceApay extends Controller {
	/**
	 * This function will be called when customer selects Apple Pay as payment method.
	 *
	 * All the things that are loaded in Apple pay payment section are returned from this function.
	 */
	public function index() {
		$this->load->language('extension/payment/cybersource_apay');
		$this->load->language('extension/payment/cybersource_common');
		$this->load->model('extension/payment/cybersource_common');
		$this->load->model('checkout/order');
		$order_id = $this->session->data['order_id'] ?? VAL_ZERO;
		$payload_data = $this->model_checkout_order->getOrder($order_id);
		$total_amount = $this->currency->format($payload_data['total'], $payload_data['currency_code'], false, false);
		$csrf = $this->session->data['csrf'] ?? VAL_EMPTY;
		$data = $this->model_extension_payment_cybersource_common->davCommon($order_id, $csrf);
		$data['language_code'] = $payload_data['language_code'];
		$data['total'] = $total_amount;
		$data['country_code'] = $payload_data['payment_iso_code_2'];
		$data['currency_code'] = $payload_data['currency_code'];
		$data['store_name'] = $this->config->get('payment_' . PAYMENT_GATEWAY_APPLE_PAY . '_store_name');
		$data['csrf_token_data'] = $this->session->data['csrf'] ?? VAL_NULL;
		$data['time_data'] = $this->session->data['csrf_time'] ?? VAL_NULL;
		return $this->load->view('extension/payment/cybersource_apay', $data);
	}

	/**
	 * This function will be called when customer clicks on confirm pay from Apple Pay payment UI(internally called from recaptcha common function).
	 */
	public function confirmPerformValidation() {
		$this->load->language('extension/payment/cybersource_apay');
		$this->load->model('extension/payment/cybersource_common');
		$this->load->model('extension/payment/cybersource_apay');
		$result = false;
		try {
			$url_hostname = ($this->request->server['HTTPS']) ? parse_url(HTTPS_SERVER, PHP_URL_HOST) : parse_url(HTTP_SERVER, PHP_URL_HOST);
			$applepay_configuration = $this->model_extension_payment_cybersource_common->getApplePayConfiguration();
			$validation_url = $this->request->post['validation_url'] ?? VAL_NULL;
			$white_listed_urls = $this->model_extension_payment_cybersource_apay->getWhiteListDomainAsPerEnvironment();
			$is_url_valid = $this->model_extension_payment_cybersource_apay->isValidUrl($validation_url);
			if (!empty($white_listed_urls) && $is_url_valid && in_array(parse_url($validation_url, PHP_URL_HOST) ?? VAL_EMPTY, $white_listed_urls)) {
				$validation_data = array(
					'merchantIdentifier' => $applepay_configuration['merchant_id'],
					'initiative' => "web",
					'initiativeContext' => $url_hostname,
					'displayName' => $this->config->get('payment_' . PAYMENT_GATEWAY_APPLE_PAY . '_store_name') ?? VAL_NULL,
				);
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
				curl_setopt($ch, CURLOPT_URL, $validation_url);
				curl_setopt($ch, CURLOPT_SSLCERT, $applepay_configuration['certificate_path']);
				curl_setopt($ch, CURLOPT_SSLKEY, $applepay_configuration['key_path']);
				curl_setopt($ch, CURLOPT_POST, VAL_ONE);
				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($validation_data));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, VAL_ONE);
				$result = curl_exec($ch);
				curl_close($ch);
			} else {
				$this->model_extension_payment_cybersource_common->logger('[ControllerExtensionPaymentCybersourceApay][confirmPerformValidation]:' . $this->language->get('error_invalid_url'));
			}
		} catch (Exception $e) {
			$result = false;
			$this->model_extension_payment_cybersource_common->logger('[ControllerExtensionPaymentCybersourceApay][confirmPerformValidation]:' . $this->language->get('error_exception'));
		}
		$this->response->addHeader(CONTENT_TYPE);
		$this->response->setOutput(json_encode($result));
	}

	/**
	 * This function will be called when merchant validation is success(internally called from merchant validation function).
	 */
	public function confirm() {
		if ((!$this->customer->isLogged()) && (!$this->config->get('config_checkout_guest'))) {
			$this->session->data['redirect'] = $this->url->link('checkout/checkout', '', true);
			$response_data['redirect'] = $this->url->link('account/login');
		} else {
			$response_data = array();
			$api_response = array();
			$total_quantity = VAL_ZERO;
			$this->load->language('extension/payment/cybersource_apay');
			$this->load->language('extension/payment/cybersource_loggers');
			$this->load->model('checkout/order');
			$this->load->model('extension/payment/cybersource_common');
			$this->load->model('extension/payment/cybersource_apay');
			$this->load->model('extension/payment/cybersource');
			$this->session->data['csrf_token']  = $this->request->post['csrf'] ?? VAL_NULL;
			$this->session->data['csrf_token_time'] = $this->request->post['time'] ?? VAL_NULL;
			$dav_address = $this->request->post['dav_address'] ?? VAL_NULL;
			$order_id = $this->session->data['order_id'] ?? VAL_NULL;
			$shipping_address = $this->request->post['shipping_address'] ?? VAL_NULL;
			$shipping_city = $this->request->post['shipping_city'] ?? VAL_NULL;
			$shipping_state = $this->request->post['shipping_state'] ?? VAL_NULL;
			$shipping_country = $this->request->post['shipping_country'] ?? VAL_NULL;
			$shipping_postal = $this->request->post['shipping_postal'] ?? VAL_NULL;
			$payment_data = $this->request->post['payment_data'] ?? VAL_NULL;
			if (VAL_ONE == $dav_address) {
				$this->model_extension_payment_cybersource_common->updateRecommendedAddress($shipping_address, $shipping_city, $shipping_state, $shipping_country, $shipping_postal, $order_id);
			}
			$payload_data = $this->model_checkout_order->getOrder($order_id);
			$payload_data['currency_code'] = $this->config->get('config_currency');
			$product_details = $this->model_checkout_order->getOrderProducts($order_id);
			$voucher_details = $this->model_checkout_order->getOrderVouchers($order_id);
			$line_items = $this->model_extension_payment_cybersource_common->getLineItemArray($product_details, $voucher_details, $order_id);
			foreach ($product_details as $product) {
				$total_quantity += $product['quantity'];
			}
			$csrf_token = $this->request->post['csrf'] ?? VAL_NULL;
			$tax_id = $this->session->data['tax_id'] ?? VAL_NULL;
			$csrf = $this->session->data['csrf'] ?? VAL_NULL;
			$csrf_token_time = $this->request->post['time'] ?? VAL_NULL;
			if ($csrf_token) {
				if (!(VAL_SIX_ZERO_ZERO < (time() - $csrf_token_time))) {
					if (VAL_ZERO == strcmp($csrf_token, $csrf)) {
						if (!empty($payment_data)) {
							try {
								$api_response = $this->model_extension_payment_cybersource_apay->getPaymentResponse($payment_data, $payload_data, $line_items, $order_id);
								if (VAL_NULL != $api_response) {
									$response_http_code = $api_response['http_code'];
									$response_http_body = $api_response['body'];
									$payment_response_array = $this->model_extension_payment_cybersource_common->getResponse($response_http_code, $response_http_body, SERVICE_AUTH);
									list($status, $custom_status) = $this->model_extension_payment_cybersource_common->getOrderStatus($payment_response_array);
									if ((CODE_TWO_ZERO_ONE == $response_http_code) && ((API_STATUS_AUTHORIZED == $payment_response_array['status']) || (API_STATUS_AUTHORIZED_RISK_DECLINED == $payment_response_array['status']) || (API_STATUS_AUTHORIZED_PENDING_REVIEW == $payment_response_array['status']))) {
										$order_result = $this->model_extension_payment_cybersource_common->addOrderHistory($order_id, $status, $custom_status, VAL_NULL, true);
										if ($order_result) {
											$order_details = $this->model_extension_payment_cybersource_apay->prepareOrderDetails($payment_response_array, $payload_data, $total_quantity, $status, $tax_id);
											$is_insertion_success = $this->model_extension_payment_cybersource->insertOrderDetails($order_details, TABLE_PREFIX_APPLE_PAY);
											if ($is_insertion_success) {
												if (API_STATUS_AUTHORIZED_RISK_DECLINED == $payment_response_array['status']) {
													$response_data['redirect']  = $this->model_extension_payment_cybersource->getFraudulentAuthReversalData($order_id, $payment_response_array['transaction_id'], TABLE_PREFIX_APPLE_PAY);
													if (VAL_NULL == $response_data['redirect']) {
														$response_data['redirect']  = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersourceApay][confirm]:' . $this->language->get('error_FM_reject'), STATUS_FAILURE);
													}
												} else {
													$response_data['redirect'] = $this->model_extension_payment_cybersource_common->getReturnUrl(VAL_EMPTY, STATUS_SUCCESS);
												}
											} else {
												$response_data['redirect'] = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersourceApay][confirm]:' . $this->language->get('error_order_table_insertion'), STATUS_FAILURE);
											}
										} else {
											$response_data['redirect'] = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersourceApay][confirm]:' . $this->language->get('error_history_table_update'), STATUS_FAILURE);
										}
									} else {
										$response_data['redirect'] = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersourceApay][confirm]:' . $response_http_body, STATUS_FAILURE);
									}
								} else {
									$response_data['redirect'] = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersourceApay][confirm]:' . $this->language->get('error_response_info'), STATUS_FAILURE);
								}
							} catch (Exception $e) {
								$response_data['redirect'] = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersourceApay][confirm]:' . $this->language->get('error_exception'), STATUS_FAILURE);
							}
						} else {
							$response_data['redirect']  = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][confirm]:' . $this->language->get('error_payment_data_missing'), STATUS_CHECKOUT);
						}
					} else {
						$response_data['redirect'] = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersourceApay][confirm]:' . $this->language->get('error_csrf_token_expired'), STATUS_CHECKOUT);
					}
				} else {
					$response_data['redirect'] = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersourceApay][confirm]:' . $this->language->get('error_csrf_token_validation_failed'), STATUS_CHECKOUT);
				}
			} else {
				$response_data['redirect'] = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersourceApay][confirm]:' . $this->language->get('error_csrf_token_missing'), STATUS_CHECKOUT);
			}
		}
		$this->model_extension_payment_cybersource_common->unsetSessionData();
		$this->response->addHeader(CONTENT_TYPE);
		$this->response->setOutput(json_encode($response_data));
	}

	public function confirmCancel() {
		if ($this->customer->isLogged()) {
			$this->load->model('extension/payment/cybersource_common');
			$this->load->model('extension/payment/cybersource_apay');
			$this->load->model('extension/payment/cybersource');
			$this->load->model('extension/payment/cybersource_query');
			$this->load->language('extension/payment/cybersource_apay');
			$this->load->language('extension/payment/cybersource_loggers');
			$this->load->model('checkout/order');
			$order_id = $this->request->post['order_id'] ?? VAL_ZERO;
			$csrf_token  = $this->request->post['csrf_token'] ?? VAL_ZERO;
			$csrf = $this->session->data['csrf'] ?? VAL_NULL;
			$order = $this->model_checkout_order->getOrder($order_id);
			$customer_id = $this->customer->getId();
			if ($customer_id == $order['customer_id']) {
				if (!empty($order_id)) {
					if ($csrf_token && VAL_ZERO == strcmp($csrf_token, $csrf)) {
						try {
							$query_auth_reversal = $this->model_extension_payment_cybersource_query->queryAuthReversalId($order_id, TABLE_PREFIX_APPLE_PAY);
							$auth_reversal_data = ($query_auth_reversal->num_rows > VAL_ZERO) ? $query_auth_reversal->row['transaction_id'] : VAL_ZERO;
							if (empty($auth_reversal_data)) {
								$order_details = $this->model_extension_payment_cybersource->getOrderDetails($order_id, TABLE_PREFIX_APPLE_PAY);
								if (!empty($order_details)) {
									$api_response = $this->model_extension_payment_cybersource->getCancelResponse($order_id, $order_details, PAYMENT_GATEWAY_APPLE_PAY);
									if (VAL_NULL != $api_response) {
										$http_code = $api_response['http_code'];
										$cancel_response_array = $this->model_extension_payment_cybersource_common->getResponse($http_code, $api_response['body'], SERVICE_AUTH_REVERSAL);
										if ((CODE_TWO_ZERO_ONE == $http_code) && (API_STATUS_REVERSED == $cancel_response_array['status'])) {
											$order_result = $this->model_extension_payment_cybersource->addOrderHistoryForCancelOrder($order_id, $this->config->get('module_' . PAYMENT_GATEWAY . '_auth_reversal_status_id'), CANCELLED, VAL_NULL, true);
											if ($order_result) {
												$item_restock = $this->model_extension_payment_cybersource->restock($order_id);
												if ($item_restock) {
													$auth_reversal_details = $this->model_extension_payment_cybersource->prepareAuthReversalDetails($cancel_response_array, $order_id);
													$is_insertion_success = $this->model_extension_payment_cybersource->insertAuthReversalDetails($auth_reversal_details, TABLE_PREFIX_APPLE_PAY);
													if (!$is_insertion_success) {
														$this->session->data['error'] = $this->language->get('warning_msg_auth_reversal_insertion');
													} else {
														$this->session->data[STATUS_SUCCESS] = $this->language->get('success_msg_auth_reversal');
													}
												} else {
													$this->session->data['error'] = $this->language->get('error_failed_to_restock');
												}
											} else {
												$this->session->data['error'] = $this->language->get('error_history_table_update');
											}
										} else {
											$this->model_extension_payment_cybersource->addOrderHistoryForCancelOrder($order_id, $this->config->get('module_' . PAYMENT_GATEWAY . '_auth_reversal_error_status_id'), CANCELLED_ERROR, VAL_NULL, true);
											$this->session->data['error'] = $this->language->get('error_msg_auth_reversal');
										}
									} else {
										$this->session->data['error'] = $this->language->get('error_response_info');
									}
								} else {
									$this->session->data['error'] = $this->language->get('error_msg_order_details');
								}
							} else {
								$this->session->data['error'] = $this->language->get('warning_msg_cancel_completed');
							}
						} catch (Exception $e) {
							$this->session->data['error'] = $this->language->get('error_msg_order_details');
						}
					} else {
						$this->session->data['error'] = $this->language->get('error_session');
					}
				} else {
					$this->session->data['error'] = $this->language->get('error_msg_order_id_not_found');
				}
			} else {
				$this->session->data['error'] = $this->language->get('error_msg_order_id_not_found');
			}
		}
	}

	/**
	 * This function will be called when customer clicks confrim order button from Apple pay payment ui section.
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
			$response_data = $this->model_extension_payment_cybersource_common->recaptchaCommon(PAYMENT_GATEWAY_APPLE_PAY, $request_data);
		}
		$this->response->addHeader(CONTENT_TYPE);
		$this->response->setOutput(json_encode($response_data));
	}

	/**
	 * Checkout page before event for loading "system/library/isv/catalog/view/javascript/apay.js" file which will hide Apple Pay payment method.
	 * catalog/controller/checkout/checkout/before.
	 *
	 * @param $route
	 * @param $args
	 */
	public function buttonHiding(&$route, &$args) {
		if ($this->config->get('payment_' . PAYMENT_GATEWAY_APPLE_PAY . '_status')) {
			$this->document->addScript('system/library/isv/catalog/view/javascript/apay.js', 'footer');
		}
	}
}
