<?php
/**
 * A much less awful way to access the Moneris API.
 * ------------------------------------------------
 * Copyright (C) 2012 Keith Silgard
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 * 
 */

define('MONERIS_LIB', dirname(__FILE__) . '/Moneris');

require_once MONERIS_LIB . '/Exception.php';
require_once MONERIS_LIB . '/Gateway.php';
require_once MONERIS_LIB . '/Processor.php';
require_once MONERIS_LIB . '/Result.php';
require_once MONERIS_LIB . '/Transaction.php';

if (! function_exists('curl_init')) {
  throw new Moneris_Exception('The Moneris API requires the CURL extension.');
}

/**
 * A really simple way to get a Moneris_Gateway object.
 */
class Moneris
{	
	const ENV_LIVE = 'live'; // use the live API server
	const ENV_STAGING = 'staging'; // use the API sandbox
	const ENV_TESTING = 'testing'; // use the mock API
	
	/**
	 * Start using the API, ya dingus!
	 *
	 * @param array $params Associative array
	 * 		Required keys:
	 * 			- api_key string
	 * 			- store_id string
	 * 		Optional keys:
	 * 			- environment string
	 * 			- require_cvd bool
	 * 			- require_avs bool
	 * 			- avs_codes array
	 * @return Moneris_Gateway
	 */
	static public function create(array $params)
	{
		if (! isset($params['api_key'])) throw new Moneris_Exception("'api_key' is required.");
		if (! isset($params['store_id'])) throw new Moneris_Exception("'store_id' is required.");
		
		$params['environment'] = isset($params['environment']) ? $params['environment'] : self::ENV_LIVE;
		
		$gateway = new Moneris_Gateway($params['api_key'], $params['store_id'], $params['environment']);
		
		if (isset($params['require_cvd'])) 
			$gateway->require_cvd((bool) $params['require_cvd']);
		
		if (isset($params['cvd_codes']))
			$gateway->successful_cvd_codes($params['cvd_codes']);
		
		if (isset($params['require_avs'])) 
			$gateway->require_avs((bool) $params['require_avs']);
		
		if (isset($params['avs_codes']))
			$gateway->successful_avs_codes($params['avs_codes']);
		
		return $gateway;
	}
	
	// don't allow instantiation
	protected function __construct(){ }
}
