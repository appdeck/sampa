<?php
/**
*
*	User data validation and sanitization
*
*	@package sampa\Core\Filter
*	@copyright 2013 appdeck
*	@link http://github.com/appdeck/sampa
*	@version 0.1
*	@since 0.1
*	@license http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3
*/

namespace sampa\Core;

use sampa\Exception;

final class Filter {

	const BOOL = 0;
	const INT = 1;
	const INT_MIN = 2;
	const INT_MAX = 3;
	const INT_RANGE = 4;
	const FLOAT = 5;
	const FLOAT_MIN = 6;
	const FLOAT_MAX = 7;
	const FLOAT_RANGE = 8;
	const STRING = 9;
	const STRING_NOHTML = 10;
	const STRING_MINLEN = 11;
	const STRING_MAXLEN = 12;
	const STRING_RANGELEN = 13;
	const EMAIL = 14;
	const URL = 15;
	const DATE = 16;
	const IP = 17;
	const REGEX = 18;

	public static function __callStatic($function, $arguments) {
		switch ($function) {
			case 'bool':
				$bool = preg_replace('/[^tf01]+/i', '', $arguments[0]);
				return in_array($bool, array('t', '1'));
			case 'int':
				return intval(filter_var($arguments[0], FILTER_SANITIZE_NUMBER_INT));
			case 'float':
				return floatval(filter_var($arguments[0], FILTER_SANITIZE_NUMBER_FLOAT, (FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_THOUSAND)));
			case 'string':
				return (string)filter_var($arguments[0], FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_NO_ENCODE_QUOTES);
			case 'notags':
				return strip_tags(self::string($arguments[0]));
			case 'email':
				return filter_var($arguments[0], FILTER_SANITIZE_EMAIL);
			case 'url':
				return filter_var($arguments[0], FILTER_SANITIZE_URL);
			case 'date':
				return preg_replace('/[^0-9\/\-\.]+/', '', $arguments[0]);
			case 'ip':
				return preg_replace('/[^0-9\.\/]+/', '', $arguments[0]);
			default:
				throw new Exception\Filter("Invalid sanitization: {$function}");
		}
	}

	public static function validate_array(array $values, $type, $param = null) {
		$i = 0;
		$size = count($values);
		while (($i < $size) && (self::validate($values[$i], $type, $param)))
			$i++;
		return ($i == $size);
	}

	public static function validate($value, $type, $param = null) {
		switch ($type) {
			case self::BOOL:
				return (!is_null(filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)));
			case self::INT:
				return (filter_var($value, FILTER_VALIDATE_INT) !== false);
			case self::INT_MIN:
				$options = array(
					'options' => array(
						'min_range' => intval($param)
					)
				);
				return (filter_var($value, FILTER_VALIDATE_INT, $options) !== false);
			case self::INT_MAX:
				$options = array(
					'options' => array(
						'max_range' => intval($param)
					)
				);
				return (filter_var($value, FILTER_VALIDATE_INT, $options) !== false);
			case self::INT_RANGE:
				$options = array(
					'options' => array(
						'min_range' => intval($param[0]),
						'max_range' => intval($param[1])
					)
				);
				return (filter_var($value, FILTER_VALIDATE_INT, $options) !== false);
			case self::FLOAT:
				return (filter_var($value, FILTER_VALIDATE_FLOAT, (FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_THOUSAND)) !== false);
			case self::FLOAT_MIN:
				return ((filter_var($value, FILTER_VALIDATE_FLOAT, (FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_THOUSAND))) && ($value >= floatval($param)));
			case self::FLOAT_MAX:
				return ((filter_var($value, FILTER_VALIDATE_FLOAT, (FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_THOUSAND))) && ($value <= floatval($param)));
			case self::FLOAT_RANGE:
				if (filter_var($value, FILTER_VALIDATE_INT, (FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_THOUSAND)))
					return ((($value >= floatval($param[0])) && ($value <= floatval($param[1]))) ? true : false);
				return false;
			case self::STRING:
				$value = trim($value);
				return (empty($value) ? false : true);
			case self::STRING_NOHTML:
				return ($value === strip_tags($value) ? true : false);
			case self::STRING_MINLEN:
				return (strlen($value) >= intval($param));
			case self::STRING_MAXLEN:
				return (strlen($value) <= intval($param));
			case self::STRING_RANGELEN:
				$len = strlen($value);
				return ((($len >= intval($param[0])) && ($len <= intval($param[1]))) ? true : false);
			case self::EMAIL:
				return (filter_var($value, FILTER_VALIDATE_EMAIL) !== false);
			case self::URL:
				return (filter_var($value, FILTER_VALIDATE_URL) !== false);
			case self::DATE:
				$options = array(
					'options' => array(
						'regexp' => '/^(0[1-9]|[1|2][0-9]|3[0|1])[\/|\-|\.]?(0[1-9]|1[0-2])[\/|\-|\.]?([1|2][0-9]{3})$/'
					)
				);
				return (filter_var($value, FILTER_VALIDATE_REGEXP, $options) !== false);
			case self::IP:
				return (filter_var($value, FILTER_VALIDATE_IP) !== false);
			case self::REGEX:
				if (is_null($param))
					return false;
				$options = array(
					'options' => array(
						'regexp' => $param
					)
				);
				return (filter_var($value, FILTER_VALIDATE_REGEXP, $options) !== false);
			default:
				throw new Exception\Filter("Invalid validation type: {$type}");
		}
	}

}
