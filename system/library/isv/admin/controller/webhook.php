<?php

namespace Isv\Admin\Controller;

use Isv\Admin\Model\Webhook as ModelWebhook;
use Throwable;

trait Webhook {
	use ModelWebhook;

	/**
	 * Handles webhook subcription creation.
	 *
	 * @param string $merchant_id organization id
	 * @param string $product_id product id
	 * @param array $event_types event types
	 * @param string $server_scheme server scheme
	 * @param string $webhook_security_token webhook url security token
	 *
	 * @return string|null
	 */
	public function webhookService($merchant_id, $product_id, $event_types, $server_scheme, $webhook_security_token) {
		$response = VAL_NULL;
		$webhook_details = array(
			'product_id' => $product_id,
			'organization_id' => $merchant_id
		);
		try {
			$this->load->model('extension/payment/cybersource_common');
			$this->load->language('extension/payment/cybersource_common');
			$this->load->language('extension/payment/cybersource_logger');
			$this->load->model('extension/payment/cybersource_query');
			$product_list_response = $this->requestAndVerifyWebhookProductList($merchant_id, $product_id, $event_types);
			if ($product_list_response['status']) {
				$subscription_creation_required = $this->verifySubscriptionCreationRequired($merchant_id, $product_id, $event_types);
				if ($subscription_creation_required['status']) {
					$digital_signature = $this->generateWebhookDigitalSignatureKey($merchant_id);
					if ($digital_signature['status']) {
						$webhook_url = $this->generateWebhookUrl($server_scheme, $webhook_security_token, 'networkTokensWebhook');
						if (isset($webhook_url)) {
							$subscription_details = $this->createWebhookSubscription($merchant_id, $product_id, $event_types, $webhook_url, $webhook_url);
							if ($subscription_details['status']) {
								$webhook_details['webhook_id'] = $subscription_details['webhook_id'];
								$webhook_details['digital_signature_key_id'] = $digital_signature['key_id'];
								$webhook_details['digital_signature_key'] = $digital_signature['key'];
								$is_insertion_success = $this->model_extension_payment_cybersource_query->queryInsertWebhookDetails($webhook_details);
								if (!$is_insertion_success) {
									$response = $this->language->get('error_webhook_table_insertion');
								}
							} else {
								$response = $subscription_details['logger_message'];
							}
						} else {
							$response = $this->language->get('error_webhook_url_generation');
						}
					} else {
						$response = $digital_signature['logger_message'];
					}
				} else {
					if (isset($subscription_creation_required['logger_message'])) {
						$response = $subscription_creation_required['logger_message'];
						return VAL_NULL;
					} else {
						$response = $this->language->get('warning_msg_webhook_service_request');
					}
				}
			} else {
				$response = $product_list_response['logger_message'];
			}
		} catch (Throwable $e) {
			$response = $e->getMessage();
		} finally {
			if (isset($response)) {
				$this->model_extension_payment_cybersource_common->logger("[ControllerTraitWebhook][webhookService] " . $response);
				$response = $this->language->get('warning_msg_webhook_service_request');
			}
		}
		return $response;
	}
}
