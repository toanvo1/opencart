<?php

namespace Isv\Admin\Model;

use Isv\Common\Helper\TypeConversion;

trait Webhook {
	/**
	 * Requesting a Digital Signature Key.
	 *
	 * @param string $merchant_id
	 *
	 * @return array
	 */
	public function generateWebhookDigitalSignatureKey(string $merchant_id): array {
		$this->load->language('extension/payment/cybersource_logger');
		$this->load->model('extension/payment/cybersource_common');
		$response = array(
			'status' => false,
			'logger_message' => $this->language->get('error_msg_digital_signature_key_generation')
		);
		$payload = array(
			"clientRequestAction" => "CREATE",
			"keyInformation" => array(
				"provider" => "nrtd",
				"tenant" => TypeConversion::convertDataToType($merchant_id, 'string'),
				"keyType" => "sharedSecret",
				"organizationId" => TypeConversion::convertDataToType($merchant_id, 'string')
			)
		);
		$payload = json_encode($payload);
		$resource = RESOURCE_KMS_EGRESS_V2_KEYS_SYM;
		$api_response = $this->model_extension_payment_cybersource_common->serviceProcessor($payload, $resource);
		if (CODE_TWO_ZERO_ONE === $api_response['http_code']) {
			$decoded_response = json_decode($api_response['body']);
			if (isset($decoded_response->keyInformation->key) && isset($decoded_response->keyInformation->keyId)) {
				$response['status'] = true;
				$response['key'] = $decoded_response->keyInformation->key;
				$response['key_id'] = $decoded_response->keyInformation->keyId;
			} else {
				$response['logger_message'] = $this->language->get('error_msg_digital_signature_key_keyId_not_found');
			}
		}
		return $response;
	}

	/**
	 * To get a list of available products and event types for an organization and checks whether required product id and event types are present or not.
	 *
	 * @param string $merchant_id
	 * @param string $product_id
	 * @param array $event_types
	 *
	 * @return array
	 */
	public function requestAndVerifyWebhookProductList(string $merchant_id, string $product_id, array $required_event_types): array {
		$response_event_types = array();
		$this->load->language('extension/payment/cybersource_logger');
		$this->load->model('extension/payment/cybersource_common');
		$response = array(
			'status' => false,
			'logger_message' => $this->language->get('error_msg_network_token_product_list')
		);
		$resource = RESOURCE_NOTIFICATION_SUBSCRIPTION_V1_PRODUCTS . TypeConversion::convertDataToType($merchant_id, 'string');
		$api_response = $this->model_extension_payment_cybersource_common->processor($resource, HTTP_METHOD_GET);
		if (CODE_TWO_ZERO_ZERO === $api_response['http_code']) {
			$decoded_response = json_decode($api_response['body']);
			foreach ($decoded_response as $product_list) {
				if ($product_list->productId === $product_id) {
					foreach ($product_list->eventTypes as $event_names) {
						array_push($response_event_types, $event_names->eventName);
					}
				}
			}
			if ($this->isEventsExists($response_event_types, $required_event_types)) {
				$response['status'] = true;
			} else {
				$response['logger_message'] = $this->language->get('error_msg_network_token_events');
			}
		}
		return $response;
	}

	/**
	 * To create required webhook subscription creation based on product id and event types.
	 *
	 * @param string $merchant_id
	 * @param string $product_id
	 * @param array $event_types
	 * @param string $webhook_url
	 * @param string $webhook_health_check_url
	 *
	 * @return array
	 */
	public function createWebhookSubscription(string $merchant_id, string $product_id, array $event_types, string $webhook_url, string $webhook_health_check_url): array {
		$this->load->language('extension/payment/cybersource_logger');
		$this->load->model('extension/payment/cybersource_common');
		$response = array(
			'status' => false,
			'logger_message' => $this->language->get('error_msg_subscription_creation')
		);
		$payload = array(
			"name" => "Webhook URL for token updates",
			"description" => "Webhook to receive Network Token life cycle updates",
			"organizationId" => TypeConversion::convertDataToType($merchant_id, 'string'),
			"productId" => TypeConversion::convertDataToType($product_id, 'string'),
			"eventTypes" => TypeConversion::convertArrayToType($event_types, array('string')),
			"webhookUrl" => TypeConversion::convertDataToType($webhook_url, 'string'),
			"healthCheckUrl" => TypeConversion::convertDataToType($webhook_health_check_url, 'string'),
			"securityPolicy" => array(
					"securityType" => "KEY"
				)
			);
		$payload = json_encode($payload);
		$resource = RESOURCE_NOTIFICATION_SUBSCRIPTION_V1_WEBHOOKS;
		$api_response = $this->model_extension_payment_cybersource_common->serviceProcessor($payload, $resource);
		if (CODE_TWO_ZERO_ONE === $api_response['http_code']) {
			$decoded_response = json_decode($api_response['body']);
			if (isset($decoded_response->webhookId) && isset($decoded_response->organizationId)) {
				$response['status'] = true;
				$response['webhook_id'] = $decoded_response->webhookId;
				$response['organization_id'] = $decoded_response->organizationId;
			} else {
				$response['logger_message'] = $this->language->get('error_msg_subscription_webhookId_not_found');
			}
		}
		return $response;
	}

