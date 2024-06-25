<?php

use Isv\Catalog\Model\Webhook;

require_once DIR_SYSTEM . 'library/isv/cybersource_constants.php';

/**
 * The functions related to Unified Checkout are in this file.
 *
 * @author Cybersource
 * @package Front Office
 * @subpackage Controller
 */
class ControllerExtensionPaymentCybersource extends Controller {
	use Webhook;

	/**
	 * This function will be called when customer selects Unified Checkout as payment method.
	 *
	 * All the things that are loaded in Unified Checkout payment section are returned from this function.
	 */
	public function index() {
		$data['update_cards'] = false;
		$this->load->language('extension/payment/cybersource');
		$this->load->language('extension/payment/cybersource_common');
		$this->load->model('extension/payment/cybersource');
		$this->load->model('extension/payment/cybersource_common');
		$unifiedcheckout_configuration = $this->model_extension_payment_cybersource_common->getUnifiedCheckoutConfiguration();
		$order_id = $this->session->data['order_id'] ?? VAL_ZERO;
		$csrf = $this->session->data['csrf'] ?? VAL_EMPTY;
		$data = $this->model_extension_payment_cybersource_common->davCommon($order_id, $csrf);
		$capture_context_response = $this->model_extension_payment_cybersource_common->getCaptureContextResponse(false);
		if (VAL_NULL != $capture_context_response) {
			$capture_context_http_code = $capture_context_response['http_code'];
			if (CODE_TWO_ZERO_ONE == $capture_context_http_code) {
				$decoded_token = $this->model_extension_payment_cybersource_common->decodeToken($capture_context_response['body']);
				if (!isset($decoded_token->ctx[VAL_ZERO]->data->clientLibrary)) {
					$data['error_form_load'] = $this->language->get('error_form_load');
				} else {
					$data['capture_context'] = $capture_context_response['body'];
					$data['unified_checkout_client_library'] = $decoded_token->ctx[VAL_ZERO]->data->clientLibrary;
					$data['error_form_load'] = VAL_EMPTY;
				}
			} else {
				$data['error_form_load'] = $this->language->get('error_form_load');
			}
		} else {
			$data['error_form_load'] = $this->language->get('error_form_load');
		}
		if ($this->customer->isLogged()) {
			$data['update_cards'] = $this->model_extension_payment_cybersource->getCards($this->customer->getId());
		}
		$data['text_pay_with_unified_checkout'] = $this->language->get('text_pay_with') . $this->config->get('payment_' . PAYMENT_GATEWAY . '_payment_option_label');
		$data['csrf_token_data'] = $this->session->data['csrf'] ?? VAL_NULL;
		$data['time_data'] = $this->session->data['csrf_time'] ?? VAL_NULL;
		$data['logged'] = $this->customer->isLogged();
		$data['token'] = $unifiedcheckout_configuration['card'];
		$data['payer_auth'] = $unifiedcheckout_configuration['payer_auth'];
		$data['cardinal_url'] = $unifiedcheckout_configuration['cardinal_url'];
		$this->session->data['enroll_check'] = VAL_ZERO;
		return $this->load->view('extension/payment/cybersource', $data);
	}

	/**
	 * This function will be called when customer clicks confirm order button from Unified Checkout payment ui section(internally called from recaptcha common function).
	 */
	public function confirmFlexData() {
		if ((!$this->customer->isLogged()) && (!$this->config->get('config_checkout_guest'))) {
			$this->session->data['redirect'] = $this->url->link('checkout/checkout', '', true);
			$json['redirect'] = $this->url->link('account/login');
		} else {
			$data = array();
			$this->load->model('extension/payment/cybersource_common');
			$this->load->language('extension/payment/cybersource');
			$saved_card = $this->session->data['saved_card'] ?? VAL_NULL;
			$order_id = $this->session->data['order_id'] ?? VAL_NULL;
			$transient_token = $this->request->post['transient_token'] ?? VAL_NULL;
			$dav_address = $this->request->post['dav_address'] ?? VAL_NULL;
			$shipping_address = $this->request->post['shipping_address'] ?? VAL_NULL;
			$shipping_city = $this->request->post['shipping_city'] ?? VAL_NULL;
			$shipping_state = $this->request->post['shipping_state'] ?? VAL_NULL;
			$shipping_country = $this->request->post['shipping_country'] ?? VAL_NULL;
			$shipping_postal = $this->request->post['shipping_postal'] ?? VAL_NULL;
			$my_check = $this->request->post['my_check'] ?? VAL_NULL;
			$payer_auth = $this->request->post['payer_auth'] ?? VAL_NULL;
			if (VAL_ONE == $dav_address) {
				$this->model_extension_payment_cybersource_common->updateRecommendedAddress($shipping_address, $shipping_city, $shipping_state, $shipping_country, $shipping_postal, $order_id);
			}

			$payment_method = $this->session->data['uc_payment_method'] ?? VAL_NULL;
			if ($transient_token) {
				$this->session->data['transient_token'] = $transient_token;
				$this->session->data['my_check'] = $my_check;
				$this->session->data['payer_auth'] = $payer_auth;
				if ($payer_auth && PAYMENT_METHOD_NAME_CC == $payment_method) {
					$data['error'] = false;
					$data['payer_auth'] = true;
					$data['url'] = OPENCART_INDEX_PATH . EXTENSION_PAYMENT_GATEWAY . "/confirmPayerAuth";
				} else {
					$data['error'] = false;
					$data['payer_auth'] = false;
					$data['url'] = OPENCART_INDEX_PATH . EXTENSION_PAYMENT_GATEWAY . "/confirm";
				}
			} elseif ($saved_card && PAYMENT_METHOD_NAME_CC == $payment_method) {
				if ($payer_auth) {
					$data['error'] = false;
					$data['payer_auth'] = true;
					$data['url'] = OPENCART_INDEX_PATH . EXTENSION_PAYMENT_GATEWAY . "/confirmPayerAuth";
				} else {
					$data['error'] = false;
					$data['payer_auth'] = false;
					$data['url'] = OPENCART_INDEX_PATH . EXTENSION_PAYMENT_GATEWAY . "/confirmSavedCard";
				}
			} else {
				$data['error'] = true;
				$data['error_warning'] = $this->language->get('error_invalid_transient_token');
			}
		}
		$this->response->setOutput(json_encode($data));
	}

	public function confirm() {
		$this->load->model('extension/payment/cybersource_common');
		$response_data = array();
		if ((!$this->customer->isLogged()) && (!$this->config->get('config_checkout_guest'))) {
			$this->session->data['redirect'] = $this->url->link('checkout/checkout', '', true);
			$response_data['redirect'] = $this->url->link('account/login');
		} else {
			list($response_data['challenge_code'], $response_data['redirect']) = $this->getUnifiedCheckoutResponse('confirm');
		}
		if (!($response_data['challenge_code'])) {
			$this->model_extension_payment_cybersource_common->unsetSessionData();
		}
		$this->response->addHeader(CONTENT_TYPE);
		$this->response->setOutput(json_encode($response_data));
	}

