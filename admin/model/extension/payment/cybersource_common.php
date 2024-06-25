<?php

use Isv\Common\Helper\TypeConversion;

require_once DIR_SYSTEM . 'library/isv/cybersource_constants.php';

/**
 * Common Model file.
 *
 * @author Cybersource
 * @package Back Office
 * @subpackage Model
 */
class ModelExtensionPaymentCybersourceCommon extends Model {
	public function getBreadcrumbsData(string $user_token, string $extension_type, string $payment_method): array {
		$data['breadcrumbs'][] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $user_token, true)
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $user_token . '&type=' . $extension_type, true)
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/' . $extension_type . FORWARD_SLASH . $payment_method, 'user_token=' . $user_token, true)
		);
		return $data['breadcrumbs'];
	}

	public function logger(string $message) {
		if ($this->config->get('module_' . PAYMENT_GATEWAY . '_enhanced_logs')) {
			$log = new Log('cybersource.log');
			$log->write($message);
		}
	}

	public function updateVoidRefundStatus(int $order_id, string $table_name): bool {
		$query_void_id = VAL_EMPTY;
		$query_void_refund_status = false;
		$this->load->model('extension/payment/cybersource_query');
		if (!empty($order_id) && !empty($table_name)) {
			$query_void_id = $this->model_extension_payment_cybersource_query->queryVoidId($order_id, $table_name);
			if (VAL_NULL != $query_void_id && VAL_ZERO < $query_void_id->num_rows) {
				$void_id = $query_void_id->row['id'];
				$query_status = $this->model_extension_payment_cybersource_query->updateStatus($void_id, $table_name);
				if (VAL_NULL != $query_status) {
					$query_void_refund_status = $query_status;
				}
			}
		}
		return $query_void_refund_status;
	}

	public function addOrderHistory(int $order_id, ?string $order_status_id, string $custom_status, ?string $comment = '', bool $notify = false): bool {
		$query_response = false;
		$this->load->model('sale/order');
		$this->load->model('extension/payment/cybersource_query');
		$order = $this->model_sale_order->getOrder($order_id);
		$current_order_status = (int)$order['order_status_id'];
		if ($current_order_status != $order_status_id) {
			$query_update_order = $this->model_extension_payment_cybersource_query->queryUpdateOrderTable($order_status_id, $order_id);
			if (VAL_NULL != $query_update_order && $query_update_order) {
				$query_insert_order = $this->model_extension_payment_cybersource_query->queryInsertOrderHistory($order_id, $order_status_id, $notify, $comment);
				if (VAL_NULL != $query_insert_order && $query_insert_order) {
					$query_response = true;
				}
			}
		}
		if ($query_response) {
			$query_update_status = $this->model_extension_payment_cybersource_query->queryUpdateCustomOrderStatus($custom_status, $order_id);
			if (VAL_NULL != $query_update_status) {
				$query_response = $query_update_status;
			}
		}
		return $query_response;
	}

	public function getGeneralConfiguration(): array {
		$general_configuration = array(
			'request_host'                      => ENVIRONMENT_TEST == $this->config->get('module_' . PAYMENT_GATEWAY . '_sandbox') ? REQUEST_HOST_API_TEST : REQUEST_HOST_API_PRODUCTION,
			'merchant_id'                       => ENVIRONMENT_TEST == $this->config->get('module_' . PAYMENT_GATEWAY . '_sandbox') ? $this->config->get('module_' . PAYMENT_GATEWAY . '_merchant_id_test') : $this->config->get('module_' . PAYMENT_GATEWAY . '_merchant_id_live'),
			'merchant_key_id'                   => ENVIRONMENT_TEST == $this->config->get('module_' . PAYMENT_GATEWAY . '_sandbox') ? $this->config->get('module_' . PAYMENT_GATEWAY . '_merchant_key_id_test') : $this->config->get('module_' . PAYMENT_GATEWAY . '_merchant_key_id_live'),
			'merchant_secret_key'               => ENVIRONMENT_TEST == $this->config->get('module_' . PAYMENT_GATEWAY . '_sandbox') ? $this->config->get('module_' . PAYMENT_GATEWAY . '_merchant_secret_key_test') : $this->config->get('module_' . PAYMENT_GATEWAY . '_merchant_secret_key_live'),
			'sandbox'                           => $this->config->get('module_' . PAYMENT_GATEWAY . '_sandbox'),
			'fraud_status'                      => VAL_ZERO == $this->config->get('module_' . PAYMENT_GATEWAY . '_fraud_status') ? DECISION_SKIP : VAL_EMPTY,
			'developer_id'                      => $this->config->get('module_' . PAYMENT_GATEWAY . '_developer_id'),
			'dfp'                               => $this->config->get('module_' . PAYMENT_GATEWAY . '_dfp'),
			'sessionId'                         => $this->session->getId(),
			'payment_batch_detail_report'       => $this->config->get('module_' . PAYMENT_GATEWAY . '_payment_batch_detail_report'),
			'transaction_request_report'        => $this->config->get('module_' . PAYMENT_GATEWAY . '_transaction_request_report'),
			'payment_batch_detail_path'         => ENVIRONMENT_TEST == $this->config->get('module_' . PAYMENT_GATEWAY . '_sandbox') ? $this->config->get('module_' . PAYMENT_GATEWAY . '_payment_batch_detail_report_path_test') : $this->config->get('module_' . PAYMENT_GATEWAY . '_payment_batch_detail_report_path_live'),
			'transaction_request_path'          => ENVIRONMENT_TEST == $this->config->get('module_' . PAYMENT_GATEWAY . '_sandbox') ? $this->config->get('module_' . PAYMENT_GATEWAY . '_transaction_request_report_path_test') : $this->config->get('module_' . PAYMENT_GATEWAY . '_transaction_request_report_path_live')
		);
		return $general_configuration;
	}

	public function getClientReferenceInfo(int $order_id): ?array {
		$client_reference_payload = VAL_NULL;
		if (!empty($order_id)) {
			$client_reference_payload = array(
				"code" => TypeConversion::convertDataToType($order_id, 'string'),
				"partner" => array(
					"developerId" => TypeConversion::convertDataToType($this->config->get('module_' . PAYMENT_GATEWAY . '_developer_id'), 'string'),
					"solutionId" => CODE_PARTNER_SOLUTION_ID
				)
			);
		}
		return $client_reference_payload;
	}

	private function generateDigest(string $request_payload): string {
		$digest_encode = VAL_NULL;
		if (!empty($request_payload)) {
			$utf8_encoded_string = utf8_encode($request_payload);
			$digest_encode = hash(ALGORITHM_SHA256, $utf8_encoded_string, true);
		}
		return base64_encode($digest_encode);
	}

	private function getHttpSignature(string $resource_path, string $http_method, string $current_date, string $payload, string $request_host): array {
		$digest = VAL_EMPTY;
		$signature_string = VAL_EMPTY;
		$header_string = VAL_EMPTY;
		$headers = array();
		if (!empty($resource_path) && !empty($http_method) && !empty($current_date) && !empty($payload) && !empty($request_host)) {
			$general_configuration = $this->getGeneralConfiguration();
			if (HTTP_METHOD_GET == $http_method || HTTP_METHOD_DELETE == $http_method) {
				$signature_string = "host: " . $request_host . "\ndate: " . $current_date . "\nrequest-target: " . $http_method . " " . $resource_path . "\nv-c-merchant-id: " . $general_configuration['merchant_id'];
				$header_string = "host date request-target v-c-merchant-id";
			} else {
				$digest = $this->generateDigest($payload);
				$signature_string = "host: " . $request_host . "\ndate: " . $current_date . "\nrequest-target: " . $http_method . " " . $resource_path . "\ndigest: SHA-256=" . $digest . "\nv-c-merchant-id: " . $general_configuration['merchant_id'];
				$header_string = "host date request-target digest v-c-merchant-id";
			}
			$signature_byte_string = utf8_encode($signature_string);
			$decode_key = base64_decode($general_configuration['merchant_secret_key']);
			$signature = base64_encode(hash_hmac(ALGORITHM_SHA256, $signature_byte_string, $decode_key, true));
			$signature_header = array(
				'keyid="' . $general_configuration['merchant_key_id'] . '"',
				'algorithm="HmacSHA256"',
				'headers="' . $header_string . '"',
				'signature="' . $signature . '"'
			);
			$signature_token = "Signature:" . implode(", ", $signature_header);
			$host = "Host:" . $request_host;
			$vc_merchant = "v-c-merchant-id:" . $general_configuration['merchant_id'];
			$headers = array($vc_merchant, $signature_token, $host, 'Date:' . $current_date);
			if (!(HTTP_METHOD_GET == $http_method || HTTP_METHOD_DELETE == $http_method)) {
				$digest_array = array("Digest: SHA-256=" . $digest);
				$headers = array_merge($headers, $digest_array);
			}
		}
		return $headers;
	}

	private function removeScheme(string $url): string {
		$disallowed = array(PROTOCOL_HTTP, PROTOCOL_HTTPS);
		foreach ($disallowed as $scheme) {
			if (VAL_ZERO === strpos($url, $scheme)) {
				$url = str_replace($scheme, VAL_EMPTY, $url);
				break;
			}
		}
		return $url;
	}

	public function processor(string $resource, string $method): array {
		$general_configuration = $this->getGeneralConfiguration();
		$request_host = $this->removeScheme($general_configuration['request_host']);
		$url = $general_configuration['request_host'] . $resource;
		$resource_encode = utf8_encode($resource);
		$date = gmdate(DATE_D_D_M_Y_G_I_S) . DATE_GMT;
		$auth_headers = $this->getHttpSignatureGet($resource_encode, $method, $date, $request_host);
		if (RESOURCE_NOTIFICATION_SUBSCRIPTION_V1_PRODUCTS === substr($resource, VAL_ZERO, strlen(RESOURCE_NOTIFICATION_SUBSCRIPTION_V1_PRODUCTS)) || RESOURCE_NOTIFICATION_SUBSCRIPTION_V1_WEBHOOKS === substr($resource, VAL_ZERO, strlen(RESOURCE_NOTIFICATION_SUBSCRIPTION_V1_WEBHOOKS))) {
			$header_params[ACCEPT] = WEBHOOK_APPLICATION_JSON;
			$header_params[CONTENT_TYPE_SERVICE_PROCESSOR] = WEBHOOK_APPLICATION_JSON;
			foreach ($header_params as $key => $val) {
				$headers[] = "$key: $val";
			}
			$auth_headers = array_merge($headers, $auth_headers);
		}
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $auth_headers);
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HEADER, VAL_ONE);
		curl_setopt($curl, CURLOPT_VERBOSE, VAL_ZERO);
		curl_setopt($curl, CURLOPT_USERAGENT, MOZILLA_FIVE_ZERO);
		if (HTTP_METHOD_DELETE === $method) {
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
		}
		$response = curl_exec($curl);
		$response_array = $this->getResponseArray($curl, $response);
		curl_close($curl);
		return $response_array;
	}

	public function getHttpSignatureGet(string $resource, string $http_method, string $current_date, string $request_host): array {
		$general_configuration = $this->getGeneralConfiguration();
		$signature_string = "host: " . $request_host . "\ndate: " .
		$current_date . "\nrequest-target: " . $http_method . " " .
		$resource . "\nv-c-merchant-id: " . $general_configuration['merchant_id'];
		$header_string = "host date request-target v-c-merchant-id";
		$signature_byte_string = utf8_encode($signature_string);
		$decode_key = base64_decode($general_configuration['merchant_secret_key']);
		$signature = base64_encode(hash_hmac(ALGORITHM_SHA256, $signature_byte_string, $decode_key, true));
		$signature_header = array(
			'keyid="' . $general_configuration['merchant_key_id'] . '"',
			'algorithm="HmacSHA256"',
			'headers="' . $header_string . '"',
			'signature="' . $signature . '"'
		);
		$signature_token = "Signature:" . implode(", ", $signature_header);
		$host = "Host:" . $request_host;
		$vc_merchant_id = "v-c-merchant-id:" . $general_configuration['merchant_id'];
		$headers = array(
			$vc_merchant_id,
			$signature_token,
			$host,
			'Date:' . $current_date
		);
		return $headers;
	}

	public function serviceProcessor(string $payload, string $resource): array {
		$response = array();
		$method = HTTP_POST;
		$header_params = array();
		$general_configuration = $this->getGeneralConfiguration();
		$url = $general_configuration['request_host'] . $resource;
		$request_host = $this->removeScheme($general_configuration['request_host']);
		$resource_encode = utf8_encode($resource);
		$date = gmdate(DATE_D_D_M_Y_G_I_S) . DATE_GMT;
		if (RESOURCE_KMS_EGRESS_V2_KEYS_SYM === $resource || RESOURCE_NOTIFICATION_SUBSCRIPTION_V1_WEBHOOKS === $resource) {
			$header_params[ACCEPT] = WEBHOOK_APPLICATION_JSON;
			$header_params[CONTENT_TYPE_SERVICE_PROCESSOR] = WEBHOOK_APPLICATION_JSON;
		} else {
			$header_params[ACCEPT] = APPLICATION_HAL_JSON_CHAR_SET;
			$header_params[CONTENT_TYPE_SERVICE_PROCESSOR] = APPLICATION_JSON_CHAR_SET;
		}
		foreach ($header_params as $key => $val) {
			$headers[] = "$key: $val";
		}
		$auth_headers = $this->getHttpSignature($resource_encode, $method, $date, $payload, $request_host);
		$header_params = array_merge($headers, $auth_headers);
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header_params);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HEADER, VAL_ONE);
		curl_setopt($curl, CURLOPT_VERBOSE, VAL_ZERO);
		curl_setopt($curl, CURLOPT_USERAGENT, MOZILLA_FIVE_ZERO);
		$response = curl_exec($curl);
		$response = $this->getResponseArray($curl, $response);
		curl_close($curl);
		return $response;
	}

	private function getResponseArray($curl, $response): array {
		$response_array = array(
			'header' => VAL_NULL,
			'body' => VAL_NULL,
			'http_code' => VAL_ZERO
		);
		if (false != $response && false != $curl) {
			$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
			$response_array['header'] = substr($response, VAL_ZERO, $header_size);
			$response_array['body'] = substr($response, $header_size);
			$response_array['http_code'] = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		}
		return $response_array;
	}

	public function getResponse($http_code, ?string $http_body, string $service, string $payment_method): array {
		$response_array = array(
			'status' => VAL_NULL,
			'amount' => VAL_NULL,
			'currency' => VAL_NULL,
			'transaction_id' => VAL_NULL
		);
		$response_data = VAL_NULL;
		if (!empty($http_code) && !empty($http_body) && !empty($service) && !empty($payment_method)) {
			if (!empty($http_body)) {
				$response_data = json_decode($http_body);
				if (!(empty($response_data->status))) {
					$response_array['status'] = $response_data->status;
				}
				if (!(empty($response_data->id))) {
					$response_array['transaction_id'] = $response_data->id;
				}
			}
			if (CODE_TWO_ZERO_ONE == $http_code) {
				if ((SERVICE_CAPTURE == $service) && (API_STATUS_PENDING == $response_array['status'])) {
					$response_array['amount'] = $response_data->orderInformation->amountDetails->totalAmount;
					$response_array['currency'] = $response_data->orderInformation->amountDetails->currency;
				} elseif ((SERVICE_REFUND == $service) && (API_STATUS_PENDING == $response_array['status']) && (PAYMENT_GATEWAY == $payment_method || PAYMENT_GATEWAY_APPLE_PAY == $payment_method)) {
					$response_array['amount'] = $response_data->refundAmountDetails->refundAmount;
					$response_array['currency'] = $response_data->refundAmountDetails->currency;
				} elseif ((SERVICE_AUTH_REVERSAL == $service) && (API_STATUS_REVERSED == $response_array['status'])) {
					$response_array['amount'] = $response_data->reversalAmountDetails->reversedAmount;
					$response_array['currency'] = $response_data->reversalAmountDetails->currency;
				} elseif ((SERVICE_VOID == $service) && (API_STATUS_VOIDED == $response_array['status'])) {
					$response_array['amount'] = $response_data->voidAmountDetails->voidAmount;
					$response_array['currency'] = $response_data->voidAmountDetails->currency;
				}
				// As we are not getting credited amount in response
				/* else if ((SERVICE_REFUND == $service) && (API_STATUS_PENDING == $response_array['status']) && (PAYMENT_GATEWAY_ECHECK == $payment_method)) {
					$responseArray['amount'] = $response_data->creditAmountDetails->creditAmount;
					$responseArray['currency'] = $response_data->creditAmountDetails->currency;
				} */
			}
		}
		return $response_array;
	}

	public function getOrderDetails(int $order_id, string $table_name): array {
		$transaction_details = array();
		$this->load->model('extension/payment/cybersource_query');
		if (!empty($order_id) && !empty($table_name)) {
			$query_transaction_details = $this->model_extension_payment_cybersource_query->queryTransactionDetails($order_id, $table_name);
			if (VAL_NULL != $query_transaction_details && VAL_ZERO < $query_transaction_details->num_rows) {
				foreach ($query_transaction_details->rows as $row) {
					$transaction_details['order_id'] = $row['order_id'];
					$transaction_details['transaction_id'] = $row['transaction_id'];
					$transaction_details['amount'] = (float)$row['amount'];
					$transaction_details['currency'] = $row['currency'];
					$transaction_details['quantity'] = $row['order_quantity'];
				}
			}
		}
		return $transaction_details;
	}

	public function restock(int $order_id, string $service, ?string $payment_action, ?array $restock_data) {
		// Stock subtraction
		$return_response = false;
		$this->load->model('sale/order');
		$order_products = $this->model_sale_order->getOrderProducts($order_id);
		if ((SERVICE_AUTH_REVERSAL == $service) || (SERVICE_VOID_CAPTURE == $service && PAYMENT_ACTION_SALE == $payment_action)) {
			foreach ($order_products as $order_product) {
				$return_response = $this->restockProduct($order_product, $order_id, $restock_data, false);
			}
		} elseif (((SERVICE_VOID_CAPTURE == $service || SERVICE_REFUND == $service) && PAYMENT_ACTION_AUTHORIZE == $payment_action) ||
			(SERVICE_REFUND == $service && PAYMENT_ACTION_SALE == $payment_action)
		) {
			foreach ($order_products as $order_product) {
				if ($order_product['product_id'] == $restock_data['product_id']) {
					$return_response = $this->restockProduct($order_product, $order_id, $restock_data, true);
				}
			}
		}
		return $return_response;
	}

	private function restockProduct($order_product, int $order_id, ?array $restock_data, bool $is_restock) {
		$return_response = false;
		$this->load->model('sale/order');
		$this->load->model('extension/payment/cybersource_query');
		$order_data = $is_restock ? $restock_data : $order_product;
		$query_update_product = $this->model_extension_payment_cybersource_query->queryUpdateProduct($order_data);
		if (VAL_NULL != $query_update_product && $query_update_product) {
			$order_options = $this->model_sale_order->getOrderOptions($order_id, $order_product['order_product_id']);
			foreach ($order_options as $order_option) {
				$query_update_product_option = $this->model_extension_payment_cybersource_query->queryUpdateProductOption($order_data, $order_option);
				if (VAL_NULL != $query_update_product_option) {
					$return_response = $query_update_product_option;
				}
			}
		}
		return $return_response;
	}

	public function getVoidCaptureResponse(int $order_id, string $transaction_id): ?array {
		$api_response = VAL_NULL;
		if (!empty($order_id) && !empty($transaction_id)) {
			$client_reference_info = $this->getClientReferenceInfo($order_id);
			if (!empty($client_reference_info)) {
				$payload = array(
					"clientReferenceInformation" => $client_reference_info
				);
				$payload = json_encode($payload);
				$resource = RESOURCE_PTS_V2_CAPTURES . $transaction_id . RESOURCE_VOIDS;
				$api_response = $this->serviceProcessor($payload, $resource);
			}
		}
		return $api_response;
	}

	public function getOrderProductDetails(int $order_id): array {
		$product_detail_array = array();
		$this->load->model('sale/order');
		if (!empty($order_id)) {
			$product_details = $this->model_sale_order->getOrderProducts($order_id);
			$size_of_products = sizeof($product_details);
			if (!empty($product_details) && VAL_ZERO < $size_of_products) {
				for ($i = VAL_ZERO; $i < $size_of_products; $i++) {
					$product_detail_array['' . $i . ''] = array(
						"order_product_id" => $product_details['' . $i . '']['order_product_id'],
						"model" => $product_details['' . $i . '']['model'],
						"name" => $product_details['' . $i . '']['name'],
						"product_id" => $product_details['' . $i . '']['product_id'],
						"quantity" => $product_details['' . $i . '']['quantity'],
						"price" => $product_details['' . $i . '']['price'],
						"total" => $product_details['' . $i . '']['total'],
						"tax" => $product_details['' . $i . '']['tax']
					);
				}
			}
		}
		return $product_detail_array;
	}

	public function getVoidRefundResponse(int $order_id, string $transaction_id): ?array {
		$api_response = VAL_NULL;
		if (!empty($order_id) && !empty($transaction_id)) {
			$client_reference_info = $this->getClientReferenceInfo($order_id);
			if (!empty($client_reference_info)) {
				$payload = array(
					"clientReferenceInformation" => $client_reference_info
				);
				$payload = json_encode($payload);
				$resource = RESOURCE_PTS_V2_REFUNDS . $transaction_id . RESOURCE_VOIDS;
				$api_response = $this->serviceProcessor($payload, $resource);
			}
		}
		return $api_response;
	}

	public function discounts(int $order_id): int {
		$discount_exists = VAL_ZERO;
		if (VAL_ZERO != $order_id) {
			$this->load->model('extension/payment/cybersource_query');
			$query_reward_point = $this->model_extension_payment_cybersource_query->queryRewardPointsAmount($order_id);
			$query_store_points = $this->model_extension_payment_cybersource_query->queryStoreCreditAmount($order_id);
			$voucher_amount_temp = $this->model_extension_payment_cybersource_query->queryVoucherAmount($order_id);
			$coupon_amount_temp = $this->model_extension_payment_cybersource_query->queryCouponAmount($order_id);

			if (VAL_NULL != $query_reward_point || VAL_NULL != $query_store_points || !empty($voucher_amount_temp) ||!empty($coupon_amount_temp)) {
				$discount_exists = VAL_ONE;
			}
		}
		return $discount_exists;
	}

	public function getShippingCost(int $order_id): array {
		$shipping_amount = VAL_ZERO;
		$voucher_amount = VAL_ZERO;
		$coupon_amount = VAL_ZERO;
		$shipping_tax = VAL_ZERO;
		$this->load->model('extension/payment/cybersource_query');
		$shipping_amount = $this->model_extension_payment_cybersource_query->queryShippingAmount($order_id);
		$voucher_amount_temp = $this->model_extension_payment_cybersource_query->queryVoucherAmount($order_id);
		if (!empty($voucher_amount_temp)) {
			$voucher_amount = $this->getAbsAmount($voucher_amount_temp);
		}
		$coupon_amount_temp = $this->model_extension_payment_cybersource_query->queryCouponAmount($order_id);
		if (!empty($coupon_amount_temp)) {
			$coupon_amount = $this->getAbsAmount($coupon_amount_temp);
		}
		$shipping_tax = $this->getShippingTax($order_id);
		return array($shipping_amount, $shipping_tax, $voucher_amount, $coupon_amount);
	}

	public function getAbsAmount(float $amount): float {
		$abs_amount = VAL_ZERO;
		if (!empty($amount)) {
			$temp = floatval($amount);
			$abs_amount = abs($temp);
		}
		return $abs_amount;
	}

	public function getShippingTax(int $order_id): float {
		$total_tax = VAL_ZERO;
		$product_details_tax = VAL_ZERO;
		$shipping_tax_amount = VAL_ZERO;
		$this->load->model('extension/payment/cybersource_query');
		if (!empty($order_id)) {
			$query_tax_amount = $this->model_extension_payment_cybersource_query->queryTaxAmount($order_id);
			if (VAL_NULL != $query_tax_amount) {
				$total_tax = (float)$query_tax_amount;
			}
			$query_product_tax = $this->model_extension_payment_cybersource_query->queryProductTaxAmount($order_id);
			if (VAL_NULL != $query_product_tax) {
				$product_details_tax = (float)$query_product_tax;
			}
			$temporary_data = $total_tax - $product_details_tax;
			$shipping_tax_amount = number_format($temporary_data, VAL_TWO, '.', '');
		}
		return $shipping_tax_amount;
	}

	// reporting
	public function getReportSettings(): array {
		$report_settings = array();
		$this->load->language('extension/payment/cybersource');
		$this->load->language('extension/payment/cybersource_common');
		$general_configuration = $this->getGeneralConfiguration();
		if (VAL_ONE == $general_configuration['payment_batch_detail_report'] && ENVIRONMENT_TEST == $general_configuration['sandbox']) {
			if (VAL_NULL != $general_configuration['payment_batch_detail_path']) {
				if (!file_exists(REPORT_TEST_DIR . $general_configuration['payment_batch_detail_path'])) {
					$result = mkdir((REPORT_TEST_DIR . $general_configuration['payment_batch_detail_path']), VAL_DIR_CREATE_PERMISSION, true) ? true : false;
				}
				$report_settings['payment_batch_detail_report_download_path'] = REPORT_TEST_DIR . $general_configuration['payment_batch_detail_path'];
			} else {
				$report_settings['payment_batch_detail_report_download_path'] = REPORT_TEST_DIR;
			}
		}
		if (VAL_ONE == $general_configuration['payment_batch_detail_report'] && ENVIRONMENT_LIVE == $general_configuration['sandbox']) {
			if (VAL_NULL != $general_configuration['payment_batch_detail_path']) {
				if (!file_exists(REPORT_LIVE_DIR . $general_configuration['payment_batch_detail_path'])) {
					$result = mkdir((REPORT_LIVE_DIR . $general_configuration['payment_batch_detail_path']), VAL_DIR_CREATE_PERMISSION, true) ? true : false;
				}
				$report_settings['payment_batch_detail_report_download_path'] = REPORT_LIVE_DIR . $general_configuration['payment_batch_detail_path'];
			} else {
				$report_settings['payment_batch_detail_report_download_path'] = REPORT_LIVE_DIR;
			}
		}
		if (VAL_ONE == $general_configuration['transaction_request_report'] && ENVIRONMENT_TEST == $general_configuration['sandbox']) {
			if (VAL_NULL != $general_configuration['transaction_request_path']) {
				if (!file_exists(REPORT_TEST_DIR . $general_configuration['transaction_request_path'])) {
					$result = mkdir((REPORT_TEST_DIR . $general_configuration['transaction_request_path']), VAL_DIR_CREATE_PERMISSION, true) ? true : false;
				}
				$report_settings['transaction_request_report_download_path'] = REPORT_TEST_DIR . $general_configuration['transaction_request_path'];
			} else {
				$report_settings['transaction_request_report_download_path'] = REPORT_TEST_DIR;
			}
		}
		if (VAL_ONE == $general_configuration['transaction_request_report'] && ENVIRONMENT_LIVE == $general_configuration['sandbox']) {
			if (VAL_NULL != $general_configuration['transaction_request_path']) {
				if (!file_exists(REPORT_LIVE_DIR . $general_configuration['transaction_request_path'])) {
					$result = mkdir((REPORT_LIVE_DIR . $general_configuration['transaction_request_path']), VAL_DIR_CREATE_PERMISSION, true) ? true : false;
				}
				$report_settings['transaction_request_report_download_path'] = REPORT_LIVE_DIR . $general_configuration['transaction_request_path'];
			} else {
				$report_settings['transaction_request_report_download_path'] = REPORT_LIVE_DIR;
			}
		}
		return $report_settings;
	}

	public function processReport(string $report_name, array $report_settings) {
		$this->load->language('extension/payment/cybersource_logger');
		$report_date = date(DATE_Y_M_D, strtotime(REPORT_START_TIME));
		if (TRANSACTION_REQUEST_REPORT == $report_name) {
			$report_path = $report_settings['transaction_request_report_download_path'];
		} elseif (PAYMENT_BATCH_DETAIL_REPORT == $report_name) {
			$report_path = $report_settings['payment_batch_detail_report_download_path'];
		}
		$report_data_response = $this->getReportData($report_date, $report_name);
		if (VAL_NULL != $report_data_response) {
			if (CODE_TWO_ZERO_ZERO == $report_data_response['http_code']) {
				$report_path = (FORWARD_SLASH == !substr($report_path, VAL_NEG_ONE)) ? $report_path : $report_path . FORWARD_SLASH;
				$this->storeReport($report_name, $report_path, $report_data_response['body']);
			} elseif (CODE_FOUR_ZERO_FOUR == $report_data_response['http_code']) {
				$this->model_extension_payment_cybersource_common->logger('[ModelExtensionPaymentCybersourceCommon]
			        [processReoprt] : ' . $this->language->get('error_reports_not_found'));
			} elseif (CODE_FOUR_ZERO_ZERO == $report_data_response['http_code']) {
				$this->model_extension_payment_cybersource_common->logger('[ModelExtensionPaymentCybersourceCommon]
			        [processReoprt] : ' . $this->language->get('error_reports_invalid_request'));
			}
		} else {
			$this->model_extension_payment_cybersource_common->logger('[ModelExtensionPaymentCybersourceCommon]
			    [processReoprt] : ' . $this->language->get('error_response_info'));
		}
	}

	private function getReportData(string $report_date, string $report_name): array {
		$resource = TRR_PBD_RESOURCE . $report_date . "&reportName=" . $report_name;
		return $this->processor($resource, HTTP_METHOD_GET);
	}

	private function storeReport(string $report_name, string $report_path, string $report_data_response) {
		$this->load->language('extension/payment/cybersource_logger');
		$report_data_response = explode("\n", $report_data_response);
		$csv_data = implode("\n", $report_data_response);
		$file_path = $report_path . $report_name . UNDER_SCORE . date(DATE_Y_M_D_H_I_S) . DOT_CSV;
		if (false == file_put_contents($file_path, $csv_data)) {
			$this->model_extension_payment_cybersource_common->logger('[ModelExtensionPaymentCybersourceCommon]
			[storeReport] : ' . $this->language->get('error_storing_report'));
		}
	}

	public function getCDReportData(string $start_time, string $end_time) {
		$resource = CONVERSION_DETAIL_RESOURCE . $start_time . "&endTime=" . $end_time;
		return $this->processor($resource, HTTP_METHOD_GET);
	}

	public function insertCDReportData($rows, string $table_name) {
		$transaction_id = VAL_NULL;
		$this->load->model('extension/payment/cybersource_query');
		$this->load->language('extension/payment/cybersource_logger');
		foreach ($rows as $row) {
			try {
				$transaction_id = $this->model_extension_payment_cybersource_query->queryRequestId($table_name, $row['requestId']);
				if (VAL_NULL != $transaction_id && VAL_ZERO == $transaction_id->num_rows) {
					$query_result = $this->model_extension_payment_cybersource_query->queryInsertCDRTable($table_name, $row);
					if (VAL_NULL != $query_result && !$query_result) {
						$this->model_extension_payment_cybersource_common->logger('[ModelExtensionPaymentCybersourceCommon]
						[insertCDReportData] : ' . $this->language->get('error_report_insertion'));
					}
				} else {
					$this->model_extension_payment_cybersource_common->logger('[ModelExtensionPaymentCybersourceCommon]
					[insertCDReportData] : ' . $this->language->get('error_CDR_updation'));
				}
			} catch (Exception $e) {
				$this->model_extension_payment_cybersource_common->logger('[ModelExtensionPaymentCybersourceCommon]
				[insertCDReportData] : ' . $this->language->get('error_CDR_data_not_found'));
			}
		}
	}

	private function getOMProductData(string $payment_method, string $table_prefix, int $order_id, string $order_product_id): array {
		$is_shipping_available = VAL_EMPTY;
		$quantites['refund_quantity'] = VAL_EMPTY;
		$quantites['capture_quantity'] = VAL_EMPTY;
		$quantites['void_capture_quantity'] = VAL_EMPTY;
		$quantites['void_refund_quantity'] = VAL_EMPTY;
		$quantites = array();
		$this->load->model('extension/payment/cybersource_query');
		$query_refund_quantity = $this->model_extension_payment_cybersource_query->queryOMRefundQuantity($table_prefix, $order_product_id, $order_id);
		$quantites['refund_quantity'] = (VAL_NULL != $query_refund_quantity && VAL_ZERO < $query_refund_quantity->num_rows) ? $query_refund_quantity->row['refund_quantity'] : VAL_ZERO;
		$query_void_refund_quantity = $this->model_extension_payment_cybersource_query->queryVoidRefundQuantity($table_prefix, $order_product_id, $order_id);
		$quantites['void_refund_quantity'] = (VAL_NULL != $query_void_refund_quantity && VAL_ZERO < $query_void_refund_quantity->num_rows) ? $query_void_refund_quantity->row['void_refund_quantity'] : VAL_ZERO;
		if (PAYMENT_GATEWAY == $payment_method || PAYMENT_GATEWAY_APPLE_PAY == $payment_method) {
			$query_capture_quantity = $this->model_extension_payment_cybersource_query->queryCaptureQuantity($table_prefix, $order_product_id, $order_id);
			$quantites['capture_quantity'] = (VAL_NULL != $query_capture_quantity && VAL_ZERO < $query_capture_quantity->num_rows) ? $query_capture_quantity->row['capture_quantity'] : VAL_ZERO;
			$query_shipping_flag = $this->model_extension_payment_cybersource_query->queryShippingFlag($table_prefix, $order_id);
			$is_shipping_available = (VAL_NULL != $query_shipping_flag && !empty($query_shipping_flag->row['shipping_flag'])) ? VAL_FLAG_YES : VAL_FLAG_NO;
			$query_void_capture_quantity = $this->model_extension_payment_cybersource_query->queryVoidCaptureQuantity($table_prefix, $order_product_id, $order_id);
			$quantites['void_capture_quantity'] = (VAL_NULL != $query_void_capture_quantity && VAL_ZERO < $query_void_capture_quantity->num_rows) ? $query_void_capture_quantity->row['void_capture_quantity'] : VAL_ZERO;
		}
		return array($quantites, $is_shipping_available);
	}

	private function getOMDetails(int $order_id, string $table_prefix, string $payment_code): array {
		$data = array();
		$capture_quantity = VAL_ZERO;
		$is_auth_reversal_enabled = FLAG_DISABLE;
		$is_capture_enabled = FLAG_DISABLE;
		$is_void_capture_enabled = FLAG_DISABLE;
		$is_refund_enabled = FLAG_DISABLE;
		$is_void_refund_enabled = FLAG_DISABLE;
		$is_store_points_exists = VAL_ZERO;
		$is_reward_points_exists = VAL_ZERO;
		$data['fraud_management'] = FLAG_DISABLE;
		$this->load->model('sale/order');
		$this->load->model('extension/payment/cybersource_query');
		$gift_vouchers = $this->model_sale_order->getOrderVouchers($order_id);
		$is_gift_certificate_exists = empty($gift_vouchers) ? VAL_ZERO : VAL_ONE;
		// Voucher Data
		$query_voucher_available = $this->model_extension_payment_cybersource_query->queryVoucherAvailable($order_id);
		$is_voucher_exists = (!empty($query_voucher_available) && $query_voucher_available->num_rows <= VAL_ZERO) ? VAL_ZERO : VAL_ONE;
		// Coupon Data
		$query_coupon_available = $this->model_extension_payment_cybersource_query->queryCouponAvailable($order_id);
		$is_coupon_exists = (!empty($query_coupon_available) && $query_coupon_available->num_rows <= VAL_ZERO) ? VAL_ZERO : VAL_ONE;
		// Reward point Data
		$query_reward_points = $this->model_extension_payment_cybersource_query->queryRewardPointsAmount($order_id);
		if (VAL_NULL != $query_reward_points) {
			$is_reward_points_exists = VAL_ONE;
		}
		// store point Data
		$query_store_points = $this->model_extension_payment_cybersource_query->queryStoreCreditAmount($order_id);
		if (VAL_NULL != $query_store_points) {
			$is_store_points_exists = VAL_ONE;
		}
		$query_order_status = $this->model_extension_payment_cybersource_query->queryOrderStatus($order_id);
		$query_api_order_status = $this->model_extension_payment_cybersource_query->queryApiOrderStatus($order_id, $table_prefix);
		$order_status = (!empty($query_order_status) && VAL_ZERO < $query_order_status->num_rows) ? $query_order_status->row['cybersource_order_status'] : VAL_EMPTY;
		$api_order_status = (!empty($query_api_order_status) && VAL_ZERO < $query_api_order_status->num_rows) ? $query_api_order_status->row['cybersource_order_status'] : VAL_EMPTY;
		if (
			API_STATUS_AUTHORIZED_RISK_DECLINED == $api_order_status
			|| API_STATUS_AUTHORIZED_PENDING_REVIEW == $api_order_status
			|| API_STATUS_PENDING_REVIEW == $api_order_status
			|| API_STATUS_DECISION_PROFILE_REJECT == $api_order_status
		) {
			$data['fraud_management'] = FLAG_ENABLE;
		}
		$query_payment_action = $this->model_extension_payment_cybersource_query->getPaymentAction($order_id, $table_prefix . TABLE_ORDER, $this->session->data['user_token']);
		$payment_action = (!empty($query_payment_action) && VAL_ZERO < $query_payment_action->num_rows) ? $query_payment_action->row['payment_action'] : VAL_ZERO;
		$query_auth_details = $this->model_extension_payment_cybersource_query->queryAuthDetails($order_id, $table_prefix);
		$auth_amount = (!empty($query_auth_details) && VAL_ZERO < $query_auth_details->num_rows) ? $query_auth_details->row['amount'] : VAL_ZERO;
		$authorized_quantity = (!empty($query_auth_details) && VAL_ZERO < $query_auth_details->num_rows) ? $query_auth_details->row['order_quantity'] : VAL_ZERO;
		if (PAYMENT_GATEWAY == $payment_code || PAYMENT_GATEWAY_APPLE_PAY == $payment_code) {
			// Auth Reversal Button Enable
			$is_capture_data_exists = $this->model_extension_payment_cybersource_query->queryDataExists($table_prefix . TABLE_CAPTURE, $order_id);
			$is_auth_reversal_data_exists = $this->model_extension_payment_cybersource_query->queryDataExists($table_prefix . TABLE_AUTH_REVERSAL, $order_id);
			if ((VAL_ZERO == strcmp(API_STATUS_AUTHORIZED, $api_order_status)
					&& VAL_ZERO == strcmp(PAYMENT_ACTION_AUTHORIZE, $payment_action)
					&& empty($is_auth_reversal_data_exists)
					&& empty($is_capture_data_exists))
				|| VAL_ZERO == strcmp(API_STATUS_AUTHORIZED_RISK_DECLINED, $api_order_status)
				|| VAL_ZERO == strcmp(API_STATUS_AUTHORIZED_PENDING_REVIEW, $api_order_status)
			) {
				$is_auth_reversal_enabled = FLAG_ENABLE;
			}
			$query_auth_reversal_amount = $this->model_extension_payment_cybersource_query->queryAuthReversalAmount($order_id, $table_prefix);
			$auth_reversal_amount = (!empty($query_auth_reversal_amount) && VAL_ZERO < $query_auth_reversal_amount->num_rows) ? $query_auth_reversal_amount->row['amount'] : VAL_EMPTY;
			$query_capture_details = $this->model_extension_payment_cybersource_query->queryCaptureQuantityDetails($order_id, $table_prefix);
			$capture_quantity = (!empty($query_capture_details) && VAL_ZERO < $query_capture_details->num_rows) ? $query_capture_details->row['capture_quantity'] : VAL_ZERO;
			$capture_amount = (!empty($query_capture_details) && VAL_ZERO < $query_capture_details->num_rows) ? $query_capture_details->row['capture_amount'] : VAL_ZERO;
			if (
				VAL_ZERO == strcmp(API_STATUS_AUTHORIZED, $api_order_status)
				&& VAL_ZERO == strcmp(PAYMENT_ACTION_AUTHORIZE, $payment_action)
				&& $capture_amount != $auth_amount
				&& empty($is_auth_reversal_data_exists)
				&& empty($auth_reversal_amount)
				&& CANCELLED != $order_status
			) {
				$is_capture_enabled = FLAG_ENABLE;
			}
			$data['is_auth_reversal_enabled'] = $is_auth_reversal_enabled;
			$data['is_capture_enabled'] = $is_capture_enabled;
			$data['capture_quantity'] = $capture_quantity;
		}
		$order = $this->model_sale_order->getOrder($order_id);
		$current_order_status = (int)$order['order_status_id'];
		// Void Capture Button Enable
		$is_void_capture_data_exists = $this->model_extension_payment_cybersource_query->queryDataExists($table_prefix . TABLE_VOID_CAPTURE, $order_id);
		$query_void_captured = ((PAYMENT_GATEWAY === $payment_code) || (PAYMENT_GATEWAY_APPLE_PAY === $payment_code)) ? $this->model_extension_payment_cybersource_query->queryVoidCaptureDetails($order_id, $table_prefix) : VAL_NULL;
		$void_captured_id = (!empty($query_void_captured) && VAL_ZERO < $query_void_captured->num_rows) ? $query_void_captured->row['order_id'] : VAL_ZERO;
		$is_refund_data_exists = $this->model_extension_payment_cybersource_query->queryDataExists($table_prefix . TABLE_REFUND, $order_id);
		if ((VAL_ZERO == strcmp(API_STATUS_PENDING, $api_order_status) || VAL_ZERO == strcmp(API_STATUS_AUTHORIZED, $api_order_status))
			&& VAL_ZERO == strcmp(PAYMENT_ACTION_SALE, $payment_action)
			&& empty($is_void_capture_data_exists)
			&& empty($is_refund_data_exists)
			&& $current_order_status != (int)$this->language->get('Shipped')
			&& $current_order_status != (int)$this->language->get('Delivered')
		) {
			$is_void_capture_enabled = FLAG_ENABLE;
			if (REFUND_ERROR == $current_order_status) {
				$is_void_capture_enabled = FLAG_DISABLE;
			}
		} elseif (
			VAL_ZERO == strcmp(API_STATUS_AUTHORIZED, $api_order_status)
			&& VAL_ZERO == strcmp(PAYMENT_ACTION_AUTHORIZE, $payment_action)
			&& !empty($void_captured_id)
			&& empty($is_refund_data_exists)
			&& $current_order_status != (int)$this->language->get('Shipped')
			&& $current_order_status != (int)$this->language->get('Delivered')
		) {
			$is_void_capture_enabled = FLAG_ENABLE;
			if (REFUND_ERROR == $current_order_status) {
				$is_void_capture_enabled = FLAG_DISABLE;
			}
		} else {
			$is_void_capture_enabled = FLAG_DISABLE;
		}
		$remaining_capture_quantity = (int)($authorized_quantity - $capture_quantity);
		$query_void_capture_quantity = ((PAYMENT_GATEWAY === $payment_code) || (PAYMENT_GATEWAY_APPLE_PAY === $payment_code)) ? $this->model_extension_payment_cybersource_query->queryVoidQuantity($order_id, $table_prefix) : VAL_NULL;
		$void_capture_quantity = (!empty($query_void_capture_quantity) && VAL_ZERO < $query_void_capture_quantity->num_rows) ? $query_void_capture_quantity->row['void_quantity'] : VAL_ZERO;
		$query_refund_quantity = $this->model_extension_payment_cybersource_query->queryRefundQuantityDetails($order_id, $table_prefix);
		$refund_quantity = (!empty($query_refund_quantity) && VAL_ZERO < $query_refund_quantity->num_rows) ? $query_refund_quantity->row['refund_quantity'] : VAL_ZERO;

		$query_refund_order_product = $this->model_extension_payment_cybersource_query->queryRefundOrderProductId($order_id, $table_prefix);
		$refund_order_product = (!empty($query_refund_order_product) && VAL_ZERO < $query_refund_order_product->num_rows) ? $query_refund_order_product->num_rows : VAL_ZERO;

		if (VAL_ONE == $refund_order_product && SHIPPING_AND_HANDLING == $query_refund_order_product->row['order_product_id'] && $auth_amount == $query_refund_order_product->row['amount']) {
			$refund_order_product = $query_refund_order_product->row['order_product_id'];
		}
		$remaining_refund_quantity = (int)($authorized_quantity - $refund_quantity - $void_capture_quantity);
		// Refund Button enable
		$voided_quantity = $authorized_quantity - $void_capture_quantity;
		if (
			FLAG_DISABLE == $is_capture_enabled
			&& FLAG_DISABLE == $is_auth_reversal_enabled
			&& $authorized_quantity > $refund_quantity
			&& $voided_quantity <= $authorized_quantity
			&& SHIPPING_AND_HANDLING !== $refund_order_product
			&& $voided_quantity != $refund_quantity && empty($is_auth_reversal_data_exists)
		) {
			$is_refund_enabled = FLAG_ENABLE;
		}
		if (REFUNDED == $order_status || CANCELLED == $order_status || VOID == $order_status || VOID_CAPTURE == $order_status) {
			$is_refund_enabled = FLAG_DISABLE;
		}
		// Void Refund button enable
		$query_void_refunded = $this->model_extension_payment_cybersource_query->queryVoidRefundDetails($order_id, $table_prefix);
		$void_refunded_id = (!empty($query_void_refunded) && VAL_ZERO < $query_void_refunded->num_rows) ? $query_void_refunded->row['order_id'] : VAL_ZERO;
		$is_void_refund_enabled = !empty($void_refunded_id) ? FLAG_ENABLE : FLAG_DISABLE;
		$query_shipping_amount = $this->model_extension_payment_cybersource_query->queryShippingCost($order_id);
		$shipping_amount = (!empty($query_shipping_amount) && VAL_ZERO < $query_shipping_amount->num_rows) ? $query_shipping_amount->row['shipping_cost'] : VAL_ZERO;
		$query_refund_shipping_check = $this->model_extension_payment_cybersource_query->queryRefundShipping($order_id, $table_prefix);
		$is_shipping_refunded = (!empty($query_refund_shipping_check) && VAL_ZERO < $query_refund_shipping_check->num_rows) ? $query_refund_shipping_check->row['shipping_flag'] : VAL_ZERO;
		$query_shipping_void_check = ((PAYMENT_GATEWAY === $payment_code) || (PAYMENT_GATEWAY_APPLE_PAY === $payment_code)) ? $this->model_extension_payment_cybersource_query->queryVoidShippingDetails($order_id, $table_prefix) : VAL_NULL;
		$is_shipping_voided = (!empty($query_shipping_void_check) && $query_shipping_void_check->num_rows > VAL_ZERO) ? VAL_ONE : VAL_ZERO;
		$data['shipping_amount'] = $shipping_amount;
		$data['authorized_amount'] = $auth_amount;
		$data['is_shipping_refunded'] = $is_shipping_refunded;
		$data['is_gift_certificate_exists'] = $is_gift_certificate_exists;
		$data['is_voucher_exists'] = $is_voucher_exists;
		$data['is_coupon_exists'] = $is_coupon_exists;
		$data['is_auth_reversal_enabled'] = $is_auth_reversal_enabled;
		$data['is_capture_enabled'] = $is_capture_enabled;
		$data['is_void_capture_enabled'] = $is_void_capture_enabled;
		$data['is_refund_enabled'] = $is_refund_enabled;
		$data['is_void_refund_enabled'] = $is_void_refund_enabled;
		$data['payment_action'] = $payment_action;
		$data['authorized_quantity'] = $authorized_quantity;
		$data['capture_quantity'] = $capture_quantity;
		$data['remaining_refund_quantity'] = $remaining_refund_quantity;
		$data['remaining_capture_quantity'] = $remaining_capture_quantity;
		$data['is_shipping_voided'] = $is_shipping_voided;
		$data['is_store_points_exists'] = $is_store_points_exists;
		$data['is_reward_points_exists'] = $is_reward_points_exists;
		return $data;
	}

	public function orderManagement(array $data, array $order_info): array {
		$is_shipping_available = VAL_EMPTY;
		$order_id = $data['order_id'];
		$data['om_products'] = array();
		$this->load->model('customer/custom_field');
		$this->load->language('extension/payment/cybersource_common');
		$this->load->model('extension/payment/cybersource_query');
		if (PAYMENT_GATEWAY_ECHECK == $order_info['payment_code']) {
			$this->load->model('extension/payment/cybersource_echeck');
			$this->load->language('extension/payment/cybersource_echeck');
		} elseif (PAYMENT_GATEWAY == $order_info['payment_code']) {
			$this->load->model('extension/payment/cybersource');
			$this->load->language('extension/payment/cybersource');
		} elseif (PAYMENT_GATEWAY_APPLE_PAY == $order_info['payment_code']) {
			$this->load->model('extension/payment/cybersource_apay');
			$this->load->language('extension/payment/cybersource_apay');
		}
		$products = $this->model_sale_order->getOrderProducts($order_id);
		foreach ($products as $product) {
			$option_data = array();
			$options = $this->model_sale_order->getOrderOptions($order_id, $product['order_product_id']);
			foreach ($options as $option) {
				if ('file' != $option['type']) {
					$option_data[] = array(
						'name'  => $option['name'],
						'value' => $option['value'],
						'type'  => $option['type']
					);
				} else {
					$upload_info = $this->model_tool_upload->getUploadByCode($option['value']);
					if ($upload_info) {
						$option_data[] = array(
							'name'  => $option['name'],
							'value' => $upload_info['name'],
							'type'  => $option['type'],
							'href'  => $this->url->link('tool/upload/download', 'user_token=' . $this->session->data['user_token'] . '&code=' . $upload_info['code'], true)
						);
					}
				}
			}
			if (PAYMENT_GATEWAY_ECHECK == $order_info['payment_code']) {
				list($quantites, $is_shipping_available) = $this->getOMProductData(PAYMENT_GATEWAY_ECHECK, TABLE_PREFIX_ECHECK, $order_id, $product['order_product_id']);
			} elseif (PAYMENT_GATEWAY == $order_info['payment_code']) {
				list($quantites, $is_shipping_available) = $this->getOMProductData(PAYMENT_GATEWAY, TABLE_PREFIX_UNIFIED_CHECKOUT, $order_id, $product['order_product_id']);
			} elseif (PAYMENT_GATEWAY_APPLE_PAY == $order_info['payment_code']) {
				list($quantites, $is_shipping_available) = $this->getOMProductData(PAYMENT_GATEWAY_APPLE_PAY, TABLE_PREFIX_APPLE_PAY, $order_id, $product['order_product_id']);
			}
			$quantites['void_capture_quantity'] = $quantites['void_capture_quantity'] ?? VAL_ZERO;
			$data['om_products'][] = array(
				'order_product_id'      => $product['order_product_id'],
				'product_id'            => $product['product_id'],
				'name'                     => $product['name'],
				'model'                    => $product['model'],
				'capture_quantity'      => $quantites['capture_quantity'] ?? VAL_ZERO,
				'refund_quantity'       => $quantites['refund_quantity'],
				'void_capture_quantity' => $quantites['void_capture_quantity'],
				'void_refund_quantity'  => $quantites['void_refund_quantity'],
				'option'                   => $option_data,
				'quantity'                => $product['quantity'],
				'price'                    => $this->currency->format($product['price'] + ($this->config->get('config_tax') ? $product['tax'] : VAL_ZERO), $order_info['currency_code'], $order_info['currency_value']),
				'total'                    => $this->currency->format(($product['price'] * ($product['quantity'] - $quantites['void_capture_quantity'] - $quantites['void_refund_quantity'])) + ($this->config->get('config_tax') ? ($product['tax'] * ($product['quantity'] - $quantites['void_capture_quantity'] - $quantites['void_refund_quantity'])) : VAL_ZERO), $order_info['currency_code'], $order_info['currency_value']),
				'href'                     => $this->url->link('catalog/product/edit', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $product['product_id'], true)
			);
		}
		$data['payment_code'] = $order_info['payment_code'];
		$data['user_token'] = $this->session->data['user_token'];
		$data['is_shipping_available'] = $is_shipping_available;
		$temporary_array = array();
		if (PAYMENT_GATEWAY == $order_info['payment_code']) {
			$temporary_array = $this->getOMDetails($order_id, TABLE_PREFIX_UNIFIED_CHECKOUT, PAYMENT_GATEWAY);
		} elseif (PAYMENT_GATEWAY_APPLE_PAY == $order_info['payment_code']) {
			$temporary_array = $this->getOMDetails($order_id, TABLE_PREFIX_APPLE_PAY, PAYMENT_GATEWAY_APPLE_PAY);
		} else {
			$temporary_array = $this->getOMDetails($order_id, TABLE_PREFIX_ECHECK, PAYMENT_GATEWAY_ECHECK);
		}
		$data = array_merge($data, $temporary_array);
		return $data;
	}

	public function getTransactionDetails(int $order_id, string $service, string $table_name): array {
		$this->load->model('extension/payment/cybersource_query');
		$data = array();
		$query_transaction_id = $this->model_extension_payment_cybersource_query->queryTransactionId($order_id, $table_name);
		$data[$service . '_status'] = FLAG_DISABLE;
		if (!empty($query_transaction_id) && VAL_ZERO < $query_transaction_id->num_rows) {
			$data[$service . '_size'] = $query_transaction_id->num_rows;
			foreach ($query_transaction_id->rows as $transaction_id) {
				$data[$service][] = array(
					'transaction_id' => $transaction_id['transaction_id']
				);
			}
			$data[$service . '_status'] = FLAG_ENABLE;
		}
		return $data;
	}
}