	/**
	 * Fetches all created webhook details for specified mechant id.
	 *
	 * @param string $merchant_id
	 * @param string $product_id
	 * @param string $event_type
	 *
	 * @return array
	 */
	public function getAllCreatedWebhooks(string $merchant_id, string $product_id, string $event_type): array {
		$this->load->model('extension/payment/cybersource_common');
		$query_data = array(
			'organizationId' => TypeConversion::convertDataToType($merchant_id, 'string'),
			'productId' => TypeConversion::convertDataToType($product_id, 'string'),
			'eventType' => TypeConversion::convertDataToType($event_type, 'string')
		);
		$resource = RESOURCE_NOTIFICATION_SUBSCRIPTION_V1_WEBHOOKS . '?' . http_build_query($query_data);
		$api_response = $this->model_extension_payment_cybersource_common->processor($resource, HTTP_METHOD_GET);
		return $api_response;
	}

	/**
	 * Fetches details about single webhook based on webhook id.
	 *
	 * @param string $webhook_id
	 *
	 * @return array
	 */
	public function getDetailsOfSingleWebhook(string $webhook_id): array {
		$this->load->model('extension/payment/cybersource_common');
		$resource = RESOURCE_NOTIFICATION_SUBSCRIPTION_V1_WEBHOOKS . FORWARD_SLASH . TypeConversion::convertDataToType($webhook_id, 'string');
		$api_response = $this->model_extension_payment_cybersource_common->processor($resource, HTTP_METHOD_GET);
		return $api_response;
	}

	/**
	 * Triggers delete webhook subscription service.
	 *
	 * @param string $webhook_id
	 *
	 * @return array
	 */
	public function deleteWebhookSubscription(string $webhook_id): array {
		$this->load->model('extension/payment/cybersource_common');
		$resource = RESOURCE_NOTIFICATION_SUBSCRIPTION_V1_WEBHOOKS . FORWARD_SLASH . TypeConversion::convertDataToType($webhook_id, 'string');
		$api_response = $this->model_extension_payment_cybersource_common->processor($resource, HTTP_METHOD_DELETE);
		return $api_response;
	}

	/**
	 * Deletes subscription existance for each event.
	 *
	 * @param string $merchant_id
	 * @param string $product_id
	 * @param array $event_types
	 *
	 * @return bool
	 */
	public function deleteSubscriptionForEachEvent(string $merchant_id, string $product_id, array $event_types): bool {
		$flag = false;
		foreach ($event_types as $event_type) {
			$api_response = $this->getAllCreatedWebhooks($merchant_id, $product_id, $event_type);
			if (CODE_TWO_ZERO_ZERO === $api_response['http_code']) {
				$decode_response = json_decode($api_response['body']);
				$organization_id_verification = isset($decode_response->organizationId) ? ($decode_response->organizationId === $merchant_id) : false;
				$product_id_verification = (isset($decode_response->productId) && $organization_id_verification) ? ($decode_response->productId === $product_id) : false;
				$event_types_verification = (isset($decode_response->eventTypes) && is_array($decode_response->eventTypes)) && $product_id_verification ? ($decode_response->eventTypes[VAL_ZERO] === $event_type) : false;
				if (isset($decode_response->webhookId) && $event_types_verification) {
					$delete_service_api_response = $this->deleteWebhookSubscription($decode_response->webhookId);
					if (CODE_TWO_ZERO_ZERO === $delete_service_api_response['http_code'] || CODE_FOUR_ZERO_FOUR === $delete_service_api_response['http_code']) {
						$flag = true;
					} else {
						$flag = false;
						break;
					}
				}
			} elseif (CODE_FOUR_ZERO_FOUR === $api_response['http_code']) {
				$flag = true;
			} else {
				$flag = false;
				break;
			}
		}
		return $flag;
	}

