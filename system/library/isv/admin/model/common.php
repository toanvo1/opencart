<?php

namespace Isv\Admin\Model;

use Isv\Common\Helper\TypeConversion;

trait Common {
	public function getLineItemArray($order_id, $product_details, $voucher_details, $shipping_cost, $shipping_tax, $is_shipping_included, $voucher_amount, $coupon_amount) {
		$count = VAL_ZERO;
		$line_items = array();
		$this->load->model('extension/payment/cybersource_query');
		$this->load->model('extension/payment/cybersource_common');
		if (!empty($product_details)) {
			$size_of_products = sizeof($product_details);
			for ($i = VAL_ZERO; $i < $size_of_products; $i++) {
				if (VAL_ZERO != $product_details['' . $i . '']['quantity']) {
					$line_items['' . $count . ''] = array(
						"productCode" => PRODUCT_CODE_DEFAULT,
						"productName" => TypeConversion::convertDataToType($product_details['' . $i . '']['name'], 'string'),
						"productSKU" => TypeConversion::convertDataToType($product_details['' . $i . '']['product_id'], 'string'),
						"quantity" => TypeConversion::convertDataToType($product_details['' . $i . '']['quantity'], 'integer'),
						"unitPrice" => TypeConversion::convertDataToType($product_details['' . $i . '']['price'], 'string'),
						"taxAmount" => TypeConversion::convertDataToType($product_details['' . $i . '']['tax'], 'string')
					);
					$count++;
				}
			}
		}
		if (VAL_FLAG_YES == $is_shipping_included && !empty($shipping_cost)) {
			$line_items['' . $count . ''] = array(
				"productCode" => SHIPPING_AND_HANDLING,
				"productName" => SHIPPING_AND_HANDLING,
				"productSKU" => SHIPPING_AND_HANDLING,
				"quantity" => VAL_ONE,
				"unitPrice" => TypeConversion::convertDataToType($shipping_cost, 'string'),
				"taxAmount" => TypeConversion::convertDataToType($shipping_tax, 'string')
			);
			$count++;
		}
		if (VAL_ZERO < $voucher_amount) {
			$line_items['' . $count . ''] = array(
				'productCode' => COUPON,
				'productName' => VOUCHER,
				'productSKU'  => VOUCHER,
				"quantity" => VAL_ONE,
				'unitPrice' => TypeConversion::convertDataToType($voucher_amount, 'string')
			);
			$count++;
		}
		if (VAL_ZERO < $coupon_amount) {
			$line_items['' . $count . ''] = array(
				'productCode' => COUPON,
				'productName' => COUPON,
				'productSKU'  => COUPON,
				"quantity" => VAL_ONE,
				'unitPrice' => TypeConversion::convertDataToType($coupon_amount, 'string')
			);
			$count++;
		}
		$reward_point_amount = $this->model_extension_payment_cybersource_query->queryRewardPointsAmount($order_id);
		$reward_point_amount = $this->model_extension_payment_cybersource_common->getAbsAmount($reward_point_amount);
		if (VAL_NULL != $reward_point_amount) {
			$line_items['' . $count . ''] = array(
				'productCode' => COUPON,
				'productName' => REWARD_POINTS,
				'productSKU'  => REWARD_POINTS,
				"quantity" => VAL_ONE,
				'unitPrice' => TypeConversion::convertDataToType($reward_point_amount, 'string')
			);
			$count++;
		}
		$store_credit_amount = $this->model_extension_payment_cybersource_query->queryStoreCreditAmount($order_id);
		$store_credit_amount = $this->model_extension_payment_cybersource_common->getAbsAmount($store_credit_amount);
		if (VAL_NULL != $store_credit_amount) {
			$line_items['' . $count . ''] = array(
				'productCode' => COUPON,
				'productName' => STORE_CREDIT_POINTS,
				'productSKU'  => STORE_CREDIT_POINTS,
				"quantity" => VAL_ONE,
				'unitPrice' => TypeConversion::convertDataToType($store_credit_amount, 'string')
			);
			$count++;
		}
		if (!empty($voucher_details)) {
			for ($j = VAL_ZERO; $j < sizeof($voucher_details); $j++) {
				$line_items['' . $count . ''] = array(
					"productCode" => PRODUCT_CODE_GIFT_CERTIFICATE,
					"productName" => GIFT_CERTIFICATE,
					"productSKU" => TypeConversion::convertDataToType($voucher_details['' . $j . '']['description'], 'string'),
					"quantity" => VAL_ONE,
					"unitPrice" => TypeConversion::convertDataToType($voucher_details['' . $j . '']['amount'], 'string')
				);
			}
		}
		return $line_items;
	}
}