	/**
	 * @param string $calling_function_name
	 *
	 * @return array
	 */
	private function getUnifiedCheckoutResponse(string $calling_function_name): array {
		$this->load->language('extension/payment/cybersource_loggers');
		$this->load->model('extension/payment/cybersource_common');
		$this->load->model('extension/payment/cybersource');
		$data = array();
		$data = $this->getInitialDetails();
		$is_save_card = $this->session->data['my_check'] ?? VAL_NULL;
		$transient_token = $this->session->data['transient_token'] ?? VAL_NULL;
		$enrollment_check = $this->session->data['enroll_check'] ?? VAL_NULL;
		$payer_auth = $this->session->data['payer_auth'] ?? VAL_NULL;
		$payment_method = $this->session->data['uc_payment_method'] ?? VAL_NULL;
		$challenge_code = false;
		$redirect = VAL_TRUE;
		$card_tokens = VAL_NULL;
		$card_data = VAL_NULL;
		if ($data['csrf_token']) {
			if (!(VAL_SIX_ZERO_ZERO < (time() - $data['csrf_token_time']))) {
				if (VAL_ZERO == strcmp($data['csrf_token'], $data['csrf'])) {
					if (!empty($transient_token)) {
						try {
							$signed_pareq = $this->session->data['signed_pareq'] ?? VAL_NULL;
							$auth_transaction_id = $this->session->data['auth_transaction_id'] ?? VAL_NULL;
							$api_response = $this->model_extension_payment_cybersource->getOrderInfo($data['order_id'], $transient_token, $is_save_card, $data['line_items'], $data['payload_data'], $data['customer_token_id'], $auth_transaction_id, $signed_pareq);
							if (VAL_NULL != $api_response) {
								$response_http_code = $api_response['http_code'];
								$response_http_body = $api_response['body'];
								$payment_response_array = $this->model_extension_payment_cybersource_common->getResponse($response_http_code, $response_http_body, SERVICE_AUTH);
								list($status, $custom_status) = $this->model_extension_payment_cybersource_common->getOrderStatus($payment_response_array);
								if ((CODE_TWO_ZERO_ONE == $response_http_code) && ((API_STATUS_AUTHORIZED == $payment_response_array['status']) || (API_STATUS_AUTHORIZED_RISK_DECLINED == $payment_response_array['status']) || (API_STATUS_AUTHORIZED_PENDING_REVIEW == $payment_response_array['status']))) {
									if (VAL_TRUE == $is_save_card && (API_STATUS_AUTHORIZED_RISK_DECLINED != $payment_response_array['status']) && (API_STATUS_AUTHORIZED_PENDING_REVIEW != $payment_response_array['status']) && PAYMENT_METHOD_NAME_CC == $payment_method) {
										$card_tokens = $this->model_extension_payment_cybersource->getCardTokens($response_http_body, $data['customer_id']);
										$card_details_response = $this->model_extension_payment_cybersource_common->getCardDetailsFromToken($card_tokens);
										if (!empty($card_details_response)) {
											if (CODE_TWO_ZERO_ZERO == $card_details_response['http_code']) {
												$card_data = $this->model_extension_payment_cybersource->getCardData($card_details_response['body']);
												$is_card_updation_success = $this->model_extension_payment_cybersource->updateTokenizationTable($payment_response_array['transaction_id'], $card_data, $card_tokens, $data['payload_data']['address_id'], $data['customer_id']);
												if ($is_card_updation_success[IS_FAILED]) {
													$this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][' . $calling_function_name . ']:' . $this->language->get('error_tokenization_table_insertion'), VAL_EMPTY);
												}
											} else {
												$this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][' . $calling_function_name . ']:' . $card_details_response['body'], VAL_EMPTY);
											}
										} else {
											$this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][' . $calling_function_name . ']:' . $this->language->get('error_response_info'), VAL_EMPTY);
										}
									}
									$order_result = $this->model_extension_payment_cybersource_common->addOrderHistory($data['order_id'], $status, $custom_status, VAL_NULL, true);
									if ($order_result) {
										$order_details = $this->model_extension_payment_cybersource->prepareOrderDetails($payment_response_array, $data['payload_data'], $data['total_quantity'], $status, $data['tax_id'], $payment_method);
										$is_insertion_success = $this->model_extension_payment_cybersource->insertOrderDetails($order_details, TABLE_PREFIX_UNIFIED_CHECKOUT);
										if ($is_insertion_success) {
											if (API_STATUS_AUTHORIZED_RISK_DECLINED == $payment_response_array['status']) {
												$redirect = $this->model_extension_payment_cybersource->getFraudulentAuthReversalData($data['order_id'], $payment_response_array['transaction_id'], TABLE_PREFIX_UNIFIED_CHECKOUT);
												if (VAL_NULL == $redirect) {
													$redirect = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][' . $calling_function_name . ']:' . $this->language->get('error_FM_reject'), STATUS_FAILURE);
												}
											} else {
												$redirect = $this->model_extension_payment_cybersource_common->getReturnUrl(VAL_EMPTY, STATUS_SUCCESS);
											}
										} else {
											$redirect = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][' . $calling_function_name . ']:' . $this->language->get('error_order_table_insertion'), STATUS_FAILURE);
										}
									} else {
										$redirect = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][' . $calling_function_name . ']:' . $this->language->get('error_history_table_update'), STATUS_FAILURE);
									}
								} elseif ((CODE_TWO_ZERO_ONE == $response_http_code) && (API_REASON_CUSTOMER_AUTHENTICATION_REQUIRED == $payment_response_array['reason']) && ($payer_auth) && VAL_ZERO == $enrollment_check && PAYMENT_METHOD_NAME_CC == $payment_method) {
									$redirect = OPENCART_INDEX_PATH . EXTENSION_PAYMENT_GATEWAY . '/confirmPayerAuth';
									$this->session->data['enroll_check'] = VAL_ZERO_ONE;
									$challenge_code = true;
								} else {
									$redirect = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][' . $calling_function_name . ']:' . $response_http_body, STATUS_FAILURE);
								}
							} else {
								$redirect = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][' . $calling_function_name . ']:' . $this->language->get('error_response_info'), STATUS_FAILURE);
							}
						} catch (Exception $e) {
							$redirect = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][' . $calling_function_name . ']:' . $this->language->get('error_exception'), STATUS_FAILURE);
						}
					} else {
						$redirect = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][' . $calling_function_name . ']:' . $this->language->get('error_transient_token_missing'), STATUS_CHECKOUT);
					}
				} else {
					$redirect = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][' . $calling_function_name . ']:' . $this->language->get('error_csrf_token_expired'), STATUS_CHECKOUT);
				}
			} else {
				$redirect = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][' . $calling_function_name . ']:' . $this->language->get('error_csrf_token_validation_failed'), STATUS_CHECKOUT);
			}
		} else {
			$redirect = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][' . $calling_function_name . ']:' . $this->language->get('error_csrf_token_missing'), STATUS_CHECKOUT);
		}
		return array($challenge_code, $redirect);
	}

	public function confirmSavedCard() {
		$this->load->model('extension/payment/cybersource_common');
		$response_data = array();
		if ((!$this->customer->isLogged()) && (!$this->config->get('config_checkout_guest'))) {
			$this->session->data['redirect'] = $this->url->link('checkout/checkout', '', true);
			$response_data['redirect'] = $this->url->link('account/login');
		} else {
			list($response_data['challenge_code'], $response_data['redirect']) = $this->getCreditCardSavedCardResponse('confirmSavedCard');
		}
		if (!($response_data['challenge_code'])) {
			$this->model_extension_payment_cybersource_common->unsetSessionData();
		}
		$this->response->addHeader(CONTENT_TYPE);
		$this->response->setOutput(json_encode($response_data));
	}

	/**
	 * @param string $calling_function_name
	 *
	 * @return array
	 */
	private function getCreditCardSavedCardResponse(string $calling_function_name): array {
		$this->load->model('extension/payment/cybersource_common');
		$this->load->language('extension/payment/cybersource_loggers');
		$this->load->model('extension/payment/cybersource_query');
		$this->load->model('extension/payment/cybersource');
		$challenge_code = false;
		$data = $this->getInitialDetails();
		$security_code = $this->session->data['security_code'] ?? VAL_NULL;
		$signed_pareq = $this->session->data['signed_pareq'] ?? VAL_NULL;
		$auth_transaction_id = $this->session->data['auth_transaction_id'] ?? VAL_NULL;
		$redirect = VAL_NULL;
		$saved_card_id = $this->session->data['card_id'] ?? VAL_NULL;
		$enrollment_check = $this->session->data['enroll_check'] ?? VAL_NULL;
		$payer_auth = $this->session->data['payer_auth'] ?? VAL_NULL;
		$saved_card_token = array();
		if ($data['csrf_token']) {
			if (!(VAL_SIX_ZERO_ZERO < (time() - $data['csrf_token_time']))) {
				if (VAL_ZERO == strcmp($data['csrf_token'], $data['csrf'])) {
					if ($saved_card_id && $data['customer_id']) {
						try {
							$query_saved_cards = $this->model_extension_payment_cybersource_query->querySavedCards($data['customer_id'], $saved_card_id);
							if (VAL_ZERO < $query_saved_cards->num_rows) {
								$saved_card_token = array(
									'payment_instrument_id'  => $query_saved_cards->row['payment_instrument_id'],
									'customer_token_id'      => $query_saved_cards->row['customer_token_id'],
									'address_id'			 => $query_saved_cards->row['address_id'],
									"instrument_identifier_id" => $query_saved_cards->row['instrument_identifier_id']
								);
							}
							if (!empty($saved_card_token) && !empty($security_code)) {
								$api_response = $this->model_extension_payment_cybersource->getOrderInfoSavedCard($data['order_id'], $saved_card_token, $security_code, $data['line_items'], $data['payload_data'], $auth_transaction_id, $signed_pareq);
								if (VAL_NULL != $api_response) {
									$response_http_code = $api_response['http_code'];
									$response_http_body = $api_response['body'];
									$payment_response_array = $this->model_extension_payment_cybersource_common->getResponse($response_http_code, $response_http_body, SERVICE_AUTH);
									list($status, $custom_status) = $this->model_extension_payment_cybersource_common->getOrderStatus($payment_response_array);
									if ((CODE_TWO_ZERO_ONE == $response_http_code) && ((API_STATUS_AUTHORIZED == $payment_response_array['status']) || (API_STATUS_AUTHORIZED_RISK_DECLINED == $payment_response_array['status']) || (API_STATUS_AUTHORIZED_PENDING_REVIEW == $payment_response_array['status']))) {
										if ($data['payload_data']['address_id'] != $saved_card_token['address_id']) {
											$is_update_address_success = $this->model_extension_payment_cybersource_query->updateAddress($data['payload_data']['address_id'], $saved_card_id);
											if (!$is_update_address_success) {
												$this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][' . $calling_function_name . ']:' . $this->language->get('error_tokenization_table_address_updation'), VAL_EMPTY);
											}
										}
										$order_result = $this->model_extension_payment_cybersource_common->addOrderHistory($data['order_id'], $status, $custom_status, VAL_NULL, true);
										if ($order_result) {
											$order_details = $this->model_extension_payment_cybersource->prepareOrderDetails($payment_response_array, $data['payload_data'], $data['total_quantity'], $status, $data['tax_id'], $this->session->data['uc_payment_method']);
											$is_insertion_success = $this->model_extension_payment_cybersource->insertOrderDetails($order_details, TABLE_PREFIX_UNIFIED_CHECKOUT);
											if ($is_insertion_success) {
												if (API_STATUS_AUTHORIZED_RISK_DECLINED == $payment_response_array['status']) {
													$redirect = $this->model_extension_payment_cybersource->getFraudulentAuthReversalData($data['order_id'], $payment_response_array['transaction_id'], TABLE_PREFIX_UNIFIED_CHECKOUT);
													if (VAL_NULL == $redirect) {
														$redirect = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][' . $calling_function_name . ']:' . $this->language->get('error_FM_reject'), STATUS_FAILURE);
													}
												} else {
													$redirect = $this->model_extension_payment_cybersource_common->getReturnUrl(VAL_EMPTY, STATUS_SUCCESS);
												}
											} else {
												$redirect = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][' . $calling_function_name . ']:' . $this->language->get('error_order_table_insertion'), STATUS_FAILURE);
											}
										} else {
											$redirect = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][' . $calling_function_name . ']:' . $this->language->get('error_history_table_update'), STATUS_FAILURE);
										}
									} elseif ((CODE_TWO_ZERO_ONE == $response_http_code) && (API_REASON_CUSTOMER_AUTHENTICATION_REQUIRED == $payment_response_array['reason']) && ($payer_auth) &&  VAL_ZERO == $enrollment_check) {
										$redirect = OPENCART_INDEX_PATH . EXTENSION_PAYMENT_GATEWAY . '/confirmPayerAuth';
										$this->session->data['enroll_check'] = VAL_ZERO_ONE;
										$challenge_code = true;
									} else {
										$redirect = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][' . $calling_function_name . ']:' . $response_http_body, STATUS_FAILURE);
									}
								} else {
									$redirect = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][' . $calling_function_name . ']:' . $this->language->get('error_response_info'), STATUS_FAILURE);
								}
							} else {
								$redirect = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][' . $calling_function_name . ']:' . $this->language->get('error_tokenization_table_fetch'), STATUS_FAILURE);
							}
						} catch (Exception $e) {
							$redirect = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][' . $calling_function_name . ']:' . $this->language->get('error_exception'), STATUS_FAILURE);
						}
					} else {
						$redirect = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][' . $calling_function_name . ']:' . $this->language->get('error_missing_card_id_or_customer_id'), STATUS_FAILURE);
					}
				} else {
					$redirect = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][' . $calling_function_name . ']:' . $this->language->get('error_csrf_token_invalid'), STATUS_CHECKOUT);
				}
			} else {
				$redirect = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][' . $calling_function_name . ']:' . $this->language->get('error_csrf_token_expired'), STATUS_CHECKOUT);
			}
		} else {
			$redirect = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][' . $calling_function_name . ']:' . $this->language->get('error_csrf_token_validation_failed'), STATUS_CHECKOUT);
		}
		return array($challenge_code, $redirect);
	}

	public function confirmPayerAuth() {
		if ((!$this->customer->isLogged()) && (!$this->config->get('config_checkout_guest'))) {
			$this->session->data['redirect'] = $this->url->link('checkout/checkout', '', true);
			$json['redirect'] = $this->url->link('account/login');
		} else {
			$jti = VAL_NULL;
			$is_save_card = VAL_NULL;
			$saved_card = VAL_FLAG_NO;
			$data = array();
			$this->load->model('extension/payment/cybersource_common');
			$this->load->model('extension/payment/cybersource');
			$order_id = $this->session->data['order_id'] ?? VAL_ZERO;
			$card_id = $this->session->data['card_id'] ?? VAL_ZERO;
			$customer_id = $this->customer->getId();
			$customer_token_id = $this->model_extension_payment_cybersource_common->getCustomerTokenId($customer_id);
			if (!empty($card_id)) {
				$saved_card = VAL_FLAG_YES;
				$data = $this->model_extension_payment_cybersource->payerAuthCommon($order_id, $jti, $is_save_card, $customer_token_id, $saved_card);
			}
			$transient_token = $this->session->data['transient_token'] ?? VAL_NULL;
			if (!empty($transient_token)) {
				$is_save_card = $this->session->data['my_check'] ?? VAL_ZERO;
				$tt_body = explode(".", $transient_token)[VAL_ONE];
				$json = base64_decode($tt_body);
				$json = json_decode($json);
				$jti = $json->jti;
				$data = $this->model_extension_payment_cybersource->payerAuthCommon($order_id, $jti, $is_save_card, $customer_token_id, $saved_card);
			}
		}
		$this->response->setOutput(json_encode($data));
	}

	public function confirmPayerAuthEnrollment() {
		$is_save_card = VAL_NULL;
		$data = array();
		$this->load->language('extension/payment/cybersource_loggers');
		$this->load->model('extension/payment/cybersource_common');
		$this->load->model('checkout/order');
		$this->load->model('extension/payment/cybersource');
		$this->load->model('extension/payment/cybersource_query');
		$url_data = ($this->request->server['HTTPS']) ? HTTPS_SERVER : HTTP_SERVER;
		$return_url = $url_data . OPENCART_INDEX_PATH . EXTENSION_PAYMENT_GATEWAY . "/confirmPayerAuthHelper";
		$data = $this->payerAuthMandateInitialDetails();
		$transient_token = $this->session->data['transient_token'] ?? VAL_NULL;
		if ($data['csrf_token']) {
			if (!(VAL_SIX_ZERO_ZERO < (time() - $data['csrf_token_time']))) {
				if (VAL_ZERO == strcmp($data['csrf_token'], $data['csrf'])) {
					try {
						if (!empty($transient_token)) {
							$is_save_card = $this->session->data['my_check'] ?? VAL_NULL;
							$tt_body = explode(".", $transient_token)[VAL_ONE];
							$json = base64_decode($tt_body);
							$json = json_decode($json);
							$jti = $json->jti;
							$rid = $this->session->data['reference_id'] ?? VAL_NULL;
							$enrollment_response = $this->model_extension_payment_cybersource->getCheckPayerAuthResponse($data['payload_data'], $data['line_items'], $data['order_id'], $jti, $rid, $is_save_card, $data['customer_token_id'], $return_url);
							$enrollment_check = $this->session->data['enroll_check'] ?? VAL_NULL;
							if (VAL_NULL != $enrollment_response) {
								$json = json_decode($enrollment_response['body']);
								$http_code = $enrollment_response['http_code'];
								$enrollment_response_array = $this->model_extension_payment_cybersource_common->getResponse($http_code, $enrollment_response['body'], SERVICE_AUTH);
								$status = $enrollment_response_array['status'];
								if (CODE_TWO_ZERO_ONE == $http_code && API_STATUS_PENDING_AUTHENTICATION == $status) {
									$signed_pareq = $json->consumerAuthenticationInformation->pareq;
									$access_token = $json->consumerAuthenticationInformation->accessToken;
									$this->session->data['signed_pareq'] = $signed_pareq;
									$auth_transaction_id = $json->consumerAuthenticationInformation->authenticationTransactionId;
									$this->session->data['auth_transaction_id'] = $auth_transaction_id;
									$merchant_data = $this->session->data['card_id'] ?? VAL_EMPTY;
									$data['access_token'] = $access_token;
									$data['merchant_data'] = $merchant_data;
								} elseif ((CODE_TWO_ZERO_ONE == $http_code) && ((API_STATUS_AUTHORIZED == $status) || (API_STATUS_AUTHORIZED_RISK_DECLINED == $status) || (API_STATUS_AUTHORIZED_PENDING_REVIEW == $status))) {
									if (VAL_TRUE == $is_save_card && (API_STATUS_AUTHORIZED_RISK_DECLINED != $status) && (API_STATUS_AUTHORIZED_PENDING_REVIEW != $status)) {
										$card_tokens = null;
										$card_tokens = $this->model_extension_payment_cybersource->getCardTokens($enrollment_response['body'], $data['customer_id']);
										$card_details_response = $this->model_extension_payment_cybersource_common->getCardDetailsFromToken($card_tokens);
										if (VAL_NULL != $card_details_response) {
											if (CODE_TWO_ZERO_ZERO == $card_details_response['http_code']) {
												$card_data = $this->model_extension_payment_cybersource->getCardData($card_details_response['body']);
												$is_card_updation_success = $this->model_extension_payment_cybersource->updateTokenizationTable(
													$enrollment_response_array['transaction_id'],
													$card_data,
													$card_tokens,
													$data['payload_data']['address_id'],
													$data['customer_id'],
												);
												if ($is_card_updation_success[IS_FAILED]) {
													$this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][confirmPayerAuthEnrollment]:' . $this->language->get('error_tokenization_table_insertion'), VAL_EMPTY);
												}
											} else {
												$this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][confirmPayerAuthEnrollment]:' . $card_details_response['body'], VAL_EMPTY);
											}
										} else {
											$this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][confirmPayerAuthEnrollment]:' . $this->language->get('error_response_info'), VAL_EMPTY);
										}
									}
									$enrollment_response_array['amount'] = $data['payload_data']['total'];
									list($status, $custom_status) = $this->model_extension_payment_cybersource_common->getOrderStatus($enrollment_response_array);
									$order_result = $this->model_extension_payment_cybersource_common->addOrderHistory($data['order_id'], $status, $custom_status, VAL_NULL, true);
									if ($order_result) {
										$order_details = $this->model_extension_payment_cybersource->prepareOrderDetails($enrollment_response_array, $data['payload_data'], $data['total_quantity'], $status, $data['tax_id'], $this->session->data['uc_payment_method']);
										$is_insertion_success = $this->model_extension_payment_cybersource->insertOrderDetails($order_details, TABLE_PREFIX_UNIFIED_CHECKOUT);
										if ($is_insertion_success) {
											if (API_STATUS_AUTHORIZED_RISK_DECLINED == $enrollment_response_array['status']) {
												$data['url'] = $this->model_extension_payment_cybersource->getFraudulentAuthReversalData($data['order_id'], $enrollment_response_array['transaction_id'], TABLE_PREFIX_UNIFIED_CHECKOUT);
												if (VAL_NULL == $data['url']) {
													$data['url'] = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][confirmPayerAuthEnrollment]:' . $this->language->get('error_FM_reject'), STATUS_FAILURE);
												}
											} else {
												$data['url'] = $this->model_extension_payment_cybersource_common->getReturnUrl(VAL_EMPTY, STATUS_SUCCESS);
											}
										} else {
											$data['url'] = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][confirmPayerAuthEnrollment]:' . $this->language->get('error_order_table_insertion'), STATUS_FAILURE);
										}
									} else {
										$data['url'] = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][confirmPayerAuthEnrollment]:' . $this->language->get('error_history_table_update'), STATUS_FAILURE);
									}
								} elseif ((CODE_TWO_ZERO_ONE == $http_code) && (API_REASON_CUSTOMER_AUTHENTICATION_REQUIRED == $enrollment_response_array['reason']) && VAL_ZERO == $enrollment_check) {
									$data['repeat'] = OPENCART_INDEX_PATH . EXTENSION_PAYMENT_GATEWAY . '/confirmPayerAuth';
									$this->session->data['enroll_check'] = VAL_ZERO_ONE;
								} else {
									$data['url'] = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][confirmPayerAuthEnrollment]:' . $enrollment_response['body'], STATUS_FAILURE);
								}
							} else {
								$data['url'] = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][confirmPayerAuthEnrollment]:' . $this->language->get('error_response_info'), STATUS_FAILURE);
							}
						} else {
							$data['url'] = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][confirmPayerAuthEnrollment]:' . $this->language->get('error_transient_token_missing'), STATUS_CHECKOUT);
						}
					} catch (Exception $e) {
						$data['url'] = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][confirmPayerAuthEnrollment]:' . $this->language->get('error_exception'), STATUS_FAILURE);
					}
				} else {
					$data['url'] = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][confirmPayerAuthEnrollment]:' . $this->language->get('error_csrf_token_invalid'), STATUS_CHECKOUT);
				}
			} else {
				$data['url'] = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][confirmPayerAuthEnrollment]:' . $this->language->get('error_csrf_token_expired'), STATUS_CHECKOUT);
			}
		} else {
			$data['url'] = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][confirmPayerAuthEnrollment]:' . $this->language->get('error_csrf_token_validation_failed'), STATUS_CHECKOUT);
		}
		if (isset($data['url'])) {
			$this->model_extension_payment_cybersource_common->unsetSessionData();
		}

		$this->response->addHeader(CONTENT_TYPE);
		$this->response->setOutput(json_encode($data));
	}

	public function confirmPayerAuthSavedCardEnroll() {
		$enroll_array = VAL_NULL;
		$data = array();
		$this->load->language('extension/payment/cybersource_loggers');
		$this->load->model('extension/payment/cybersource_common');
		$this->load->model('checkout/order');
		$this->load->model('extension/payment/cybersource');
		$this->load->model('extension/payment/cybersource_query');
		$url_data = $this->request->server['HTTPS'] ? HTTPS_SERVER : HTTP_SERVER;
		$return_url = $url_data . OPENCART_INDEX_PATH . EXTENSION_PAYMENT_GATEWAY . "/confirmPayerAuthHelper";
		$data = $this->payerAuthMandateInitialDetails();
		$saved_card_id = $this->session->data['card_id'] ?? VAL_NULL;
		$enrollment_check = $this->session->data['enroll_check'] ?? VAL_NULL;
		if ($data['csrf_token']) {
			if (!(VAL_SIX_ZERO_ZERO < (time() - $data['csrf_token_time']))) {
				if (VAL_ZERO == strcmp($data['csrf_token'], $data['csrf'])) {
					if ($saved_card_id && $data['customer_id']) {
						$security_code = $this->session->data['security_code'] ?? VAL_NULL;
						$query_saved_cards = $this->model_extension_payment_cybersource_query->querySavedCards($data['customer_id'], $saved_card_id);
						if (VAL_ZERO < $query_saved_cards->num_rows) {
							$saved_card_token = array(
								'payment_instrument_id'  => $query_saved_cards->row['payment_instrument_id'],
								'customer_token_id'      => $query_saved_cards->row['customer_token_id'],
								'address_id'			 => $query_saved_cards->row['address_id'],
								"instrument_identifier_id" => $query_saved_cards->row['instrument_identifier_id']
							);
						}
						try {
							$rid = $this->session->data['reference_id'] ?? VAL_NULL;
							if (!empty($saved_card_token) && !empty($security_code)) {
								$payment_response = $this->model_extension_payment_cybersource->getSavedCardEnrollResponse($data['payload_data'], $data['line_items'], $saved_card_token, $security_code, $data['order_id'], $rid, $return_url);
								if (VAL_NULL != $payment_response) {
									$http_code = $payment_response['http_code'];
									$payment_response_array = $this->model_extension_payment_cybersource_common->getResponse($http_code, $payment_response['body'], SERVICE_AUTH);
									$status = $payment_response_array['status'];
									if (CODE_TWO_ZERO_ONE == $http_code && API_STATUS_PENDING_AUTHENTICATION == $status) {
										$json = json_decode($payment_response['body']);
										$enroll_array['signedPareq'] = $json->consumerAuthenticationInformation->pareq;
										$enroll_array['acsUrl'] = $json->consumerAuthenticationInformation->acsUrl;
										$enroll_array['accessToken'] = $json->consumerAuthenticationInformation->accessToken;
										$this->session->data['signed_pareq'] = $enroll_array['signedPareq'];
										$enroll_array['authTransactionId'] = $json->consumerAuthenticationInformation->authenticationTransactionId;
										$this->session->data['auth_transaction_id'] = $enroll_array['authTransactionId'];
										$merchant_data = $this->session->data['card_id'] ?? VAL_EMPTY;
										$data['access_token'] = $enroll_array['accessToken'];
										$data['merchant_data'] = $merchant_data;
									} elseif ((CODE_TWO_ZERO_ONE == $http_code) && ((API_STATUS_AUTHORIZED == $status) || (API_STATUS_AUTHORIZED_RISK_DECLINED == $status) || (API_STATUS_AUTHORIZED_PENDING_REVIEW == $status))) {
										if ($data['payload_data']['address_id'] != $saved_card_token['address_id']) {
											$is_update_success = $this->model_extension_payment_cybersource_query->updateAddress($data['payload_data']['address_id'], $saved_card_id);
											if (!$is_update_success) {
												$this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][confirmPayerAuthSavedCardEnroll]:' . $this->language->get('error_tokenization_table_address_updation'), VAL_EMPTY);
											}
										}
										$payment_response_array['amount'] = $data['payload_data']['total'];
										list($status, $custom_status) = $this->model_extension_payment_cybersource_common->getOrderStatus($payment_response_array);
										$order_result = $this->model_extension_payment_cybersource_common->addOrderHistory($data['order_id'], $status, $custom_status, VAL_NULL, true);
										if ($order_result) {
											$order_details = $this->model_extension_payment_cybersource->prepareOrderDetails($payment_response_array, $data['payload_data'], $data['total_quantity'], $status, $data['tax_id'], $this->session->data['uc_payment_method']);
											$is_insertion_success = $this->model_extension_payment_cybersource->insertOrderDetails($order_details, TABLE_PREFIX_UNIFIED_CHECKOUT);
											if (!$is_insertion_success) {
												$data['url'] = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][confirmPayerAuthSavedCardEnroll]:' . $this->language->get('error_order_table_insertion'), STATUS_FAILURE);
											} else {
												if (API_STATUS_AUTHORIZED_RISK_DECLINED == $payment_response_array['status']) {
													$data['url'] = $this->model_extension_payment_cybersource->getFraudulentAuthReversalData($data['order_id'], $payment_response_array['transaction_id'], TABLE_PREFIX_UNIFIED_CHECKOUT);
													if (VAL_NULL == $data['url']) {
														$data['url'] = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][confirmPayerAuthSavedCardEnroll]:' . $this->language->get('error_FM_reject'), STATUS_FAILURE);
													}
												} else {
													$data['url'] = $this->model_extension_payment_cybersource_common->getReturnUrl(VAL_EMPTY, STATUS_SUCCESS);
												}
											}
										} else {
											$data['url'] = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][confirmPayerAuthSavedCardEnroll]:' . $this->language->get('error_history_table_update'), STATUS_FAILURE);
										}
									} elseif ((CODE_TWO_ZERO_ONE == $http_code) && (API_REASON_CUSTOMER_AUTHENTICATION_REQUIRED == $payment_response_array['reason']) && VAL_ZERO == $enrollment_check) {
										$data['repeat'] = OPENCART_INDEX_PATH . EXTENSION_PAYMENT_GATEWAY . '/confirmPayerAuth';
										$this->session->data['enroll_check'] = VAL_ZERO_ONE;
									} else {
										$data['url'] = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][confirmPayerAuthSavedCardEnroll]:' . $payment_response['body'], STATUS_FAILURE);
									}
								} else {
									$data['url'] = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][confirmPayerAuthSavedCardEnroll]:' . $this->language->get('error_response_info'), STATUS_FAILURE);
								}
							} else {
								$data['url'] = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][confirmPayerAuthSavedCardEnroll]:' . $this->language->get('error_tokenization_table_fetch'), STATUS_CHECKOUT);
							}
						} catch (Exception $e) {
							$data['url'] = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][confirmPayerAuthSavedCardEnroll]:' . $this->language->get('error_exception'), STATUS_FAILURE);
						}
					} else {
						$data['url'] = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][confirmPayerAuthSavedCardEnroll]:' . $this->language->get('error_missing_card_id_or_customer_id'), STATUS_FAILURE);
					}
				} else {
					$data['url'] = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][confirmPayerAuthSavedCardEnroll]:' . $this->language->get('error_csrf_token_invalid'), STATUS_CHECKOUT);
				}
			} else {
				$data['url'] = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][confirmPayerAuthSavedCardEnroll]:' . $this->language->get('error_csrf_token_validation_failed'), STATUS_CHECKOUT);
			}
		} else {
			$data['url'] = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][confirmPayerAuthSavedCardEnroll]:' . $this->language->get('error_csrf_token_validation_failed'), STATUS_CHECKOUT);
		}
		if (isset($data['url'])) {
			$this->model_extension_payment_cybersource_common->unsetSessionData();
		}
		$this->response->addHeader(CONTENT_TYPE);
		$this->response->setOutput(json_encode($data));
	}

	public function confirmPayerAuthHelper() {
		$data['return_url'] = VAL_NULL;
		$this->load->model('checkout/order');
		$this->load->model('extension/payment/cybersource_common');
		$this->load->language('extension/payment/cybersource');
		$url_data = ($this->request->server['HTTPS']) ? HTTPS_SERVER : HTTP_SERVER;
		$data['url'] = $url_data . OPENCART_INDEX_PATH . EXTENSION_PAYMENT_GATEWAY . "/confirm";
		$card = $this->request->post['MD'] ?? VAL_NULL;
		$auth_transaction_id = $this->request->post['TransactionId'] ?? VAL_EMPTY;
		if (null == $card) {
			$data['return_url'] = $url_data . OPENCART_INDEX_PATH . EXTENSION_PAYMENT_GATEWAY . "/confirm";
		} else {
			$data['return_url'] = $url_data . OPENCART_INDEX_PATH . EXTENSION_PAYMENT_GATEWAY . "/confirmSavedCard";
		}
		if (empty($auth_transaction_id)) {
			$data['return_url'] = $this->model_extension_payment_cybersource_common->getReturnUrl('[ControllerExtensionPaymentCybersource][confirmPayerAuthHelper]:' . VAL_EMPTY, STATUS_FAILURE);
			$this->model_extension_payment_cybersource_common->unsetSessionData();
		}
		$this->response->setOutput($this->load->view('extension/payment/payer_auth_helper', $data));
		header_remove('Set-Cookie');
		$this->response->output();
		exit;
	}

	/**
	 * Gives all the intiail details.
	 *
	 * @return array
	 */
	private function getInitialDetails(): array {
		$this->load->model('extension/payment/cybersource');
		$this->load->model('checkout/order');
		$data = array();
		$data['total_quantity'] = VAL_ZERO;
		$data['order_id'] = $this->session->data['order_id'] ?? VAL_ZERO;
		$data['customer_id'] = $this->customer->getId();
		$data['customer_token_id'] = $this->model_extension_payment_cybersource_common->getCustomerTokenId($data['customer_id']);
		$data['payload_data'] = $this->model_checkout_order->getOrder($data['order_id']);
		$data['payload_data']['currency_code'] = $this->config->get('config_currency');
		$data['payload_data']['address_id']  = $this->session->data['payment_address']['address_id'] ?? VAL_NULL;
		$product_details = $this->model_checkout_order->getOrderProducts($data['order_id']);
		$data['line_items'] = $this->model_extension_payment_cybersource->getLineItemDetails($data['order_id']);
		foreach ($product_details as $product) {
			$data['total_quantity'] += $product['quantity'];
		}
		$data['csrf_token'] = $this->session->data['csrf_token'] ?? VAL_NULL;
		$data['tax_id'] = $this->session->data['tax_id'] ?? VAL_NULL;
		$data['csrf'] = $this->session->data['csrf'] ?? VAL_NULL;
		$data['csrf_token_time'] = $this->session->data['csrf_token_time'] ?? VAL_NULL;
		return $data;
	}

	/**
	 * Fetches payer auth mandate fields from js.
	 *
	 * @return array
	 */
	private function payerAuthMandateInitialDetails(): array {
		$data = $this->getInitialDetails();
		$data['payload_data']['HTTP_ACCEPT'] = $this->request->server['HTTP_ACCEPT'];
		$data['payload_data']['browser_java_enabled'] = $this->request->post['browser_java_enabled'] ?? VAL_NULL;
		$data['payload_data']['browser_language'] = $this->request->post['browser_language'] ?? VAL_NULL;
		$data['payload_data']['browser_color_depth'] = $this->request->post['browser_color_depth'] ?? VAL_NULL;
		$data['payload_data']['browser_screen_height'] = $this->request->post['browser_screen_height'] ?? VAL_NULL;
		$data['payload_data']['browser_screen_width'] = $this->request->post['browser_screen_width'] ?? VAL_NULL;
		$data['payload_data']['browser_time_difference'] = $this->request->post['browser_time_difference'] ?? VAL_NULL;

		return $data;
	}

	public function confirmCancel() {
		if ($this->customer->isLogged()) {
			$this->load->model('extension/payment/cybersource_common');
			$this->load->model('extension/payment/cybersource');
			$this->load->model('extension/payment/cybersource_query');
			$this->load->language('extension/payment/cybersource');
			$this->load->language('extension/credit_card/cybersource');
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
							$query_auth_reversal = $this->model_extension_payment_cybersource_query->queryAuthReversalId($order_id, TABLE_PREFIX_UNIFIED_CHECKOUT);
							$auth_reversal_data = ($query_auth_reversal->num_rows > VAL_ZERO) ? $query_auth_reversal->row['transaction_id'] : VAL_ZERO;
							if (empty($auth_reversal_data)) {
								$order_details = $this->model_extension_payment_cybersource->getOrderDetails($order_id, TABLE_PREFIX_UNIFIED_CHECKOUT);
								if (!empty($order_details)) {
									$api_response = $this->model_extension_payment_cybersource->getCancelResponse($order_id, $order_details, PAYMENT_GATEWAY);
									if (VAL_NULL != $api_response) {
										$http_code = $api_response['http_code'];
										$cancel_response_array = $this->model_extension_payment_cybersource_common->getResponse($http_code, $api_response['body'], SERVICE_AUTH_REVERSAL);
										if ((CODE_TWO_ZERO_ONE == $http_code) && (API_STATUS_REVERSED == $cancel_response_array['status'])) {
											$order_result = $this->model_extension_payment_cybersource->addOrderHistoryForCancelOrder($order_id, $this->config->get('module_' . PAYMENT_GATEWAY . '_auth_reversal_status_id'), CANCELLED, VAL_NULL, true);
											if ($order_result) {
												$item_restock = $this->model_extension_payment_cybersource->restock($order_id);
												if ($item_restock) {
													$auth_reversal_details = $this->model_extension_payment_cybersource->prepareAuthReversalDetails($cancel_response_array, $order_id);
													$is_insertion_success = $this->model_extension_payment_cybersource->insertAuthReversalDetails($auth_reversal_details, TABLE_PREFIX_UNIFIED_CHECKOUT);
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
						$this->session->data['error'] = $this->language->get('error_session_expire');
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
	 * This function will be called when customer clicks confirm order button from Unified Checkout payment ui section.
	 */
	public function confirmPaymentRecaptcha() {
		if ((!$this->customer->isLogged()) && (!$this->config->get('config_checkout_guest'))) {
			$this->session->data['redirect'] = $this->url->link('checkout/checkout', '', true);
			$response_data['redirect'] = $this->url->link('account/login');
		} else {
			$this->load->model('extension/payment/cybersource_common');
			$this->load->language('extension/payment/cybersource_common');
			$request_data['recaptcha_token'] = $this->request->post['token'] ?? VAL_NULL;
			$request_data['csrf'] = $this->request->post['csrf'] ?? VAL_NULL;
			$request_data['time'] = $this->request->post['time'] ?? VAL_NULL;
			$request_data['saved_card'] = $this->request->post['saved_card'] ?? VAL_NULL;
			$request_data['save_card_check'] = $this->request->post['my_check'] ?? VAL_NULL;
			$request_data['card_id'] = $this->request->post['card_id'] ?? VAL_NULL;
			$request_data['sec_code'] = $this->request->post['security_code'] ?? VAL_NULL;
			$transient_token = $this->request->post['transient_token'] ?? VAL_NULL;
			$this->session->data['uc_payment_method'] = PAYMENT_METHOD_NAME_CC;
			if ($transient_token) {
				$decoded_token = $this->model_extension_payment_cybersource_common->decodeToken($transient_token);
				if (null === $decoded_token) {
					$response_data['error'] = true;
					$response_data['error_warning'] = $this->language->get('error_failure');
					unset($this->session->data['uc_payment_method']);
				} else {
					$payment_solution_value = $decoded_token->content->processingInformation->paymentSolution->value ?? false;
					if ($payment_solution_value) {
						if (PAYMENT_SOLUTION_GPAY == $payment_solution_value) {
							$this->session->data['uc_payment_method'] = PAYMENT_METHOD_NAME_GPAY;
						} elseif (PAYMENT_SOLUTION_VSRC == $payment_solution_value) {
							$this->session->data['uc_payment_method'] = PAYMENT_METHOD_NAME_VSRC;
						}
					}
				}
			}
			if (isset($this->session->data['uc_payment_method'])) {
				$response_data = $this->model_extension_payment_cybersource_common->recaptchaCommon(PAYMENT_GATEWAY, $request_data);
			}
		}
		$this->response->addHeader(CONTENT_TYPE);
		$this->response->setOutput(json_encode($response_data));
	}

	/**
	 * Receives webhook notifications of network token updates.
	 */
	public function networkTokensWebhook() {
		try {
			$this->load->model('extension/payment/cybersource_common');
			$this->load->language('extension/payment/cybersource_loggers');
			$this->load->model('extension/payment/cybersource_query');
			$response_code = CODE_FOUR_ZERO_FOUR;
			$network_token_updates_status = $this->config->get('payment_' . PAYMENT_GATEWAY . '_status') && $this->config->get('payment_' . PAYMENT_GATEWAY . '_card') && $this->config->get('payment_' . PAYMENT_GATEWAY . '_network_token_updates_status');
			$token_verification = isset($this->request->get['token']) && $network_token_updates_status ? hash_equals($this->config->get('payment_' . PAYMENT_GATEWAY . '_webhook_security_token'), $this->request->get['token']) : false;
			if ($token_verification) {
				if (HTTP_METHOD_POST === $this->request->server['REQUEST_METHOD']) {
					$headers = $this->request->server;
					$notification_data = json_decode(file_get_contents("php://input"));
					$merchant_id = ENVIRONMENT_TEST === $this->config->get('module_' . PAYMENT_GATEWAY . '_sandbox') ? $this->config->get('module_' . PAYMENT_GATEWAY . '_merchant_id_test') : $this->config->get('module_' . PAYMENT_GATEWAY . '_merchant_id_live');
					$organization_id_verification = isset($notification_data->payload[VAL_ZERO]->organizationId) ? ($notification_data->payload[VAL_ZERO]->organizationId === $merchant_id) : false;
					if (!empty($notification_data) && $organization_id_verification) {
						$webhook_details = $this->model_extension_payment_cybersource_query->queryWebhookDetails($merchant_id, $notification_data->webhookId ?? VAL_NULL);
						if (isset($webhook_details)) {
							$notification_validation = $this->notificationValidation($headers['HTTP_V_C_SIGNATURE'], $notification_data->payload, $webhook_details);
							if ($notification_validation) {
								$resource_instrument_identifier_id = $notification_data->payload[VAL_ZERO]->data->_links->instrumentIdentifiers[VAL_ZERO]->href ?? VAL_NULL;
								if (!empty($resource_instrument_identifier_id)) {
									$instrument_identifier_id = explode(FORWARD_SLASH, $resource_instrument_identifier_id);
									$instrument_identifier_id = end($instrument_identifier_id);
									$card_id = $this->model_extension_payment_cybersource_query->queryCardId($instrument_identifier_id);
									if (isset($card_id)) {
										$api_response = $this->model_extension_payment_cybersource_common->serviceProcessor(VAL_EMPTY, $resource_instrument_identifier_id, true, HTTP_METHOD_GET);
										if (CODE_TWO_ZERO_ZERO === $api_response['http_code']) {
											$response_body = json_decode($api_response['body']);
											$tokenized_card = $response_body->tokenizedCard;
											if ("ACTIVE" === $tokenized_card->state) {
												$card_details = array();
												$card_details['instrument_identifier_id'] = $response_body->id ?? $instrument_identifier_id;
												$card_details['card_number'] = substr_replace($response_body->card->number, $tokenized_card->card->suffix, -(strlen($tokenized_card->card->suffix)));
												$card_details['expiration_month'] = $tokenized_card->card->expirationMonth;
												$card_details['expiration_year'] = $tokenized_card->card->expirationYear;
												$is_card_updation_success = $this->model_extension_payment_cybersource_query->queryUpdateCard($card_details);
												if (!$is_card_updation_success) {
													$this->model_extension_payment_cybersource_common->logger("[ControllerExtensionPaymentCybersource][webhook]: " . $this->language->get('error_tokenization_table_updation'));
												} else {
													$response_code = CODE_TWO_ZERO_ZERO;
												}
											} else {
												$this->model_extension_payment_cybersource_common->logger("[ControllerExtensionPaymentCybersource][webhook]: " . $this->language->get('error_tokenized_card_not_active'));
											}
										} else {
											$this->model_extension_payment_cybersource_common->logger("[ControllerExtensionPaymentCybersource][webhook]: " . $this->language->get('error_fetching_updated_token'));
										}
									} else {
										$this->model_extension_payment_cybersource_common->logger("[ControllerExtensionPaymentCybersource][webhook]: " . $this->language->get('error_notification_instrument_id_not_exist'));
									}
								} else {
									$this->model_extension_payment_cybersource_common->logger("[ControllerExtensionPaymentCybersource][webhook]: " . $this->language->get('error_notification_instrument_id_not_found'));
								}
							} else {
								$this->model_extension_payment_cybersource_common->logger("[ControllerExtensionPaymentCybersource][webhook]: " . $this->language->get('error_notification_validation'));
							}
						} else {
							$this->model_extension_payment_cybersource_common->logger("[ControllerExtensionPaymentCybersource][webhook]: " . $this->language->get('error_webhook_subcription_details_not_exist'));
						}
					} else {
						$this->model_extension_payment_cybersource_common->logger("[ControllerExtensionPaymentCybersource][webhook]: " . $this->language->get('error_notification_verification'));
					}
				} elseif (HTTP_METHOD_GET === strtolower($this->request->server['REQUEST_METHOD'])) {
					$response_code = CODE_TWO_ZERO_ZERO;
				} else {
					$this->model_extension_payment_cybersource_common->logger("[ControllerExtensionPaymentCybersource][webhook]: " . $this->language->get('error_notification_invalid_http_method'));
				}
			} else {
				$this->model_extension_payment_cybersource_common->logger("[ControllerExtensionPaymentCybersource][webhook]: " . $this->language->get('error_notification_token_mismatch'));
			}
		} catch (Throwable $e) {
			$this->model_extension_payment_cybersource_common->logger("[ControllerExtensionPaymentCybersource][webhook]: " . $this->language->get('error_exception') . $e->getMessage());
		}
		$this->response->addHeader(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . VAL_EMPTY_SPACE . $response_code);
		$this->response->setOutput(VAL_EMPTY_SPACE);
	}
}