	/**
	 * Deletes subscription details from DB and api.
	 *
	 * @param string $merchant_id
	 * @param string $product_id
	 * @param string $webhook_id
	 * @param array $event_types
	 *
	 * @return bool
	 */
	public function deleteSubscriptionDetails(string $merchant_id, string $product_id, string $webhook_id, array $event_types): bool {
		$this->load->model('extension/payment/cybersource_query');
		$response = false;
		$delete_event_response = $this->deleteSubscriptionForEachEvent($merchant_id, $product_id, $event_types);
		if ($delete_event_response) {
			$is_deletion_success = $this->model_extension_payment_cybersource_query->queryDeleteWebhookDetails($webhook_id);
			if ($is_deletion_success) {
				$response = true;
			}
		}
		return $response;
	}

	/**
	 * Verify whether a new subscription needs to be created or already exists.
	 *
	 * @param string $merchant_id
	 * @param string $product_id
	 * @param array $event_types
	 *
	 * @return array
	 */
	public function verifySubscriptionCreationRequired(string $merchant_id, string $product_id, array $event_types): array {
		$this->load->language('extension/payment/cybersource_logger');
		$this->load->model('extension/payment/cybersource_query');
		$response = array(
			'status' => false,
			'logger_message' => VAL_NULL
		);
		$query_webhook_details = $this->model_extension_payment_cybersource_query->queryWebhookDetails($merchant_id, $product_id);
		if (!empty($query_webhook_details) && isset($query_webhook_details['webhook_id'])) {
			$webhook_details_api_response = $this->getDetailsOfSingleWebhook($query_webhook_details['webhook_id']);
			if (CODE_TWO_ZERO_ZERO === $webhook_details_api_response['http_code']) {
				$webhook_details_response = json_decode($webhook_details_api_response['body']);
				$organization_id_verification = isset($webhook_details_response->organizationId) ? ($webhook_details_response->organizationId === $merchant_id) : false;
				$product_id_verification = isset($webhook_details_response->productId) && $organization_id_verification ? ($webhook_details_response->productId === $product_id) : false;
				$event_types_verification = isset($webhook_details_response->eventTypes) && $product_id_verification ? $this->isEventsExists($webhook_details_response->eventTypes, $event_types) : false;
				if ($event_types_verification) {
					$response['logger_message'] = $this->language->get('error_msg_subscription_already_exists');
				} else {
					$response['status'] = $this->deleteSubscriptionDetails($merchant_id, $product_id, $query_webhook_details['webhook_id'], $event_types);
				}
			} elseif (CODE_FOUR_ZERO_FOUR === $webhook_details_api_response['http_code']) {
				$response['status'] = $this->deleteSubscriptionDetails($merchant_id, $product_id, $query_webhook_details['webhook_id'], $event_types);
			}
		} else {
			$response['status'] = $this->deleteSubscriptionForEachEvent($merchant_id, $product_id, $event_types);
		}
		return $response;
	}

	/**
	 * Verifies existence of required events in all possible events.
	 *
	 * @param array $all_existing_events
	 * @param array $requesting_events
	 *
	 * @return bool
	 */
	public function isEventsExists(array $all_existing_events, array $requesting_events): bool {
		$response = false;
		if (!empty($all_existing_events) && !empty($requesting_events)) {
			$response = true;
			foreach ($requesting_events as $event_type) {
				$response = $response && in_array($event_type, $all_existing_events);
				if (!$response) {
					break;
				}
			}
		}
		return $response;
	}

	/**
	 * Generates the call back url which is to be send while creating subscription.
	 *
	 * @param string $server_scheme
	 * @param string $webhook_security_token
	 * @param string $end_point_method
	 *
	 * @return string|null
	 */
	public function generateWebhookUrl(string $server_scheme, string $webhook_security_token, string $end_point_method): ?string {
		$webhook_url = VAL_NULL;
		if (isset($server_scheme) && isset($webhook_security_token) && isset($end_point_method)) {
			if ($server_scheme) {
				$url_data = parse_url(HTTPS_CATALOG);
				$url = HTTPS_CATALOG;
			} else {
				$url_data = parse_url(HTTP_SERVER);
				$url = HTTP_CATALOG;
			}
			if (!isset($url_data['port'])) {
				$url_data['port'] = 443;
				$url = $url_data['scheme'] . '://' . $url_data['host'] . ':' . $url_data['port'] . $url_data['path'] ?? VAL_EMPTY;
			}
			$webhook_url = $url . OPENCART_INDEX_PATH . EXTENSION_PAYMENT_GATEWAY . FORWARD_SLASH . $end_point_method . '&token=' . $webhook_security_token;
		}
		return $webhook_url;
	}
}
