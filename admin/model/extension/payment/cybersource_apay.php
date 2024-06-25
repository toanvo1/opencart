<?php

require_once DIR_SYSTEM . 'library/isv/cybersource_constants.php';

/**
 * Apple Pay Model file.
 *
 * @author Cybersource
 * @package Back Office
 * @subpackage Model
 */
class ModelExtensionPaymentCybersourceApay extends Model {
	public function install() {
		$table_prefix = TABLE_PREFIX_APPLE_PAY;
		$this->load->model('extension/payment/cybersource_query');
		$this->model_extension_payment_cybersource_query->queryCreateOrderTable($table_prefix);
		$this->model_extension_payment_cybersource_query->queryCreateCaptureTable($table_prefix);
		$this->model_extension_payment_cybersource_query->queryCreateAuthReversalTable($table_prefix);
		$this->model_extension_payment_cybersource_query->queryCreateVoidCaptureTable($table_prefix);
		$this->model_extension_payment_cybersource_query->queryCreateRefundTable($table_prefix);
		$this->model_extension_payment_cybersource_query->queryCreateVoidRefundTable($table_prefix);
		$this->model_extension_payment_cybersource_query->queryCreateCDRTable($table_prefix);
	}

	public function uninstall() {
		$this->load->model('extension/payment/cybersource_query');
		$this->model_extension_payment_cybersource_query->queryDropTable(TABLE_PREFIX_APPLE_PAY . TABLE_ORDER);
		$this->model_extension_payment_cybersource_query->queryDropTable(TABLE_PREFIX_APPLE_PAY . TABLE_CAPTURE);
		$this->model_extension_payment_cybersource_query->queryDropTable(TABLE_PREFIX_APPLE_PAY . TABLE_AUTH_REVERSAL);
		$this->model_extension_payment_cybersource_query->queryDropTable(TABLE_PREFIX_APPLE_PAY . TABLE_VOID_CAPTURE);
		$this->model_extension_payment_cybersource_query->queryDropTable(TABLE_PREFIX_APPLE_PAY . TABLE_REFUND);
		$this->model_extension_payment_cybersource_query->queryDropTable(TABLE_PREFIX_APPLE_PAY . TABLE_VOID_REFUND);
		$this->model_extension_payment_cybersource_query->queryDropTable(TABLE_PREFIX_APPLE_PAY . TABLE_CONVERSION_DETAIL_REPORT);
	}
}
