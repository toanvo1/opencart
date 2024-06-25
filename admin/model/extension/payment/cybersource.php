<?php

require_once DIR_SYSTEM . 'library/isv/cybersource_constants.php';

/**
 * Unified Checkout Model file.
 *
 * @author Cybersource
 * @package Back Office
 * @subpackage Model
 */
class ModelExtensionPaymentCybersource extends Model {
	public function install() {
		$table_prefix = TABLE_PREFIX_UNIFIED_CHECKOUT;
		$this->load->model('extension/payment/cybersource_query');
		$this->model_extension_payment_cybersource_query->queryCreateOrderTable($table_prefix);
		$this->model_extension_payment_cybersource_query->queryCreateCaptureTable($table_prefix);
		$this->model_extension_payment_cybersource_query->queryCreateAuthReversalTable($table_prefix);
		$this->model_extension_payment_cybersource_query->queryCreateVoidCaptureTable($table_prefix);
		$this->model_extension_payment_cybersource_query->queryCreateRefundTable($table_prefix);
		$this->model_extension_payment_cybersource_query->queryCreateVoidRefundTable($table_prefix);
		$this->model_extension_payment_cybersource_query->queryCreateCDRTable($table_prefix);
		$this->model_extension_payment_cybersource_query->queryCreateTokenizationTable();
		$this->model_extension_payment_cybersource_query->queryCreateTokenCheckTable();
		$this->model_extension_payment_cybersource_query->queryCreateWebhookTable();
	}

	public function uninstall() {
		$this->load->model('extension/payment/cybersource_query');
		$this->model_extension_payment_cybersource_query->queryDropTable(TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_ORDER);
		$this->model_extension_payment_cybersource_query->queryDropTable(TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_CAPTURE);
		$this->model_extension_payment_cybersource_query->queryDropTable(TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_AUTH_REVERSAL);
		$this->model_extension_payment_cybersource_query->queryDropTable(TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_VOID_CAPTURE);
		$this->model_extension_payment_cybersource_query->queryDropTable(TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_REFUND);
		$this->model_extension_payment_cybersource_query->queryDropTable(TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_VOID_REFUND);
		$this->model_extension_payment_cybersource_query->queryDropTable(TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_CONVERSION_DETAIL_REPORT);
		$this->model_extension_payment_cybersource_query->queryDropTable(TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_TOKENIZATION);
		$this->model_extension_payment_cybersource_query->queryDropTable(TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_TOKEN_CHECK);
		$this->model_extension_payment_cybersource_query->queryDropTable(TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_WEBHOOK);
	}

	/**
	 * Return initial required data for payment configuration.
	 *
	 * @return array
	 */
	public function getDefaultUcData(): array {
		$allowed_default_cards = array('visa', 'mastercard', 'discover', 'amex');
		foreach ($allowed_default_cards as $status_name) {
			$allowed_default_cards_values['payment_' . PAYMENT_GATEWAY . UNDER_SCORE . $status_name . '_card_status'] = VAL_ONE;
		}
		$payment_label_default_values = array('payment_' . PAYMENT_GATEWAY . '_payment_option_label' => 'Credit/Debit Card');
		return array_map('trim', array_merge($payment_label_default_values, $allowed_default_cards_values));
	}
}
