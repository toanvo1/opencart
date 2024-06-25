<?php

require_once DIR_SYSTEM . 'library/isv/cybersource_constants.php';

/**
 * All the queries related to FO will be in this file.
 *
 * @author Cybersource
 * @package Front Office
 * @subpackage Model
 */
class ModelExtensionPaymentCybersourceQuery extends Model {
	/**
	 * Gives applied voucher amount for specified order id.
	 *
	 * @param int $order_id order id
	 *
	 * @return object|null
	 */
	public function queryVoucherAmount(int $order_id): ?float {
		$return_response = VAL_NULL;
		if (VAL_ZERO < $order_id && is_int($order_id)) {
			$query_order_id = $order_id;
			$query_voucher_amount = 'SELECT `value` AS `voucher_amount` FROM `' . $this->db->escape(DB_PREFIX) . 'order_total` WHERE `order_id` ="' . (int)$query_order_id . '" AND `code` ="' . $this->db->escape(VOUCHER) . '"';
			list($is_success, $query_response) = $this->errorHandler($query_voucher_amount);
			if ($is_success) {
				$return_response = (!empty($query_response) && VAL_ZERO < $query_response->num_rows) ? (float)$query_response->row['voucher_amount'] : VAL_ZERO;
			}
		}
		return $return_response;
	}

	public function queryRewardPointsAmount(int $order_id): ?float {
		$return_response = VAL_NULL;
		if (VAL_ZERO < $order_id && is_int($order_id)) {
			$query_order_id = $order_id;
			$query_voucher_amount = 'SELECT `value` AS `reward_points` FROM `' . $this->db->escape(DB_PREFIX) . 'order_total` WHERE `order_id` ="' . (int)$query_order_id . '" AND `code` ="' . $this->db->escape(REWARD) . '"';
			list($is_success, $query_response) = $this->errorHandler($query_voucher_amount);
			if ($is_success) {
				$return_response = (!empty($query_response) && VAL_ZERO < $query_response->num_rows) ? (float)$query_response->row['reward_points'] : VAL_ZERO;
			}
		}
		return $return_response;
	}

	public function queryStoreCreditAmount(int $order_id): ?float {
		$return_response = VAL_NULL;
		if (VAL_ZERO < $order_id  && is_int($order_id)) {
			$query_order_id = $order_id;
			$query_voucher_amount = 'SELECT `value` AS `store_credit` FROM `' . $this->db->escape(DB_PREFIX) . 'order_total` WHERE `order_id` ="' . (int)$query_order_id . '" AND `code` ="' . $this->db->escape(CREDIT) . '"';
			list($is_success, $query_response) = $this->errorHandler($query_voucher_amount);
			if ($is_success) {
				$return_response = (!empty($query_response) && VAL_ZERO < $query_response->num_rows) ? (float)$query_response->row['store_credit'] : VAL_ZERO;
			}
		}
		return $return_response;
	}

	/**
	 * Gives applied coupon amount for specified order id.
	 *
	 * @param int $order_id order id
	 *
	 * @return object|null
	 */
	public function queryCouponAmount(int $order_id): ?float {
		$return_response = VAL_NULL;
		if (VAL_ZERO < $order_id && is_int($order_id)) {
			$query_order_id = $order_id;
			$query_coupon_amount = 'SELECT `value` AS `coupon_amount` FROM `' . $this->db->escape(DB_PREFIX) . 'order_total` WHERE `order_id` ="' . (int)$query_order_id . '" AND `code` ="' . $this->db->escape(COUPON) . '"';
			list($is_success, $query_response) = $this->errorHandler($query_coupon_amount);
			if ($is_success) {
				$return_response = (!empty($query_response) && VAL_ZERO < $query_response->num_rows) ? (float)$query_response->row['coupon_amount'] : VAL_ZERO;
			}
		}
		return $return_response;
	}

