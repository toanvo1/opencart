<?php

namespace Isv\Common\Helper;

class TypeConversion {
	/**
	 * Convert data apart form array to specified data type.
	 *
	 * @param mixed $data
	 * @param string $data_type
	 * @param bool $sanitize
	 *
	 * @return string|int|null|bool
	 */
	public static function convertDataToType($data, $data_type, $sanitize = true) {
		if (null === $data || null === $data_type || !is_string($data_type)) {
			return $data;
		}
		if (gettype($data) === $data_type) {
			return $data;
		}
		$data = $sanitize ? htmlentities($data) : $data;
		if ('string' === $data_type) {
			return (string)$data;
		} elseif ('integer' === $data_type) {
			return false !== filter_var($data, FILTER_VALIDATE_FLOAT) ? (int)$data : $data;
		} elseif ('boolean' === $data_type) {
			if (in_array($data, array("1", "true", "on", "yes", "0", "false", "off", "no", ""))) {
				return filter_var($data, FILTER_VALIDATE_BOOLEAN);
			}
		}
		return $data;
	}

	/**
	 * Convert array data to specified data type.
	 * If count of data_type_array is greater than data_array then extra data_type_array values will be ignore.
	 * If count of data_type_array is lesser than data_array then for remaining data_array values string will be the datatype.
	 *
	 * @param array $data_array
	 * @param array $data_type_array
	 * @param array $sanitize_array
	 *
	 * @return array|string
	 */
	public static function convertArrayToType($data_array, $data_type_array = array('string'), $sanitize_array = array(true)) {
		if (!is_array($data_array) || empty($data_array) || !is_array($data_type_array) || empty($data_type_array) || !is_array($sanitize_array) || empty($sanitize_array)) {
			return $data_array;
		}
		return array_map(function ($data, $data_type, $sanitize) {
			return self::convertDataToType($data, $data_type ?? 'string', $sanitize ?? true);
		}, $data_array, $data_type_array, $sanitize_array);
	}
}
