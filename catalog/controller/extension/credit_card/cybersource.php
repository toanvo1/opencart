<?php

require_once DIR_SYSTEM . 'library/isv/cybersource_constants.php';

/**
 * The functions related to Unified Checkout adding in my account section are in this file.
 *
 * @author Cybersource
 * @package Front Office
 * @subpackage Controller
 */
class ControllerExtensionCreditCardCybersource extends Controller {
	/**
	 * This function will be called when customer clicks cybersource my cards from my account section.
	 *
	 * All the things that are loaded in my card section are returned from this function.
	 */
	public function index() {
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('extension/credit_card/cybersource', VAL_EMPTY, true);
			$this->response->redirect($this->url->link('account/login', VAL_EMPTY, true));
		}
		$this->load->language('extension/credit_card/cybersource');
		$this->load->language('extension/payment/cybersource');
		$this->load->language('extension/payment/cybersource_common');
		$this->load->model('extension/payment/cybersource');
		$this->load->model('extension/payment/cybersource_common');
		$this->model_extension_payment_cybersource->loadScript();
		$this->document->setTitle($this->language->get('heading_title'));
		$data = $this->model_extension_payment_cybersource->getHeaderFooter('account/account');
		$data['breadcrumbs'] = $this->model_extension_payment_cybersource->getBreadcrumbs();
		try {
			if ($this->config->get('payment_' . PAYMENT_GATEWAY . '_status') && $this->config->get('payment_' . PAYMENT_GATEWAY . '_card')) {
				$customer_id = $this->customer->getId();
				if (VAL_NULL != $customer_id) {
					$general_configuration = $this->model_extension_payment_cybersource_common->getGeneralConfiguration();
					$data['text_checkout_payment_address'] = $this->language->get('text_checkout_payment_address');
					if (isset($this->session->data['success_my_card'])) {
						$data['success_my_card'] = $this->session->data['success_my_card'];
						unset($this->session->data['success_my_card']);
					} else {
						$data['success_my_card'] = VAL_EMPTY;
					}
					if (isset($this->session->data['error_my_card'])) {
						$data['error_my_card'] = $this->session->data['error_my_card'];
						unset($this->session->data['error_my_card']);
					} else {
						$data['error_my_card'] = VAL_EMPTY;
					}
					$data['update_card'] = $this->url->link('extension/credit_card/cybersource/confirmUpdateCardDetails', VAL_EMPTY, true);
					$data['cards'] = $this->model_extension_payment_cybersource->getCards($customer_id);
					$data['cards_not_present'] = true;
					$capture_context_response = $this->model_extension_payment_cybersource_common->getCaptureContextResponse(true);
					$http_code = $capture_context_response['http_code'];
					if (CODE_TWO_ZERO_ONE == $http_code) {
						$decoded_token = $this->model_extension_payment_cybersource_common->decodeToken($capture_context_response['body']);
						if (!isset($decoded_token->ctx[VAL_ZERO]->data->clientLibrary)) {
							$data['error_capture_context'] = $this->language->get('error_form_load');
						} else {
							$data['capture_context'] = $capture_context_response['body'];
							$data['unified_checkout_client_library'] = $decoded_token->ctx[VAL_ZERO]->data->clientLibrary;
						}
					} else {
						$data['error_capture_context'] = $this->language->get('error_form_load');
					}
					$csrf = $this->session->data['csrf'] ?? VAL_EMPTY;
					$this->model_extension_payment_cybersource_common->generateToken($csrf);
					$data['recaptcha_enabled'] = $general_configuration['recaptcha_enabled'];
					$data['recaptcha_site_key'] = $general_configuration['recaptcha_site_key'];
					$data['csrf_token_data'] = $this->session->data['csrf'] ?? VAL_NULL;
					$data['time_data'] = $this->session->data['csrf_time'] ?? VAL_NULL;
				} else {
					$data['error_my_card'] = $this->language->get('error_customer_info');
					$data['cards_not_present'] = false;
					$data['error_capture_context'] = $this->language->get('error_form_load');
				}
			} else {
				$data['error_my_card'] = $this->language->get('error_service_unavailable');
				$data['cards_not_present'] = false;
				$data['error_capture_context'] = $this->language->get('error_form_load');
			}
		} catch (Exception $e) {
			$data['error_my_card'] = $this->language->get('error_add_card');
			$data['cards_not_present'] = false;
			$data['error_capture_context'] = $this->language->get('error_form_load');
		}
		$this->response->setOutput($this->load->view('extension/credit_card/cybersource_mycards', $data));
	}

	/**
	 * This function will be called when customer clicks add card button from my card ui section(internally called from recaptch common function).
	 */
	public function confirm() {
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('extension/credit_card/cybersource', VAL_EMPTY, true);
			$response_data['redirect'] = $this->url->link('account/login');
		} else {
			$response_data = array();
			$this->load->language('extension/payment/cybersource');
			$this->load->language('extension/credit_card/cybersource');
			$this->load->model('checkout/order');
			$this->load->model('account/address');
			$this->load->model('extension/payment/cybersource');
			$this->load->model('extension/payment/cybersource_common');
			$this->load->model('extension/payment/cybersource_query');
			$currency = $this->config->get('config_currency') ?? VAL_NULL;
			$address_id = $this->request->post['address_id'] ?? VAL_NULL;
			$csrf_token = $this->session->data['csrf_token'] ?? VAL_NULL;
			$csrf = $this->session->data['csrf'] ?? VAL_NULL;
			$csrf_token_time = $this->session->data['csrf_token_time'] ?? VAL_NULL;
			$transient_token = $this->request->post['transient_token'] ?? VAL_NULL;
			$customer_id = $this->customer->getId();
			$response_data['redirect'] = $this->url->link('extension/credit_card/cybersource');
			$unifiedcheckout_configuration = $this->model_extension_payment_cybersource_common->getUnifiedCheckoutConfiguration();
			$saved_card_limit = empty($unifiedcheckout_configuration['saved_card_limit_time_frame']) ? VAL_ZERO : $unifiedcheckout_configuration['saved_card_limit_time_frame'];
			$saved_card_limit_frame = HYPEN . $saved_card_limit . LIMIT_IN_HOURS;
			$current_date_time = date(DATE_Y_M_D_H_COLON_I_COLON_S, strtotime($saved_card_limit_frame, strtotime(CURRENT_DATE)));
			$query_rate_limiter_card = $this->model_extension_payment_cybersource_query->queryRateLimiterCard($customer_id, $current_date_time);
			$rate_limiter_cards = array();
			if (VAL_ZERO < $query_rate_limiter_card->num_rows) {
				foreach ($query_rate_limiter_card->rows as $row) {
					$rate_limiter_cards[] = array(
						'card_id' => $row['card_id']
					);
				}
			}
			$query_number_of_trails = $this->model_extension_payment_cybersource_query->queryNumberOfTrails($customer_id, $current_date_time);
			$number_of_trails = (VAL_ZERO < $query_number_of_trails->num_rows) ? $query_number_of_trails->row['attempts'] : VAL_ZERO;
			$card_count = sizeof($rate_limiter_cards) + $number_of_trails;
			if (VAL_ZERO != $unifiedcheckout_configuration['card'] && VAL_ZERO != $unifiedcheckout_configuration['limit_saved_card_rate'] && $card_count >= $unifiedcheckout_configuration['saved_card_limit_frame']) {
				$save_failed_card = $this->model_extension_payment_cybersource->saveFailedCard($customer_id, $current_date_time);
				if ($save_failed_card) {
					$this->session->data['error_my_card'] = $this->language->get('error_saved_card_limit_frame');
				}
			} else {
				if ($csrf_token && VAL_ZERO == strcmp($csrf_token, $csrf)) {
					if (VAL_SIX_ZERO_ZERO >= (time() - $csrf_token_time)) {
						if (!empty($transient_token)) {
							$api_response = $this->model_extension_payment_cybersource->addCard($transient_token, $currency, $address_id, $customer_id);
							$response_http_code = $api_response['http_code'];
							$response_http_body = $api_response['body'];
							$payment_response_array = $this->model_extension_payment_cybersource_common->getResponse($response_http_code, $response_http_body, PAYMENT_ACTION_AUTHORIZE);
							if ((CODE_TWO_ZERO_ONE == $response_http_code) && (API_STATUS_AUTHORIZED == $payment_response_array['status'])) {
								$card_tokens = $this->model_extension_payment_cybersource->getCardTokens($response_http_body, $customer_id);
								$card_details_response = $this->model_extension_payment_cybersource_common->getCardDetailsFromToken($card_tokens);
								if (CODE_TWO_ZERO_ZERO == $card_details_response['http_code']) {
									$card_data = $this->model_extension_payment_cybersource->getCardData($card_details_response['body']);
									$result = $this->model_extension_payment_cybersource->updateTokenizationTable($payment_response_array['transaction_id'], $card_data, $card_tokens, $address_id, $customer_id);
									if ($result[IS_ADDED]) {
										$this->session->data['success_my_card'] = $this->language->get("text_success_add_card");
									} elseif ($result[IS_UPDATED]) {
										$this->session->data['success_my_card'] = $this->language->get('text_success_update_card');
									}
								} else {
									$this->session->data['error_my_card'] = $this->model_extension_payment_cybersource->updateSaveFailedCard($customer_id, $current_date_time, "error_add_card");
								}
							} elseif (CODE_TWO_ZERO_ONE == $response_http_code && (API_STATUS_AUTHORIZED_RISK_DECLINED == $payment_response_array['status'] || API_STATUS_AUTHORIZED_PENDING_REVIEW == $payment_response_array['status'])) {
								$this->session->data['error_my_card'] = $this->model_extension_payment_cybersource->updateSaveFailedCard($customer_id, $current_date_time, "error_failed_to_add_card");
							} else {
								$this->session->data['error_my_card'] = $this->model_extension_payment_cybersource->updateSaveFailedCard($customer_id, $current_date_time, "error_while_saving_card");
							}
						} else {
							$this->session->data['error_my_card'] = $this->language->get("error_session_expire");
						}
					} else {
						$this->session->data['error_my_card'] = $this->language->get("error_fail_auth");
					}
				} else {
					$this->session->data['error_my_card'] = $this->language->get("error_session_expire");
				}
			}
			$this->model_extension_payment_cybersource_common->unsetSessionData();
		}
		$this->response->setOutput(json_encode($response_data));
	}

	/**
	 * This function will be called when customer clicks update button for perticular card from my card ui section.
	 */
	public function confirmUpdateCardDetails() {
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('extension/credit_card/cybersource', VAL_EMPTY, true);
			$this->response->redirect($this->url->link('account/login', VAL_EMPTY, true));
		}
		$this->load->language('extension/credit_card/cybersource');
		$this->load->language('extension/payment/cybersource_common');
		$this->load->model('extension/payment/cybersource');
		$this->load->model('extension/payment/cybersource_common');
		$this->model_extension_payment_cybersource->loadScript();
		$this->document->setTitle($this->language->get('heading_title_for_update_card'));
		$data = $this->model_extension_payment_cybersource->getHeaderFooter('extension/credit_card/cybersource');
		$data['breadcrumbs'] = $this->model_extension_payment_cybersource->getBreadcrumbs();
		$card_id = $this->request->get['card_id'] ?? VAL_NULL;
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title_for_update_card'),
			'href' => $this->url->link('extension/credit_card/cybersource/confirmUpdateCardDetails', 'card_id=' . $card_id, true)
		);
		$data['text_checkout_payment_address'] = $this->language->get('text_checkout_payment_address');
		$csrf = $this->session->data['csrf'] ?? VAL_EMPTY;
		$this->model_extension_payment_cybersource_common->generateToken($csrf);
		$data['csrf_token_data'] = $this->session->data['csrf'] ?? VAL_NULL;
		if ($this->config->get('payment_' . PAYMENT_GATEWAY . '_status') && $this->config->get('payment_' . PAYMENT_GATEWAY . '_card')) {
			try {
				$customer_id = $this->customer->getId();
				if (VAL_NULL != $card_id && VAL_NULL != $customer_id) {
					$card_info = $this->model_extension_payment_cybersource->getCard($card_id, $customer_id);
					if (empty($card_info)) {
						$data['error_card_update'] = $this->language->get('error_card_not_found');
					} else {
						$data['update_cards'] = $card_info;
					}
				} else {
					$data['error_card_update'] = $this->language->get('error_failed_updation');
				}
			} catch (Exception $e) {
				$data['error_card_update'] = $this->language->get('error_failed_updation');
			}
		} else {
			$data['error_card_update'] = $this->language->get('error_service_unavailable');
		}
		$this->response->setOutput($this->load->view('extension/credit_card/cybersource_updatecard', $data));
	}

	/**
	 * This function will be called when customer clicks update button from update card ui section.
	 */
	public function confirmExecuteCardUpdate() {
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('extension/credit_card/cybersource', VAL_EMPTY, true);
			$response_data['redirect'] = $this->url->link('account/login');
		} else {
			$this->load->language('extension/payment/cybersource');
			$this->load->language('extension/credit_card/cybersource');
			$this->load->model('extension/payment/cybersource');
			$this->load->model('extension/payment/cybersource_common');
			$this->load->model('extension/payment/cybersource_query');
			$response_data = array();
			$response_data['redirect'] = $this->url->link('extension/credit_card/cybersource');
			$card_info = array();
			$customer_id = $this->customer->getId();
			$card_detail_ui = array();
			$card_id = $this->request->post['card_id'] ?? VAL_NULL;
			$card_detail_ui['address_id'] = $this->request->post['address_id'] ?? VAL_NULL;
			$card_detail_ui['expiration_month']  = $this->request->post['expiration_month'] ?? VAL_NULL;
			$card_detail_ui['expiration_year'] = $this->request->post['expiration_year'] ?? VAL_NULL;
			$csrf_token  = $this->request->post['csrf_token'] ?? VAL_ZERO;
			$csrf = $this->session->data['csrf'] ?? VAL_NULL;
			try {
				if (!($this->config->get('payment_' . PAYMENT_GATEWAY . '_status') && $this->config->get('payment_' . PAYMENT_GATEWAY . '_card'))) {
					$data['error_my_card'] = $this->language->get('error_service_unavailable');
				} else {
					if ($csrf_token && VAL_ZERO == strcmp($csrf_token, $csrf)) {
						$query_card_info = $this->model_extension_payment_cybersource_query->queryCardInfo($customer_id, $card_id);
						if (VAL_ZERO < $query_card_info->num_rows) {
							$card_info = array(
								'address_id' => $query_card_info->row['address_id'],
								'expiry_month' => $query_card_info->row['expiry_month'],
								'expiry_year' => $query_card_info->row['expiry_year'],
								'instrument_identifier_id' => $query_card_info->row['instrument_identifier_id'],
								'customer_token_id' => $query_card_info->row['customer_token_id'],
								'payment_instrument_id' => $query_card_info->row['payment_instrument_id']
							);
						}
						if (!empty($card_info)) {
							if (($card_info['expiry_month'] != $card_detail_ui['expiration_month']) || ($card_info['expiry_year'] != $card_detail_ui['expiration_year']) || $card_info['address_id'] != $card_detail_ui['address_id']) {
								if (VAL_NULL == $card_detail_ui['address_id'] || VAL_ZERO == $card_detail_ui['address_id']) {
									$card_detail_ui['address_id'] = $card_info['address_id'];
								}
								$address_data = $this->model_extension_payment_cybersource->getAddressById($card_detail_ui['address_id']);
								$api_response = $this->model_extension_payment_cybersource->updatePaymentToken($address_data, $card_detail_ui, $card_info);
								$http_code = $api_response['http_code'];
								if (CODE_TWO_ZERO_ZERO == $http_code) {
									$card_details = $this->model_extension_payment_cybersource->getCardDetails($api_response['body']);
									$is_card_updation_success = $this->model_extension_payment_cybersource->updateCardDetails($customer_id, $card_details, $card_detail_ui['address_id'], $card_id);
									if ($is_card_updation_success) {
										$this->session->data['success_my_card'] = $this->language->get('text_success_update_card');
									} else {
										$this->session->data['error_my_card'] = $this->language->get('error_update_card');
									}
								} else {
									$this->session->data['error_my_card'] = $this->language->get('error_update_card');
								}
							}
						} else {
							$this->session->data['error_my_card'] = $this->language->get('error_fetch_default_card');
						}
					} else {
						$this->session->data['error_my_card'] = $this->language->get("error_session_expire");
					}
				}
			} catch (Exception $e) {
				$this->session->data['error_my_card'] = $this->language->get('error_update_card');
			}
		}
		$this->response->addHeader(CONTENT_TYPE);
		$this->response->setOutput(json_encode($response_data));
	}

	/**
	 * This function will be called when customer clicks detete button for perticular card from my card ui section.
	 */
	public function confirmExecuteCardDelete() {
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('extension/credit_card/cybersource', VAL_EMPTY, true);
			$response_data['redirect'] = $this->url->link('account/login');
		} else {
			$response_data = array();
			$response_data['redirect'] = $this->url->link('extension/credit_card/cybersource');
			$api_response['http_code'] = VAL_ZERO;
			$this->load->language('extension/payment/cybersource');
			$this->load->language('extension/credit_card/cybersource');
			$this->load->model('extension/payment/cybersource');
			$this->load->model('extension/payment/cybersource_query');
			$customer_id = $this->customer->getId();
			$card_id  = (int)$this->request->post['card_id'] ?? VAL_ZERO;
			$csrf_token  = $this->request->post['csrf_token'] ?? VAL_ZERO;
			$csrf = $this->session->data['csrf'] ?? VAL_NULL;
			if ($csrf_token && VAL_ZERO == strcmp($csrf_token, $csrf)) {
				if (VAL_NULL != $customer_id && VAL_NULL != $card_id && is_int($card_id)) {
					try {
						if (!($this->config->get('payment_' . PAYMENT_GATEWAY . '_status') && $this->config->get('payment_' . PAYMENT_GATEWAY . '_card'))) {
							$data['error_my_card'] = $this->language->get('error_service_unavailable');
						} else {
							$query_card_token = $this->model_extension_payment_cybersource_query->queryCardToken($customer_id, $card_id);
							$query_card_count = $this->model_extension_payment_cybersource_query->queryCardCount($customer_id);
							$card_token = array(
								'customer_token_id' => $query_card_token->row['customer_token_id'],
								'payment_instrument_id' => $query_card_token->row['payment_instrument_id'],
								'default_state' => $query_card_token->row['default_state']
							);
							if (!empty($card_token)) {
								if (VAL_ONE == $card_token['default_state'] && VAL_ONE < $query_card_count->row['customer_id']) {
									$this->session->data['error_my_card'] = $this->language->get('error_default_card');
								} elseif ((VAL_ONE == $card_token['default_state'] && VAL_ONE == $query_card_count->row['customer_id']) || VAL_ZERO == $card_token['default_state']) {
									$api_response = $this->model_extension_payment_cybersource->deleteSavedCard($card_token);
									if (CODE_TWO_ZERO_FOUR == $api_response['http_code']) {
										$is_delete_success = $this->model_extension_payment_cybersource_query->queryDeleteSavedCardDetails($card_id, $customer_id);
										if ($is_delete_success) {
											$this->session->data['success_my_card'] = $this->language->get('text_success_delete_card');
										} else {
											$this->session->data['error_my_card'] = $this->language->get('error_delete_card');
										}
									} elseif (CODE_FOUR_ZERO_NINE == $api_response['http_code']) {
										$this->session->data['error_my_card'] = $this->language->get('error_default_card');
									} else {
										$this->session->data['error_my_card'] = $this->language->get('error_delete_card');
									}
								}
							} else {
								$this->session->data['error_my_card'] = $this->language->get('error_delete_card');
							}
						}
					} catch (Exception $e) {
						$this->session->data['error_my_card'] = $this->language->get('error_delete_card');
					}
				} else {
					$this->session->data['error_my_card'] = $this->language->get('error_delete_card');
				}
			} else {
				$this->session->data['error_my_card'] = $this->language->get("error_session_expire");
			}
		}
		$this->response->addHeader(CONTENT_TYPE);
		$this->response->setOutput(json_encode($response_data));
	}

	/**
	 * This function will be called when customer clicks set as default button for perticular card from my card ui section.
	 */
	public function confirmDefaultCard() {
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('extension/credit_card/cybersource', VAL_EMPTY, true);
			$response_data['redirect'] = $this->url->link('account/login');
		} else {
			$this->load->language('extension/payment/cybersource');
			$this->load->language('extension/credit_card/cybersource');
			$this->load->model('extension/payment/cybersource');
			$this->load->model('extension/payment/cybersource_query');
			$response_data = array();
			$response_data['redirect'] = $this->url->link('extension/credit_card/cybersource');
			$customer_id = $this->customer->getId();
			$card_id  = $this->request->post['card_id'] ?? VAL_ZERO;
			$csrf_token  = $this->request->post['csrf_token'] ?? VAL_ZERO;
			$csrf = $this->session->data['csrf'] ?? VAL_NULL;
			if ($csrf_token && VAL_ZERO == strcmp($csrf_token, $csrf)) {
				if (VAL_NULL != $customer_id && VAL_NULL != $card_id) {
					try {
						if (!($this->config->get('payment_' . PAYMENT_GATEWAY . '_status') && $this->config->get('payment_' . PAYMENT_GATEWAY . '_card'))) {
							$data['error_my_card'] = $this->language->get('error_service_unavailable');
						} else {
							$query_card_token = $this->model_extension_payment_cybersource_query->queryCardToken($customer_id, $card_id);
							$card_tokens = array(
								'customer_token_id' => $query_card_token->row['customer_token_id'],
								'payment_instrument_id' => $query_card_token->row['payment_instrument_id'],
							);
							if (!empty($card_tokens)) {
								$api_response = $this->model_extension_payment_cybersource->setDefaultCard($card_tokens);
								$response_body = json_decode($api_response['body']);
								$default_state = $response_body->default;
								if (CODE_TWO_ZERO_ZERO == $api_response['http_code'] && VAL_ONE == $default_state) {
									$is_update_success = $this->model_extension_payment_cybersource->updateDefaultCard($card_id, $customer_id);
									if ($is_update_success) {
										$this->session->data['success_my_card'] = $this->language->get("text_success_default_card");
									} else {
										$this->session->data['error_my_card'] = $this->language->get("error_default_card_failed");
									}
								} else {
									$this->session->data['error_my_card'] = $this->language->get('error_default_card_failed');
								}
							} else {
								$this->session->data['error_my_card'] = $this->language->get('error_card_not_found');
							}
						}
					} catch (Exception $e) {
						$this->session->data['error_my_card'] = $this->language->get('error_default_card_failed');
					}
				} else {
					$this->session->data['error_my_card'] = $this->language->get('error_default_card_failed');
				}
			} else {
				$this->session->data['error_my_card'] = $this->language->get("error_session_expire");
			}
		}
		$this->response->addHeader(CONTENT_TYPE);
		$this->response->setOutput(json_encode($response_data));
	}

	/**
	 * This function will be called when customer clicks add card button from my card ui section.
	 */
	public function confirmMyCardsRecaptcha() {
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('extension/credit_card/cybersource', VAL_EMPTY, true);
			$response_data['redirect'] = $this->url->link('account/login');
		} else {
			$this->load->model('extension/payment/cybersource_common');
			$request_data['recaptcha_token'] = $this->request->post['token'] ?? VAL_NULL;
			$request_data['csrf'] = $this->request->post['csrf'] ?? VAL_NULL;
			$request_data['time'] = $this->request->post['time'] ?? VAL_NULL;
			$response_data = $this->model_extension_payment_cybersource_common->recaptchaCommon("my_account", $request_data);
		}
		$this->response->addHeader(CONTENT_TYPE);
		$this->response->setOutput(json_encode($response_data));
	}

	/**
	 * This function will be called when customer clicks continue button from my card address ui section.
	 */
	public function confirmSave() {
		$this->load->language('checkout/checkout');
		$this->load->model('account/address');
		$response_data = array();
		if (isset($this->request->post['payment_address']) && 'existing' == $this->request->post['payment_address']) {
			if (empty($this->request->post['address_id'])) {
				$response_data['error']['warning'] = $this->language->get('error_address');
			} elseif (!in_array($this->request->post['address_id'], array_keys($this->model_account_address->getAddresses()))) {
				$response_data['error']['warning'] = $this->language->get('error_address');
			}
			if (!$response_data) {
				$this->session->data['payment_address'] = $this->model_account_address->getAddress($this->request->post['address_id']);
				unset($this->session->data['payment_method']);
				unset($this->session->data['payment_methods']);
			}
		} else {
			if ((utf8_strlen(trim($this->request->post['firstname'])) < VAL_ONE) || (utf8_strlen(trim($this->request->post['firstname'])) > 32)) {
				$response_data['error']['firstname'] = $this->language->get('error_firstname');
			}
			if ((utf8_strlen(trim($this->request->post['lastname'])) < VAL_ONE) || (utf8_strlen(trim($this->request->post['lastname'])) > 32)) {
				$response_data['error']['lastname'] = $this->language->get('error_lastname');
			}
			if ((utf8_strlen(trim($this->request->post['address_1'])) < 3) || (utf8_strlen(trim($this->request->post['address_1'])) > 128)) {
				$response_data['error']['address_1'] = $this->language->get('error_address_1');
			}
			if ((utf8_strlen($this->request->post['city']) < VAL_TWO) || (utf8_strlen($this->request->post['city']) > 32)) {
				$response_data['error']['city'] = $this->language->get('error_city');
			}
			$this->load->model('localisation/country');
			$country_info = $this->model_localisation_country->getCountry($this->request->post['country_id']);
			if ($country_info && $country_info['postcode_required'] && (utf8_strlen(trim($this->request->post['postcode'])) < VAL_TWO || utf8_strlen(trim($this->request->post['postcode'])) > 10)) {
				$response_data['error']['postcode'] = $this->language->get('error_postcode');
			}
			if (VAL_EMPTY == $this->request->post['country_id']) {
				$response_data['error']['country'] = $this->language->get('error_country');
			}
			if (!isset($this->request->post['zone_id']) || VAL_EMPTY == $this->request->post['zone_id'] || !is_numeric($this->request->post['zone_id'])) {
				$response_data['error']['zone'] = $this->language->get('error_zone');
			}
			$this->load->model('account/custom_field');
			$custom_fields = $this->model_account_custom_field->getCustomFields($this->config->get('config_customer_group_id'));
			foreach ($custom_fields as $custom_field) {
				if ('address' == $custom_field['location']) {
					if ($custom_field['required'] && empty($this->request->post['custom_field'][$custom_field['location']][$custom_field['custom_field_id']])) {
						$response_data['error']['custom_field' . $custom_field['custom_field_id']] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
					} elseif (('text' == $custom_field['type']) && !empty($custom_field['validation']) && !filter_var($this->request->post['custom_field'][$custom_field['location']][$custom_field['custom_field_id']], FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => $custom_field['validation'])))) {
						$response_data['error']['custom_field' . $custom_field['custom_field_id']] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
					}
				}
			}
			if (!$response_data) {
				$address_id = $this->model_account_address->addAddress($this->customer->getId(), $this->request->post);
				$this->session->data['payment_address'] = $this->model_account_address->getAddress($address_id);
				if (!$this->customer->getAddressId()) {
					$this->load->model('account/customer');
					$this->model_account_customer->editAddressId($this->customer->getId(), $address_id);
				}
				unset($this->session->data['payment_method']);
				unset($this->session->data['payment_methods']);
			}
		}
		$this->response->addHeader(CONTENT_TYPE);
		$this->response->setOutput(json_encode($response_data));
	}
}
