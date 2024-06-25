<?php

namespace Isv\Catalog\Model;

trait Webhook {
	/**
	 * Validates the notification from webhook.
	 *
	 * @param string $digital_signature
	 * @param array $payload
	 * @param array $webhook_details
	 *
	 * @return boolean
	 */
	public function notificationValidation(string $digital_signature, array $payload, array $webhook_details): bool {
		$response = false;
		if (!empty($digital_signature) && !empty($payload) && !empty($webhook_details)) {
			$signature_data = $this->splitSignature($digital_signature);
			if (isset($signature_data) && (isset($signature_data['time_stamp']) && isset($signature_data['key_id']) && isset($signature_data['signature']))) {
				$time_stamped_payload = $signature_data['time_stamp'] . "." . json_encode($payload, JSON_UNESCAPED_SLASHES);
				$digital_signature_key = ($webhook_details['digital_signature_key_id'] === $signature_data['key_id']) ? $webhook_details['digital_signature_key'] : VAL_NULL;
				if (isset($digital_signature_key)) {
					$decode_key = base64_decode($digital_signature_key);
					$signature = base64_encode(hash_hmac(ALGORITHM_SHA256, $time_stamped_payload, $decode_key, true));
					if (hash_equals($signature, $signature_data['signature'])) {
						$response = true;
					}
				}
			}
		}
		return $response;
	}

	/**
	 * Splits the signature in the notification.
	 *
	 * @param string $digital_signature
	 *
	 * @return array
	 */
	public function splitSignature(string $digital_signature): array {
		$signature_data = array();
		$signature_parts = explode(";", $digital_signature);
		$signature_data['time_stamp'] = trim(explode("t=", $signature_parts[VAL_ZERO])[VAL_ONE]);
		$signature_data['key_id'] = trim(explode("keyId=", $signature_parts[VAL_ONE])[VAL_ONE]);
		$signature_data['signature'] = trim(explode("sig=", $signature_parts[VAL_TWO])[VAL_ONE]);
		return $signature_data;
	}
}
