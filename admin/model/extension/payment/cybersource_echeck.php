<?php

require_once DIR_SYSTEM . 'library/isv/cybersource_constants.php';

/**
 * eCheck Model file.
 *
 * @author Cybersource
 * @package Back Office
 * @subpackage Model
 */
class ModelExtensionPaymentCybersourceEcheck extends Model {
	public function install() {
		$table_prefix = TABLE_PREFIX_ECHECK;
		$this->load->model('extension/payment/cybersource_query');
		$this->model_extension_payment_cybersource_query->queryCreateOrderTable($table_prefix);
		$this->model_extension_payment_cybersource_query->queryCreateVoidCaptureTable($table_prefix);
		$this->model_extension_payment_cybersource_query->queryCreateRefundTable($table_prefix);
		$this->model_extension_payment_cybersource_query->queryCreateVoidRefundTable($table_prefix);
		$this->model_extension_payment_cybersource_query->queryCreateCDRTable($table_prefix);
	}

	public function uninstall() {
		$table_prefix = TABLE_PREFIX_ECHECK;
		$this->load->model('extension/payment/cybersource_query');
		$this->model_extension_payment_cybersource_query->queryDropTable(TABLE_PREFIX_ECHECK . TABLE_ORDER);
		$this->model_extension_payment_cybersource_query->queryDropTable(TABLE_PREFIX_ECHECK . TABLE_VOID_CAPTURE);
		$this->model_extension_payment_cybersource_query->queryDropTable(TABLE_PREFIX_ECHECK . TABLE_REFUND);
		$this->model_extension_payment_cybersource_query->queryDropTable(TABLE_PREFIX_ECHECK . TABLE_VOID_REFUND);
		$this->model_extension_payment_cybersource_query->queryDropTable(TABLE_PREFIX_ECHECK . TABLE_CONVERSION_DETAIL_REPORT);
	}
}
