<?php

require_once DIR_SYSTEM . 'library/isv/cybersource_constants.php';

/**
 * Query file.
 *
 * @author Cybersource
 * @package Back Office
 * @subpackage Model
 */
class ModelExtensionPaymentCybersourceQuery extends Model {
	public function errorHandler(string $query): array {
		$this->load->model('extension/payment/cybersource_common');
		$query_output = null;
		$is_success = false;
		try {
			$query_output = $this->db->query($query);
			$is_success = true;
		} catch (Exception $e) {
			$this->model_extension_payment_cybersource_common->logger($e->getMessage());
		}
		return array($is_success, $query_output);
	}

	public function queryCreateTaxTable(string $table_prefix) {
		$this->load->model('extension/payment/cybersource_common');
		$this->load->language('extension/payment/cybersource_logger');
		if (!empty($table_prefix) && is_string($table_prefix)) {
			$query_table_prefix = $table_prefix;
			$query_create_tax_table = "CREATE TABLE IF NOT EXISTS `" . $this->db->escape($query_table_prefix . TABLE_TAX) . "` (
				`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				`tax_id` VARCHAR(10) NOT NULL,
				`transaction_id` VARCHAR(26) NOT NULL,
				`taxable_amount` DECIMAL( 20, 4 ) NOT NULL,
				`tax_amount` DECIMAL( 20, 4 ) NOT NULL,
				`total_amount` DECIMAL( 20, 4 ) NOT NULL,
				`currency` VARCHAR(128) NOT NULL,
				`status` VARCHAR(128) NOT NULL,
				`date_added` DATETIME NOT NULL
				) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;";
			list($is_success, $query_response) = $this->errorHandler($query_create_tax_table);
			if (!$is_success) {
				$this->model_extension_payment_cybersource_common->logger('[ModelExtensionPaymentCybersourceQuery]
				[queryCreateTaxTable]' . $this->language->get('error_create_table') . $table_prefix . TABLE_TAX);
			}
		} else {
			$this->model_extension_payment_cybersource_common->logger('[ModelExtensionPaymentCybersourceQuery]
			[queryCreateTaxTable]' . $this->language->get('error_create_table') . $table_prefix . TABLE_TAX);
		}
	}

	public function queryCreateDavTable(string $table_prefix) {
		$this->load->model('extension/payment/cybersource_common');
		$this->load->language('extension/payment/cybersource_logger');
		if (!empty($table_prefix) && is_string($table_prefix)) {
			$query_table_prefix = $table_prefix;
			$query_create_dav_table = "CREATE TABLE IF NOT EXISTS `" . $this->db->escape($query_table_prefix . TABLE_DAV) . "` (
				`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				`order_id` int(11) NOT NULL,
				`transaction_id` VARCHAR(24) NOT NULL,
				`recommended_address1` VARCHAR(128) NOT NULL,
				`recommended_city` VARCHAR(128) NOT NULL,
				`recommended_country` VARCHAR(128) NOT NULL,
				`recommended_postal_code` VARCHAR(10) NOT NULL,
				`recommended_zone` VARCHAR(128) NOT NULL,
				`entered_address1` VARCHAR(128) NOT NULL,
				`entered_city` VARCHAR(128) NOT NULL,
				`entered_country` VARCHAR(128) NOT NULL,
				`entered_postal_code` VARCHAR(10) NOT NULL,
				`entered_zone` VARCHAR(128) NOT NULL,
				`status` VARCHAR(64) NOT NULL,
				`date_added` DATETIME NOT NULL
				)ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;";
			list($is_success, $query_response) = $this->errorHandler($query_create_dav_table);
			if (!$is_success) {
				$this->model_extension_payment_cybersource_common->logger('[ModelExtensionPaymentCybersourceQuery]
				[queryCreateDavTable]' . $this->language->get('error_create_table') . $table_prefix . TABLE_DAV);
			}
		} else {
			$this->model_extension_payment_cybersource_common->logger('[ModelExtensionPaymentCybersourceQuery]
			[queryCreateDavTable]' . $this->language->get('error_create_table') . $table_prefix . TABLE_DAV);
		}
	}

	public function queryCreateOrderStatusTable(string $table_prefix) {
		$this->load->model('extension/payment/cybersource_common');
		$this->load->language('extension/payment/cybersource_logger');
		if (!empty($table_prefix) && is_string($table_prefix)) {
			$query_table_prefix = $table_prefix;
			$query_create_order_status_table = "CREATE TABLE IF NOT EXISTS `" . $this->db->escape($query_table_prefix . TABLE_ORDER_STATUS) . "` (
				`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				`order_id` int(11) NOT NULL,
				`cybersource_order_status` VARCHAR(64) NOT NULL
				)ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;";
			list($is_success, $query_response) = $this->errorHandler($query_create_order_status_table);
			if (!$is_success) {
				$this->model_extension_payment_cybersource_common->logger('[ModelExtensionPaymentCybersourceQuery]
				[queryCreateOrderStatusTable]' . $this->language->get('error_create_table') . $table_prefix . TABLE_ORDER_STATUS);
			}
		} else {
			$this->model_extension_payment_cybersource_common->logger('[ModelExtensionPaymentCybersourceQuery]
			[queryCreateOrderStatusTable]' . $this->language->get('error_create_table') . $table_prefix . TABLE_ORDER_STATUS);
		}
	}

	public function queryCreateOrderTable(string $table_prefix) {
		$this->load->model('extension/payment/cybersource_common');
		$this->load->language('extension/payment/cybersource_logger');
		if (!empty($table_prefix) && is_string($table_prefix)) {
			$query_table_prefix = $table_prefix;
			$query_create_order = "CREATE TABLE IF NOT EXISTS `" . $this->db->escape($query_table_prefix . TABLE_ORDER) . "` (
				`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				`order_id` int(11) NOT NULL,
				`transaction_id` VARCHAR(26) NOT NULL,
				`tax_id` VARCHAR(10) NOT NULL,
				`cybersource_order_status` VARCHAR(64) NOT NULL,
				`oc_order_status` INT(10) NOT NULL,
				`payment_action` VARCHAR(12) NOT NULL,
				`currency` VARCHAR(3) NOT NULL,
				`order_quantity` INT(10) NOT NULL,
				`amount` DECIMAL( 20, 4 ) NOT NULL,
				`refunded_amount` DECIMAL( 20, 4 ) NOT NULL,
				`refunded_quantity` INT(10) NOT NULL,";
			if (TABLE_PREFIX_UNIFIED_CHECKOUT == $query_table_prefix) {
				$query_create_order .= "`payment_method` VARCHAR(20) NOT NULL,";
			}
			$query_create_order .= "`date_added` DATETIME NOT NULL
				)ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;";
			list($is_success, $query_response) = $this->errorHandler($query_create_order);
			if (!$is_success) {
				$this->model_extension_payment_cybersource_common->logger('[ModelExtensionPaymentCybersourceQuery]
				[queryCreateOrderTable]' . $this->language->get('error_create_table') . $table_prefix . TABLE_ORDER);
			}
		} else {
			$this->model_extension_payment_cybersource_common->logger('[ModelExtensionPaymentCybersourceQuery]
			[queryCreateOrderTable]' . $this->language->get('error_create_table') . $table_prefix . TABLE_ORDER);
		}
	}

	public function queryCreateCaptureTable(string $table_prefix) {
		$this->load->model('extension/payment/cybersource_common');
		$this->load->language('extension/payment/cybersource_logger');
		if (!empty($table_prefix) && is_string($table_prefix)) {
			$query_table_prefix = $table_prefix;
			$query_create_capture = "CREATE TABLE IF NOT EXISTS `" . $this->db->escape($query_table_prefix . TABLE_CAPTURE) . "` (
				`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				`order_id` int(11) NOT NULL,
				`transaction_id` VARCHAR(26) NOT NULL,
				`cybersource_order_status` VARCHAR(64) NOT NULL,
				`oc_order_status` INT(10) NOT NULL,
				`currency` VARCHAR(3) NOT NULL,
				`capture_quantity` INT(10) NOT NULL,
				`amount` DECIMAL( 20, 4 ) NOT NULL,
				`order_product_id` VARCHAR(25) NOT NULL,
				`sequence_count` INT(10) NOT NULL,
				`shipping_flag` VARCHAR(10) NOT NULL,
				`void_flag` VARCHAR(10) NOT NULL,
				`refunded_amount` DECIMAL( 20, 4 ) NOT NULL,
				`refunded_quantity` INT(10) NOT NULL,
				`date_added` DATETIME NOT NULL
				)ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;";
			list($is_success, $query_response) = $this->errorHandler($query_create_capture);
			if (!$is_success) {
				$this->model_extension_payment_cybersource_common->logger('[ModelExtensionPaymentCybersourceQuery]
				[queryCreateCaptureTable]' . $this->language->get('error_create_table') . $table_prefix . TABLE_CAPTURE);
			}
		} else {
			$this->model_extension_payment_cybersource_common->logger('[ModelExtensionPaymentCybersourceQuery]
			[queryCreateCaptureTable]' . $this->language->get('error_create_table') . $table_prefix . TABLE_CAPTURE);
		}
	}

	public function queryCreateAuthReversalTable(string $table_prefix) {
		$this->load->model('extension/payment/cybersource_common');
		$this->load->language('extension/payment/cybersource_logger');
		if (!empty($table_prefix) && is_string($table_prefix)) {
			$query_table_prefix = $table_prefix;
			$query_create_auth_reversal = "CREATE TABLE IF NOT EXISTS `" . $this->db->escape($query_table_prefix . TABLE_AUTH_REVERSAL) . "` (
				`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				`order_id` int(11) NOT NULL,
				`transaction_id` VARCHAR(26) NOT NULL,
				`cybersource_order_status` VARCHAR(64) NOT NULL,
				`oc_order_status` INT(10) NOT NULL,
				`currency` VARCHAR(3) NOT NULL,
				`amount` DECIMAL( 20, 4 ) NOT NULL,
				`date_added` DATETIME NOT NULL
				)ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;";
			list($is_success, $query_response) = $this->errorHandler($query_create_auth_reversal);
			if (!$is_success) {
				$this->model_extension_payment_cybersource_common->logger('[ModelExtensionPaymentCybersourceQuery]
				[queryCreateAuthReversalTable]' . $this->language->get('error_create_table') . $table_prefix . TABLE_AUTH_REVERSAL);
			}
		} else {
			$this->model_extension_payment_cybersource_common->logger('[ModelExtensionPaymentCybersourceQuery]
			[queryCreateAuthReversalTable]' . $this->language->get('error_create_table') . $table_prefix . TABLE_AUTH_REVERSAL);
		}
	}

	public function queryCreateVoidCaptureTable(string $table_prefix) {
		$this->load->model('extension/payment/cybersource_common');
		$this->load->language('extension/payment/cybersource_logger');
		if (!empty($table_prefix) && is_string($table_prefix)) {
			$query_table_prefix = $table_prefix;
			$query_create_void_capture_table = "CREATE TABLE IF NOT EXISTS `" . $this->db->escape($query_table_prefix . TABLE_VOID_CAPTURE) . "` (
				`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				`order_id` int(11) NOT NULL,
				`transaction_id` VARCHAR(26) NOT NULL,
				`cybersource_order_status` VARCHAR(64) NOT NULL,
				`oc_order_status` INT(10) NOT NULL,
				`currency` VARCHAR(3) NOT NULL,
				`amount` DECIMAL( 20, 4 ) NOT NULL,
				`date_added` DATETIME NOT NULL
				)ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;";
			list($is_success, $query_response) = $this->errorHandler($query_create_void_capture_table);
			if (!$is_success) {
				$this->model_extension_payment_cybersource_common->logger('[ModelExtensionPaymentCybersourceQuery]
				[queryCreateVoidCaptureTable]' . $this->language->get('error_create_table') . $table_prefix . TABLE_VOID_CAPTURE);
			}
		} else {
			$this->model_extension_payment_cybersource_common->logger('[ModelExtensionPaymentCybersourceQuery]
			[queryCreateVoidCaptureTable]' . $this->language->get('error_create_table') . $table_prefix . TABLE_VOID_CAPTURE);
		}
	}

	public function queryCreateRefundTable(string $table_prefix) {
		$this->load->model('extension/payment/cybersource_common');
		$this->load->language('extension/payment/cybersource_logger');
		if (!empty($table_prefix) && is_string($table_prefix)) {
			$query_table_prefix = $table_prefix;
			$query_create_refund_table = "CREATE TABLE IF NOT EXISTS `" . $this->db->escape($query_table_prefix . TABLE_REFUND) . "` (
				`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				`order_id` int(11) NOT NULL,
				`transaction_id` VARCHAR(26) NOT NULL,
				`cybersource_order_status` VARCHAR(64) NOT NULL,
				`oc_order_status` INT(10) NOT NULL,
				`currency` VARCHAR(3) NOT NULL,
				`refund_quantity` INT(10) NOT NULL,
				`amount` DECIMAL( 20, 4 ) NOT NULL,
				`order_product_id` VARCHAR(25) NOT NULL,
				`shipping_flag` VARCHAR(10) NOT NULL, 
				`void_flag` VARCHAR(10) NOT NULL, 
				`date_added` DATETIME NOT NULL 
				)ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;";
			list($is_success, $query_response) = $this->errorHandler($query_create_refund_table);
			if (!$is_success) {
				$this->model_extension_payment_cybersource_common->logger('[ModelExtensionPaymentCybersourceQuery]
				[queryCreateRefundTable]' . $this->language->get('error_create_table') . $table_prefix . TABLE_REFUND);
			}
		} else {
			$this->model_extension_payment_cybersource_common->logger('[ModelExtensionPaymentCybersourceQuery]
			[queryCreateRefundTable]' . $this->language->get('error_create_table') . $table_prefix . TABLE_REFUND);
		}
	}

	public function queryCreateVoidRefundTable(string $table_prefix) {
		$this->load->model('extension/payment/cybersource_common');
		$this->load->language('extension/payment/cybersource_logger');
		if (!empty($table_prefix) && is_string($table_prefix)) {
			$query_table_prefix = $table_prefix;
			$query_create_void_refund_table = "CREATE TABLE IF NOT EXISTS `" . $this->db->escape($query_table_prefix . TABLE_VOID_REFUND) . "` (
				`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				`order_id` int(11) NOT NULL,
				`transaction_id` VARCHAR(24) NOT NULL,
				`cybersource_order_status` VARCHAR(64) NOT NULL,
				`oc_order_status` INT(10) NOT NULL,
				`currency` VARCHAR(3) NOT NULL,
				`amount` DECIMAL( 20, 4 ) NOT NULL,
				`date_added` DATETIME NOT NULL
				)ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;";
			list($is_success, $query_response) = $this->errorHandler($query_create_void_refund_table);
			if (!$is_success) {
				$this->model_extension_payment_cybersource_common->logger('[ModelExtensionPaymentCybersourceQuery]
				[queryCreateVoidRefundTable]' . $this->language->get('error_create_table') . $table_prefix . TABLE_VOID_REFUND);
			}
		} else {
			$this->model_extension_payment_cybersource_common->logger('[ModelExtensionPaymentCybersourceQuery]
			[queryCreateVoidRefundTable]' . $this->language->get('error_create_table') . $table_prefix . TABLE_VOID_REFUND);
		}
	}

	public function queryCreateCDRTable(string $table_prefix) {
		$this->load->model('extension/payment/cybersource_common');
		$this->load->language('extension/payment/cybersource_logger');
		if (!empty($table_prefix) && is_string($table_prefix)) {
			$query_table_prefix = $table_prefix;
			$query_create_cdn_table = "CREATE TABLE IF NOT EXISTS `" . $this->db->escape($query_table_prefix . TABLE_CONVERSION_DETAIL_REPORT) . "`(
				`id` INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
				`merchant_reference` VARCHAR(10),
				`conversion_time` VARCHAR(22),
				`request_id` VARCHAR(26),
				`original_decision` VARCHAR(22),
				`new_decision` VARCHAR(22),
				`reviewer` VARCHAR(64),
				`reviewer_comments` VARCHAR(255),
				`queue` VARCHAR(22),
				`profile` VARCHAR(64) 
				)ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;";
			list($is_success, $query_response) = $this->errorHandler($query_create_cdn_table);
			if (!$is_success) {
				$this->model_extension_payment_cybersource_common->logger('[ModelExtensionPaymentCybersourceQuery]
				[queryCreateCDRTable]' . $this->language->get('error_create_table') . $table_prefix . TABLE_CONVERSION_DETAIL_REPORT);
			}
		} else {
			$this->model_extension_payment_cybersource_common->logger('[ModelExtensionPaymentCybersourceQuery]
			[queryCreateCDRTable]' . $this->language->get('error_create_table') . $table_prefix . TABLE_CONVERSION_DETAIL_REPORT);
		}
	}

	public function queryCreateTokenizationTable() {
		$this->load->model('extension/payment/cybersource_common');
		$this->load->language('extension/payment/cybersource_logger');
		$query_create_tokenization_table = "CREATE TABLE IF NOT EXISTS `" . $this->db->escape(TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_TOKENIZATION) . "` (
			`card_id` INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`customer_id` INT(10) NOT NULL,
			`transaction_id` VARCHAR(26) NOT NULL,
			`customer_name` VARCHAR(255) NOT NULL,
			`address_id` INT(10) UNSIGNED,
			`card_number` VARCHAR(19) NOT NULL,
			`expiry_month` VARCHAR(2) NOT NULL,
			`expiry_year` VARCHAR(4) NOT NULL,
			`payment_instrument_id` varchar(32) NOT NULL,
			`instrument_identifier_id` varchar(32) NOT NULL,
			`customer_token_id` varchar(32) NOT NULL,
			`default_state` INT(1) NOT NULL,
			`date_added` datetime,
			FOREIGN KEY (`address_id`)
			REFERENCES `" . DB_PREFIX . "address`(`address_id`)
			) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;";
		list($is_success, $query_response) = $this->errorHandler($query_create_tokenization_table);
		if (!$is_success) {
			$this->model_extension_payment_cybersource_common->logger('[ModelExtensionPaymentCybersourceQuery]
			[queryCreateTokenizationTable]' . $this->language->get('error_create_table') . TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_TOKENIZATION);
		}
	}

	public function queryCreateTokenCheckTable() {
		$this->load->model('extension/payment/cybersource_common');
		$this->load->language('extension/payment/cybersource_logger');
		$query_create_token_check_table = "CREATE TABLE IF NOT EXISTS `" . $this->db->escape(TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_TOKEN_CHECK) . "` (
			`card_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`customer_id` INT(10) NOT NULL,
			`counter` INT(10) NOT NULL,
			`date_added` datetime
			) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;";
		list($is_success, $query_response) = $this->errorHandler($query_create_token_check_table);
		if (!$is_success) {
			$this->model_extension_payment_cybersource_common->logger('[ModelExtensionPaymentCybersourceQuery]
			[queryCreateTokenCheckTable]' . $this->language->get('error_create_table') . TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_TOKEN_CHECK);
		}
	}

	public function queryCreateWebhookTable() {
		$this->load->model('extension/payment/cybersource_common');
		$this->load->language('extension/payment/cybersource_logger');
		$query_create_webhook_table = "CREATE TABLE IF NOT EXISTS `" . $this->db->escape(TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_WEBHOOK) . "` (
			`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`organization_id` VARCHAR(64) NOT NULL,
			`product_id` VARCHAR(64) NOT NULL,
			`digital_signature_key` VARCHAR(256) NOT NULL,
			`digital_signature_key_id` VARCHAR(256) NOT NULL,
			`webhook_id` VARCHAR(256) NOT NULL,
			`date_added` DATETIME NOT NULL
			)ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;";
		list($is_success, $query_response) = $this->errorHandler($query_create_webhook_table);
		if (!$is_success) {
			$this->model_extension_payment_cybersource_common->logger('[ModelExtensionPaymentCybersourceQuery]
			[queryCreateWebhookTable]' . $this->language->get('error_create_table') . TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_WEBHOOK);
		}
	}

	public function queryDropTable(string $table_name) {
		$this->load->language('extension/payment/cybersource_logger');
		if (!empty($table_name) && is_string($table_name)) {
			$query_table_name = $table_name;
			$query_drop_table = "DROP TABLE IF EXISTS `" . $this->db->escape($query_table_name) . "`;";
			list($is_success, $query_response) = $this->errorHandler($query_drop_table);
			if (!$is_success) {
				$this->model_extension_payment_cybersource_common->logger('[ModelExtensionPaymentCybersourceQuery]
				[queryDropTable]' . $this->language->get('error_drop_table') . $table_name);
			}
		} else {
			$this->model_extension_payment_cybersource_common->logger('[ModelExtensionPaymentCybersourceQuery]
			[queryDropTable]' . $this->language->get('error_drop_table') . $table_name);
		}
	}

	public function queryUpdateModification(int $status) {
		$this->load->language('extension/payment/cybersource_logger');
		if (isset($status) && is_int($status)) {
			$query_status = $status;
			$query_update_modification = "UPDATE `" . $this->db->escape(DB_PREFIX) . "modification` SET `status` = '" . (int)$query_status . "' WHERE `code` = '" . $this->db->escape(PAYMENT_GATEWAY) . "'";
			list($is_success, $query_response) = $this->errorHandler($query_update_modification);
			if (!$is_success) {
				$this->model_extension_payment_cybersource_common->logger('[ModelExtensionPaymentCybersourceQuery]
				[queryUpdateModification]' . $this->language->get('error_update_modification'));
			}
		} else {
			$this->model_extension_payment_cybersource_common->logger('[ModelExtensionPaymentCybersourceQuery]
			[queryUpdateModification]' . $this->language->get('error_update_modification'));
		}
	}

	public function queryInsertAuthReversalDetails(array $auth_reversal_details, string $table_prefix): bool {
		$return_response = false;
		if (!empty($auth_reversal_details) && is_array($auth_reversal_details) && !empty($table_prefix) && is_string($table_prefix)) {
			$query_insert_auth_reversal = "INSERT INTO `" . $this->db->escape($table_prefix . TABLE_AUTH_REVERSAL) . "` 
				SET `order_id` = '" . (int)$auth_reversal_details['order_id'] . "',
				`transaction_id` = '" . $this->db->escape($auth_reversal_details['transaction_id']) . "', 
				`cybersource_order_status` = '" . $this->db->escape($auth_reversal_details['cybersource_order_status']) . "', 
				`oc_order_status` = '" . $this->db->escape($auth_reversal_details['oc_order_status']) . "',
				`currency` = '" . $this->db->escape($auth_reversal_details['currency']) . "', 
				`amount` = '" . (float)$auth_reversal_details['amount'] . "',
				`date_added` = '" . $this->db->escape($auth_reversal_details['date_added']) . "'";
			list($is_success, $query_response) = $this->errorHandler($query_insert_auth_reversal);
			if ($is_success && VAL_NULL != $query_response) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryInsertCaptureDetails(array $capture_details, string $table_prefix): bool {
		$return_response = false;
		if (!empty($capture_details) && is_array($capture_details)  && !empty($table_prefix) && is_string($table_prefix)) {
			$query_insert_capture = "INSERT INTO `" . $this->db->escape($table_prefix . TABLE_CAPTURE) . "` 
				SET `order_id` = '" . (int)$capture_details['order_id'] . "',
				`transaction_id` = '" . $this->db->escape($capture_details['transaction_id']) . "', 
				`cybersource_order_status` = '" . $this->db->escape($capture_details['cybersource_order_status']) . "', 
				`oc_order_status` = '" . (int)$capture_details['oc_order_status'] . "',
				`currency` = '" . $this->db->escape($capture_details['currency']) . "', 
				`capture_quantity` = '" . (int)$capture_details['capture_quantity'] . "',
				`amount` = '" . (float)$capture_details['amount'] . "',
				`order_product_id` = '" . $this->db->escape($capture_details['order_product_id']) . "',
				`sequence_count` = '" . (int)$capture_details['sequence_count'] . "',
				`shipping_flag` = '" . $this->db->escape($capture_details['shipping_flag']) . "',
				`void_flag` = '" . $this->db->escape($capture_details['void_flag']) . "',
				`refunded_amount` = '" . $this->db->escape($capture_details['refunded_amount']) . "',
				`refunded_quantity` = '" . $this->db->escape($capture_details['refunded_quantity']) . "',
				`date_added` = '" . $this->db->escape($capture_details['date_added']) . "'";
			list($is_success, $query_response) = $this->errorHandler($query_insert_capture);
			if ($is_success && VAL_NULL != $query_response) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryInsertPartialCaptureDetails(array $partial_capture_details, string $table_prefix): bool {
		$return_response = false;
		if (!empty($partial_capture_details) && is_array($partial_capture_details) && !empty($table_prefix) && is_string($table_prefix)) {
			$query_insert_partial_capture = "INSERT INTO `" . $this->db->escape($table_prefix . TABLE_CAPTURE) . "` 
				SET `order_id` = '" . (int)$partial_capture_details['order_id'] . "',
				`transaction_id` = '" . $this->db->escape($partial_capture_details['transaction_id']) . "', 
				`cybersource_order_status` = '" . $this->db->escape($partial_capture_details['cybersource_order_status']) . "', 
				`oc_order_status` = '" . (int)$partial_capture_details['oc_order_status'] . "',
				`currency` = '" . $this->db->escape($partial_capture_details['currency']) . "', 
				`capture_quantity` = '" . (int)$partial_capture_details['capture_quantity'] . "',
				`amount` = '" . (float)$partial_capture_details['amount'] . "',
				`order_product_id` = '" . $this->db->escape($partial_capture_details['order_product_id']) . "',
				`sequence_count` = '" . (int)$partial_capture_details['sequence_count'] . "',
				`shipping_flag` = '" . $this->db->escape($partial_capture_details['shipping_flag']) . "',
				`void_flag` = '" . $this->db->escape($partial_capture_details['void_flag']) . "',
				`refunded_amount` = '" . $this->db->escape($partial_capture_details['refunded_amount']) . "',
				`refunded_quantity` = '" . $this->db->escape($partial_capture_details['refunded_quantity']) . "',
				`date_added` = '" . $this->db->escape($partial_capture_details['date_added']) . "'";
			list($is_success, $query_response) = $this->errorHandler($query_insert_partial_capture);
			if ($is_success && VAL_NULL != $query_response) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryInsertVoidCaptureDetails(array $void_capture_details, string $table_prefix): bool {
		$return_response = false;
		if (!empty($void_capture_details) && !empty($table_prefix) && is_array($void_capture_details) && is_string($table_prefix)) {
			$query_table_prefix = $table_prefix;
			$query_insert_void_capture = "INSERT INTO `" . $this->db->escape($query_table_prefix . TABLE_VOID_CAPTURE) . "` 
				SET `order_id` = '" . (int)$void_capture_details['order_id'] . "',
				`transaction_id` = '" . $this->db->escape($void_capture_details['transaction_id']) . "', 
				`cybersource_order_status` = '" . $this->db->escape($void_capture_details['cybersource_order_status']) . "', 
				`oc_order_status` = '" . $this->db->escape($void_capture_details['oc_order_status']) . "',
				`currency` = '" . $this->db->escape($void_capture_details['currency']) . "', 
				`amount` = '" . (float)$void_capture_details['amount'] . "',
				`date_added` = '" . $this->db->escape($void_capture_details['date_added']) . "'";
			list($is_success, $query_response) = $this->errorHandler($query_insert_void_capture);
			if ($is_success && VAL_NULL != $query_response) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryInsertRefundDetails(array $refund_details, string $table_prefix): bool {
		$return_response = false;
		if (!empty($refund_details) && !empty($table_prefix) && is_array($refund_details) && is_string($table_prefix)) {
			$query_table_prefix = $table_prefix;
			$query_insert_refund = "INSERT INTO `" . $this->db->escape($query_table_prefix . TABLE_REFUND) . "` 
				SET `order_id` = '" . (int)$refund_details['order_id'] . "',
				`transaction_id` = '" . $this->db->escape($refund_details['transaction_id']) . "', 
				`cybersource_order_status` = '" . $this->db->escape($refund_details['cybersource_order_status']) . "', 
				`oc_order_status` = '" . (int)$refund_details['oc_order_status'] . "',
				`currency` = '" . $this->db->escape($refund_details['currency']) . "', 
				`refund_quantity` = '" . (int)$refund_details['refund_quantity'] . "',
				`amount` = '" . (float)$refund_details['amount'] . "',
				`order_product_id` = '" . $this->db->escape($refund_details['order_product_id']) . "',
				`shipping_flag` = '" . $this->db->escape($refund_details['shipping_flag']) . "',
				`void_flag` = '" . $this->db->escape($refund_details['void_flag']) . "',
				`date_added` = '" . $this->db->escape($refund_details['date_added']) . "'";
			list($is_success, $query_response) = $this->errorHandler($query_insert_refund);
			if ($is_success && VAL_NULL != $query_response) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryInsertVoidRefundDetails(array $void_refund_details, string $table_name): bool {
		$return_response = false;
		if (!empty($void_refund_details) && !empty($table_name) && is_array($void_refund_details) && is_string($table_name)) {
			$query_table_name = $table_name;
			$query_insert_void_refund = "INSERT INTO `" . $this->db->escape($query_table_name . TABLE_VOID_REFUND) . "` 
				SET `order_id` = '" . (int)$void_refund_details['order_id'] . "',
				`transaction_id` = '" . $this->db->escape($void_refund_details['transaction_id']) . "', 
				`cybersource_order_status` = '" . $this->db->escape($void_refund_details['cybersource_order_status']) . "', 
				`oc_order_status` = '" . $this->db->escape($void_refund_details['oc_order_status']) . "',
				`currency` = '" . $this->db->escape($void_refund_details['currency']) . "', 
				`amount` = '" . (float)$void_refund_details['amount'] . "',
				`date_added` = '" . $this->db->escape($void_refund_details['date_added']) . "'";
			list($is_success, $query_response) = $this->errorHandler($query_insert_void_refund);
			if ($is_success && VAL_NULL != $query_response) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function getShippingOrderProductId(?int $order_id, string $table_prefix) {
		$return_response = VAL_NULL;
		if (!empty($order_id) && is_int($order_id)  && !empty($table_prefix) && is_string($table_prefix)) {
			$query_order_id = $order_id;
			$query_shipping_capture_data = "SELECT `order_product_id` 
				FROM `" . $this->db->escape($table_prefix . TABLE_CAPTURE) . "` 
				WHERE `order_id`= " . (int)$query_order_id . " AND 
				`order_product_id`= '" . $this->db->escape(SHIPPING_AND_HANDLING) . "'";
			list($is_success, $query_response) = $this->errorHandler($query_shipping_capture_data);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryCaptureDetails(?int $order_id, string $transaction_id, string $table_prefix): ?object {
		$return_response = VAL_NULL;
		if (!empty($order_id) && !empty($transaction_id) && is_numeric($transaction_id) && is_int($order_id) && !empty($table_prefix) && is_string($table_prefix)) {
			$query_order_id = $order_id;
			$query_transaction_id = $transaction_id;
			$query_capture_details = "SELECT `capture_quantity`, `order_product_id` FROM 
				`" . $this->db->escape($table_prefix . TABLE_CAPTURE) . "` WHERE `order_id` = " . (int)$query_order_id . " AND `void_flag` = 
				'" . $this->db->escape(VAL_FLAG_NO) . "' AND NOT `order_product_id` = '" . $this->db->escape(SHIPPING_AND_HANDLING) . "' AND `transaction_id` = 
				'" . $this->db->escape($query_transaction_id) . "'";
			list($is_success, $query_response) = $this->errorHandler($query_capture_details);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryCaptureTransactionId(?int $order_id, string $table_prefix): ?object {
		$return_response = VAL_NULL;
		if (!empty($order_id) && is_int($order_id) && !empty($table_prefix) && is_string($table_prefix)) {
			$query_order_id = $order_id;
			$query_capture_transaction_id = "SELECT `transaction_id` 
				FROM `" . $this->db->escape($table_prefix . TABLE_CAPTURE) . "` 
				WHERE `order_id` = " . (int)$query_order_id . " AND `void_flag` = '" . $this->db->escape(VAL_FLAG_NO) . "' 
				AND NOT `order_product_id` = '" . $this->db->escape(SHIPPING_AND_HANDLING) . "'
				GROUP BY `transaction_id`";
			list($is_success, $query_response) = $this->errorHandler($query_capture_transaction_id);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryOrderProductId(?int $order_id, ?string $order_product_id): ?object {
		$return_response = VAL_NULL;
		if (!empty($order_id) && !empty($order_product_id) && is_int($order_id) && is_string($order_product_id)) {
			$query_order_id = $order_id;
			$query_order_product_id = $order_product_id;
			$query_order_product = "SELECT `product_id` FROM 
				`" . $this->db->escape(DB_PREFIX) . "order_product` WHERE `order_id` = " . (int)$query_order_id . " AND `order_product_id` = 
				'" . (int)$this->db->escape($query_order_product_id) . "'";
			list($is_success, $query_response) = $this->errorHandler($query_order_product);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryOrderProductDetails(?int $order_id, string $table_prefix): ?object {
		$return_response = VAL_NULL;
		if (!empty($order_id) && is_int($order_id) && !empty($table_prefix) && is_string($table_prefix)) {
			$query_order_id = $order_id;
			$query_order_product_details = "SELECT `order_product_id`, `product_id`,
				`quantity` - (SELECT coalesce(sum(`capture_quantity`),0) FROM `" . $this->db->escape($table_prefix . TABLE_CAPTURE) . "` 
				as cc WHERE NOT cc.`order_product_id` = '" . $this->db->escape(SHIPPING_AND_HANDLING) . "' AND cc.`order_product_id` = op.`order_product_id` 
				AND `order_id` = " . (int)$query_order_id . ") AS quantity FROM `" . $this->db->escape(DB_PREFIX) . "order_product` as op
				WHERE op.`order_id` = " . (int)$query_order_id;
			list($is_success, $query_response) = $this->errorHandler($query_order_product_details);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryRefundCaptureAmount(?int $order_id, string $table_prefix): ?float {
		$amount = null;
		if (!empty($order_id) && is_int($order_id) && !empty($table_prefix) && is_string($table_prefix)) {
			$query_order_id = $order_id;
			$query_capture_amount = "SELECT `amount` FROM `" . $this->db->escape($table_prefix . TABLE_CAPTURE) . "` WHERE `order_id` =" . (int)$query_order_id . " LIMIT 1";
			list($is_success, $query_response) = $this->errorHandler($query_capture_amount);
			if ($is_success) {
				$amount = (!empty($query_response) && VAL_ZERO < $query_response->num_rows) ? $query_response->row['amount'] : VAL_ZERO;
			}
		}
		return (float)$amount;
	}

	public function queryUpdateProduct(array $order_product): ?bool {
		$return_response = VAL_NULL;
		if (!empty($order_product) && is_array($order_product)) {
			$query_order_product = $order_product;
			$query_update_product = "UPDATE `" . $this->db->escape(DB_PREFIX) . "product` SET quantity = (quantity + " . (int)$query_order_product['quantity'] . ") WHERE product_id = '" . (int)$query_order_product['product_id'] . "' AND subtract = '1'";
			list($is_success, $query_response) = $this->errorHandler($query_update_product);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryUpdateProductOption(array $order_product, array $order_option): ?bool {
		$return_response = VAL_NULL;
		if (!empty($order_product) && !empty($order_option) && is_array($order_product) && is_array($order_option)) {
			$query_order_product = $order_product;
			$query_order_option = $order_option;
			$query_update_product_option = "UPDATE " . $this->db->escape(DB_PREFIX) . "product_option_value SET quantity = (quantity + " . (int)$query_order_product['quantity'] . ") WHERE product_option_value_id = '" . (int)$query_order_option['product_option_value_id'] . "' AND subtract = '1'";
			list($is_success, $query_response) = $this->errorHandler($query_update_product_option);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryRefundDetailsFromCapture(?int $order_id, string $table_prefix): ?object {
		$return_response = VAL_NULL;
		if (!empty($order_id) && !empty($table_prefix) && is_int($order_id) && is_string($table_prefix)) {
			$query_order_id = $order_id;
			$query_table_prefix = $table_prefix;
			$query_refund_details = "SELECT `transaction_id`, `capture_quantity`, `refunded_quantity`, `amount`, 
				`refunded_amount`, `currency` FROM `" . $this->db->escape($query_table_prefix . TABLE_CAPTURE) . "`
				WHERE `void_flag`='" . $this->db->escape(VAL_FLAG_NO) . "' AND `order_id` =" . (int)$query_order_id;
			list($is_success, $query_response) = $this->errorHandler($query_refund_details);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryRefundDetails(?int $order_id, $order_product_id, string $table_prefix): ?object {
		$return_response = null;
		if (!empty($order_id) && !empty($order_product_id) && !empty($table_prefix) && is_int($order_id) && is_string($table_prefix)) {
			$query_order_id = $order_id;
			$query_order_product_id = $order_product_id;
			if (SHIPPING_AND_HANDLING == $order_product_id) {
				$query_refund_details = "SELECT `transaction_id`, `amount`, `refunded_amount`, `capture_quantity`, 
					`refunded_quantity`, `currency` FROM `" . $this->db->escape($table_prefix . TABLE_CAPTURE) . "`
					WHERE `order_id` = " . (int)$query_order_id . " AND (`order_product_id` = '" . $this->db->escape($query_order_product_id) . "' OR `order_product_id` = " . $this->db->escape(VAL_ZERO) . ") AND `refunded_amount` < `amount` AND void_flag ='" . $this->db->escape(VAL_FLAG_NO) . "'";
				list($is_success, $query_response) = $this->errorHandler($query_refund_details);
				if ($is_success && VAL_NULL != $query_response) {
					$return_response = $query_response;
				}
			} else {
				$query_refund_details = "SELECT `transaction_id`, `amount`, `refunded_amount`, `capture_quantity`, 
					`refunded_quantity`, `currency` FROM `" . $this->db->escape($table_prefix . TABLE_CAPTURE) . "`
					WHERE `order_id` = " . (int)$query_order_id . " AND (`order_product_id` = '" . (int)$this->db->escape($query_order_product_id) . "'
					OR `order_product_id` = " . $this->db->escape(VAL_ZERO) . ") AND `refunded_quantity` < `capture_quantity` AND 
					`refunded_amount` < `amount` AND void_flag ='" . $this->db->escape(VAL_FLAG_NO) . "'";
				list($is_success, $query_response) = $this->errorHandler($query_refund_details);
				if ($is_success && VAL_NULL != $query_response) {
					$return_response = $query_response;
				}
			}
		}
		return $return_response;
	}

	public function queryUpdateRefundDetails(string $table_name, int $quantity, float $amount, string $transaction_id): bool {
		$return_response = false;
		if (!empty($table_name) && !empty($transaction_id)
			&& is_string($table_name) && is_int($quantity) && is_float($amount) && is_string($transaction_id)) {
			$query_table_name = $table_name;
			$query_quantity = $quantity;
			$query_amount = $amount;
			$query_transaction_id = $transaction_id;
			$is_updation_success = "UPDATE `" . $this->db->escape($query_table_name) . "` 
				SET `refunded_quantity` = " . (int)$query_quantity . ", `refunded_amount` = '" . (float)$query_amount . "' 
				WHERE `transaction_id` = '" . $this->db->escape($query_transaction_id) . "' ";
			list($is_success, $query_response) = $this->errorHandler($is_updation_success);
			if ($is_success  && VAL_NULL != $query_response) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryUpdateRefundDetailsForCapture($order_product_id, string $transaction_id, int $quantity, float $amount, string $table_prefix): bool {
		$return_response = false;
		if (!empty($order_product_id) && !empty($transaction_id)
			&& is_string($transaction_id) && !empty($table_prefix) && is_string($table_prefix) && is_int($quantity) && is_float($amount) && (SHIPPING_AND_HANDLING == $order_product_id && VAL_ZERO == $quantity) || VAL_ZERO < $quantity) {
			$query_order_product_id = $order_product_id;
			$query_transaction_id = $transaction_id;
			$query_quantity = $quantity;
			$query_amount = $amount;
			$query_table_prefix = $table_prefix;
			$is_updation_success = "UPDATE `" . $this->db->escape($query_table_prefix . TABLE_CAPTURE) . "` SET 
				`refunded_quantity` = " . (int)$query_quantity . ", 
				`refunded_amount` = " . (float)$query_amount . " 
				WHERE `transaction_id` = '" . $this->db->escape($query_transaction_id) . "' 
				AND (`order_product_id` = '" . $this->db->escape($query_order_product_id) . "' OR 
				`order_product_id` = '" . $this->db->escape(VAL_ZERO) . "')";
			list($is_success, $query_response) = $this->errorHandler($is_updation_success);
			if ($is_success && VAL_NULL != $query_response) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryProductIdForRefund(?int $order_id, string $order_product_id): ?object {
		$return_response = null;
		if (!empty($order_id) && !empty($order_product_id) && is_int($order_id) && is_string($order_product_id)) {
			$query_order_id = $order_id;
			$query_order_product_id = $order_product_id;
			$query_product_id = "SELECT `product_id` FROM 
				`" . $this->db->escape(DB_PREFIX) . "order_product` WHERE `order_id` = " . (int)$query_order_id . " AND NOT `order_product_id` = 
				'" . $this->db->escape(SHIPPING_AND_HANDLING) . "' AND `order_product_id` = " . (int)$this->db->escape($query_order_product_id);
			list($is_success, $query_response) = $this->errorHandler($query_product_id);
			if ($is_success && VAL_NULL != $query_response) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryPaymentConfiguration(): bool {
		$is_payment_configured = false;
		$query_payment_configuration = "SELECT `extension_id` FROM `" . $this->db->escape(DB_PREFIX) . "extension` WHERE code = '" . $this->db->escape(PAYMENT_CONFIGURATION) . "'";
		list($is_success, $query_response) = $this->errorHandler($query_payment_configuration);
		if ($is_success && !empty($query_response)) {
			$is_payment_configured = ($query_response->num_rows > VAL_ZERO) ? true : false;
		}
		return $is_payment_configured;
	}

	public function queryTotalOrderQuantity(?int $order_id): ?object {
		$return_response = null;
		if (!empty($order_id) && is_int($order_id)) {
			$query_order_id = $order_id;
			$query_total_quantity = "SELECT sum(`quantity`) as `quantity`
				FROM `" . $this->db->escape(DB_PREFIX) . "order_product` WHERE `order_id` = " . (int)$query_order_id;
			list($is_success, $query_response) = $this->errorHandler($query_total_quantity);
			if ($is_success && VAL_NULL != $query_response) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryPartialAmount(?int $order_id, string $table_prefix): ?object {
		$return_response = null;
		if (!empty($order_id) && is_int($order_id) && !empty($table_prefix) && is_string($table_prefix)) {
			$query_order_id = $order_id;
			$query_table_prefix = $table_prefix;
			$query_partial_amount =	"SELECT sum(`amount`) as amount
				FROM `" . $this->db->escape($query_table_prefix . TABLE_CAPTURE) . "` 
				WHERE `order_id` = " . (int)$query_order_id;
			list($is_success, $query_response) = $this->errorHandler($query_partial_amount);
			if ($is_success && VAL_NULL != $query_response) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryCapturedQuantity(?int $order_id, string $table_prefix): ?object {
		$return_response = null;
		if (!empty($order_id) && is_int($order_id) && !empty($table_prefix) && is_string($table_prefix)) {
			$query_order_id = $order_id;
			$query_table_prefix = $table_prefix;
			$query_captured_quantity = "SELECT sum(`capture_quantity`) AS `quantity`
				FROM `" . $this->db->escape($query_table_prefix . TABLE_CAPTURE) . "` 
				WHERE `order_id` = " . (int)$query_order_id;
			list($is_success, $query_response) = $this->errorHandler($query_captured_quantity);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function querySequenceCount(?int $order_id, string $table_prefix): ?object {
		$return_response = null;
		if (!empty($order_id) && is_int($order_id) && !empty($table_prefix) && is_string($table_prefix)) {
			$query_order_id = $order_id;
			$query_table_prefix = $table_prefix;
			$query_seq_count = "SELECT `sequence_count` 
				FROM `" . $this->db->escape($query_table_prefix . TABLE_CAPTURE) . "` 
				WHERE `order_id` = " . (int)$query_order_id;
			list($is_success, $query_response) = $this->errorHandler($query_seq_count);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function querySequenceCountOrder(?int $order_id, string $table_prefix): ?object {
		$return_response = null;
		if (!empty($order_id) && is_int($order_id) && !empty($table_prefix) && is_string($table_prefix)) {
			$query_order_id = $order_id;
			$query_table_prefix = $table_prefix;
			$query_seq_count = "SELECT `sequence_count`
				FROM `" . $this->db->escape($query_table_prefix . TABLE_CAPTURE) . "` 
				WHERE `order_id` = " . (int)$query_order_id . " ORDER BY `sequence_count` 
				DESC LIMIT 1";
			list($is_success, $query_response) = $this->errorHandler($query_seq_count);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function querySequenceCountId(?int $order_id, string $table_prefix): ?object {
		$return_response = null;
		if (!empty($order_id) && is_int($order_id) && !empty($table_prefix) && is_string($table_prefix)) {
			$query_order_id = $order_id;
			$query_table_prefix = $table_prefix;
			$query_seq_count_id = "SELECT `id`
				FROM `" . $this->db->escape($query_table_prefix . TABLE_CAPTURE) . "` 
				WHERE `order_id` = " . (int)$query_order_id . " ORDER BY `sequence_count` 
				DESC LIMIT 1";
			list($is_success, $query_response) = $this->errorHandler($query_seq_count_id);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryVoidId(?int $order_id, string $table_name): ?object {
		$return_response = null;
		if (!empty($order_id) && is_int($order_id) && !empty($table_name) && is_string($table_name)) {
			$query_order_id = $order_id;
			$query_table_name = $table_name;
			$query_void_id = "SELECT `id`
				FROM `" . $this->db->escape($query_table_name) . "`
				WHERE `order_id` = " . (int)$query_order_id . " ORDER BY `id` DESC LIMIT 1";
			list($is_success, $query_response) = $this->errorHandler($query_void_id);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryUpdateCaptureStatus(int $sequence_value, string $table_prefix): bool {
		$return_response = false;
		if (!empty($sequence_value) && is_int($sequence_value) && !empty($table_prefix) && is_string($table_prefix)) {
			$query_sequence_value = $sequence_value;
			$query_table_prefix = $table_prefix;
			$is_updation_success = "UPDATE `" . $this->db->escape($query_table_prefix . TABLE_CAPTURE) . "` 
				SET `oc_order_status` = " . (int)$this->config->get('module_' . PAYMENT_GATEWAY . '_capture_status_id') . " 
				WHERE `id` = " . (int)$query_sequence_value;
			list($is_success, $query_response) = $this->errorHandler($is_updation_success);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryUpdateVoidCaptureStatus(int $sequence_value, string $table_prefix): bool {
		$return_response = false;
		if (!empty($sequence_value) && is_int($sequence_value) && !empty($table_prefix) && is_string($table_prefix)) {
			$query_sequence_value = $sequence_value;
			$query_table_prefix = $table_prefix;
			$is_updation_success = "UPDATE `" . $this->db->escape($query_table_prefix . TABLE_VOID_CAPTURE) . "` 
				SET `oc_order_status` = " . (int)$this->config->get('module_' . PAYMENT_GATEWAY . '_void_status_id') . " 
				WHERE `id` = " . (int)$query_sequence_value;
			list($is_success, $query_response) = $this->errorHandler($is_updation_success);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryUpdateVoidCaptureFlag(string $transaction_id, string $table_prefix): bool {
		$return_response = false;
		if (!empty($transaction_id) && is_string($transaction_id) && !empty($table_prefix) && is_string($table_prefix)) {
			$query_transaction_id = $transaction_id;
			$query_table_prefix = $table_prefix;
			$is_updation_success = "UPDATE `" . $this->db->escape($query_table_prefix . TABLE_CAPTURE) . "` 
				SET `void_flag` = '" . $this->db->escape(VAL_FLAG_YES) . "' WHERE
				`transaction_id` = '" . $this->db->escape($query_transaction_id) . "'";
			list($is_success, $query_response) = $this->errorHandler($is_updation_success);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function querySequenceCountLimit(?int $order_id, string $table_prefix): ?object {
		$return_response = null;
		if (!empty($order_id) && is_int($order_id) && !empty($table_prefix) && is_string($table_prefix)) {
			$query_order_id = $order_id;
			$query_table_prefix = $table_prefix;
			$query_seq_count = "SELECT `sequence_count`
				FROM `" . $this->db->escape($query_table_prefix . TABLE_CAPTURE) . "`  
				WHERE `order_id` = '" . (int)$query_order_id . "' ORDER BY `sequence_count` DESC 
				LIMIT 0,1";
			list($is_success, $query_response) = $this->errorHandler($query_seq_count);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryPartialCaptureProductDetails(?int $order_id, $order_product_id): ?object {
		$return_response = null;
		if (!empty($order_id) && is_int($order_id)) {
			$query_order_id = $order_id;
			$query_order_product_id = $order_product_id;
			$query_product_details = "SELECT `order_product_id`,`product_id`,`name`,`model`,`quantity`,`price`,`total`,`tax`
				FROM `" . $this->db->escape(DB_PREFIX) . "order_product` WHERE `order_id` = '" . (int)$query_order_id . "' AND `order_product_id` = '" . (int)$this->db->escape($query_order_product_id) . "'";
			list($is_success, $query_response) = $this->errorHandler($query_product_details);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryCaptureProductDetails(?int $order_id, string $table_prefix): ?object {
		$return_response = null;
		if (!empty($order_id) && is_int($order_id) && !empty($table_prefix) && is_string($table_prefix)) {
			$query_order_id = $order_id;
			$query_table_prefix = $table_prefix;
			$query_product_details = "SELECT `order_product_id`, `name`, `price`, `product_id`,
				`model`, `total`, `tax`, `quantity` - (SELECT coalesce(sum(`capture_quantity`),0) FROM `" . $this->db->escape($query_table_prefix . TABLE_CAPTURE) . "` 
				as cc WHERE `order_id` = " . (int)$query_order_id . " AND NOT cc.`order_product_id` = '" . $this->db->escape(SHIPPING_AND_HANDLING) . "' AND op.`order_product_id` = cc.`order_product_id`) AS capture_quantity FROM `" . $this->db->escape(DB_PREFIX) . "order_product` as op
				WHERE op.`order_id` = " . (int)$query_order_id;
			list($is_success, $query_response) = $this->errorHandler($query_product_details);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryTransactionId(?int $order_id, string $table_name): ?object {
		$return_response = VAL_NULL;
		if (!empty($order_id) && !empty($table_name) && is_int($order_id) && is_string($table_name)) {
			$query_order_id = $order_id;
			$query_table_name = $table_name;
			$query_transaction_id = "SELECT `transaction_id` FROM `" . $this->db->escape($query_table_name) . "`
				WHERE order_id = '" . (int)$query_order_id . "'GROUP BY `transaction_id`";
			list($is_success, $query_response) = $this->errorHandler($query_transaction_id);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryNewDecision(string $start_time, string $end_time, string $table_name): ?object {
		$return_response = VAL_NULL;
		if (!empty($start_time) && !empty($end_time) && !empty($table_name)
			&& is_string($start_time) && is_string($end_time) && is_string($table_name)) {
			$query_start_time = $start_time;
			$query_end_time = $end_time;
			$query_table_name = $table_name;
			$query_new_decision = "SELECT `request_id`, `new_decision` 
				FROM `" . $this->db->escape($query_table_name . TABLE_CONVERSION_DETAIL_REPORT) . "` WHERE 
				`conversion_time` BETWEEN '" . $this->db->escape($query_start_time) . "' AND '" . $this->db->escape($query_end_time) . "'";
			list($is_success, $query_response) = $this->errorHandler($query_new_decision);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryPaymentActionforDM(string $request_id, string $table_name): ?object {
		$return_response = VAL_NULL;
		if (!empty($request_id) && !empty($table_name) && is_string($table_name) && is_string($request_id)) {
			$query_table_name = $table_name;
			$query_request_id = $request_id;
			$query_payment_action = "SELECT `payment_action`
				FROM `" . $this->db->escape($query_table_name . TABLE_ORDER) . "`
				WHERE `transaction_id` = '" . $this->db->escape($query_request_id) . "' AND 
				(`cybersource_order_status`='" . $this->db->escape(API_STATUS_AUTHORIZED_PENDING_REVIEW) . "' OR 
				`cybersource_order_status`='" . $this->db->escape(API_STATUS_PENDING_REVIEW) . "'
				)";
			list($is_success, $query_response) = $this->errorHandler($query_payment_action);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryUpdateOrderStatus(?string $status, ?int $oc_order_status, string $request_id, string $table_name): ?bool {
		$return_response = VAL_NULL;
		if (!empty($status) && !empty($oc_order_status) && !empty($request_id) && !empty($table_name)
			&& is_string($status) && is_int($oc_order_status) && is_string($request_id) && is_string($table_name)) {
			$query_status = $status;
			$query_request_id = $request_id;
			$query_oc_order_status = $oc_order_status;
			$query_table_name = $table_name;
			$query_update_order_status = "UPDATE `" . $this->db->escape($query_table_name . TABLE_ORDER) . "` SET 
				`cybersource_order_status`='" . $this->db->escape($query_status) . "',
				`oc_order_status`='" . (int)$query_oc_order_status . "' WHERE 
				`transaction_id`='" . $this->db->escape($query_request_id) . "'";
			list($is_success, $query_response) = $this->errorHandler($query_update_order_status);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryUpdateCustomOrderStatus(string $custom_status, ?int $order_id): ?bool {
		$return_response = VAL_NULL;
		if (!empty($order_id) && !empty($custom_status) && is_string($custom_status) && is_int($order_id)) {
			$query_custom_status = $custom_status;
			$query_order_id = $order_id;
			$query_update_status = "UPDATE `" . $this->db->escape(TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_ORDER_STATUS) . "` SET cybersource_order_status = '" . $this->db->escape($query_custom_status) . "' WHERE order_id = '" . (int)$query_order_id . "'";
			list($is_success, $query_response) = $this->errorHandler($query_update_status);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryOrderId(string $request_id, string $table_name): ?object {
		$return_response = VAL_NULL;
		if (!empty($request_id) && !empty($table_name) && is_string($table_name) && is_string($request_id)) {
			$query_request_id = $request_id;
			$query_table_name = $table_name;
			$query_order_id = "SELECT  `order_id` FROM `" . $this->db->escape($query_table_name . TABLE_ORDER) . "` WHERE 
				`transaction_id` = '" . $this->db->escape($query_request_id) . "'";
			list($is_success, $query_response) = $this->errorHandler($query_order_id);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryRefundAuthAmount(?int $order_id, string $table_name): array {
		$amount = null;
		$payment_action = null;
		if (!empty($order_id) && !empty($table_name) && is_int($order_id) && is_string($table_name)) {
			$query_table_name = $table_name;
			$query_order_id = $order_id;
			$query_auth_details = "SELECT `payment_action`, `amount` FROM `" . $this->db->escape($query_table_name) . "` 
		  		WHERE `order_id` =" . (int)$query_order_id;
			list($is_success, $query_response) = $this->errorHandler($query_auth_details);
			if ($is_success) {
				if (!empty($query_response) && VAL_ZERO < $query_response->num_rows) {
					$amount = $query_response->row['amount'];
					$payment_action = $query_response->row['payment_action'];
				}
			}
		}
		return array((float)$amount, $payment_action);
	}

	public function queryRefundDetailsFromAuth(?int $order_id, string $table_prefix): ?object {
		$return_response = null;
		if (!empty($order_id) && !empty($table_prefix) && is_int($order_id) && is_string($table_prefix)) {
			$query_table_prefix = $table_prefix;
			$query_order_id = $order_id;
			$query_refund_details = "SELECT `transaction_id`, `order_quantity`, `refunded_quantity`, `amount`, `refunded_amount`, `currency` 
				FROM `" . $this->db->escape($query_table_prefix . TABLE_ORDER) . "` WHERE `order_id` =" . (int)$query_order_id;
			list($is_success, $query_response) = $this->errorHandler($query_refund_details);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryCaptureAmountForRefund(string $payment_action, ?int $order_id, string $table_prefix): ?float {
		$capture_amount = null;
		if (!empty($order_id) && !empty($payment_action) && !empty($table_prefix)
			&& is_string($payment_action) && is_int($order_id) && is_string($table_prefix)) {
			$query_table_prefix = $table_prefix;
			$query_order_id = $order_id;
			$query_payment_action = $payment_action;
			if (PAYMENT_ACTION_SALE == $query_payment_action) {
				$query_capture_amount = "SELECT `amount` FROM `" . $this->db->escape($query_table_prefix . TABLE_ORDER) . "` WHERE `order_id` = " . (int)$query_order_id;
				list($is_success, $query_response) = $this->errorHandler($query_capture_amount);
				if ($is_success) {
					$capture_amount = empty($query_response->row) ? VAL_ZERO : (float)$query_response->row['amount'];
				}
			} elseif (PAYMENT_ACTION_AUTHORIZE == $query_payment_action) {
				$query_capture_amount = "SELECT sum(`amount`) AS amount 
					FROM `" . $this->db->escape($query_table_prefix . TABLE_CAPTURE) . "` where `order_id` = " . (int)$query_order_id . " AND
					`void_flag` = '" . $this->db->escape(VAL_FLAG_NO) . "'";
				list($is_success, $query_response) = $this->errorHandler($query_capture_amount);
				if ($is_success) {
					$capture_amount = empty($query_response->row) ? VAL_ZERO : (float)$query_response->row['amount'];
				}
			}
		}
		return $capture_amount;
	}

	public function queryRefundAmount(?int $order_id, string $table_prefix): ?float {
		$refund_amount = null;
		if (!empty($order_id) && !empty($table_prefix) && is_int($order_id) && is_string($table_prefix)) {
			$query_table_prefix = $table_prefix;
			$query_order_id = $order_id;
			$query_refund_amount = "SELECT sum(`amount`) AS amount 
				FROM `" . $this->db->escape($query_table_prefix . TABLE_REFUND) . "` where `order_id` = " . (int)$query_order_id . " AND
				`void_flag` ='" . $this->db->escape(VAL_FLAG_NO) . "'";
			list($is_success, $query_response) = $this->errorHandler($query_refund_amount);
			if ($is_success) {
				$refund_amount = (empty($query_response) && (VAL_ZERO > $query_response->num_rows)) ? VAL_ZERO : (float)$query_response->row['amount'];
			}
		}
		return $refund_amount;
	}

	public function queryUpdateRefundStatus(?int $order_id, int $oc_order_status, string $transaction_id, string $table_prefix): bool {
		$return_response = false;
		if (!empty($order_id) && !empty($oc_order_status) && !empty($transaction_id) && !empty($table_prefix)
			&& is_int($order_id) && is_int($oc_order_status) && is_string($transaction_id) && is_string($table_prefix)) {
			$query_table_prefix = $table_prefix;
			$query_order_id = $order_id;
			$query_oc_order_status = $oc_order_status;
			$query_transaction_id = $transaction_id;
			$is_updation_success = "UPDATE `" . $this->db->escape($query_table_prefix . TABLE_REFUND) . "` 
				SET `oc_order_status` = " . (int)$query_oc_order_status . " WHERE `transaction_id` = '" .
				$this->db->escape($query_transaction_id) . "' AND `order_id` = " . (int)$query_order_id;
			list($is_success, $query_response) = $this->errorHandler($is_updation_success);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryRefundQuantity(?int $order_id, string $table_name): ?object {
		$return_response = VAL_NULL;
		if (!empty($order_id) && !empty($table_name) && is_int($order_id) && is_string($table_name)) {
			$query_table_name = $table_name;
			$query_order_id = $order_id;
			$query_refund_details = "SELECT sum(`refund_quantity`) as `refund_quantity`, `order_product_id` FROM `" . $this->db->escape($query_table_name) . "` WHERE `order_id` ='" . (int)$query_order_id . "' group by order_product_id";
			list($is_success, $query_response) = $this->errorHandler($query_refund_details);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryOrderProductQuantity($order_product_id): ?object {
		$return_response = VAL_NULL;
		if (!empty($order_product_id)) {
			$query_order_product_id = $order_product_id;
			$query_quantity = "SELECT `quantity` FROM `" . $this->db->escape(DB_PREFIX) . "order_product` WHERE `order_product_id` = '" . (int)$this->db->escape($query_order_product_id) . "'";
			list($is_success, $query_response) = $this->errorHandler($query_quantity);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryVoidPartialAmount(?int $order_id, string $table_name): ?object {
		$return_response = VAL_NULL;
		if (!empty($order_id) && !empty($table_name) && is_int($order_id) && is_string($table_name)) {
			$query_table_name = $table_name;
			$query_order_id = $order_id;
			$query_capture_amount = "SELECT sum(`amount`) AS `amount`
				FROM `" . $this->db->escape($query_table_name) . "`
				WHERE `order_id` = " . (int)$query_order_id . " AND `void_flag` = '" . $this->db->escape(VAL_FLAG_YES) . "'";
			list($is_success, $query_response) = $this->errorHandler($query_capture_amount);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function getRefundShippingDetails(?int $order_id, string $table_name): ?object {
		$return_response = VAL_NULL;
		if (!empty($order_id) && !empty($table_name) && is_int($order_id) && is_string($table_name)) {
			$query_table_name = $table_name;
			$query_order_id = $order_id;
			$query_refund_shipping_flag = "SELECT count(`shipping_flag`) AS shipping_flag 
				FROM `" . $this->db->escape($query_table_name) . "`
				WHERE `order_id`=" . (int)$query_order_id . " AND `shipping_flag`='" . $this->db->escape(VAL_FLAG_YES) . "'";
			list($is_success, $query_response) = $this->errorHandler($query_refund_shipping_flag);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryVoidRefundIds(?int $order_id, string $table_name): ?object {
		$return_response = VAL_NULL;
		if (!empty($order_id) && !empty($table_name) && is_int($order_id) && is_string($table_name)) {
			$query_table_name = $table_name;
			$query_order_id = $order_id;
			$query_void_refund_ids = "SELECT `transaction_id` 
				FROM `" . $this->db->escape($query_table_name) . "` WHERE `order_id` = " . (int)$query_order_id . " AND 
				`void_flag` = '" . $this->db->escape(VAL_FLAG_NO) . "' GROUP BY `transaction_id`";
			list($is_success, $query_response) = $this->errorHandler($query_void_refund_ids);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryUpdateVoidRefundFlag(string $transaction_id, string $table_name): ?bool {
		$return_response = VAL_NULL;
		if (!empty($transaction_id) && !empty($table_name) && is_string($transaction_id) && is_string($table_name)) {
			$query_table_name = $table_name;
			$query_transaction_id = $transaction_id;
			$query_update_void_refund = "UPDATE `" . $this->db->escape($query_table_name) . "` 
				SET `void_flag` = '" . $this->db->escape(VAL_FLAG_YES) . "' WHERE `transaction_id` = '" .
				$this->db->escape($query_transaction_id) . "'";
			list($is_success, $query_response) = $this->errorHandler($query_update_void_refund);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function updateStatus($void_id, string $table_name): ?bool {
		$return_response = VAL_NULL;
		if (!empty($void_id) && !empty($table_name) && is_string($table_name)) {
			$query_update_status = "UPDATE `" . $this->db->escape($table_name) . "` 
			SET `oc_order_status` = " . (int)$this->config->get('module_' . PAYMENT_GATEWAY . '_void_status_id') . " WHERE 
			`id` = " . (int)$void_id;
			list($is_success, $query_response) = $this->errorHandler($query_update_status);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryUpdateOrderTable(int $order_status_id, ?int $order_id) {
		$return_response = VAL_NULL;
		if (!empty($order_id) && !empty($order_status_id) && is_int($order_id) && is_int($order_status_id)) {
			$query_order_status_id = $order_status_id;
			$query_order_id = $order_id;
			$query_update_order = "UPDATE `" . $this->db->escape(DB_PREFIX) . "order` SET order_status_id = '" . (int)$query_order_status_id . "', date_modified = NOW() WHERE order_id = '" . (int)$query_order_id . "'";
			list($is_success, $query_response) = $this->errorHandler($query_update_order);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryInsertOrderHistory(?int $order_id, int $order_status_id, $notify, ?string $comment): ?bool {
		$return_response = VAL_NULL;
		if (!empty($order_id) && !empty($order_status_id) && is_int($order_id) && is_int($order_status_id)) {
			$query_order_status_id = $order_status_id;
			$query_order_id = $order_id;
			$query_notify = $notify;
			$query_comment = $comment;
			$query_insert_order_history = "INSERT INTO " . $this->db->escape(DB_PREFIX) . "order_history SET order_id = '" . (int)$query_order_id . "', order_status_id = '" . (int)$query_order_status_id . "', notify = '" . (int)$query_notify . "', comment = '" . $this->db->escape($query_comment) . "', date_added = NOW()";
			list($is_success, $query_response) = $this->errorHandler($query_insert_order_history);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryTransactionDetails(?int $order_id, string $table_name): ?object {
		$return_response = VAL_NULL;
		if (!empty($order_id) && !empty($table_name) && is_int($order_id) && is_string($table_name)) {
			$query_table_name = $table_name;
			$query_order_id = $order_id;
			$query_transaction_details = "SELECT `order_id`,`transaction_id`,`amount`,`currency`,`order_quantity`
				FROM `" . $this->db->escape($query_table_name) . "` WHERE `order_id` = " . (int)$query_order_id;
			list($is_success, $query_response) = $this->errorHandler($query_transaction_details);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryShippingAmount(?int $order_id): ?float {
		$return_response = VAL_NULL;
		if (!empty($order_id) && is_int($order_id)) {
			$query_order_id = $order_id;
			$query_shipping_amount = 'SELECT DISTINCT `value` AS `shipping_cost` FROM `' . $this->db->escape(DB_PREFIX) . 'order_total` WHERE `order_id` = "' . (int)$query_order_id . '" and `code` = "' . $this->db->escape(SHIPPING) . '"';
			list($is_success, $query_response) = $this->errorHandler($query_shipping_amount);
			if ($is_success) {
				$return_response = (!empty($query_response) && VAL_ZERO < $query_response ->num_rows) ? (float)$query_response->row['shipping_cost'] : VAL_ZERO;
			}
		}
		return $return_response;
	}

	public function queryVoucherAmount(?int $order_id): ?float {
		$return_response = VAL_NULL;
		if (VAL_ZERO < $order_id) {
			$query_order_id = $order_id;
			$query_voucher_amount = 'SELECT `value` AS `voucher_amount` FROM `' . $this->db->escape(DB_PREFIX) . 'order_total` WHERE `order_id` ="' . (int)$query_order_id . '" AND `code` ="' . $this->db->escape(VOUCHER) . '"';
			list($is_success, $query_response) = $this->errorHandler($query_voucher_amount);
			if ($is_success) {
				$return_response = (!empty($query_response) && VAL_ZERO < $query_response->num_rows) ? (float)$query_response->row['voucher_amount'] : VAL_ZERO;
			}
		}
		return $return_response;
	}

	public function queryCouponAmount(?int $order_id): ?float {
		$coupon_amount = VAL_NULL;
		if (VAL_ZERO < $order_id) {
			$query_order_id = $order_id;
			$query_coupon_amount = 'SELECT `value` AS `coupon_amount` FROM `' . $this->db->escape(DB_PREFIX) . 'order_total` WHERE `order_id` ="' . (int)$query_order_id . '" AND `code` ="' . $this->db->escape(COUPON) . '"';
			list($is_success, $query_response) = $this->errorHandler($query_coupon_amount);
			if ($is_success) {
				$coupon_amount = (!empty($query_response) && VAL_ZERO < $query_response->num_rows) ? (float)$query_response->row['coupon_amount'] : VAL_ZERO;
			}
		}
		return $coupon_amount;
	}

	public function queryTaxAmount(?int $order_id): ?string {
		$return_response = VAL_NULL;
		if (!empty($order_id) && is_int($order_id)) {
			$query_order_id = $order_id;
			$query_tax_amount = 'SELECT sum(`value`) as `tax_amount` FROM `' . $this->db->escape(DB_PREFIX) . 'order_total` WHERE `order_id` = "' . (int)$query_order_id . '" AND (`code` = "' . $this->db->escape(TAX) . '" OR `code` = "' . $this->db->escape(PAYMENT_GATEWAY) . '")';
			list($is_success, $query_response) = $this->errorHandler($query_tax_amount);
			if ($is_success) {
				$return_response = str_replace(",", "", $query_response->row['tax_amount']);
			}
		}
		return $return_response;
	}

	public function queryProductTaxAmount(?int $order_id): ?string {
		$return_response = VAL_NULL;
		if (!empty($order_id) && is_int($order_id)) {
			$query_order_id = $order_id;
			$query_product_tax_amount = 'SELECT sum(`quantity` * `tax`) AS `tax_amount` FROM `' . $this->db->escape(DB_PREFIX) . 'order_product`
				WHERE `order_id` = "' . (int)$query_order_id . '"';
			list($is_success, $query_response) = $this->errorHandler($query_product_tax_amount);
			if ($is_success) {
				$return_response = str_replace(",", "", $query_response->row['tax_amount']);
			}
		}
		return $return_response;
	}

	public function queryInsertCDRTable(string $table_name, array $row): ?bool {
		$return_response = VAL_NULL;
		if (!empty($table_name) && !empty($row) && is_string($table_name) && is_array($row)) {
			$query_table_name = $table_name;
			$query_insert_cdn_table = "INSERT INTO `" . $this->db->escape($query_table_name) . "` 
				SET `merchant_reference` = '" . $this->db->escape($row['merchantReferenceNumber']) . "',
				`conversion_time` = '" . $this->db->escape($row['conversionTime']) . "', 
				`request_id` = '" . $this->db->escape($row['requestId']) . "', 
				`original_decision` = '" . $this->db->escape($row['originalDecision']) . "',
				`new_decision` = '" . $this->db->escape($row['newDecision']) . "', 
				`reviewer` = '" . $this->db->escape($row['reviewer']) . "', 
				`reviewer_comments` = '" . $this->db->escape($row['reviewerComments']) . "',
				`queue` = '" . $this->db->escape($row['queue']) . "',
				`profile` = '" . $this->db->escape($row['profile']) . "'";
			list($is_success, $query_response) = $this->errorHandler($query_insert_cdn_table);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryRequestId(string $table_name, string $request_id): ?object {
		$return_response = VAL_NULL;
		if (!empty($table_name) && !empty($request_id) && is_string($table_name) && is_string($request_id)) {
			$query_table_name = $table_name;
			$query_request_id = $request_id;
			$query_request_id = "SELECT `request_id` FROM `" . $this->db->escape($query_table_name) . "` 
				where `request_id` = '" . $this->db->escape($query_request_id) . "'";
			list($is_success, $query_response) = $this->errorHandler($query_request_id);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryOMRefundQuantity(string $table_prefix, string $order_product_id, ?int $order_id): ?object {
		$return_response = VAL_NULL;
		if (!empty($table_prefix) && !empty($order_product_id) && !empty($order_id)
			&& is_string($table_prefix) && is_string($order_product_id) && is_int($order_id)) {
			$query_table_prefix = $table_prefix;
			$query_order_id = $order_id;
			$query_order_product_id = $order_product_id;
			$query_refund_quantity = "SELECT DISTINCT sum(refund_quantity) AS `refund_quantity`
				FROM `" . $this->db->escape($query_table_prefix . TABLE_REFUND) . "`
				WHERE `order_product_id` = '" . $this->db->escape($query_order_product_id) . "' AND `order_id` = " . (int)$query_order_id;
			list($is_success, $query_response) = $this->errorHandler($query_refund_quantity);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryVoidRefundQuantity(string $table_prefix, string $order_product_id, ?int $order_id): ?object {
		$return_response = VAL_NULL;
		if (!empty($table_prefix) && !empty($order_product_id) && !empty($order_id)
			&& is_string($table_prefix) && is_string($order_product_id) && is_int($order_id)) {
			$query_table_prefix = $table_prefix;
			$query_order_id = $order_id;
			$query_order_product_id = $order_product_id;
			$query_void_refund_quantity = "SELECT DISTINCT sum(refund_quantity) as `void_refund_quantity`
				FROM `" . $this->db->escape($query_table_prefix . TABLE_REFUND) . "`
				WHERE `order_product_id` = '" . $this->db->escape($query_order_product_id) . "' AND `order_id` = 
				'" . (int)$query_order_id . "' AND `void_flag` ='" . $this->db->escape(VAL_FLAG_YES) . "'";
			list($is_success, $query_response) = $this->errorHandler($query_void_refund_quantity);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryCaptureQuantity(string $table_prefix, string $order_product_id, ?int $order_id): ?object {
		$return_response = VAL_NULL;
		if (!empty($table_prefix) && !empty($order_product_id) && !empty($order_id)
			&& is_string($table_prefix) && is_string($order_product_id) && is_int($order_id)) {
			$query_table_prefix = $table_prefix;
			$query_order_id = $order_id;
			$query_order_product_id = $order_product_id;
			$query_capture_quantity = "SELECT DISTINCT sum(capture_quantity) AS `capture_quantity`
				FROM `" . $this->db->escape($query_table_prefix . TABLE_CAPTURE) . "`
				WHERE `order_product_id` = '" . $this->db->escape($query_order_product_id) . "' AND `order_id` = " . (int)$query_order_id;
			list($is_success, $query_response) = $this->errorHandler($query_capture_quantity);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryVoidCaptureQuantity(string $table_prefix, string $order_product_id, ?int $order_id): ?object {
		$return_response = VAL_NULL;
		if (!empty($table_prefix) && !empty($order_product_id) && !empty($order_id)
			&& is_string($table_prefix) && is_string($order_product_id) && is_int($order_id)) {
			$query_table_prefix = $table_prefix;
			$query_order_id = $order_id;
			$query_order_product_id = $order_product_id;
			$query_void_capture_quantity = "SELECT DISTINCT sum(capture_quantity) as `void_capture_quantity`
				FROM `" . $this->db->escape($query_table_prefix . TABLE_CAPTURE) . "`
				WHERE `order_product_id` = '" . $this->db->escape($query_order_product_id) . "' AND `order_id` = 
				'" . (int)$query_order_id . "' AND `void_flag` = '" . $this->db->escape(VAL_FLAG_YES) . "'";
			list($is_success, $query_response) = $this->errorHandler($query_void_capture_quantity);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryShippingFlag(string $table_prefix, ?int $order_id): ?object {
		$return_response = VAL_NULL;
		if (!empty($order_id) && !empty($table_prefix) && is_int($order_id) && is_string($table_prefix)) {
			$query_table_prefix = $table_prefix;
			$query_order_id = $order_id;
			$query_shipping_flag = "SELECT  shipping_flag 
				FROM `" . $this->db->escape($query_table_prefix . TABLE_CAPTURE) . "`
				WHERE `order_id`=" . (int)$query_order_id . " AND `shipping_flag`='" . $this->db->escape(VAL_FLAG_YES) . "'";
			list($is_success, $query_response) = $this->errorHandler($query_shipping_flag);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryDataExists(string $table_name, ?int $order_id): ?int {
		$is_data_exists = VAL_NULL;
		if (!empty($table_name) && !empty($order_id) && is_string($table_name) && is_int($order_id)) {
			$query_table_name = $table_name;
			$query_order_id = $order_id;
			$query_data = 'SELECT `order_id` FROM `' . $this->db->escape($query_table_name) . '` WHERE `order_id` = ' . (int)$query_order_id;
			list($is_success, $query_response) = $this->errorHandler($query_data);
			if ($is_success) {
				$is_data_exists = (!empty($query_response) && VAL_ZERO < $query_response->num_rows) ? (int)$query_response->row['order_id'] : VAL_NULL;
			}
		}
		return $is_data_exists;
	}

	public function queryVoucherAvailable(?int $order_id): ?object {
		$return_response = VAL_NULL;
		if (!empty($order_id) && is_int($order_id)) {
			$query_order_id = $order_id;
			$query_voucher_available = 'SELECT `order_id` FROM `' . $this->db->escape(DB_PREFIX) . 'voucher_history` WHERE `order_id` = ' . (int)$query_order_id;
			list($is_success, $query_response) = $this->errorHandler($query_voucher_available);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryCouponAvailable(?int $order_id): ?object {
		$return_response = VAL_NULL;
		if (!empty($order_id) && is_int($order_id)) {
			$query_order_id = $order_id;
			$query_coupon_available = 'SELECT `order_id` FROM `' . $this->db->escape(DB_PREFIX) . 'coupon_history` WHERE `order_id` = ' . (int)$query_order_id;
			list($is_success, $query_response) = $this->errorHandler($query_coupon_available);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryCaptureDetail(?int $order_id, string $table_prefix): ?object {
		$return_response = VAL_NULL;
		if (VAL_ZERO < $order_id && !empty($table_prefix) && is_string($table_prefix)) {
			$query_order_id = $order_id;
			$query_capture_quantity = "SELECT sum(capture_quantity) as `capture_quantity`, order_product_id FROM `" . $this->db->escape($table_prefix . TABLE_CAPTURE) . "` WHERE `order_id` = '" . (int)$query_order_id . "' and void_flag = '" . $this->db->escape(VAL_FLAG_YES) . "' GROUP BY order_product_id";
			list($is_success, $query_response) = $this->errorHandler($query_capture_quantity);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryRewardPointsAmount(?int $order_id): ?float {
		$return_response = VAL_NULL;
		if (VAL_ZERO < $order_id) {
			$query_order_id = $order_id;
			$query_voucher_amount = 'SELECT `value` AS `reward_points` FROM `' . $this->db->escape(DB_PREFIX) . 'order_total` WHERE `order_id` ="' . (int)$query_order_id . '" AND `code` = "' . $this->db->escape(REWARD) . '"';
			list($is_success, $query_response) = $this->errorHandler($query_voucher_amount);
			if ($is_success) {
				$return_response = (!empty($query_response) && VAL_ZERO < $query_response->num_rows) ? (float)$query_response->row['reward_points'] : VAL_ZERO;
			}
		}
		return $return_response;
	}

	public function queryStoreCreditAmount(?int $order_id): ?float {
		$return_response = VAL_NULL;
		if (VAL_ZERO < $order_id) {
			$query_order_id = $order_id;
			$query_voucher_amount = 'SELECT `value` AS `store_credit` FROM `' . $this->db->escape(DB_PREFIX) . 'order_total` WHERE `order_id` = "' . (int)$query_order_id . '" AND `code` = "' . $this->db->escape(CREDIT) . '"';
			list($is_success, $query_response) = $this->errorHandler($query_voucher_amount);
			if ($is_success) {
				$return_response = (!empty($query_response) && VAL_ZERO < $query_response->num_rows) ? (float)$query_response->row['store_credit'] : VAL_ZERO;
			}
		}
		return $return_response;
	}

	public function queryOrderStatus(?int $order_id): ?object {
		$return_response = VAL_NULL;
		if (!empty($order_id) && is_int($order_id)) {
			$query_order_id = $order_id;
			$query_order_status = "SELECT `cybersource_order_status` FROM `" . $this->db->escape(TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_ORDER_STATUS) . "` 
			WHERE order_id = '" . (int)$query_order_id . "'";
			list($is_success, $query_response) = $this->errorHandler($query_order_status);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryApiOrderStatus(?int $order_id, string $table_prefix): ?object {
		$return_response = VAL_NULL;
		if (!empty($order_id) && !empty($table_prefix) && is_int($order_id) && is_string($table_prefix)) {
			$query_table_prefix = $table_prefix;
			$query_order_id = $order_id;
			$query_api_order_status = "SELECT `cybersource_order_status` FROM `" . $this->db->escape($query_table_prefix . TABLE_ORDER) . "`
				WHERE order_id = '" . (int)$query_order_id . "'";
			list($is_success, $query_response) = $this->errorHandler($query_api_order_status);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryAuthDetails(?int $order_id, string $table_prefix): ?object {
		$return_response = VAL_NULL;
		if (!empty($order_id) && !empty($table_prefix) && is_string($table_prefix) && is_int($order_id)) {
			$query_table_prefix = $table_prefix;
			$query_order_id = $order_id;
			$query_auth_details = 'SELECT `order_quantity`, `amount` FROM `' . $this->db->escape($query_table_prefix . TABLE_ORDER) . '` WHERE `order_id` = ' . (int)$query_order_id;
			list($is_success, $query_response) = $this->errorHandler($query_auth_details);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryAuthReversalAmount(?int $order_id, string $table_prefix): ?object {
		$return_response = VAL_NULL;
		if (!empty($order_id) && is_int($order_id) && !empty($table_prefix) && is_string($table_prefix)) {
			$query_order_id = $order_id;
			$query_table_prefix = $table_prefix;
			$query_auth_reversal_amount = 'SELECT `amount` FROM `' . $this->db->escape($query_table_prefix . TABLE_AUTH_REVERSAL) . '` WHERE `order_id` = ' . (int)$query_order_id;
			list($is_success, $query_response) = $this->errorHandler($query_auth_reversal_amount);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryCaptureQuantityDetails(?int $order_id, string $table_prefix): ?object {
		$return_response = VAL_NULL;
		if (!empty($order_id) && is_int($order_id) && !empty($table_prefix) && is_string($table_prefix)) {
			$query_order_id = $order_id;
			$query_table_prefix = $table_prefix;
			$query_capture_details = 'SELECT sum(`capture_quantity`) AS capture_quantity, sum(`amount`) AS `capture_amount` FROM `' . $this->db->escape($query_table_prefix . TABLE_CAPTURE) . '` WHERE `order_id` = ' . (int)$query_order_id;
			list($is_success, $query_response) = $this->errorHandler($query_capture_details);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryVoidCaptureDetails(?int $order_id, string $table_prefix): ?object {
		$return_response = VAL_NULL;
		if (!empty($order_id) && is_int($order_id) && !empty($table_prefix) && is_string($table_prefix)) {
			$query_order_id = $order_id;
			$query_table_prefix = $table_prefix;
			$query_void_captured = 'SELECT `order_id` FROM `' . $this->db->escape($query_table_prefix . TABLE_CAPTURE) . '` WHERE `order_id` = ' . (int)$query_order_id . ' AND `void_flag` = "' . $this->db->escape(VAL_FLAG_NO) . '"';
			list($is_success, $query_response) = $this->errorHandler($query_void_captured);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryVoidQuantity(?int $order_id, string $table_prefix): ?object {
		$return_response = VAL_NULL;
		if (!empty($order_id) && is_int($order_id) && !empty($table_prefix) && is_string($table_prefix)) {
			$query_order_id = $order_id;
			$query_table_prefix = $table_prefix;
			$query_void_quantity = 'SELECT sum(`capture_quantity`) AS void_quantity
				FROM `' . $this->db->escape($query_table_prefix . TABLE_CAPTURE) . '`
				WHERE `order_id` = "' . (int)$query_order_id . '" and void_flag = "' . $this->db->escape(VAL_FLAG_YES) . '"';
			list($is_success, $query_response) = $this->errorHandler($query_void_quantity);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryRefundQuantityDetails(?int $order_id, string $table_prefix): ?object {
		$return_response = VAL_NULL;
		if (!empty($order_id) && !empty($table_prefix) && is_int($order_id) && is_string($table_prefix)) {
			$query_table_prefix = $table_prefix;
			$query_order_id = $order_id;
			$query_refund_quantity = 'SELECT DISTINCT sum(refund_quantity) AS `refund_quantity`
				FROM `' . $this->db->escape($query_table_prefix . TABLE_REFUND) . '`
				WHERE `order_id` = "' . (int)$query_order_id . '"';
			list($is_success, $query_response) = $this->errorHandler($query_refund_quantity);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryRefundOrderProductId(?int $order_id, string $table_prefix): ?object {
		$return_response = VAL_NULL;
		if (!empty($order_id) && !empty($table_prefix) && is_int($order_id) && is_string($table_prefix)) {
			$query_table_prefix = $table_prefix;
			$query_order_id = $order_id;
			$query_refund_quantity = 'SELECT `order_product_id`, `amount` FROM `' . $this->db->escape($query_table_prefix . TABLE_REFUND) . '`
				WHERE `order_id` = ' . (int)$query_order_id . '';
			list($is_success, $query_response) = $this->errorHandler($query_refund_quantity);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryVoidRefundDetails(?int $order_id, string $table_prefix): ?object {
		$return_response = VAL_NULL;
		if (!empty($order_id) && !empty($table_prefix) && is_int($order_id) && is_string($table_prefix)) {
			$query_table_prefix = $table_prefix;
			$query_order_id = $order_id;
			$query_void_refunded = 'SELECT `order_id` FROM `' . $this->db->escape($query_table_prefix . TABLE_REFUND) . '` WHERE `order_id` = ' . (int)$query_order_id . ' AND `void_flag` = "' . $this->db->escape(VAL_FLAG_NO) . '"';
			list($is_success, $query_response) = $this->errorHandler($query_void_refunded);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryShippingCost(?int $order_id): ?object {
		$return_response = VAL_NULL;
		if (!empty($order_id) && is_int($order_id)) {
			$query_order_id = $order_id;
			$query_shipping_amount = "SELECT DISTINCT value AS `shipping_cost` FROM `" . $this->db->escape(DB_PREFIX) . "order_total`
				WHERE `order_id` = " . (int)$query_order_id . " AND `code` = '" . $this->db->escape(SHIPPING) . "'";
			list($is_success, $query_response) = $this->errorHandler($query_shipping_amount);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryRefundShipping(?int $order_id, string $table_prefix): ?object {
		$return_response = VAL_NULL;
		if (!empty($order_id) && !empty($table_prefix) && is_int($order_id) && is_string($table_prefix)) {
			$query_table_prefix = $table_prefix;
			$query_order_id = $order_id;
			$query_refund_shipping_check = "SELECT count(`shipping_flag`) AS shipping_flag 
				FROM `" . $this->db->escape($query_table_prefix . TABLE_REFUND) . "`
				WHERE `order_id`=" . (int)$query_order_id . " AND `shipping_flag`='" . $this->db->escape(VAL_FLAG_YES) . "'";
			list($is_success, $query_response) = $this->errorHandler($query_refund_shipping_check);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryVoidShippingDetails(?int $order_id, string $table_prefix): ?object {
		$return_response = VAL_NULL;
		if (!empty($order_id) && is_int($order_id) && !empty($table_prefix) && is_string($table_prefix)) {
			$query_order_id = $order_id;
			$query_table_prefix = $table_prefix;
			$query_shipping_void_check = 'SELECT `order_id` 
				FROM `' . $this->db->escape($query_table_prefix . TABLE_CAPTURE) . '` WHERE `order_id` = ' .
				(int)$query_order_id . ' AND `shipping_flag` = "' . $this->db->escape(VAL_FLAG_YES) . '"  AND `void_flag` = "' . $this->db->escape(VAL_FLAG_YES) . '"';
			list($is_success, $query_response) = $this->errorHandler($query_shipping_void_check);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function getPaymentAction(?int $or_num, string $tab_name, $user_token): ?object {
		$return_response = VAL_NULL;
		$user_token_data = $this->session->data['user_token'] ?? VAL_EMPTY;
		if (VAL_ZERO == strcmp($user_token_data, $user_token)) {
			$tab_name = htmlspecialchars($this->db->escape($tab_name));
			if (!empty($or_num) && is_int($or_num) && !empty($tab_name) && preg_match(REGEX_STRING_UNDERSCORE, $tab_name)) {
				$query_order_id = $or_num;
				$tabel_name = $tab_name;
				$action_part_one = "SELECT `payment_action`, `amount`";
				$action_part_two = " FROM `";
				$action_part_three = "` WHERE `order_id` = ";
				$payment_action = $this->db->escape($action_part_one . $action_part_two . $tabel_name . $action_part_three . (int)$query_order_id);
				list($is_success, $response) = $this->errorHandler($payment_action);
				if ($is_success) {
					$return_response = $response;
				}
			}
		}
		return $return_response;
	}

	/**
	 * Gives user selected payment method name, based on specified order id.
	 *
	 * @param int $order_id order id
	 * @param string $table_name table name
	 *
	 * @return object|null
	 */
	public function queryUcPaymentMethod(?int $order_id, string $table_name): ?object {
		$return_response = VAL_NULL;
		if (!empty($order_id) && !empty($table_name) && is_int($order_id) && is_string($table_name)) {
			$query_table_name = $table_name;
			$query_order_id = $order_id;
			$query_transaction_details = "SELECT `payment_method` FROM `" . $this->db->escape($query_table_name) . "` WHERE `order_id` = " . (int)$query_order_id;
			list($is_success, $query_response) = $this->errorHandler($query_transaction_details);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	/**
	 * Inserts webhook data.
	 *
	 * @param array $webhook_data
	 *
	 * @return bool
	 */
	public function queryInsertWebhookDetails(array $webhook_data): bool {
		$return_response = false;
		if (!empty($webhook_data) && is_array($webhook_data)) {
			$query_insert_webhook_data = "INSERT INTO `" . $this->db->escape(TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_WEBHOOK) . "` 
				SET `organization_id` = '" . $this->db->escape($webhook_data['organization_id']) . "', 
				`product_id` = '" . $this->db->escape($webhook_data['product_id']) . "', 
				`digital_signature_key` = '" . $this->db->escape($webhook_data['digital_signature_key']) . "', 
				`digital_signature_key_id` = '" . $this->db->escape($webhook_data['digital_signature_key_id']) . "',
				`webhook_id` = '" . $this->db->escape($webhook_data['webhook_id']) . "', 
				`date_added` = '" . CURRENT_DATE . "'";
			list($is_success, $query_response) = $this->errorHandler($query_insert_webhook_data);
			if ($is_success && VAL_NULL != $query_response) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	/**
	 * Fetches webhook data based on organization_id/merchant_id.
	 *
	 * @param string $merchant_id
	 * @param string $product_id
	 *
	 * @return array|null
	 */
	public function queryWebhookDetails(string $merchant_id, string $product_id): ?array {
		$return_response = VAL_NULL;
		if (!empty($merchant_id) && is_string($merchant_id) && !empty($product_id) && is_string($product_id)) {
			$query_merchant_id = $merchant_id;
			$query_product_id = $product_id;
			$query_webhook_details = "SELECT `webhook_id` FROM `" . $this->db->escape(TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_WEBHOOK) . "` WHERE `organization_id` = '" . $this->db->escape($query_merchant_id) . "' AND `product_id` = '" . $this->db->escape($query_product_id) . "'";
			list($is_success, $query_response) = $this->errorHandler($query_webhook_details);
			if ($is_success) {
				$return_response = (!empty($query_response) && VAL_ZERO < $query_response->num_rows) ? $query_response->row : VAL_NULL;
			}
		}
		return $return_response;
	}

	/**
	 * Removes webhook details.
	 *
	 * @param string $webhook_id
	 *
	 * @return bool|null
	 */
	public function queryDeleteWebhookDetails(string $webhook_id): ?bool {
		$return_response = VAL_NULL;
		if (!empty($webhook_id) && is_string($webhook_id)) {
			$query_webhook_id = $webhook_id;
			$query_delete_webhook_details = "DELETE FROM `" . $this->db->escape(TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_WEBHOOK) . "` WHERE `webhook_id`= '" . $this->db->escape($query_webhook_id) . "'";
			list($is_success, $query_response) = $this->errorHandler($query_delete_webhook_details);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}
}