	/**
	 * Inserts data to order table.
	 *
	 * @param array $order_details contains all the details that are required to insert into order table.
	 * @param string $payment_method contains payment method.
	 *
	 * @return bool|null
	 */
	public function queryInsertOrder(array $order_details, string $payment_method): ?bool {
		$return_response = VAL_NULL;
		if (!empty($order_details) && !empty($payment_method) && is_array($order_details) && is_string($payment_method)) {
			$query_order_details = $order_details;
			$query_payment_method = $payment_method;
			$query_insert_order_details = "INSERT INTO  `" . $this->db->escape($query_payment_method . TABLE_ORDER) . "` 
				SET `order_id` = '" . (int)$query_order_details['order_id'] . "',
				`transaction_id` = '" . $this->db->escape($query_order_details['transaction_id']) . "',
				`tax_id` = '" . $this->db->escape($query_order_details['tax_id']) . "', 
				`cybersource_order_status` = '" . $this->db->escape($query_order_details['cybersource_order_status']) . "', 
				`oc_order_status` = '" . $this->db->escape($query_order_details['oc_order_status']) . "',
				`payment_action` = '" . $this->db->escape($query_order_details['payment_action']) . "', 
				`currency` = '" . $this->db->escape($query_order_details['currency']) . "', 
				`order_quantity` = '" . $this->db->escape($query_order_details['order_quantity']) . "',
				`amount` = '" . (float)$query_order_details['amount'] . "',";
			if (TABLE_PREFIX_UNIFIED_CHECKOUT == $payment_method) {
				$query_insert_order_details .= "`payment_method` = '" . $this->db->escape($query_order_details['payment_method']) . "',";
			}
			$query_insert_order_details .= "`date_added` = '" . $this->db->escape($query_order_details['date_added']) . "'";
			list($is_success, $query_response) = $this->errorHandler($query_insert_order_details);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	/**
	 * Gives zone code for specified zone id.
	 *
	 * @param int $zone_id zone id
	 *
	 * @return string|null
	 */
	public function queryZoneCode(string $zone_id): ?object {
		$return_response = null;
		if (!empty($zone_id) && is_string($zone_id)) {
			$query_zone_code = "SELECT `code` FROM `" . $this->db->escape(DB_PREFIX) . "zone` WHERE zone_id = '" . $this->db->escape($zone_id) . "'";
			list($is_success, $query_response) = $this->errorHandler($query_zone_code);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryZoneIdByName(string $zone_name): ?object {
		$return_response = null;
		if (!empty($zone_name) && is_string($zone_name)) {
			$query_zone_id_by_name = "SELECT `zone_id` FROM `" . $this->db->escape(DB_PREFIX) . "zone` WHERE name = '" . $this->db->escape($zone_name) . "'";
			list($is_success, $query_response) = $this->errorHandler($query_zone_id_by_name);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	public function queryIsoCode(string $country_id): ?object {
		$return_response = null;
		if (!empty($country_id) && is_string($country_id)) {
			$query_country_id = $country_id;
			$query_iso_code = "SELECT * FROM `" . $this->db->escape(DB_PREFIX) . "country` WHERE country_id = '" . $this->db->escape($query_country_id) . "'";
			list($is_success, $query_response) = $this->errorHandler($query_iso_code);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	/**
	 * Gives shipping code for specified order id.
	 *
	 * @param int $order_id order id
	 *
	 * @return object|null
	 */
	public function queryShippingCost(int $order_id): ?object {
		$return_response = null;
		if (!empty($order_id) && is_int($order_id)) {
			$query_order_id = $order_id;
			$query_shipping_cost = 'SELECT DISTINCT value AS `shipping_cost` FROM `' . $this->db->escape(DB_PREFIX) . 'order_total` WHERE `order_id` = "' . (int)$query_order_id . '" and `code` = "' . $this->db->escape(SHIPPING) . '"';
			list($is_success, $query_response) = $this->errorHandler($query_shipping_cost);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	/**
	 * Gives country id and name for specified country code.
	 *
	 * @param int $country_code country code
	 *
	 * @return object|null
	 */
	public function queryCountryDetails(string $country_code): ?object {
		$return_response = null;
		if (!empty($country_code) && is_string($country_code)) {
			$query_country_code = $country_code;
			$query_country_data = "SELECT `country_id`, `name` FROM `" . $this->db->escape(DB_PREFIX) . "country` WHERE iso_code_2 = '" . $this->db->escape($query_country_code) . "'";
			list($is_success, $query_response) = $this->errorHandler($query_country_data);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	/**
	 * Gives zone name for specified state iso code and country id.
	 *
	 * @param string $state_iso_code state iso code
	 * @param int $country_id country id
	 *
	 * @return object|null
	 */
	public function queryZoneName(string $state_iso_code, int $country_id): ?object {
		$return_response = null;
		if (!empty($state_iso_code) && !empty($country_id) && is_string($state_iso_code) && is_int($country_id)) {
			$query_state_iso_code = $state_iso_code;
			$query_country_id = $country_id;
			$query_zone_name = "SELECT `name` FROM `" . $this->db->escape(DB_PREFIX) . "zone` WHERE code = '" . $this->db->escape($query_state_iso_code) . "' AND country_id ='" . (int)$query_country_id . "'";
			list($is_success, $query_response) = $this->errorHandler($query_zone_name);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	/**
	 * Updates Opencart order table if user selects recommended address in DAV.
	 *
	 * @param string $shipping_address shipping address
	 * @param string $shipping_city shipping city
	 * @param string $shipping_state shipping state
	 * @param string $shipping_country shipping country
	 * @param string $shipping_postal shipping postal
	 * @param int $order_id order id
	 *
	 * @return bool|null
	 */
	public function queryUpdateOrder(string $shipping_address, string $shipping_city, string $shipping_state, string $shipping_zone_id, string $shipping_country, string $shipping_postal, ?int $order_id): ?bool {
		$return_response = null;
		if (is_string($shipping_address) && is_string($shipping_city) && is_string($shipping_state) && is_string($shipping_zone_id) && is_string($shipping_country) && is_string($shipping_postal) && !empty($order_id)) {
			$query_shipping_address = $shipping_address;
			$query_shipping_city = $shipping_city;
			$query_shipping_state = $shipping_state;
			$query_shipping_state_id = $shipping_zone_id;
			$query_shipping_country = $shipping_country;
			$query_shipping_postal_code = $shipping_postal;
			$query_order_id = $order_id;
			$query_update_address = "UPDATE `" . $this->db->escape(DB_PREFIX) . "order` SET shipping_address_1 = '" . $this->db->escape($query_shipping_address) . "', shipping_city = '" . $this->db->escape($query_shipping_city) . "', shipping_postcode = '" . $this->db->escape($query_shipping_postal_code) . "', shipping_country = '" . $this->db->escape($query_shipping_country) . "', shipping_zone = '" . $this->db->escape($query_shipping_state) . "',
				shipping_zone_id = '" . $this->db->escape($query_shipping_state_id) . "'	WHERE order_id = '" . (int)$query_order_id . "'";
			list($is_success, $query_response) = $this->errorHandler($query_update_address);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	/**
	 * Inserts data to dav table.
	 *
	 * @param array $address_details contains all the details that are required to insert into dav table.
	 *
	 * @return bool|null
	 */
	public function queryInsertDavDetails(array $address_details): ?bool {
		$return_response = null;
		if (!empty($address_details) && is_array($address_details)) {
			$query_address_details = $address_details;
			$query_insert_dav_details = "INSERT INTO `" . $this->db->escape(TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_DAV) . "` 
				SET `order_id` = '" . (int)$query_address_details['order_id'] . "',
				`transaction_id` = '" . $this->db->escape($query_address_details['transaction_id']) . "', 
				`recommended_address1` = '" . $this->db->escape($query_address_details['recommended_address1']) . "', 
				`recommended_city` = '" . $this->db->escape($query_address_details['recommended_city']) . "',
				`recommended_country` = '" . $this->db->escape($query_address_details['recommended_country']) . "', 
				`recommended_postal_code` = '" . $this->db->escape($query_address_details['recommended_postal_code']) . "',
				`recommended_zone` = '" . $this->db->escape($query_address_details['recommended_zone']) . "',
				`entered_address1` = '" . $this->db->escape($query_address_details['entered_address1']) . "',
				`entered_city` = '" . $this->db->escape($query_address_details['entered_city']) . "',
				`entered_country` = '" . $this->db->escape($query_address_details['entered_country']) . "', 
				`entered_postal_code` = '" . $this->db->escape($query_address_details['entered_postal_code']) . "',
				`entered_zone` = '" . $this->db->escape($query_address_details['entered_zone']) . "',
				`status` = '" . $this->db->escape($query_address_details['status']) . "',
				`date_added` = '" . $this->db->escape($query_address_details['date_added']) . "'";
			list($is_success, $query_response) = $this->errorHandler($query_insert_dav_details);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	/**
	 * Inserts data to tax table.
	 *
	 * @param array $tax_details contains all the details that are required to insert into tax table.
	 *
	 * @return bool|null
	 */
	public function queryInsertTaxDetails(array $tax_details): ?bool {
		$return_response = null;
		if (!empty($tax_details) && is_array($tax_details)) {
			$query_tax_details = $tax_details;
			$query_insert_tax_details = "INSERT INTO `" . $this->db->escape(TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_TAX) . "` 
				SET `tax_id` = '" . $this->db->escape($query_tax_details['tax_id']) . "',
				`transaction_id` = '" . $this->db->escape($query_tax_details['transaction_id']) . "', 
				`taxable_amount` = '" . (float)$query_tax_details['taxable_amount'] . "', 
				`tax_amount` = '" . (float)$query_tax_details['tax_amount'] . "',
				`total_amount` = '" . (float)$query_tax_details['total_amount'] . "', 
				`currency` = '" . $this->db->escape($query_tax_details['currency']) . "',
				`status` = '" . $this->db->escape($query_tax_details['status']) . "',
				`date_added` = '" . $this->db->escape($query_tax_details['date_added']) . "'";
			list($is_success, $query_response) = $this->errorHandler($query_insert_tax_details);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	/**
	 * Gives customer token id for specified customer id.
	 *
	 * @param int $customer_id customer id
	 *
	 * @return object|null
	 */
	public function queryCustomerTokenId(int $customer_id): ?object {
		$return_response = null;
		if (!empty($customer_id) && is_int($customer_id)) {
			$query_customer_id = $customer_id;
			$query_customer_token_id = "SELECT `customer_token_id` FROM `" . $this->db->escape(TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_TOKENIZATION) . "` WHERE `customer_id`=" . (int)$query_customer_id;
			list($is_success, $query_response) = $this->errorHandler($query_customer_token_id);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	/**
	 * Fetch and Gives trasaction id and amount form order table for specified order id.
	 *
	 * @param int $order_id order id
	 * @param string $table_prefix
	 *
	 * @return object|null
	 */
	public function queryTransactionDetails(int $order_id, string $table_prefix): ?object {
		$return_response = null;
		if (!empty($order_id) && is_int($order_id) && !empty($table_prefix) && is_string($table_prefix)) {
			$query_order_id = $order_id;
			$query_table_prefix = $table_prefix;
			$query_transaction_data = "SELECT `transaction_id`,`amount` FROM `" . $this->db->escape($query_table_prefix . TABLE_ORDER) . "` WHERE `order_id` = " . (int)$query_order_id;
			list($is_success, $query_response) = $this->errorHandler($query_transaction_data);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	/**
	 * Inserts data to tokenization table.
	 *
	 * @param array $token_details contains all the details that are required to insert into tokenization table.
	 *
	 * @return bool|null
	 */
	public function queryInsertTokenizationDetails(array $token_details): ?bool {
		$return_response = null;
		if (!empty($token_details) && is_array($token_details)) {
			$query_token_details = $token_details;
			$query_insert_token_data = "INSERT INTO `" . $this->db->escape(TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_TOKENIZATION) . "` 
				SET `transaction_id` = " . (int)($query_token_details['transaction_id']) . ", 
				`customer_name` = '" . $this->db->escape($query_token_details['customer_name']) . "', 
				`customer_id` = " . (int)($query_token_details['customer_id']) . ",
				`address_id` = " . (int)($query_token_details['address_id']) . ", 
				`card_number` = '" . $this->db->escape($query_token_details['card_number']) . "', 
				`expiry_month` = '" . $this->db->escape($query_token_details['expiry_month']) . "',
				`expiry_year` = '" . $this->db->escape($query_token_details['expiry_year']) . "',
				`payment_instrument_id` = '" . $this->db->escape($query_token_details['payment_instrument_id']) . "',
				`instrument_identifier_id` = '" . $this->db->escape($query_token_details['instrument_identifier_id']) . "',
				`customer_token_id` = '" . $this->db->escape($query_token_details['customer_token_id']) . "',
				`default_state` = " . (int)$this->db->escape($query_token_details['default_state']) . ",
				`date_added` = '" . $this->db->escape($query_token_details['date_added']) . "'";
			list($is_success, $query_response) = $this->errorHandler($query_insert_token_data);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	/**
	 * Updates tokenization table with passed parameters.
	 *
	 * @param int $address_id address id
	 * @param int $card_id card id
	 * @param int $customer_id customer id
	 * @param array $card_details card details
	 *
	 * @return bool|null
	 */
	public function queryUpdateCardDetails(int $address_id, int $card_id, int $customer_id, array $card_details): ?bool {
		$return_response = null;
		if (is_int($address_id) && is_int($card_id) && is_int($customer_id) && is_array($card_details)) {
			$query_address_id = $address_id;
			$query_card_id = $card_id;
			$query_customer_id = $customer_id;
			$query_card_details = $card_details;
			$query_update_expiry_date = "UPDATE `" . $this->db->escape(TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_TOKENIZATION) . "` SET`address_id` = " . (int)$query_address_id . ", `expiry_month` = '" . $this->db->escape($query_card_details['expiration_month']) . "', `expiry_year` = '" . $this->db->escape($query_card_details['expiration_year']) . "' WHERE`card_id` = " . (int)$query_card_id . " AND`customer_id` = " . (int)$query_customer_id;
			list($is_success, $query_response) = $this->errorHandler($query_update_expiry_date);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	/**
	 * Deletes specified card id from tokenization table.
	 *
	 * @param int $card_id card id
	 * @param int $customer_id customer id
	 *
	 * @return bool|null
	 */
	public function queryDeleteSavedCardDetails(int $card_id, int $customer_id): ?bool {
		$return_response = null;
		if (!empty($card_id) && !empty($customer_id) && is_int($card_id) && is_int($customer_id)) {
			$query_card_id = $card_id;
			$query_customer_id = $customer_id;
			$query_delete_saved_card = "DELETE FROM `" . $this->db->escape(TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_TOKENIZATION) . "` WHERE 
				`customer_id`= " . (int)$query_customer_id . " AND `card_id`=" . (int)$query_card_id;
			list($is_success, $query_response) = $this->errorHandler($query_delete_saved_card);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	/**
	 * Gives default card for specified customer id.
	 *
	 * @param int $customer_id customer id
	 *
	 * @return object|null
	 */
	public function queryDefaultCard(int $customer_id): ?object {
		$return_response = null;
		if (!empty($customer_id) && is_int($customer_id)) {
			$query_customer_id = $customer_id;
			$query_default_card = "SELECT `card_id` FROM `" . $this->db->escape(TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_TOKENIZATION) . "` WHERE `default_state` = 1 AND `customer_id`= " . (int)$query_customer_id;
			list($is_success, $query_response) = $this->errorHandler($query_default_card);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	/**
	 * Updates new card as default card.
	 *
	 * @param int $previous_default_id previous default setted card id
	 * @param int $card_id new default card id
	 *
	 * @return bool|null
	 */
	public function queryUpdateDefaultCard(int $previous_default_id, int $card_id): ?bool {
		$return_response = null;
		if (!empty($previous_default_id) && is_int($card_id) && !empty($card_id) && is_int($previous_default_id)) {
			$query_previous_default_id = $previous_default_id;
			$query_card_id = $card_id;
			$query_update_default_card = "UPDATE `" . $this->db->escape(TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_TOKENIZATION) . "` SET `default_state` = CASE `card_id`  WHEN " . (int)$query_card_id . " THEN 1 WHEN " . (int)$query_previous_default_id . " THEN 0 END WHERE `card_id` IN (" . (int)$query_card_id . ", " . (int)$query_previous_default_id . ")";
			list($is_success, $query_response) = $this->errorHandler($query_update_default_card);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	/**
	 * Gives all the card details for specified customer id.
	 *
	 * @param int $customer_id customer id
	 *
	 * @return object|null
	 */
	public function queryCards(?int $customer_id): ?object {
		$return_response = null;
		if (!empty($customer_id) && is_int($customer_id)) {
			$query_customer_id = $customer_id;
			$query_card_details = "SELECT * FROM `" . $this->db->escape(TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_TOKENIZATION) . "` WHERE customer_id = '" . (int)$query_customer_id . "' ORDER BY default_state DESC";
			list($is_success, $query_response) = $this->errorHandler($query_card_details);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	/**
	 * Gives card detail for specified card and customer id.
	 *
	 * @param int $card_id card id
	 * @param int $customer_id customer id
	 *
	 * @return object|null
	 */
	public function queryCard(int $card_id, int $customer_id): ?object {
		$return_response = null;
		if (!empty($card_id) && !empty($customer_id) && is_int($card_id) && is_int($customer_id)) {
			$query_card_id = $card_id;
			$query_customer_id = $customer_id;
			$query_cards_info = "SELECT * FROM `" . $this->db->escape(TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_TOKENIZATION) . "` WHERE card_id = '" . (int)$query_card_id . "'  AND customer_id = '" . (int)$query_customer_id . "'";
			list($is_success, $query_response) = $this->errorHandler($query_cards_info);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	/**
	 * Inserts auth reversal data to auth reversal table.
	 *
	 * @param array $auth_reversal_details contains all the details that are required to insert into auth reversal table.
	 * @param string $table_prefix
	 *
	 * @return bool|null
	 */
	public function queryInsertAuthReversal(array $auth_reversal_details, string $table_prefix): ?bool {
		$return_response = null;
		if (!empty($auth_reversal_details) && is_array($auth_reversal_details) && !empty($table_prefix) && is_string($table_prefix)) {
			$query_auth_reversal_details = $auth_reversal_details;
			$query_table_prefix = $table_prefix;
			$query_insert_auth_reversal = "INSERT INTO `" . $this->db->escape($query_table_prefix . TABLE_AUTH_REVERSAL) . "` 
				SET `order_id` = '" . (int)$query_auth_reversal_details['order_id'] . "',
				`transaction_id` = '" . $this->db->escape($query_auth_reversal_details['transaction_id']) . "', 
				`cybersource_order_status` = '" . $this->db->escape($query_auth_reversal_details['cybersource_order_status']) . "', 
				`oc_order_status` = '" . $this->db->escape($query_auth_reversal_details['oc_order_status']) . "',
				`currency` = '" . $this->db->escape($query_auth_reversal_details['currency']) . "', 
				`amount` = '" . (float)$query_auth_reversal_details['amount'] . "',
				`date_added` = '" . $this->db->escape($query_auth_reversal_details['date_added']) . "'";
			list($is_success, $query_response) = $this->errorHandler($query_insert_auth_reversal);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	/**
	 * Gives failed attempts till specified date and time and for specified customer.
	 *
	 * @param int $customer_id customer id
	 * @param string $current_date_time date and time
	 *
	 * @return object|null
	 */
	public function queryAttempts(int $customer_id, string $current_date_time): ?object {
		$return_response = null;
		if (!empty($customer_id) && !empty($current_date_time) && is_int($customer_id) && is_string($current_date_time)) {
			$query_customer_id = $customer_id;
			$query_current_date_time = $current_date_time;
			$query_attempts = "SELECT `card_id` AS `failed_attempts`, `counter` FROM `" . $this->db->escape(TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_TOKEN_CHECK) . "`
				WHERE `customer_id` = " . (int)$query_customer_id . " AND (`date_added` between '" . $this->db->escape($query_current_date_time) . "' and '" . $this->db->escape(CURRENT_DATE) . "')";
			list($is_success, $query_response) = $this->errorHandler($query_attempts);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	/**
	 * Gives all failed attempts for specified customer.
	 *
	 * @param int $customer_id
	 *
	 * @return object|null
	 */
	public function queryFailedAttempts(int $customer_id): ?object {
		$return_response = null;
		if (!empty($customer_id) && is_int($customer_id)) {
			$query_customer_id = $customer_id;
			$query_failed_attempts = "SELECT `card_id` AS `failed_attempts` FROM `" . $this->db->escape(TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_TOKEN_CHECK) . "` WHERE `customer_id` = " . (int)$query_customer_id;
			list($is_success, $query_response) = $this->errorHandler($query_failed_attempts);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	/**
	 * Inserts counter for specified customer id along current time stamp.
	 *
	 * @param int $customer_id customer id
	 * @param int $counter counter
	 *
	 * @return bool|null
	 */
	public function queryInsertTokenCheck(int $customer_id, int $counter): ?bool {
		$return_response = null;
		if (!empty($customer_id) && !empty($counter) && is_int($counter) && is_int($customer_id)) {
			$query_customer_id = $customer_id;
			$query_counter = $counter;
			$query_token_check = "INSERT INTO `" . $this->db->escape(TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_TOKEN_CHECK) . "` SET `customer_id` = '" . (int)$query_customer_id . "', `counter` = '" . (int)$query_counter . "', `date_added` = '" . $this->db->escape(CURRENT_DATE) . "'";
			list($is_success, $query_response) = $this->errorHandler($query_token_check);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	/**
	 * Updates counter for specified customer id along current time stamp.
	 *
	 * @param int $customer_id customer id
	 * @param int $counter counter
	 *
	 * @return bool
	 */
	public function queryUpdateTokenCheck(int $customer_id, int $counter): ?bool {
		$return_response = null;
		if (!empty($customer_id) && !empty($counter) && is_int($customer_id) && is_int($counter)) {
			$query_customer_id = $customer_id;
			$query_counter = $counter;
			$query_token_check = "UPDATE `" . $this->db->escape(TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_TOKEN_CHECK) . "`
				SET `counter` = " . (int)$query_counter . ", `date_added` = '" . $this->db->escape(CURRENT_DATE) . "' WHERE `customer_id` = " . (int)$query_customer_id;
			list($is_success, $query_response) = $this->errorHandler($query_token_check);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	/**
	 * Gives card details for specified customer id and saved card id.
	 *
	 * @param int $customer_id customer id
	 * @param int $saved_card_id saved card id
	 *
	 * @return bool|null
	 */
	public function querySavedCards(int $customer_id, int $saved_card_id): ?object {
		$return_response = null;
		if (!empty($customer_id) && !empty($saved_card_id) && is_int($customer_id) && is_int($saved_card_id)) {
			$query_saved_card_id = $saved_card_id;
			$query_customer_id = $customer_id;
			$query_saved_cards = "SELECT `address_id`, `payment_instrument_id`, `instrument_identifier_id`, `customer_token_id` FROM `" . $this->db->escape(TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_TOKENIZATION) . "` WHERE `customer_id` =" . (int)$query_customer_id . " and `card_id` = " . (int)$query_saved_card_id;
			list($is_success, $query_response) = $this->errorHandler($query_saved_cards);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	/**
	 * Updates tokenization table with passed parameters.
	 *
	 * @param int $address_id address id
	 * @param array $card_tokens contains card token data.
	 * @param array $card_data contains new card data.
	 * @param int $customer_id customer id
	 * @param array $saved_cards previous card data
	 *
	 * @return bool|null
	 */
	public function queryUpdateTokenization(int $address_id, array $card_tokens, array $card_data, int $customer_id, array $saved_cards): ?bool {
		$return_response = null;
		if (is_int($address_id) && is_int($customer_id) && is_array($card_tokens) && is_array($card_data) && is_array($saved_cards)) {
			$query_address_id = $address_id;
			$query_card_tokens = $card_tokens;
			$query_card_data = $card_data;
			$query_customer_id = $customer_id;
			$query_saved_cards = $saved_cards;
			$query_update_token = "UPDATE `" . $this->db->escape(TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_TOKENIZATION) . "`
				SET `address_id` = " . (int)$query_address_id . ",
				`payment_instrument_id`='" . $this->db->escape($query_card_tokens['payment_instrument_id']) . "', 
				`expiry_month`='" . $this->db->escape($query_card_data['expiry_month']) . "', 
				`expiry_year`='" . $this->db->escape($query_card_data['expiry_year']) . "'
				WHERE `customer_id` = " . (int)$query_customer_id . " 
				and `expiry_month` = '" . $this->db->escape($query_saved_cards['expiry_month']) . "' 
				and `expiry_year` = '" . $this->db->escape($query_saved_cards['expiry_year']) . "' 
				and `customer_token_id` = '" . $this->db->escape($query_saved_cards['customer_token_id']) . "' and 
				`instrument_identifier_id` = '" . $this->db->escape($query_saved_cards['instrument_identifier_id']) . "'";
			list($is_success, $query_response) = $this->errorHandler($query_update_token);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	/**
	 * Gives all card details for specified customer id.
	 *
	 * @param int $customer_id customer id
	 *
	 * @return object|null
	 */
	public function querySavedCardToken(int $customer_id): ?object {
		$return_response = null;
		if (!empty($customer_id) && is_int($customer_id)) {
			$query_customer_id = $customer_id;
			$query_saved_card = "SELECT `address_id`, `card_number`,  `expiry_month`, `expiry_year` , `instrument_identifier_id`,  `customer_token_id` FROM `" . $this->db->escape(TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_TOKENIZATION) . "` WHERE `customer_id` =" . (int)$query_customer_id;
			list($is_success, $query_response) = $this->errorHandler($query_saved_card);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	/**
	 * Gives card details for specified customer id.
	 *
	 * @param int $customer_id customer id
	 * @param string $current_date_time start time
	 *
	 * @return object|null
	 */
	public function queryRateLimiterCard(int $customer_id, string $current_date_time): ?object {
		$return_response = null;
		if (!empty($customer_id) && !empty($current_date_time) && is_int($customer_id) && is_string($current_date_time)) {
			$query_customer_id = $customer_id;
			$query_current_date_time = $current_date_time;
			$query_card_id = "SELECT `card_id` FROM `" . $this->db->escape(TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_TOKENIZATION) . "`  WHERE   `customer_id` = " . (int)$query_customer_id . " AND (`date_added` between '" . $this->db->escape($query_current_date_time) . "' AND '" . $this->db->escape(CURRENT_DATE) . "')";
			list($is_success, $query_response) = $this->errorHandler($query_card_id);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	/**
	 * Gives attempts for specified customer id and start time.
	 *
	 * @param int $customer_id customer id
	 * @param string $current_date_time start id
	 *
	 * @return object|null
	 */
	public function queryNumberOfTrails(int $customer_id, string $current_date_time): ?object {
		$return_response = null;
		if (!empty($customer_id) && !empty($current_date_time) && is_int($customer_id) && !empty($current_date_time)) {
			$query_customer_id = $customer_id;
			$query_current_date_time = $current_date_time;
			$query_counter = "SELECT `counter` AS `attempts` FROM `" . $this->db->escape(TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_TOKEN_CHECK) . "`  WHERE   `customer_id` = " . (int)$query_customer_id . " AND (`date_added` between '" . $this->db->escape($query_current_date_time) . "' AND '" . $this->db->escape(CURRENT_DATE) . "')";
			list($is_success, $query_response) = $this->errorHandler($query_counter);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	/**
	 * Gives card details for specified customer id and card id.
	 *
	 * @param int $customer_id customer id
	 * @param int $card_id card id
	 *
	 * @return object|null
	 */
	public function queryCardInfo(int $customer_id, int $card_id): ?object {
		$return_response = null;
		if (!empty($customer_id) && !empty($card_id) && is_int($customer_id) && is_int($card_id)) {
			$query_customer_id = $customer_id;
			$query_card_id = $card_id;
			$query_card_info = "SELECT `card_id` , `customer_name` , `card_number` ,  `expiry_month` , `expiry_year`, `address_id`, `instrument_identifier_id`, `customer_token_id`, `payment_instrument_id` FROM `" . $this->db->escape(TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_TOKENIZATION) . "` WHERE `customer_id`= " . (int)$query_customer_id . " AND `card_id`= " . (int)$query_card_id;
			list($is_success, $query_response) = $this->errorHandler($query_card_info);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	/**
	 * Gives card details for specified customer id and card id.
	 *
	 * @param int $customer_id customer id
	 * @param int $card_id card id
	 *
	 * @return object|null
	 */
	public function queryCardToken(int $customer_id, int $card_id): ?object {
		$return_response = null;
		if (!empty($customer_id) && !empty($card_id) && is_int($customer_id) && is_int($card_id)) {
			$query_customer_id = $customer_id;
			$query_card_id = $card_id;
			$query_card_token = "SELECT `payment_instrument_id`, `customer_token_id`, `default_state` FROM `" . $this->db->escape(TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_TOKENIZATION) . "` WHERE  `customer_id`= " . (int)$query_customer_id . " AND `card_id` = " . (int)$query_card_id;
			list($is_success, $query_response) = $this->errorHandler($query_card_token);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	/**
	 * Gives count of customer id (checks if specified customer id is presents in tokenization table or not).
	 *
	 * @param int $customer_id customer id
	 *
	 * @return object|null
	 */
	public function queryCardCount(int $customer_id): ?object {
		$return_response = null;
		if (!empty($customer_id) && is_int($customer_id)) {
			$query_customer_id = $customer_id;
			$query_customer_id = "SELECT count(`customer_id`) as customer_id FROM `" . $this->db->escape(TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_TOKENIZATION) . "` where  `customer_id`=" . (int)$query_customer_id;
			list($is_success, $query_response) = $this->errorHandler($query_customer_id);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	/**
	 * Updates address details in tokenization table for specified card.
	 *
	 * @param int $address_id address id
	 * @param int $saved_card_id card id
	 *
	 * @return bool|null
	 */
	public function updateAddress(int $address_id, int $saved_card_id): ?bool {
		$return_response = null;
		if (!empty($address_id) && !empty($saved_card_id) && is_int($address_id) && is_int($saved_card_id)) {
			$query_address_id = $address_id;
			$query_saved_card_id = $saved_card_id;
			$query_update_address = "UPDATE `" . $this->db->escape(TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_TOKENIZATION) . "` SET  address_id = '" . (int)$query_address_id . "' WHERE card_id = '" . (int)$query_saved_card_id . "'";
			list($is_success, $query_response) = $this->errorHandler($query_update_address);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	/**
	 * Gives transaction id form auth reversal table for specified order id.
	 *
	 * @param int $order_id order id
	 * @param string $table_prefix
	 *
	 * @return object|null
	 */
	public function queryAuthReversalId(int $order_id, string $table_prefix): ?object {
		$return_response = null;
		if (VAL_ZERO < $order_id && is_int($order_id) && !empty($table_prefix) && is_string($table_prefix)) {
			$query_order_id = $order_id;
			$query_table_prefix = $table_prefix;
			$query_transaction_id = "SELECT `transaction_id` FROM `" . $this->db->escape($query_table_prefix . TABLE_AUTH_REVERSAL) . "` WHERE `order_id` = " . (int)$query_order_id;
		}
		list($is_success, $query_response) = $this->errorHandler($query_transaction_id);
		if ($is_success) {
			$return_response = $query_response;
		}
		return $return_response;
	}

	/**
	 * Updates order table order status id with specified value.
	 *
	 * @param int $order_status_id order status id
	 * @param int $order_id order id
	 *
	 * @return bool|null
	 */
	public function queryUpdateOrderTable(int $order_status_id, int $order_id): ?bool {
		$return_response = null;
		if (!empty($order_status_id) && !empty($order_id) && is_int($order_status_id) && is_int($order_id)) {
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

	/**
	 * Inserts order history table with passed parameter.
	 *
	 * @param int $order_id order id
	 * @param int $order_status_id order status id
	 * @param bool $notify
	 * @param string $comment
	 *
	 * @return bool|null
	 */
	public function queryInsertOrderHistory(int $order_id, int $order_status_id, bool $notify, ?string $comment): ?bool {
		$return_response = null;
		if (is_int($order_id) && is_int($order_status_id)) {
			$query_order_id = $order_id;
			$query_order_status_id = $order_status_id;
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

	/**
	 * Updates order status table, order status with specified custom status.
	 *
	 * @param string $custom_status custom status
	 * @param int $order_id order id
	 *
	 * @return bool|null
	 */
	public function queryUpdateOrderStatus(string $custom_status, int $order_id): ?bool {
		$return_response = null;
		if (!empty($custom_status) && !empty($order_id) && is_int($order_id) && is_string($custom_status)) {
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

	/**
	 * Inserts passed parameters to order status table.
	 *
	 * @param string $custom_status
	 * @param int $order_id
	 *
	 * @return bool|null
	 */
	public function queryInsertOrderStatus(string $custom_status, int $order_id): ?bool {
		$return_response = null;
		if (!empty($custom_status) && !empty($order_id) && is_string($custom_status) && is_int($order_id)) {
			$query_custom_status = $custom_status;
			$query_order_id = $order_id;
			$query_update_status = "INSERT INTO " . $this->db->escape(TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_ORDER_STATUS) . " SET order_id = '" . (int)$query_order_id . "', cybersource_order_status = '" . $this->db->escape($query_custom_status) . "'";
			list($is_success, $query_response) = $this->errorHandler($query_update_status);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	/**
	 * Updates Opencart product table with passed parameter array.
	 *
	 * @param array $order_product contians data related to ordered product
	 *
	 * @return bool|null
	 */
	public function queryUpdateProduct(array $order_product): ?bool {
		$return_response = null;
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

	/**
	 * Updates Opencart product table with passed parameter array.
	 *
	 * @param array $order_product contians data related to ordered product
	 *
	 * @return bool|null
	 */
	public function queryUpdateProductDeductStock(array $order_product): ?bool {
		$return_response = null;
		if (!empty($order_product) && is_array($order_product)) {
			$query_order_product = $order_product;
			$query_update_product = "UPDATE `" . $this->db->escape(DB_PREFIX) . "product` SET quantity = (quantity - " . (int)$query_order_product['quantity'] . ") WHERE product_id = '" . (int)$query_order_product['product_id'] . "' AND subtract = '1'";
			list($is_success, $query_response) = $this->errorHandler($query_update_product);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	/**
	 * Updates Opencart product option value table with passed parameter array.
	 *
	 * @param array $order_product contians data related to ordered product
	 * @param array $order_option
	 *
	 * @return bool|null
	 */
	public function queryUpdateProductOption(array $order_product, array $order_option): ?bool {
		$return_response = null;
		if (!empty($order_product) && !empty($order_option) && is_array($order_option) && is_array($order_product)) {
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

	/**
	 * Updates Opencart product option value table with passed parameter array.
	 *
	 * @param array $order_product contians data related to ordered product
	 * @param array $order_option
	 *
	 * @return bool|null
	 */
	public function queryUpdateProductOptionDeductStock(array $order_product, array $order_option): ?bool {
		$return_response = null;
		if (!empty($order_product) && !empty($order_option) && is_array($order_option) && is_array($order_product)) {
			$query_order_product = $order_product;
			$query_order_option = $order_option;
			$query_update_product_option = "UPDATE " . $this->db->escape(DB_PREFIX) . "product_option_value SET quantity = (quantity - " . (int)$query_order_product['quantity'] . ") WHERE product_option_value_id = '" . (int)$query_order_option['product_option_value_id'] . "' AND subtract = '1'";
			list($is_success, $query_response) = $this->errorHandler($query_update_product_option);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	/**
	 * Gives payment code for specified order id.
	 *
	 * @param int $order_id order id
	 *
	 * @return object|null
	 */
	public function queryPaymentCode(int $order_id): ?object {
		$return_response = null;
		if (!empty($order_id) && is_int($order_id)) {
			$query_order_id = $order_id;
			$query_payment_code = "SELECT `payment_code` FROM `" . $this->db->escape(DB_PREFIX) . "order` WHERE `order_id` = " . (int)$query_order_id;
			list($is_success, $query_response) = $this->errorHandler($query_payment_code);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	/**
	 * Gives payment action for specified order id from order table.
	 *
	 * @param int $order_id order id
	 * @param string $table_prefix
	 *
	 * @return object|null
	 */
	public function getPaymentAction(?int $or_num, $csrf, string $table_prefix): ?object {
		$return_response = null;
		$csrf_data = $this->session->data['csrf'] ?? VAL_EMPTY;
		$table_prefix = htmlspecialchars($this->db->escape($table_prefix));
		if ((VAL_ZERO == strcmp($csrf_data, $csrf)) && !empty($table_prefix) && is_string($table_prefix)) {
			if (!empty($or_num) && is_int($or_num)) {
				$query_order_id = $or_num;
				$query_table_prefix = $table_prefix;
				$action_part_one = "SELECT `payment_action`";
				$action_part_two = " FROM `";
				$action_part_three = "` WHERE `order_id` = ";
				$payment_action = $this->db->escape($action_part_one . $action_part_two . $query_table_prefix . TABLE_ORDER . $action_part_three . (int)$query_order_id);
				list($is_success, $response) = $this->errorHandler($payment_action);
				if ($is_success) {
					$return_response = $response;
				}
			}
		}
		return $return_response;
	}

	/**
	 * Gives sum of order product quantity form Opencart order product table for specifed order id.
	 *
	 * @param int $order_id order id
	 *
	 * @return object|null
	 */
	public function queryProductQuantity(int $order_id): ?object {
		$return_response = null;
		if (!empty($order_id) && is_int($order_id)) {
			$query_order_id = $order_id;
			$query_order_quantity = "SELECT sum(`quantity`) AS `quantity` FROM `" . $this->db->escape(DB_PREFIX) . "order_product` WHERE `order_id` = " . (int)$query_order_id;
			list($is_success, $query_response) = $this->errorHandler($query_order_quantity);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	/**
	 * Gives sum of capture quantity form capture table for specified order id.
	 *
	 * @param int $order_id order id
	 * @param string $table_prefix
	 *
	 * @return object|null
	 */
	public function queryCaptureQuantity(int $order_id, string $table_prefix): ?object {
		$return_response = null;
		if (!empty($order_id) && is_int($order_id) && !empty($table_prefix) && is_string($table_prefix)) {
			$query_order_id = $order_id;
			$query_table_prefix = $table_prefix;
			$query_capture_quantity = "SELECT sum(`capture_quantity`) AS `quantity` FROM `" . $this->db->escape($query_table_prefix . TABLE_CAPTURE) . "` WHERE `order_id` = " . (int)$query_order_id;
			list($is_success, $query_response) = $this->errorHandler($query_capture_quantity);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	/**
	 * Gives extension id from OpenCart extension table for credit card and apple pay payment method.(tells whether credit card or apple pay payment is enabled or not in BO).
	 *
	 * @param int $order_id order id
	 *
	 * @return object|null
	 */
	public function queryExtensionId(int $order_id): ?object {
		$return_response = null;
		if (!empty($order_id) && is_int($order_id)) {
			$query_payment_code = $this->queryPaymentCode($order_id);
			$payment_code = VAL_NULL != $query_payment_code ? (VAL_ZERO < $query_payment_code->num_rows ? $query_payment_code->row['payment_code'] : VAL_ZERO) : VAL_ZERO;
			if (!empty($payment_code) && is_string($payment_code)) {
				$query_extension_id = "SELECT `extension_id` FROM `" . $this->db->escape(DB_PREFIX) . "extension` WHERE code = '" . $this->db->escape($payment_code) . "' and type = '" . $this->db->escape(EXTENSION_TYPE_PAYMENT) . "'";
				list($is_success, $query_response) = $this->errorHandler($query_extension_id);
				if ($is_success) {
					$return_response = $query_response;
				}
			}
		}
		return $return_response;
	}

	public function queryDataExists($order_id, string $table_prefix): ?object {
		$return_response = null;
		if (!empty($order_id) && is_int($order_id) && !empty($table_prefix) && is_string($table_prefix)) {
			$query_order_id = $order_id;
			$query_table_prefix = $table_prefix;
			$query_extension_id = "SELECT `transaction_id` FROM `" . $this->db->escape($query_table_prefix . TABLE_ORDER) . "` WHERE `order_id` = " . (int)$query_order_id;
			list($is_success, $query_response) = $this->errorHandler($query_extension_id);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	/**
	 * Gives order status from order staus table for specified order id.
	 *
	 * @param int $order_id order id
	 *
	 * @return object|null
	 */
	public function queryStatus(int $order_id): ?object {
		$return_response = null;
		if (!empty($order_id) && is_int($order_id)) {
			$query_order_id = $order_id;
			$query_current_status = 'SELECT `cybersource_order_status` FROM `' . $this->db->escape(TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_ORDER_STATUS) . '` WHERE `order_id` = ' . (int)$query_order_id;
			list($is_success, $query_response) = $this->errorHandler($query_current_status);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	/**
	 * Gives transaction id count from capture table for specified order id.
	 *
	 * @param int $order_id order id
	 * @param string $table_prefix
	 *
	 * @return object|null
	 */
	public function queryCaptureCount(int $order_id, string $table_prefix): ?object {
		$return_response = VAL_NULL;
		if (!empty($order_id) && is_int($order_id) && !empty($table_prefix) && is_string($table_prefix)) {
			$query_order_id = $order_id;
			$query_table_prefix = $table_prefix;
			$query_capture_count = 'SELECT count(`transaction_id`) AS capture_count FROM `' . $this->db->escape($query_table_prefix . TABLE_CAPTURE) . '` WHERE `order_id` = ' . (int)$query_order_id;
			list($is_success, $query_response) = $this->errorHandler($query_capture_count);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	/**
	 * Gives transaction id count from auth reversal table for specified order id.
	 *
	 * @param int $order_id
	 * @param string $table_prefix
	 *
	 * @return object|null
	 */
	public function queryAuthRevCount(int $order_id, string $table_prefix): ?object {
		$return_response = null;
		if (!empty($order_id) && is_int($order_id) && !empty($table_prefix) && is_string($table_prefix)) {
			$query_order_id = $order_id;
			$query_table_prefix = $table_prefix;
			$query_auth_rev_count = 'SELECT count(`transaction_id`) AS auth_rev_count FROM `' . $this->db->escape($query_table_prefix . TABLE_AUTH_REVERSAL) . '` WHERE `order_id` = ' . (int)$query_order_id;
			list($is_success, $query_response) = $this->errorHandler($query_auth_rev_count);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	/**
	 * Gives tax amount from Opencart order total table for specified order id.
	 *
	 * @param int $order_id order id
	 *
	 * @return object|null
	 */
	public function queryTaxAmount(int $order_id): ?string {
		$return_response = null;
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

	/**
	 * Gives total amount along with tax form Opencart order product table for specified order id.
	 *
	 * @param int $order_id order id
	 *
	 * @return object|null
	 */
	public function queryProductTaxAmount(int $order_id): ?string {
		$return_response = null;
		if (!empty($order_id) && is_int($order_id)) {
			$query_order_id = $order_id;
			$query_product_tax_amount = 'SELECT sum(`quantity` * `tax`)  as `total_amount` FROM `' . $this->db->escape(DB_PREFIX) . 'order_product` WHERE `order_id` = ' . (int)$query_order_id;
			list($is_success, $query_response) = $this->errorHandler($query_product_tax_amount);
			if ($is_success) {
				$return_response = str_replace(",", "", $query_response->row['total_amount']);
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
	 * All the query used in this FO of this module will be calling this funcion.
	 *
	 * Here we will excutes the passed query, if exception occurs then we are logging the exception.
	 *
	 * @param string $query contains query string
	 *
	 * @return array
	 */
	public function errorHandler(string $query): array {
		$query_output = null;
		$is_success = false;
		$temp_query = null;
		$this->load->model('extension/payment/cybersource_common');
		if (!empty($query)) {
			$temp_query = $query;
			try {
				$query_output = $this->db->query($temp_query);
				$is_success = true;
			} catch (Exception $e) {
				$this->model_extension_payment_cybersource_common->logger($e->getMessage());
			}
		}
		return array($is_success, $query_output);
	}

	/**
	 * Updates tokenization table with passed parameters.
	 *
	 * @param array $card_details card details
	 *
	 * @return bool|null
	 */
	public function queryUpdateCard(array $card_details): ?bool {
		$return_response = null;
		if (!empty($card_details) && is_array($card_details)) {
			$query_card_details = $card_details;
			$query_update_expiry_date = "UPDATE `" . $this->db->escape(TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_TOKENIZATION) . "` SET `card_number` = '" . $this->db->escape($query_card_details['card_number']) . "', `expiry_month` = '" . $this->db->escape($query_card_details['expiration_month']) . "', `expiry_year` = '" . $this->db->escape($query_card_details['expiration_year']) . "' WHERE `instrument_identifier_id` = '" . $this->db->escape($query_card_details['instrument_identifier_id']) . "'";
			list($is_success, $query_response) = $this->errorHandler($query_update_expiry_date);
			if ($is_success) {
				$return_response = $query_response;
			}
		}
		return $return_response;
	}

	/**
	 * Fetches webhook data based on organization_id/merchant_id.
	 *
	 * @param string $merchant_id merchant id
	 * @param string $webhook_id webhook id
	 *
	 * @return array|null
	 */
	public function queryWebhookDetails(string $merchant_id, string $webhook_id): ?array {
		$return_response = VAL_NULL;
		if (!empty($merchant_id) && is_string($merchant_id) && !empty($webhook_id) && is_string($webhook_id)) {
			$query_merchant_id = $merchant_id;
			$query_webhook_id = $webhook_id;
			$query_webhook_details = "SELECT `digital_signature_key`, `digital_signature_key_id` FROM `" . $this->db->escape(TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_WEBHOOK) . "` WHERE `organization_id` = '" . $this->db->escape($query_merchant_id) . "' AND `webhook_id` = '" . $this->db->escape($query_webhook_id) . "'";
			list($is_success, $query_response) = $this->errorHandler($query_webhook_details);
			if ($is_success) {
				$return_response = (!empty($query_response) && VAL_ZERO < $query_response->num_rows) ? $query_response->row : VAL_NULL;
			}
		}
		return $return_response;
	}

	/**
	 * Gives card id for specified instrument identifier id.
	 *
	 * @param string $instrument_identifier_id instrument identifier id
	 *
	 * @return int|null
	 */
	public function queryCardId(string $instrument_identifier_id): ?int {
		$return_response = VAL_NULL;
		if (!empty($instrument_identifier_id) && is_string($instrument_identifier_id)) {
			$query_instrument_identifier_id = $instrument_identifier_id;
			$query_card_id = "SELECT `card_id` FROM `" . $this->db->escape(TABLE_PREFIX_UNIFIED_CHECKOUT . TABLE_TOKENIZATION) . "` WHERE `instrument_identifier_id` = '" . $this->db->escape($query_instrument_identifier_id) . "'";
			list($is_success, $query_response) = $this->errorHandler($query_card_id);
			if ($is_success) {
				$return_response = (!empty($query_response) && VAL_ZERO < $query_response->num_rows) ? (int)$query_response->row['card_id'] : VAL_NULL;
			}
		}
		return $return_response;
	}
}
