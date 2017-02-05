<?php

	function dd($variable = null){
		print_r($variable);
		die();
		return true;
	}

	function pre($variable = null){
		echo "<pre>";
		print_r($variable);
		echo "</pre>";
		return true;
	}


	/**************************************************************************************************************
	Parses a list of errors (array)
	Returns a formatted list (context => ..., message => ...)
	If your param $errors contains a key that you don't want to be passed in the response with the same name, use the array $replaces to define the pattern to replace they key names
	***************************************************************************************************************/
	function parseErrors($errors = array()) {
		$replaces = array (
				"pass" => "password",
			);

		if (!is_array($errors) || !count($errors)) {
			return array();
		} else {
			$response = array();
			foreach ($errors as $key => $value) {
				$response[] = array(
						"context" => isset($replaces[$key])? $replaces[$key] : $key,
						"message" => is_array($value)? $value[0] : $value
					);
			}
			return $response;
		}
	}


	/**************************************************************************************************************
	equiv to rand, mt_rand
	returns int in *closed* interval [$min,$max]
	see https://codeascraft.com/2012/07/19/better-random-numbers-in-php-using-devurandom/
	***************************************************************************************************************/
	function devurandom_rand($min = 0, $max = 0x7FFFFFFF) {
		$diff = $max - $min;
		if ($diff < 0 || $diff > 0x7FFFFFFF) {
		throw new RuntimeException("Bad range");
		}
		$bytes = mcrypt_create_iv(4, MCRYPT_DEV_URANDOM);
		if ($bytes === false || strlen($bytes) != 4) {
			throw new RuntimeException("Unable to get required bytes.");
		}
		$ary = unpack("Nint", $bytes);
		$val = $ary['int'] & 0x7FFFFFFF;   // 32-bit safe
		$fp = (float) $val / 2147483647.0; // convert to [0,1]
		return round($fp * $diff) + $min;
	}


	/**************************************************************************************************************
	Unsets array keys that should not be used in update/insert DB queries
	***************************************************************************************************************/
	function filter_inputs_pre_query ($allowedFields, $inputs) {
		foreach ($inputs as $key => $value) {
			if ( !in_array($key, $allowedFields) ) {
				unset($inputs[$key]);
			}
		}

		return $inputs;
	}

?>
