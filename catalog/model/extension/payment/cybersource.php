<?php

use Isv\Common\Helper\TypeConversion;
use Isv\Common\Payload\PaymentProcessingInformation;

require_once DIR_SYSTEM . 'library/isv/cybersource_constants.php';

/**
 * Unified Checkout Model file.
 *
 * @author Cybersource
 * @package Front Office
 * @subpackage Model
 */
class ModelExtensionPaymentCybersource extends Model {
	public function getLineItemDetails($order_id) {
		$this->load->model('checkout/order');
		$this->load->model('extension/payment/cybersource_common');
		$product_details = $this->model_checkout_order->getOrderProducts($order_id);
		$voucher_details = $this->model_checkout_order->getOrderVouchers($order_id);
		$line_items = $this->model_extension_payment_cybersource_common->getLineItemArray($product_details, $voucher_details, $order_id);
		return $line_items;
	}

	/**
	 * Opencart by defualt call this function to get payment information.
	 *
	 * @param string $address
	 * @param int $total
	 *
	 * @return array
	 */
	public function getMethod($address, $total): array {
		$this->load->language('extension/payment/cybersource');
		$payment_display_data = array();
		if ($total > VAL_ZERO) {
			$payment_display_data = array(
				'code'       => PAYMENT_GATEWAY,
				'title'      => $this->config->get('payment_' . PAYMENT_GATEWAY . '_payment_option_label'),
				'terms'      => VAL_EMPTY,
				'sort_order' => $this->config->get('payment_' . PAYMENT_GATEWAY . '_sort_order')
			);
		}
		return $payment_display_data;
	}

	/**
	 * Gives transaction details from order table.
	 *
	 * @param int $order_id order id
	 * @param string $table_prefix
	 *
	 * @return array
	 */
	public function getOrderDetails(int $order_id, string $table_prefix): array {
		$transactions = array();
		$this->load->model('extension/payment/cybersource_query');
		if (VAL_ZERO < $order_id) {
			$query_transaction_details = $this->model_extension_payment_cybersource_query->queryTransactionDetails($order_id, $table_prefix);
			if (VAL_NULL != $query_transaction_details && VAL_ZERO < $query_transaction_details->num_rows) {
				foreach ($query_transaction_details->rows as $transaction_details) {
					$transactions['transaction_id'] = $transaction_details['transaction_id'];
					$transactions['amount'] = $transaction_details['amount'];
				}
			}
		}
		return $transactions;
	}

	/**
	 * Insert token details to tokenization table.
	 *
	 * @param array $token_details Contains token details data
	 *
	 * @return array
	 */
	public function insertTokenizationTable(array $token_details): array {
		$return_insert_token_response = false;
		$this->load->model('extension/payment/cybersource_query');
		if (!empty($token_details)) {
			$query_insert_token_data = $this->model_extension_payment_cybersource_query->queryInsertTokenizationDetails($token_details);
			if (VAL_NULL != $query_insert_token_data) {
				$return_insert_token_response = $query_insert_token_data;
			}
		}
		if (!$return_insert_token_response) {
			$result = array(
				IS_ADDED => false,
				IS_UPDATED => false,
				IS_FAILED => true
			);
		} else {
			$result = array(
				IS_ADDED => true,
				IS_UPDATED => false,
				IS_FAILED => false
			);
		}
		return $result;
	}

	/**
	 * Prepares order details which is used to store data in order table.
	 *
	 * @param array $response ebc Unified Checkout response
	 * @param array $payload_data ebc request payload
	 * @param int $total_quantity total quantity of products that is ordered
	 * @param array $unifiedcheckout_configuration
	 * @param string $tax_id tax id from tax table
	 * @param string $payment_method payment method name
	 *
	 * @return array
	 */
	public function prepareOrderDetails(array $response, array $payload_data, int $total_quantity, string $status, ?string $tax_id, string $payment_method): array {
		$order_details = VAL_NULL;
		$order_details['order_id'] = $payload_data['order_id'];
		$order_details['transaction_id'] = $response['transaction_id'];
		$order_details['cybersource_order_status'] = $response['status'];
		$order_details['oc_order_status'] = $status;
		$order_details['payment_action'] = $this->config->get('module_' . PAYMENT_GATEWAY . '_transaction_method');
		$order_details['currency'] = $response['currency'];
		$order_details['order_quantity'] = $total_quantity;
		$order_details['tax_id'] = $tax_id;
		$order_details['amount'] = $response['amount'];
		$order_details['payment_method'] = $payment_method;
		$order_details['date_added'] = CURRENT_DATE;
		return $order_details;
	}

	/**
	 * Insert order details which is got from prepareOrderDetails() to order table.
	 *
	 * @param array $order_details Contents order details
	 * @param string $table_prefix
	 *
	 * @return bool
	 */
	public function insertOrderDetails(array $order_details, string $table_prefix): bool {
		$query_insert_order_data = false;
		$this->load->model('extension/payment/cybersource_query');
		if (!empty($order_details)) {
			$query_response = $this->model_extension_payment_cybersource_query->queryInsertOrder($order_details, $table_prefix);
			if (VAL_NULL != $query_response) {
				$query_insert_order_data = $query_response;
			}
		}
		return $query_insert_order_data;
	}

	/**
	 * Update card details with user selected year month and address.
	 *
	 * @param int $customer_id Logged in customer user ID
	 * @param array $card_details card details from response
	 * @param int $address_id entered or previous address id
	 * @param int $card_id card id
	 *
	 * @return bool
	 */
	public function updateCardDetails(int $customer_id, array $card_details, int $address_id, int $card_id): bool {
		$query_update_card_details = false;
		$this->load->model('extension/payment/cybersource_query');
		$query_update_expiry_details = $this->model_extension_payment_cybersource_query->queryUpdateCardDetails($address_id, $card_id, $customer_id, $card_details);
		if (VAL_NULL != $query_update_expiry_details) {
			$query_update_card_details = $query_update_expiry_details;
		}
		return $query_update_card_details;
	}

	/**
	 * Extracts card details from update card response.
	 *
	 * @param string $response ebc update card response body
	 *
	 * @return array
	 */
	public function getCardDetails(string $response): array {
		$card_details = array();
		$token_response = json_decode($response);
		$card_details['expiration_month'] = $token_response->card->expirationMonth;
		$card_details['expiration_year'] = $token_response->card->expirationYear;
		return $card_details;
	}

	/**
	 * Changes the default card.
	 *
	 * @param int $card_id card id
	 * @param int $customer_id Logged in customer id
	 *
	 * @return bool
	 */
	public function updateDefaultCard(int $card_id, int $customer_id): bool {
		$this->load->model('extension/payment/cybersource_query');
		$query_default_card = $this->model_extension_payment_cybersource_query->queryDefaultCard($customer_id);
		$previous_default_id = VAL_NULL != $query_default_card ? $query_default_card->row['card_id'] : VAL_ZERO;
		$query_update_default_card = $this->model_extension_payment_cybersource_query->queryUpdateDefaultCard($previous_default_id, $card_id);
		$return_response = VAL_NULL != $query_update_default_card ? $query_update_default_card : false;
		return $return_response;
	}

	/**
	 * Gives all the saved card details for specified customer.
	 *
	 * @param int $customer_id Logged in customer id based on which we will fetch stored card details.
	 *
	 * @return array
	 */
	public function getCards(?int $customer_id): array {
		$card_details = array();
		$this->load->model('extension/payment/cybersource_query');
		$query_cards_info = $this->model_extension_payment_cybersource_query->queryCards($customer_id);
		if (VAL_NULL != $query_cards_info && VAL_ZERO < $query_cards_info->num_rows) {
			foreach ($query_cards_info->rows as $card_info) {
				$card_details[] = $this->getCustomerCardDetails($card_info);
			}
		}
		return $card_details;
	}

	/**
	 * Gives card details of specified customer and card.
	 *
	 * @param int $card_id card id
	 * @param int $customer_id Logged in user id
	 *
	 * @return array|null
	 */
	public function getCard(int $card_id, int $customer_id): ?array {
		$card_details = VAL_NULL;
		$this->load->model('extension/payment/cybersource_query');
		$query_cards_info = $this->model_extension_payment_cybersource_query->queryCard($card_id, $customer_id);
		if (VAL_NULL != $query_cards_info && VAL_ZERO < $query_cards_info->num_rows) {
			$card_details = $this->getCustomerCardDetails($query_cards_info->row);
		}
		return $card_details;
	}

