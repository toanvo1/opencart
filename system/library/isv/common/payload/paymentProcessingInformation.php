<?php

namespace Isv\Common\Payload;

require_once DIR_SYSTEM . 'library/isv/cybersource_constants.php';

class PaymentProcessingInformation {
	public $registry;

	public function __construct($registry) {
		$this->registry = $registry;
	}

	public function __get($name) {
		return $this->registry->get($name);
	}

	/**
	 * Includes payment solution in payload data.
	 *
	 * @param array $payload
	 * @param string $file_name
	 * @param string $order_id
	 *
	 * @return array
	 */
	public function paymentSolution(array $payload, string $file_name, string $order_id): array {
		$this->load->model('extension/payment/cybersource_query');
		if (PAYMENT_GATEWAY === $file_name) {
			$query_uc_payment_method = $this->model_extension_payment_cybersource_query->queryUcPaymentMethod($order_id, TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_ORDER);
			$uc_payment_method = $query_uc_payment_method->row['payment_method'] ?? null;
			if (PAYMENT_METHOD_NAME_VSRC === $uc_payment_method) {
				$payload['processingInformation']['paymentSolution'] = PAYMENT_SOLUTION_VSRC;
			} elseif (PAYMENT_METHOD_NAME_GPAY === $uc_payment_method) {
				$payload['processingInformation']['paymentSolution'] = PAYMENT_SOLUTION_GPAY;
			}
		} elseif (PAYMENT_GATEWAY_APPLE_PAY === $file_name) {
			$payload['processingInformation']['paymentSolution'] = PAYMENT_SOLUTION_APPLE_PAY;
		}

		return $payload;
	}
}