	/**
	 * Format address and other card details according to opencart standards for display.
	 *
	 * @param array $card_details details related to card
	 *
	 * @return array
	 */
	private function getCustomerCardDetails(array $card_details): array {
		$card_data = array();
		$this->load->model('account/address');
		$result = $this->model_account_address->getAddress((int)$card_details['address_id']);
		if (false != $result) {
			$format = $result['address_format'] ? $result['address_format'] : '{firstname} {lastname}' . "\n" . '{company}' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city} {postcode}' . "\n" . '{zone}' . "\n" . '{country}';
			$find = array(
				'{firstname}',
				'{lastname}',
				'{company}',
				'{address_1}',
				'{address_2}',
				'{city}',
				'{postcode}',
				'{zone}',
				'{zone_code}',
				'{country}'
			);
			$replace = array(
				'firstname' => $result['firstname'],
				'lastname'  => $result['lastname'],
				'company'   => $result['company'],
				'address_1' => $result['address_1'],
				'address_2' => $result['address_2'],
				'city'      => $result['city'],
				'postcode'  => $result['postcode'],
				'zone'      => $result['zone'],
				'zone_code' => $result['zone_code'],
				'country'   => $result['country']
			);
			$card_data = array(
				'card_id'       => $card_details['card_id'],
				'customer_name' => $card_details['customer_name'],
				'card_number'   => $card_details['card_number'],
				'expiry_month'  => $card_details['expiry_month'],
				'expiry_year'   => $card_details['expiry_year'],
				'default_state' => $card_details['default_state'],
				'address_id'    => $card_details['address_id'],
				'address'       => str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', trim(str_replace($find, $replace, $format))))
			);
		} else {
			$card_data = array(
				'card_id'       => $card_details['card_id'],
				'customer_name' => $card_details['customer_name'],
				'card_number'   => $card_details['card_number'],
				'expiry_month'  => $card_details['expiry_month'],
				'expiry_year'   => $card_details['expiry_year'],
				'default_state' => $card_details['default_state'],
				'address_id'    => $card_details['address_id']
			);
		}
		return $card_data;
	}

	/**
	 * Gives address details for specified address id.
	 *
	 * @param int $address_id address id
	 *
	 * @return array|null
	 */
	public function getAddressById(int $address_id): ?array {
		$address_data = VAL_NULL;
		$this->load->model('account/address');
		$address = $this->model_account_address->getAddress($address_id);
		if (false != $address) {
			$address_data = $address;
		}
		return $address_data;
	}

	/**
	 * Checks response for tokens if not present fetchs token from tokenization table.
	 *
	 * @param string $response ebc response for Unified Checkout
	 * @param int $customer_id Logged in customer id
	 *
	 * @return array
	 */
	public function getCardTokens(string $response, int $customer_id): array {
		$tokens = VAL_NULL;
		$this->load->model('extension/payment/cybersource_common');
		$response_data = json_decode($response);
		$tokens['customer_token_id'] = !empty($response_data->tokenInformation->customer->id) ? $response_data->tokenInformation->customer->id : $this->model_extension_payment_cybersource_common->getCustomerTokenId($customer_id);
		$tokens['instrument_identifier_id'] = $response_data->tokenInformation->instrumentIdentifier->id;
		$tokens['payment_instrument_id'] = $response_data->tokenInformation->paymentInstrument->id;
		return $tokens;
	}

	/**
	 * Prepares auth reversal details(cancel order) which is used to store data in auth reversal table.
	 *
	 * @param array $auth_reversal_response_array ebc auth reversal response
	 * @param int $order_id order id
	 *
	 * @return array
	 */
	public function prepareAuthReversalDetails(array $auth_reversal_response_array, int $order_id): array {
		$auth_reversal_details = VAL_NULL;
		$auth_reversal_details['order_id'] = $order_id;
		$auth_reversal_details['transaction_id'] = $auth_reversal_response_array['transaction_id'];
		$auth_reversal_details['cybersource_order_status'] = $auth_reversal_response_array['status'];
		$auth_reversal_details['oc_order_status'] = $this->config->get('module_' . PAYMENT_GATEWAY . '_auth_reversal_status_id');
		$auth_reversal_details['currency'] = $auth_reversal_response_array['currency'];
		$auth_reversal_details['amount'] = $auth_reversal_response_array['amount'];
		$auth_reversal_details['date_added'] = CURRENT_DATE;
		return $auth_reversal_details;
	}

	/**
	 * Insert auth reversal details which is got from prepareAuthReversalDetails() to auth reversal table.
	 *
	 * @param array $auth_reversal_details
	 * @param string $table_prefix
	 *
	 * @return bool
	 */
	public function insertAuthReversalDetails(array $auth_reversal_details, string $table_prefix): bool {
		$query_response = false;
		if (!empty($auth_reversal_details) && !empty($table_prefix)) {
			$query_insert_auth_reversal = $this->model_extension_payment_cybersource_query->queryInsertAuthReversal($auth_reversal_details, $table_prefix);
			if (VAL_NULL != $query_insert_auth_reversal) {
				$query_response = $query_insert_auth_reversal;
			}
		}
		return $query_response;
	}

	/**
	 * Adds cancelled for order history status for auth reversal(cancel order).
	 *
	 * @param int $order_id order id
	 * @param int $order_status_id id for cancelled order status
	 * @param string $custom_status custom status string which will be stored in custom order_status table
	 * @param string $comment
	 * @param bool $notify
	 *
	 * @return [type]
	 */
	public function addOrderHistoryForCancelOrder(int $order_id, string $order_status_id, string $custom_status, ?string $comment = '', bool $notify = false) {
		$query_response = false;
		$this->load->model('checkout/order');
		$this->load->model('extension/payment/cybersource_query');
		$order = $this->model_checkout_order->getOrder($order_id);
		$current_order_status = (int)$order['order_status_id'];
		if ($current_order_status != $order_status_id) {
			$query_update_order = $this->model_extension_payment_cybersource_query->queryUpdateOrderTable($order_status_id, $order_id);
			if (VAL_NULL != $query_update_order && $query_update_order) {
				$query_insert_order = $this->model_extension_payment_cybersource_query->queryInsertOrderHistory($order_id, $order_status_id, $notify, $comment);
				if (VAL_NULL != $query_insert_order && $query_insert_order) {
					$query_response = true;
				}
			}
		} else {
			return true;
		}
		if ($query_response) {
			$query_update_status = $this->model_extension_payment_cybersource_query->queryUpdateOrderStatus($custom_status, $order_id);
			if (VAL_NULL != $query_update_status) {
				$query_response = $query_update_status;
			}
		}
		return $query_response;
	}

	/**
	 * Restocks products when customer cancels the order.
	 *
	 * @param int $order_id order id
	 *
	 * @return [type]
	 */
	public function restock(int $order_id) {
		$return_response = false;
		$this->load->model('checkout/order');
		$this->load->model('extension/payment/cybersource_query');
		$order_products = $this->model_checkout_order->getOrderProducts($order_id);
		foreach ($order_products as $order_product) {
			$query_update_product = $this->model_extension_payment_cybersource_query->queryUpdateProduct($order_product);
			if (VAL_NULL != $query_update_product && $query_update_product) {
				$order_options = $this->model_checkout_order->getOrderOptions($order_id, $order_product['order_product_id']);
				foreach ($order_options as $order_option) {
					$query_update_product_option = $this->model_extension_payment_cybersource_query->queryUpdateProductOption($order_product, $order_option);
					if (VAL_NULL != $query_update_product_option) {
						$return_response = $query_update_product_option;
					}
				}
			}
		}
		return $query_update_product;
	}

	/**
	 * Extracts card data from response.
	 *
	 * @param string $response ebc response for card details
	 *
	 * @return array
	 */
	public function getCardData(string $response): array {
		$card_info = VAL_NULL;
		$response_data = json_decode($response);
		$card_info['expiry_month'] = $response_data->card->expirationMonth;
		$card_info['expiry_year'] = $response_data->card->expirationYear;
		$card_info['card_number'] = $response_data->_embedded->instrumentIdentifier->card->number;
		return $card_info;
	}

	/**
	 * Gives logged in customer first and last name.
	 *
	 * @return string
	 */
	public function getCustomerName(): string {
		$first_name = $this->customer->getFirstName();
		$last_name = $this->customer->getLastName();
		$customer_name = $first_name . ' ' . $last_name;
		return $customer_name;
	}

	/**
	 * Updates count when card adding failed.
	 *
	 * @param int $customer_id Logged in customer id
	 * @param string $current_date_time current time and date
	 *
	 * @return bool
	 */
	public function saveFailedCard(int $customer_id, string $current_date_time): bool {
		$attempts_counter = VAL_ZERO;
		$query_token_check = false;
		$this->load->model('extension/payment/cybersource_query');
		$query_attempts = $this->model_extension_payment_cybersource_query->queryAttempts($customer_id, $current_date_time);
		if (VAL_NULL != $query_attempts) {
			if (VAL_ZERO >= $query_attempts->num_rows) {
				$query_failed_attempts = $this->model_extension_payment_cybersource_query->queryFailedAttempts($customer_id);
				if (VAL_NULL != $query_failed_attempts && VAL_ZERO >= $query_failed_attempts->num_rows) {
					$counter = VAL_ONE;
					$query_response = $this->model_extension_payment_cybersource_query->queryInsertTokenCheck($customer_id, $counter);
					if (VAL_NULL != $query_response) {
						$query_token_check = $query_response;
					}
				} elseif (VAL_NULL != $query_failed_attempts && VAL_ONE == $query_failed_attempts->num_rows) {
					$counter = VAL_ONE;
					$query_response = $this->model_extension_payment_cybersource_query->queryUpdateTokenCheck($customer_id, $counter);
					if (VAL_NULL != $query_response) {
						$query_token_check = $query_response;
					}
				}
			} elseif (VAL_ZERO < $query_attempts->num_rows) {
				$attempts_counter = $query_attempts->row['counter'];
				$counter = $attempts_counter + VAL_ONE;
				$query_response = $this->model_extension_payment_cybersource_query->queryUpdateTokenCheck($customer_id, $counter);
				if (VAL_NULL != $query_response) {
					$query_token_check = $query_response;
				}
			}
		}
		return $query_token_check;
	}

	public function getFraudulentAuthReversalData(int $order_id, $transaction_id, $table_prefix): ?string {
		$this->load->language('extension/payment/cybersource');
		$this->load->language('extension/payment/cybersource_loggers');
		$this->load->model('extension/payment/cybersource_common');
		$redirect = null;
		$is_auth_reversal_triggered = false;
		$class_name = (TABLE_PREFIX_UNIFIED_CHECKOUT == $table_prefix) ? 'ModelExtensionPaymentCybersource' : 'ModelExtensionPaymentCybersourceApay';
		$file_name = (TABLE_PREFIX_UNIFIED_CHECKOUT == $table_prefix) ? PAYMENT_GATEWAY : PAYMENT_GATEWAY_APPLE_PAY;
		$order_details = $this->model_extension_payment_cybersource->getOrderDetails($order_id, $table_prefix);
		if (!empty($order_details)) {
			$resources = RESOURCE_TSS_V2_TRANSACTIONS . $transaction_id;
			$transaction_details_response = $this->model_extension_payment_cybersource_common->serviceProcessor(VAL_EMPTY, $resources, true, HTTP_METHOD_GET);
			if (CODE_TWO_ZERO_ZERO == $transaction_details_response['http_code']) {
				$json = json_decode($transaction_details_response['body']);
				if (!empty($json->_links->relatedTransactions)) {
					$related_transactions = $json->_links->relatedTransactions;
					foreach ($related_transactions as $related_transaction) {
						$href_url = $related_transaction->href;
						$href_url_split = explode('/', $href_url);
						$transaction_id = end($href_url_split);
						$resources = RESOURCE_TSS_V2_TRANSACTIONS . $transaction_id;
						$transaction_details_response = $this->model_extension_payment_cybersource_common->serviceProcessor(VAL_EMPTY, $resources, true, HTTP_METHOD_GET);
						if (CODE_TWO_ZERO_ZERO == $transaction_details_response['http_code']) {
							$json = json_decode($transaction_details_response['body']);
							$applications_array = $json->applicationInformation->applications;
							foreach ($applications_array as $application) {
								$application_name = $application->name;
								$application_code = $application->rCode;
								$application_flag = $application->rFlag;
								if (APPLICATION_NAME_AUTH_REVERSAL == $application_name && VAL_ONE == $application_code && APPLICATION_FLAG_SOK == $application_flag) {
									$is_auth_reversal_triggered = true;
									$item_restock = $this->model_extension_payment_cybersource->restock($order_id);
									if (!$item_restock) {
										$redirect = $this->model_extension_payment_cybersource_common->getReturnUrl('[' . $class_name . '][getFraudulentAuthReversalData]:' . $this->language->get('error_failed_to_restock'), STATUS_FAILURE);
									}
									break;
								}
							}
							if ($is_auth_reversal_triggered) {
								break;
							}
						} else {
							$redirect = $this->model_extension_payment_cybersource_common->getReturnUrl('[' . $class_name . '][getFraudulentAuthReversalData]:' . $this->language->get('error_msg_retrieve_transaction'), STATUS_FAILURE);
						}
					}
				}
				if (false === $is_auth_reversal_triggered) {
					$api_response = $this->model_extension_payment_cybersource->getCancelResponse($order_id, $order_details, $file_name);
					if (VAL_NULL != $api_response) {
						$http_code = $api_response['http_code'];
						$cancel_response_array = $this->model_extension_payment_cybersource_common->getResponse($http_code, $api_response['body'], SERVICE_AUTH_REVERSAL);
						if ((CODE_TWO_ZERO_ONE == $http_code) && (API_STATUS_REVERSED == $cancel_response_array['status'])) {
							$item_restock = $this->model_extension_payment_cybersource->restock($order_id);
							if ($item_restock) {
								$auth_reversal_details = $this->model_extension_payment_cybersource->prepareAuthReversalDetails($cancel_response_array, $order_id);
								$is_auth_reversal_insertion_success = $this->model_extension_payment_cybersource->insertAuthReversalDetails($auth_reversal_details, $table_prefix);
								if (!$is_auth_reversal_insertion_success) {
									$redirect = $this->model_extension_payment_cybersource_common->getReturnUrl('[' . $class_name . '][getFraudulentAuthReversalData]:' . $this->language->get('warning_msg_auth_reversal_insertion'), STATUS_FAILURE);
								}
							} else {
								$redirect = $this->model_extension_payment_cybersource_common->getReturnUrl('[' . $class_name . '][getFraudulentAuthReversalData]:' . $this->language->get('error_failed_to_restock'), STATUS_FAILURE);
							}
						} else {
							$redirect = $this->model_extension_payment_cybersource_common->getReturnUrl('[' . $class_name . '][getFraudulentAuthReversalData]:' . $this->language->get('error_msg_auth_reversal'), STATUS_FAILURE);
						}
					} else {
						$redirect = $this->model_extension_payment_cybersource_common->getReturnUrl('[' . $class_name . '][getFraudulentAuthReversalData]:' . $this->language->get('error_response_info'), STATUS_FAILURE);
					}
				}
			} else {
				$redirect = $this->model_extension_payment_cybersource_common->getReturnUrl('[' . $class_name . '][getFraudulentAuthReversalData]:' . $this->language->get('error_msg_retrieve_transaction'), STATUS_FAILURE);
			}
		} else {
			$redirect = $this->model_extension_payment_cybersource_common->getReturnUrl('[' . $class_name . '][getFraudulentAuthReversalData]:' . $this->language->get('error_response_info'), STATUS_FAILURE);
		}
		return $redirect;
	}

	/**
	 * Payload will get created with necessary data and send that to Unified Checkout end point.
	 *
	 * @param int $order_id order id
	 * @param string $transient_token token generated my flex micro from
	 * @param mixed $is_save_card is card needs to be saved or not
	 * @param array $line_items It will have line items details
	 * @param array $payload_data It will have data that are needed to create payload
	 * @param string|null $customer_token_id It will have customer token id if presents in db
	 * @param mixed $auth_transaction_id
	 * @param mixed $signed_pareq
	 *
	 * @return array
	 */
	public function getOrderInfo(int $order_id, string $transient_token, $is_save_card, array $line_items, array $payload_data, ?string $customer_token_id, $auth_transaction_id, $signed_pareq): array {
		$consumer_authentication_information = VAL_EMPTY;
		$action_token_types = VAL_EMPTY;
		$this->load->model('extension/payment/cybersource_common');
		$general_configuration = $this->model_extension_payment_cybersource_common->getGeneralConfiguration();
		$unifiedcheckout_configuration = $this->model_extension_payment_cybersource_common->getUnifiedCheckoutConfiguration();
		$session_id = (VAL_ONE == $general_configuration['dfp']) ? TypeConversion::convertDataToType($general_configuration['session_id'], 'string') : VAL_EMPTY;
		$client_reference_info = $this->model_extension_payment_cybersource_common->getClientReferenceInfo($order_id);
		$payment_method = $this->session->data['uc_payment_method'] ?? VAL_NULL;
		if (VAL_TRUE == $is_save_card && PAYMENT_METHOD_NAME_CC == $payment_method) {
			if (VAL_ONE == $unifiedcheckout_configuration['payer_auth']) {
				$action_list = TypeConversion::convertArrayToType(array("VALIDATE_CONSUMER_AUTHENTICATION", $general_configuration['fraud_status'], "TOKEN_CREATE"), array('string'));
				$consumer_authentication_information = array(
					"authenticationTransactionId" => TypeConversion::convertDataToType($auth_transaction_id, 'string'),
					"signedPares" => TypeConversion::convertDataToType($signed_pareq, 'string')
				);
			} else {
				$action_list = TypeConversion::convertArrayToType(array($general_configuration['fraud_status'], "TOKEN_CREATE"), array('string'));
			}
			$action_token_types = (!empty($customer_token_id)) ? TypeConversion::convertArrayToType(array("paymentInstrument", "instrumentIdentifier"), array('string')) : TypeConversion::convertArrayToType(array("customer", "paymentInstrument", "instrumentIdentifier"), array('string'));
			$processing_information = array(
				"capture" => TypeConversion::convertDataToType($unifiedcheckout_configuration['capture'], 'boolean'),
				"actionList" => $action_list,
				"actionTokenTypes" => $action_token_types
			);
		} else {
			if (VAL_ONE == $unifiedcheckout_configuration['payer_auth'] && PAYMENT_METHOD_NAME_CC == $payment_method) {
				$action_list = TypeConversion::convertArrayToType(array("VALIDATE_CONSUMER_AUTHENTICATION", $general_configuration['fraud_status']), array('string'));
				$consumer_authentication_information = array(
					"authenticationTransactionId" => TypeConversion::convertDataToType($auth_transaction_id, 'string'),
					"signedPares" => TypeConversion::convertDataToType($signed_pareq, 'string')
				);
			} else {
				$action_list = TypeConversion::convertArrayToType(array($general_configuration['fraud_status']), array('string'));
			}
			$processing_information = array(
				"capture" => TypeConversion::convertDataToType($unifiedcheckout_configuration['capture'], 'boolean'),
				"actionList" => $action_list,
			);
		}
		if ((empty($customer_token_id) || PAYMENT_METHOD_NAME_CC != $payment_method) && (VAL_TRUE == $is_save_card || VAL_FALSE == $is_save_card)) {
			$payload = array(
				"clientReferenceInformation" => $client_reference_info,
				"processingInformation" => $processing_information,
				"paymentInformation" => array(
					"card" => array(
						"typeSelectionIndicator" => TypeConversion::convertDataToType(VAL_ONE, 'string')
					)
				),
				"orderInformation" => array(
					"billTo" => array(
						"firstName" => TypeConversion::convertDataToType($payload_data['payment_firstname'], 'string'),
						"lastName" => TypeConversion::convertDataToType($payload_data['payment_lastname'], 'string'),
						"address1" => TypeConversion::convertDataToType($payload_data['payment_address_1'], 'string'),
						"postalCode" => TypeConversion::convertDataToType($payload_data['payment_postcode'], 'string'),
						"locality" => TypeConversion::convertDataToType($payload_data['payment_city'], 'string'),
						"administrativeArea" => TypeConversion::convertDataToType($payload_data['payment_zone_code'], 'string'),
						"country" => TypeConversion::convertDataToType($payload_data['payment_iso_code_2'], 'string'),
						"phoneNumber" => TypeConversion::convertDataToType($payload_data['telephone'], 'string'),
						"email" => TypeConversion::convertDataToType($payload_data['email'], 'string')
					),
					"shipTo" => array(
						"firstName" => TypeConversion::convertDataToType($payload_data['shipping_firstname'], 'string'),
						"lastName" => TypeConversion::convertDataToType($payload_data['shipping_lastname'], 'string'),
						"address1" => TypeConversion::convertDataToType($payload_data['shipping_address_1'], 'string'),
						"postalCode" => TypeConversion::convertDataToType($payload_data['shipping_postcode'], 'string'),
						"locality" => TypeConversion::convertDataToType($payload_data['shipping_city'], 'string'),
						"administrativeArea" => TypeConversion::convertDataToType($payload_data['shipping_zone_code'], 'string'),
						"country" => TypeConversion::convertDataToType($payload_data['shipping_iso_code_2'], 'string'),
						"phoneNumber" => TypeConversion::convertDataToType($payload_data['telephone'], 'string'),
						"email" => TypeConversion::convertDataToType($payload_data['email'], 'string')
					),
					"lineItems" => $line_items,
					"amountDetails" => array(
						"totalAmount" => TypeConversion::convertDataToType($payload_data['total'], 'string'),
						"currency" => TypeConversion::convertDataToType($payload_data['currency_code'], 'string')
					),
				),
				"tokenInformation" => array(
					"transientTokenJwt" => TypeConversion::convertDataToType($transient_token, 'string')
				),
				"deviceInformation" => array(
					"fingerprintSessionId" => $session_id
				),
			);
		} else {
			$payload = array(
				"clientReferenceInformation" => $client_reference_info,
				"processingInformation" => $processing_information,
				"paymentInformation" => array(
					"customer" => array(
						"id" => TypeConversion::convertDataToType($customer_token_id, 'string')
					),
					"card" => array(
						"typeSelectionIndicator" => TypeConversion::convertDataToType(VAL_ONE, 'string')
					)
				),
				"orderInformation" => array(
					"billTo" => array(
						"firstName" => TypeConversion::convertDataToType($payload_data['payment_firstname'], 'string'),
						"lastName" => TypeConversion::convertDataToType($payload_data['payment_lastname'], 'string'),
						"address1" => TypeConversion::convertDataToType($payload_data['payment_address_1'], 'string'),
						"postalCode" => TypeConversion::convertDataToType($payload_data['payment_postcode'], 'string'),
						"locality" => TypeConversion::convertDataToType($payload_data['payment_city'], 'string'),
						"administrativeArea" => TypeConversion::convertDataToType($payload_data['payment_zone_code'], 'string'),
						"country" => TypeConversion::convertDataToType($payload_data['payment_iso_code_2'], 'string'),
						"phoneNumber" => TypeConversion::convertDataToType($payload_data['telephone'], 'string'),
						"email" => TypeConversion::convertDataToType($payload_data['email'], 'string')
					),
					"shipTo" => array(
						"firstName" => TypeConversion::convertDataToType($payload_data['shipping_firstname'], 'string'),
						"lastName" => TypeConversion::convertDataToType($payload_data['shipping_lastname'], 'string'),
						"address1" => TypeConversion::convertDataToType($payload_data['shipping_address_1'], 'string'),
						"postalCode" => TypeConversion::convertDataToType($payload_data['shipping_postcode'], 'string'),
						"locality" => TypeConversion::convertDataToType($payload_data['shipping_city'], 'string'),
						"administrativeArea" => TypeConversion::convertDataToType($payload_data['shipping_zone_code'], 'string'),
						"country" => TypeConversion::convertDataToType($payload_data['shipping_iso_code_2'], 'string'),
						"phoneNumber" => TypeConversion::convertDataToType($payload_data['telephone'], 'string'),
						"email" => TypeConversion::convertDataToType($payload_data['email'], 'string')
					),
					"lineItems" => $line_items,
					"amountDetails" => array(
						"totalAmount" => TypeConversion::convertDataToType($payload_data['total'], 'string'),
						"currency" => TypeConversion::convertDataToType($payload_data['currency_code'], 'string')
					),
				),
				"tokenInformation" => array(
					"transientTokenJwt" => TypeConversion::convertDataToType($transient_token, 'string')
				),
				"deviceInformation" => array(
					"fingerprintSessionId" => $session_id
				),
			);
		}
		if (VAL_ONE == $unifiedcheckout_configuration['payer_auth'] && PAYMENT_METHOD_NAME_CC == $payment_method) {
			$payload['consumerAuthenticationInformation'] = $consumer_authentication_information;
		}
		$payload = json_encode($payload);
		$resource = RESOURCE_PTS_V2_PAYMENTS;
		$api_response = $this->model_extension_payment_cybersource_common->serviceProcessor($payload, $resource, true, VAL_EMPTY);
		return $api_response;
	}

	/**
	 * Payload will get created with necessary data and send that to auth reversal end point.
	 *
	 * @param int $order_id order id
	 * @param array $order_details It will have data that are needed to create payload
	 *
	 * @return array
	 */
	public function getCancelResponse($order_id, $order_details, $file_name) {
		$this->load->model('extension/payment/cybersource_common');
		$line_items = $this->getLineItemDetails($order_id);
		$client_reference_info = $this->model_extension_payment_cybersource_common->getClientReferenceInfo($order_id);
		$payload = array(
			"clientReferenceInformation" => $client_reference_info,
			"reversalInformation" => array(
				"amountDetails" => array(
					"totalAmount" => TypeConversion::convertDataToType($order_details['amount'], 'string')
				)
			),
			"orderInformation" => array(
				"lineItems" => $line_items
			)
		);
		$payment_processing_information = new PaymentProcessingInformation($this->registry);
		if (PAYMENT_GATEWAY === $file_name || PAYMENT_GATEWAY_APPLE_PAY === $file_name) {
			$payload = $payment_processing_information->paymentSolution($payload, $file_name, $order_id);
		}
		$payload = json_encode($payload);
		$resource = RESOURCE_PTS_V2_PAYMENTS . $order_details['transaction_id'] . RESOURCE_REVERSALS;
		$api_response = $this->model_extension_payment_cybersource_common->serviceProcessor($payload, $resource, false, VAL_EMPTY);
		return $api_response;
	}

	/**
	 * Payload will get created with necessary data and send that to Unified Checkout end point.
	 *
	 * @param int $order_id order id
	 * @param array $saved_card_tokens tokens are stored in db
	 * @param mixed $security_code user entered cvv
	 * @param array $line_items It will have line items details
	 * @param array $payload_data It will have data that are needed to create payload
	 * @param mixed $auth_transaction_id
	 * @param mixed $signed_pareq
	 *
	 * @return array
	 */
	public function getOrderInfoSavedCard(int $order_id, array $saved_card_tokens, $security_code, array $line_items, array $payload_data, $auth_transaction_id, $signed_pareq): array {
		$consumer_authentication_information = VAL_NULL;
		$this->load->model('extension/payment/cybersource_common');
		$general_configuration = $this->model_extension_payment_cybersource_common->getGeneralConfiguration();
		$unifiedcheckout_configuration = $this->model_extension_payment_cybersource_common->getUnifiedCheckoutConfiguration();
		$session_id = (VAL_ONE == $general_configuration['dfp']) ? TypeConversion::convertDataToType($general_configuration['session_id'], 'string') : VAL_EMPTY;
		if (VAL_ONE == $unifiedcheckout_configuration['payer_auth']) {
			$action_list = TypeConversion::convertArrayToType(array("VALIDATE_CONSUMER_AUTHENTICATION", $general_configuration['fraud_status']), array('string'));
			$consumer_authentication_information = array(
				"authenticationTransactionId" => TypeConversion::convertDataToType($auth_transaction_id, 'string'),
				"signedPares" => TypeConversion::convertDataToType($signed_pareq, 'string')
			);
		} else {
			$action_list = TypeConversion::convertArrayToType(array($general_configuration['fraud_status']), array('string'));
		}
		$client_reference_info = $this->model_extension_payment_cybersource_common->getClientReferenceInfo($order_id);
		$payload = array(
			"clientReferenceInformation" => $client_reference_info,
			"processingInformation" => array(
				"capture" => TypeConversion::convertDataToType($unifiedcheckout_configuration['capture'], 'boolean'),
				"actionList" => $action_list
			),
			"paymentInformation" => array(
				"paymentInstrument" => array(
					"id" => TypeConversion::convertDataToType($saved_card_tokens['payment_instrument_id'], 'string')
				),
				"customer" => array(
					"id" => TypeConversion::convertDataToType($saved_card_tokens['customer_token_id'], 'string')
				),
				"card" => array(
					"securityCode" => TypeConversion::convertDataToType($security_code, 'string'),
					"typeSelectionIndicator" => TypeConversion::convertDataToType(VAL_ONE, 'string'),
				)
			),
			"orderInformation" => array(
				"billTo" => array(
					"firstName" => TypeConversion::convertDataToType($payload_data['payment_firstname'], 'string'),
					"lastName" => TypeConversion::convertDataToType($payload_data['payment_lastname'], 'string'),
					"address1" => TypeConversion::convertDataToType($payload_data['payment_address_1'], 'string'),
					"postalCode" => TypeConversion::convertDataToType($payload_data['payment_postcode'], 'string'),
					"locality" => TypeConversion::convertDataToType($payload_data['payment_city'], 'string'),
					"administrativeArea" => TypeConversion::convertDataToType($payload_data['payment_zone_code'], 'string'),
					"country" => TypeConversion::convertDataToType($payload_data['payment_iso_code_2'], 'string'),
					"phoneNumber" => TypeConversion::convertDataToType($payload_data['telephone'], 'string'),
					"email" => TypeConversion::convertDataToType($payload_data['email'], 'string')
				),
				"shipTo" => array(
					"firstName" => TypeConversion::convertDataToType($payload_data['shipping_firstname'], 'string'),
					"lastName" => TypeConversion::convertDataToType($payload_data['shipping_lastname'], 'string'),
					"address1" => TypeConversion::convertDataToType($payload_data['shipping_address_1'], 'string'),
					"postalCode" => TypeConversion::convertDataToType($payload_data['shipping_postcode'], 'string'),
					"locality" => TypeConversion::convertDataToType($payload_data['shipping_city'], 'string'),
					"administrativeArea" => TypeConversion::convertDataToType($payload_data['shipping_zone_code'], 'string'),
					"country" => TypeConversion::convertDataToType($payload_data['shipping_iso_code_2'], 'string'),
					"phoneNumber" => TypeConversion::convertDataToType($payload_data['telephone'], 'string'),
					"email" => TypeConversion::convertDataToType($payload_data['email'], 'string')
				),
				"lineItems" => $line_items,
				"amountDetails" => array(
					"totalAmount" => TypeConversion::convertDataToType($payload_data['total'], 'string'),
					"currency" => TypeConversion::convertDataToType($payload_data['currency_code'], 'string')
				),
			),
			"deviceInformation" => array(
				"fingerprintSessionId" => $session_id
			),
		);
		if (VAL_ONE == $unifiedcheckout_configuration['payer_auth']) {
			$payload['consumerAuthenticationInformation'] = $consumer_authentication_information;
		}
		$payload = json_encode($payload);
		$resource = RESOURCE_PTS_V2_PAYMENTS;
		$api_response = $this->model_extension_payment_cybersource_common->serviceProcessor($payload, $resource, true, VAL_EMPTY);
		return $api_response;
	}

	/**
	 * Payload will get created with necessary data and send that to 3ds Unified Checkout end point.
	 *
	 * @param array $payload_data It will have data that are needed to create payload
	 * @param array $line_items It will have line items details
	 * @param int $order_id order id
	 * @param mixed $jti
	 * @param mixed $rid
	 * @param mixed $is_save_card is card needs to be saved or not
	 * @param mixed $customer_token_id It will have customer token id if presents in db
	 * @param mixed $return_url
	 *
	 * @return array
	 */
	public function getCheckPayerAuthResponse(array $payload_data, array $line_items, int $order_id, $jti, $rid, $is_save_card, $customer_token_id, $return_url): array {
		$action_token_types = VAL_EMPTY;
		$customer = VAL_EMPTY;
		$this->load->model('extension/payment/cybersource_common');
		$unifiedcheckout_configuration = $this->model_extension_payment_cybersource_common->getUnifiedCheckoutConfiguration();
		$general_configuration = $this->model_extension_payment_cybersource_common->getGeneralConfiguration();
		$session_id = (VAL_ONE == $general_configuration['dfp']) ? TypeConversion::convertDataToType($general_configuration['session_id'], 'string') : VAL_EMPTY;
		$client_reference_info = $this->model_extension_payment_cybersource_common->getClientReferenceInfo($order_id);
		$challenge_code = $unifiedcheckout_configuration['payer_auth_challenge'];
		$enroll_check = $this->session->data['enroll_check'] ?? VAL_NULL;
		if ($enroll_check || ($challenge_code && VAL_TRUE == $is_save_card)) {
			$consumer_authentication_information = array(
				"returnUrl"  => TypeConversion::convertDataToType($return_url, 'string'),
				"acsWindowSize" => VAL_ZERO_ONE,
				"referenceId" => TypeConversion::convertDataToType($rid, 'string'),
				"challengeCode" => VAL_ZERO_FOUR
			);
			$this->session->data['enroll_check'] = VAL_ZERO_ONE;
		} else {
			$consumer_authentication_information = array(
				"returnUrl"  => TypeConversion::convertDataToType($return_url, 'string'),
				"acsWindowSize" => VAL_ZERO_ONE,
				"referenceId" => TypeConversion::convertDataToType($rid, 'string')
			);
		}
		if (VAL_TRUE == $is_save_card) {
			$action_list = TypeConversion::convertArrayToType(array("CONSUMER_AUTHENTICATION", $general_configuration['fraud_status'], "TOKEN_CREATE"), array('string'));
			if (!empty($customer_token_id)) {
				$action_token_types = TypeConversion::convertArrayToType(array("paymentInstrument", "instrumentIdentifier"), array('string'));
				$customer = array(
					"id" => TypeConversion::convertDataToType($customer_token_id, 'string')
				);
			} else {
				$action_token_types = TypeConversion::convertArrayToType(array("customer", "paymentInstrument", "instrumentIdentifier"), array('string'));
			}
			$processing_information = array(
				"capture" => TypeConversion::convertDataToType($unifiedcheckout_configuration['capture'], 'boolean'),
				"actionList" => $action_list,
				"actionTokenTypes" => $action_token_types
			);
		} else {
			$action_list = TypeConversion::convertArrayToType(array("CONSUMER_AUTHENTICATION", $general_configuration['fraud_status']), array('string'));
			$processing_information = array(
				"capture" => TypeConversion::convertDataToType($unifiedcheckout_configuration['capture'], 'boolean'),
				"actionList" => $action_list,
			);
		}

		if ((empty($customer_token_id)) && (VAL_TRUE == $is_save_card  || VAL_FALSE == $is_save_card)) {
			$payload = array(
				"clientReferenceInformation" => $client_reference_info,
				"processingInformation" => $processing_information,
				"paymentInformation" => array(
					"card" => array(
						"typeSelectionIndicator" => TypeConversion::convertDataToType(VAL_ONE, 'string'),
					)
				),
				"orderInformation" => array(
					"billTo" => array(
						"firstName" => TypeConversion::convertDataToType($payload_data['payment_firstname'], 'string'),
						"lastName" => TypeConversion::convertDataToType($payload_data['payment_lastname'], 'string'),
						"address1" => TypeConversion::convertDataToType($payload_data['payment_address_1'], 'string'),
						"postalCode" => TypeConversion::convertDataToType($payload_data['payment_postcode'], 'string'),
						"locality" => TypeConversion::convertDataToType($payload_data['payment_city'], 'string'),
						"administrativeArea" => TypeConversion::convertDataToType($payload_data['payment_zone_code'], 'string'),
						"country" => TypeConversion::convertDataToType($payload_data['payment_iso_code_2'], 'string'),
						"phoneNumber" => TypeConversion::convertDataToType($payload_data['telephone'], 'string'),
						"email" => TypeConversion::convertDataToType($payload_data['email'], 'string')
					),
					"shipTo" => array(
						"firstName" => TypeConversion::convertDataToType($payload_data['shipping_firstname'], 'string'),
						"lastName" => TypeConversion::convertDataToType($payload_data['shipping_lastname'], 'string'),
						"address1" => TypeConversion::convertDataToType($payload_data['shipping_address_1'], 'string'),
						"postalCode" => TypeConversion::convertDataToType($payload_data['shipping_postcode'], 'string'),
						"locality" => TypeConversion::convertDataToType($payload_data['shipping_city'], 'string'),
						"administrativeArea" => TypeConversion::convertDataToType($payload_data['shipping_zone_code'], 'string'),
						"country" => TypeConversion::convertDataToType($payload_data['shipping_iso_code_2'], 'string'),
						"phoneNumber" => TypeConversion::convertDataToType($payload_data['telephone'], 'string'),
						"email" => TypeConversion::convertDataToType($payload_data['email'], 'string')
					),
					"lineItems" => $line_items,
					"amountDetails" => array(
						"totalAmount" => TypeConversion::convertDataToType($payload_data['total'], 'string'),
						"currency" => TypeConversion::convertDataToType($payload_data['currency_code'], 'string')
					),
				),
				"tokenInformation" => array(
					"transientToken" => TypeConversion::convertDataToType($jti, 'string')
				),
				"deviceInformation" => array(
					"fingerprintSessionId" => $session_id
				),
				"consumerAuthenticationInformation" => $consumer_authentication_information,
			);
		} else {
			$payload = array(
				"clientReferenceInformation" => $client_reference_info,
				"processingInformation" => $processing_information,
				"paymentInformation" => array(
					"customer" => $customer,
					"card" => array(
						"typeSelectionIndicator" => TypeConversion::convertDataToType(VAL_ONE, 'string'),
					)
				),
				"orderInformation" => array(
					"billTo" => array(
						"firstName" => TypeConversion::convertDataToType($payload_data['payment_firstname'], 'string'),
						"lastName" => TypeConversion::convertDataToType($payload_data['payment_lastname'], 'string'),
						"address1" => TypeConversion::convertDataToType($payload_data['payment_address_1'], 'string'),
						"postalCode" => TypeConversion::convertDataToType($payload_data['payment_postcode'], 'string'),
						"locality" => TypeConversion::convertDataToType($payload_data['payment_city'], 'string'),
						"administrativeArea" => TypeConversion::convertDataToType($payload_data['payment_zone_code'], 'string'),
						"country" => TypeConversion::convertDataToType($payload_data['payment_iso_code_2'], 'string'),
						"phoneNumber" => TypeConversion::convertDataToType($payload_data['telephone'], 'string'),
						"email" => TypeConversion::convertDataToType($payload_data['email'], 'string')
					),
					"shipTo" => array(
						"firstName" => TypeConversion::convertDataToType($payload_data['shipping_firstname'], 'string'),
						"lastName" => TypeConversion::convertDataToType($payload_data['shipping_lastname'], 'string'),
						"address1" => TypeConversion::convertDataToType($payload_data['shipping_address_1'], 'string'),
						"postalCode" => TypeConversion::convertDataToType($payload_data['shipping_postcode'], 'string'),
						"locality" => TypeConversion::convertDataToType($payload_data['shipping_city'], 'string'),
						"administrativeArea" => TypeConversion::convertDataToType($payload_data['shipping_zone_code'], 'string'),
						"country" => TypeConversion::convertDataToType($payload_data['shipping_iso_code_2'], 'string'),
						"phoneNumber" => TypeConversion::convertDataToType($payload_data['telephone'], 'string'),
						"email" => TypeConversion::convertDataToType($payload_data['email'], 'string')
					),
					"lineItems" => $line_items,
					"amountDetails" => array(
						"totalAmount" => TypeConversion::convertDataToType($payload_data['total'], 'string'),
						"currency" => TypeConversion::convertDataToType($payload_data['currency_code'], 'string')
					),
				),
				"tokenInformation" => array(
					"transientToken" => TypeConversion::convertDataToType($jti, 'string')
				),
				"deviceInformation" => array(
					"fingerprintSessionId" => $session_id
				),
				"consumerAuthenticationInformation" => $consumer_authentication_information,
			);
		}
		$payload = $this->payerAuthMandatePayloadFields($payload, $payload_data);
		$payload = json_encode($payload, JSON_UNESCAPED_SLASHES);
		$resource = RESOURCE_PTS_V2_PAYMENTS;
		$response = $this->model_extension_payment_cybersource_common->serviceProcessor($payload, $resource, true, VAL_EMPTY);
		return $response;
	}

	/**
	 * Payload will get created with necessary data and send that to 3ds Unified Checkout end point.
	 *
	 * @param array $payload_data It will have data that are needed to create payload
	 * @param array $line_items It will have line items details
	 * @param array $saved_card_tokens tokens are stored in db
	 * @param string $security_code user entered cvv
	 * @param int $order_id order id
	 * @param mixed $rid
	 * @param string $return_url
	 *
	 * @return array
	 */
	public function getSavedCardEnrollResponse(array $payload_data, array $line_items, array $saved_card_tokens, string $security_code, int $order_id, $rid, string $return_url): array {
		$this->load->model('extension/payment/cybersource_common');
		$general_configuration = $this->model_extension_payment_cybersource_common->getGeneralConfiguration();
		$unifiedcheckout_configuration = $this->model_extension_payment_cybersource_common->getUnifiedCheckoutConfiguration();
		$session_id = (VAL_ONE == $general_configuration['dfp']) ? TypeConversion::convertDataToType($general_configuration['session_id'], 'string') : VAL_EMPTY;
		$action_list = TypeConversion::convertArrayToType(array("CONSUMER_AUTHENTICATION", $general_configuration['fraud_status']), array('string'));
		$client_reference_info = $this->model_extension_payment_cybersource_common->getClientReferenceInfo($order_id);
		$enroll_check = $this->session->data['enroll_check'] ?? VAL_NULL;
		if ($enroll_check) {
			$consumer_authentication_information = array(
				"returnUrl"  => TypeConversion::convertDataToType($return_url, 'string'),
				"acsWindowSize" => VAL_ZERO_ONE,
				"referenceId" => TypeConversion::convertDataToType($rid, 'string'),
				"challengeCode" => VAL_ZERO_FOUR
			);
		} else {
			$consumer_authentication_information = array(
				"returnUrl"  => TypeConversion::convertDataToType($return_url, 'string'),
				"acsWindowSize" => VAL_ZERO_ONE,
				"referenceId" => TypeConversion::convertDataToType($rid, 'string')
			);
		}

		$payload = array(
			"clientReferenceInformation" => $client_reference_info,
			"processingInformation" => array(
				"capture" => TypeConversion::convertDataToType($unifiedcheckout_configuration['capture'], 'boolean'),
				"actionList" => $action_list
			),
			"paymentInformation" => array(
				"paymentInstrument" => array(
					"id" => TypeConversion::convertDataToType($saved_card_tokens['payment_instrument_id'], 'string')
				),
				"customer" => array(
					"id" => TypeConversion::convertDataToType($saved_card_tokens['customer_token_id'], 'string')
				),
				"card" => array(
					"securityCode" => TypeConversion::convertDataToType($security_code, 'string'),
					"typeSelectionIndicator" => TypeConversion::convertDataToType(VAL_ONE, 'string'),
				),
			),
			"orderInformation" => array(
				"billTo" => array(
					"firstName" => TypeConversion::convertDataToType($payload_data['payment_firstname'], 'string'),
					"lastName" => TypeConversion::convertDataToType($payload_data['payment_lastname'], 'string'),
					"address1" => TypeConversion::convertDataToType($payload_data['payment_address_1'], 'string'),
					"postalCode" => TypeConversion::convertDataToType($payload_data['payment_postcode'], 'string'),
					"locality" => TypeConversion::convertDataToType($payload_data['payment_city'], 'string'),
					"administrativeArea" => TypeConversion::convertDataToType($payload_data['payment_zone_code'], 'string'),
					"country" => TypeConversion::convertDataToType($payload_data['payment_iso_code_2'], 'string'),
					"phoneNumber" => TypeConversion::convertDataToType($payload_data['telephone'], 'string'),
					"email" => TypeConversion::convertDataToType($payload_data['email'], 'string')
				),
				"shipTo" => array(
					"firstName" => TypeConversion::convertDataToType($payload_data['shipping_firstname'], 'string'),
					"lastName" => TypeConversion::convertDataToType($payload_data['shipping_lastname'], 'string'),
					"address1" => TypeConversion::convertDataToType($payload_data['shipping_address_1'], 'string'),
					"postalCode" => TypeConversion::convertDataToType($payload_data['shipping_postcode'], 'string'),
					"locality" => TypeConversion::convertDataToType($payload_data['shipping_city'], 'string'),
					"administrativeArea" => TypeConversion::convertDataToType($payload_data['shipping_zone_code'], 'string'),
					"country" => TypeConversion::convertDataToType($payload_data['shipping_iso_code_2'], 'string'),
					"phoneNumber" => TypeConversion::convertDataToType($payload_data['telephone'], 'string'),
					"email" => TypeConversion::convertDataToType($payload_data['email'], 'string')
				),
				"lineItems" => $line_items,
				"amountDetails" => array(
					"totalAmount" => TypeConversion::convertDataToType($payload_data['total'], 'string'),
					"currency" => TypeConversion::convertDataToType($payload_data['currency_code'], 'string')
				),
			),
			"deviceInformation" => array(
				"fingerprintSessionId" => $session_id
			),
			"consumerAuthenticationInformation" => $consumer_authentication_information,
		);
		$payload = $this->payerAuthMandatePayloadFields($payload, $payload_data);
		$payload = json_encode($payload, JSON_UNESCAPED_SLASHES);
		$resource = RESOURCE_PTS_V2_PAYMENTS;
		$api_response = $this->model_extension_payment_cybersource_common->serviceProcessor($payload, $resource, true, VAL_EMPTY);
		return $api_response;
	}

	/**
	 * Payload will get created with necessary data and send that to 3ds Unified Checkout end point.
	 *
	 * @param int $order_id order id
	 * @param mixed $jti
	 * @param mixed $is_save_card
	 * @param string $customer_token_id It will have customer token id if presents in db
	 * @param string $saved_card
	 *
	 * @return array
	 */
	private function createSetUpRequest(int $order_id, $jti, $is_save_card, ?string $customer_token_id, string $saved_card): array {
		$response_array = VAL_NULL;
		$status = VAL_NULL;
		$this->load->model('extension/payment/cybersource_common');
		$client_reference_info = $this->model_extension_payment_cybersource_common->getClientReferenceInfo($order_id);

		if (!empty($customer_token_id) && (VAL_TRUE == $is_save_card || VAL_FLAG_YES == $saved_card)) {
			$payload = array(
				"clientReferenceInformation" => $client_reference_info,
				"paymentInformation" => array(
					"customer" => array(
						"customerId" => TypeConversion::convertDataToType($customer_token_id, 'string')
					)
				),
			);
		} else {
			$payload = array(
				"clientReferenceInformation" => $client_reference_info,
				"tokenInformation" => array(
					"transientToken" => TypeConversion::convertDataToType($jti, 'string')
				),
			);
		}
		$payload = json_encode($payload);
		$resource = RESOURCE_PAYER_AUTH_SETUP;
		$api_response = $this->model_extension_payment_cybersource_common->serviceProcessor($payload, $resource, true, VAL_EMPTY);
		if (VAL_NULL != $api_response['body']) {
			$response_data = json_decode($api_response['body']);
			$status = $response_data->status;
		}
		if (API_STATUS_COMPLETED == $status) {
			$response_array['data_collection_url'] = $response_data->consumerAuthenticationInformation->deviceDataCollectionUrl;
			$response_array['access_token'] = $response_data->consumerAuthenticationInformation->accessToken;
			$response_array['reference_id'] = $response_data->consumerAuthenticationInformation->referenceId;
			$response_array['status'] = $response_data->status;
			$response_array[STATUS_SUCCESS] = true;
		} else {
			$this->model_extension_payment_cybersource_common->unsetSessionData();
			$response_array[STATUS_SUCCESS] = false;
		}
		return $response_array;
	}

	/**
	 * Updates if card present already or else inserts data to tokenization table.
	 *
	 * @param string $transaction_id trasaction id from response
	 * @param array $card_data card data from ui
	 * @param array $card_tokens card token from db
	 * @param int $address_id address id
	 * @param int $customer_id customer id
	 *
	 * @return array
	 */
	public function updateTokenizationTable(string $transaction_id, array $card_data, array $card_tokens, int $address_id, int $customer_id): array {
		$not_same_card = false;
		$saved_cards = array();
		$result = array();
		$this->load->model('extension/payment/cybersource_query');
		$query_saved_cards = $this->model_extension_payment_cybersource_query->querySavedCardToken($customer_id);
		if (VAL_NULL != $query_saved_cards) {
			$saved_card_count = $query_saved_cards->num_rows;
			for ($i = VAL_ZERO; $i < $saved_card_count; $i++) {
				$saved_cards['' . $i . ''] = array(
					"address_id" => $query_saved_cards->rows['' . $i . '']['address_id'],
					"card_number" => $query_saved_cards->rows['' . $i . '']['card_number'],
					"expiry_month" => $query_saved_cards->rows['' . $i . '']['expiry_month'],
					"expiry_year" => $query_saved_cards->rows['' . $i . '']['expiry_year'],
					"instrument_identifier_id" => $query_saved_cards->rows['' . $i . '']['instrument_identifier_id'],
					"customer_token_id" => $query_saved_cards->rows['' . $i . '']['customer_token_id']
				);
			}
		}
		if (!empty($saved_cards)) {
			$size_of_saved_card = sizeof($saved_cards);
			for ($i = VAL_ZERO; $i < $size_of_saved_card; $i++) {
				if (($saved_cards['' . $i . '']['card_number'] == $card_data['card_number'])
					&& ($saved_cards['' . $i . '']['customer_token_id'] == $card_tokens['customer_token_id'])
					&& ($saved_cards['' . $i . '']['instrument_identifier_id'] == $card_tokens['instrument_identifier_id'])
				) {
					$not_same_card = false;
					$query_response = $this->model_extension_payment_cybersource_query->queryUpdateTokenization($address_id, $card_tokens, $card_data, $customer_id, $saved_cards[$i]);
					if (VAL_NULL != $query_response) {
						$result = $query_response;
					}
					if (!$result) {
						$result = array(
							IS_ADDED => false,
							IS_UPDATED => false,
							IS_FAILED => true
						);
					} else {
						$result = array(
							IS_ADDED => false,
							IS_UPDATED => true,
							IS_FAILED => false
						);
					}
					break;
				} else {
					$not_same_card = true;
				}
			}
			if ($not_same_card) {
				$default_state = VAL_ZERO;
				$insert_data = array(
					'transaction_id' => $transaction_id,
					'customer_name' => $this->getCustomerName(),
					'customer_id' => $customer_id,
					'address_id' => $address_id,
					'card_number' => $card_data['card_number'],
					'expiry_month' => $card_data['expiry_month'],
					'expiry_year' => $card_data['expiry_year'],
					'payment_instrument_id' => $card_tokens['payment_instrument_id'],
					'instrument_identifier_id' => $card_tokens['instrument_identifier_id'],
					'customer_token_id' => $card_tokens['customer_token_id'],
					'default_state' => $default_state,
					'date_added' => CURRENT_DATE
				);
				$result = $this->insertTokenizationTable($insert_data);
			}
		} else {
			$default_state = VAL_ONE;
			$insert_data = array(
				'transaction_id' => $transaction_id,
				'customer_name' => $this->getCustomerName(),
				'customer_id' => $customer_id,
				'address_id' => $address_id,
				'card_number' => $card_data['card_number'],
				'expiry_month' => $card_data['expiry_month'],
				'expiry_year' => $card_data['expiry_year'],
				'payment_instrument_id' => $card_tokens['payment_instrument_id'],
				'instrument_identifier_id' => $card_tokens['instrument_identifier_id'],
				'customer_token_id' => $card_tokens['customer_token_id'],
				'default_state' => $default_state,
				'date_added' => CURRENT_DATE
			);
			$result = $this->insertTokenizationTable($insert_data);
		}
		return $result;
	}

	/**
	 * Payload will get created with necessary data and send that to saved card update card end point.
	 *
	 * @param array $address_data contains new address data if customer choose new address to update
	 * @param array $card_details card details
	 * @param array $card_info card info from db
	 *
	 * @return array
	 */
	public function updatePaymentToken(?array $address_data, array $card_details, array $card_info): array {
		$this->load->model('extension/payment/cybersource_common');
		$customer_email_id = $this->customer->getEmail();
		$payload = array(
			"card" => array(
				"expirationMonth" => TypeConversion::convertDataToType($card_details['expiration_month'], 'string'),
				"expirationYear" => TypeConversion::convertDataToType($card_details['expiration_year'], 'string')
			),
			"billTo" => array(
				"firstName" => TypeConversion::convertDataToType($address_data['firstname'], 'string'),
				"lastName" => TypeConversion::convertDataToType($address_data['lastname'], 'string'),
				"address1" => TypeConversion::convertDataToType($address_data['address_1'], 'string'),
				"locality" => TypeConversion::convertDataToType($address_data['city'], 'string'),
				"administrativeArea" => TypeConversion::convertDataToType($address_data['zone_code'], 'string'),
				"postalCode" => TypeConversion::convertDataToType($address_data['postcode'], 'string'),
				"country" => TypeConversion::convertDataToType($address_data['iso_code_2'], 'string'),
				"email" => TypeConversion::convertDataToType($customer_email_id, 'string')
			),
			"instrumentIdentifier" => array(
				"id" => TypeConversion::convertDataToType($card_info['instrument_identifier_id'], 'string')
			)
		);
		$payload = json_encode($payload);
		$resource = RESOURCE_TMS_V2_CUSTOMERS . $card_info['customer_token_id'] . RESOURCE_PAYMENT_INSTRUMENTS . $card_info['payment_instrument_id'];
		$api_response = $this->model_extension_payment_cybersource_common->serviceProcessor($payload, $resource, false, UPDATE_CARD);
		return $api_response;
	}

	/**
	 * Send request to saved card delete end point.
	 *
	 * @param array $card_token card token from db
	 *
	 * @return array
	 */
	public function deleteSavedCard(array $card_token): array {
		$this->load->model('extension/payment/cybersource_common');
		$payload = VAL_EMPTY;
		$resource = RESOURCE_TMS_V2_CUSTOMERS . $card_token['customer_token_id'] . RESOURCE_PAYMENT_INSTRUMENTS . $card_token['payment_instrument_id'];
		$api_response = $this->model_extension_payment_cybersource_common->serviceProcessor($payload, $resource, true, HTTP_METHOD_DELETE);
		return $api_response;
	}

	/**
	 * Payload will get created with necessary data and send that to saved card set default card end point.
	 *
	 * @param array $card_tokens card token from db
	 *
	 * @return array
	 */
	public function setDefaultCard(array $card_tokens): array {
		$this->load->model('extension/payment/cybersource_common');
		$payload = array(
			'default' => true
		);
		$payload = json_encode($payload);
		$resource = RESOURCE_TMS_V2_CUSTOMERS . $card_tokens['customer_token_id'] . RESOURCE_PAYMENT_INSTRUMENTS . $card_tokens['payment_instrument_id'];
		$api_response = $this->model_extension_payment_cybersource_common->serviceProcessor($payload, $resource, false, UPDATE_CARD);
		return $api_response;
	}

	/**
	 * Payload will get created with necessary data and send that to Unified Checkout end point.
	 *
	 * @param string $transient_token token generated my flex micro from
	 * @param string $currency store defualt currency
	 * @param int|null $address customer selected address
	 * @param int $customer_id customer id
	 *
	 * @return array
	 */
	public function addCard(string $transient_token, string $currency, ?int $address_id, int $customer_id): array {
		$this->load->model('extension/payment/cybersource_common');
		$this->load->model('account/customer');
		$address = $this->getAddressById($address_id);
		$general_configuration = $this->model_extension_payment_cybersource_common->getGeneralConfiguration();
		$session_id = (VAL_ONE == $general_configuration['dfp']) ? TypeConversion::convertDataToType($general_configuration['session_id'], 'string') : VAL_EMPTY;
		$action_list = TypeConversion::convertArrayToType(array($general_configuration['fraud_status'], TOKEN_CREATE), array('string'));
		$customer_data = $this->model_account_customer->getCustomer($customer_id);
		$merchant_ref = $this->model_extension_payment_cybersource_common->generateMerchantRef();
		$customer_token_id = $this->model_extension_payment_cybersource_common->getCustomerTokenId($customer_id);
		$client_reference_info = $this->model_extension_payment_cybersource_common->getClientReferenceInfo($merchant_ref);
		if (!empty($customer_token_id)) {
			$payload = array(
				"clientReferenceInformation" => $client_reference_info,
				"processingInformation" => array(
					"actionList" => $action_list,
					"actionTokenTypes" => TypeConversion::convertArrayToType(array("paymentInstrument", "instrumentIdentifier"), array('string'))
				),
				"paymentInformation" => array(
					"customer" => array(
						"id" => TypeConversion::convertDataToType($customer_token_id, 'string')
					)
				),
				"orderInformation" => array(
					"billTo" => array(
						"firstName" => TypeConversion::convertDataToType($address['firstname'], 'string'),
						"lastName" => TypeConversion::convertDataToType($address['lastname'], 'string'),
						"address1" => TypeConversion::convertDataToType($address['address_1'], 'string'),
						"postalCode" => TypeConversion::convertDataToType($address['postcode'], 'string'),
						"locality" => TypeConversion::convertDataToType($address['city'], 'string'),
						"administrativeArea" => TypeConversion::convertDataToType($address['zone_code'], 'string'),
						"country" => TypeConversion::convertDataToType($address['iso_code_2'], 'string'),
						"phoneNumber" => TypeConversion::convertDataToType($customer_data['telephone'], 'string'),
						"email" => TypeConversion::convertDataToType($customer_data['email'], 'string')
					),
					"amountDetails" => array(
						"totalAmount" => VAL_ZERO_POINT_ZERO_ZERO,
						"currency" => TypeConversion::convertDataToType($currency, 'string')
					),
				),
				"tokenInformation" => array(
					"transientTokenJwt" => TypeConversion::convertDataToType($transient_token, 'string')
				),
				"deviceInformation" => array(
					"fingerprintSessionId" => $session_id
				),
			);
		} else {
			$payload = array(
				"clientReferenceInformation" => $client_reference_info,
				"processingInformation" => array(
					"actionList" => $action_list,
					"actionTokenTypes" => TypeConversion::convertArrayToType(array("customer", "paymentInstrument", "instrumentIdentifier"), array('string'))
				),
				"orderInformation" => array(
					"billTo" => array(
						"firstName" => TypeConversion::convertDataToType($address['firstname'], 'string'),
						"lastName" => TypeConversion::convertDataToType($address['lastname'], 'string'),
						"address1" => TypeConversion::convertDataToType($address['address_1'], 'string'),
						"postalCode" => TypeConversion::convertDataToType($address['postcode'], 'string'),
						"locality" => TypeConversion::convertDataToType($address['city'], 'string'),
						"administrativeArea" => TypeConversion::convertDataToType($address['zone_code'], 'string'),
						"country" => TypeConversion::convertDataToType($address['iso_code_2'], 'string'),
						"phoneNumber" => TypeConversion::convertDataToType($customer_data['telephone'], 'string'),
						"email" => TypeConversion::convertDataToType($customer_data['email'], 'string')
					),
					"amountDetails" => array(
						"totalAmount" => VAL_ZERO_POINT_ZERO_ZERO,
						"currency" => TypeConversion::convertDataToType($currency, 'string')
					),
				),
				"tokenInformation" => array(
					"transientTokenJwt" => TypeConversion::convertDataToType($transient_token, 'string')
				),
				"deviceInformation" => array(
					"fingerprintSessionId" => $session_id
				)
			);
		}
		$payload = json_encode($payload);
		$resource = RESOURCE_PTS_V2_PAYMENTS;
		$api_response = $this->model_extension_payment_cybersource_common->serviceProcessor($payload, $resource, true, VAL_EMPTY);
		return $api_response;
	}

	/**
	 * Gives breadcrumbs for my cards and update cards ui sections.
	 *
	 * @return array
	 */
	public function getBreadcrumbs(): array {
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_account'),
			'href' => $this->url->link('account/account', '', true)
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/credit_card/cybersource', '', true)
		);
		return $data['breadcrumbs'];
	}

	/**
	 * Updates saved failed card count.
	 *
	 * @param int $customer_id customer id
	 * @param string $current_date_time current date time
	 * @param string $msg error message
	 *
	 * @return string|null
	 */
	public function updateSaveFailedCard(int $customer_id, string $current_date_time, string $msg): ?string {
		$this->load->model('extension/payment/cybersource_common');
		$this->load->language('extension/credit_card/cybersource');
		$error_msg = VAL_NULL;
		$save_failed_card = $this->saveFailedCard($customer_id, $current_date_time);
		if ($save_failed_card) {
			$error_msg = $this->language->get($msg);
		} else {
			$this->model_extension_payment_cybersource_common->logger("[ModelExtensionPaymentCybersource][confirm] Failed to update db token_check.");
		}
		return $error_msg;
	}

	/**
	 * Gives header and footer for my cards and update cards ui.
	 *
	 * @param string $back_button_url contians back button url
	 *
	 * @return array
	 */
	public function getHeaderFooter(string $back_button_url): array {
		$data['back'] = $this->url->link($back_button_url, VAL_EMPTY, true);
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');
		return $data;
	}

	/**
	 * Load scripts for address part in my cards ui section.
	 */
	public function loadScript() {
		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment-with-locales.min.js');
		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js');
	}

	/**
	 * @param int $order_id
	 * @param mixed $jti
	 * @param mixed $is_save_card
	 * @param string|null $customer_token_id
	 * @param string $saved_card
	 *
	 * @return [type]
	 */
	public function payerAuthCommon(int $order_id, $jti, $is_save_card, ?string $customer_token_id, string $saved_card) {
		$this->load->model('extension/payment/cybersource_common');
		$response_array = $this->createSetUpRequest(
			$order_id,
			$jti,
			$is_save_card,
			$customer_token_id,
			$saved_card
		);
		if ($response_array[STATUS_SUCCESS]) {
			$this->session->data['reference_id'] = $response_array['reference_id'];
			$data['data_collection_url'] = $response_array['data_collection_url'];
			$data['access_token'] = $response_array['access_token'];
			$data['saved_card'] = $saved_card;
			$data['status'] = $response_array['status'];
		} else {
			$data['redirect'] = $this->model_extension_payment_cybersource_common->getReturnUrl(VAL_EMPTY, STATUS_FAILURE);
		}
		return $data;
	}

	/**
	 * Payer Auth mandate payload fields.
	 *
	 * @param array $payload
	 * @param array $payload_data
	 *
	 * @return array
	 */
	private function payerAuthMandatePayloadFields(array $payload, array $payload_data): array {
		$payload['deviceInformation']['ipAddress'] = TypeConversion::convertDataToType($payload_data['ip'], 'string');
		$payload['deviceInformation']['httpBrowserJavaEnabled'] = TypeConversion::convertDataToType($payload_data['browser_java_enabled'], 'boolean');
		$payload['deviceInformation']['httpAcceptBrowserValue'] = TypeConversion::convertDataToType($payload_data['HTTP_ACCEPT'], 'string');
		$payload['deviceInformation']['httpBrowserLanguage'] = TypeConversion::convertDataToType($payload_data['browser_language'], 'string');
		$payload['deviceInformation']['httpBrowserColorDepth'] = TypeConversion::convertDataToType($payload_data['browser_color_depth'], 'string');
		$payload['deviceInformation']['httpBrowserScreenHeight'] = TypeConversion::convertDataToType($payload_data['browser_screen_height'], 'string');
		$payload['deviceInformation']['httpBrowserScreenWidth'] = TypeConversion::convertDataToType($payload_data['browser_screen_width'], 'string');
		$payload['deviceInformation']['httpBrowserTimeDifference'] = TypeConversion::convertDataToType($payload_data['browser_time_difference'], 'string');
		$payload['deviceInformation']['userAgentBrowserValue'] = TypeConversion::convertDataToType($payload_data['user_agent'], 'string');
		$payload['deviceInformation']['httpBrowserJavaScriptEnabled'] = TypeConversion::convertDataToType(true, 'boolean');
		$payload['consumerAuthenticationInformation']['deviceChannel'] = TypeConversion::convertDataToType(DEVICE_CHANNEL_BROWSER, 'string');

		return $payload;
	}
}
