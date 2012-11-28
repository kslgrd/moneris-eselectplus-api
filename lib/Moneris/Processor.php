<?php
/**
 * 
 */
class Moneris_Processor
{
	/**
	 * API config variables pulled from the terrible Moneris API.
	 * @var array
	 */
	static protected $_config = array(
		'protocol' => 'https',
		'host' => 'esqa.moneris.com',
		'port' => '443',
		'url' => '/gateway2/servlet/MpgRequest',
		'api_version' =>'PHP - 2.5.1',
		'timeout' => '60'
	);
	
	/**
	 * Get the API config.
	 *
	 * @param string $environment 
	 * @return array
	 */
	static public function config($environment)
	{
		if ($environment == Moneris::ENV_STAGING) {
			self::$_config['host'] = 'esqa.moneris.com';
		} else {
			self::$_config['host'] = 'www3.moneris.com';
		}
		return self::$_config;
	}
	
	/**
	 * Do the necessary magic to process this transaction via the Moneris API.
	 *
	 * @param Moneris_Transaction $transaction 
	 * @return Moneris_Result
	 */
	static public function process(Moneris_Transaction $transaction)
	{
		if (! $transaction->is_valid()) {
			//TODO: set error code
			$result->error_code(Moneris_Result::ERROR_INVALID_POST_DATA);
			return $result; 
		}
		
		$response = self::_call_api($transaction);
		return $transaction->validate_response($response);
	}
	
	/**
	 * Do the curl call to process the API request.
	 *
	 * @param Moneris_Transaction $transaction 
	 * @return SimpleXMLElement
	 */
	static protected function _call_api(Moneris_Transaction $transaction)
	{
		$gateway = $transaction->gateway();
		$config = self::config($gateway->environment());
		
		$url = $config['protocol'] . '://' .
			   $config['host'] . ':' .
			   $config['port'] .
			   $config['url'];
		
		$xml = $transaction->to_xml();
		
		// this is pulled directly from mpgClasses.php
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
		curl_setopt($ch, CURLOPT_TIMEOUT, $config['timeout']);
		curl_setopt($ch, CURLOPT_USERAGENT, $config['api_version']);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
		
		$response = curl_exec($ch);
		curl_close($ch);
		
		// if the response fails for any reason, just use some stock XML
		// also taken directly from mpgClasses:
		if (! $response) {
			$response="<?xml version=\"1.0\"?><response><receipt><ReceiptId>Global Error Receipt</ReceiptId><ReferenceNum>null</ReferenceNum><ResponseCode>null</ResponseCode><ISO>null</ISO> <AuthCode>null</AuthCode><TransTime>null</TransTime><TransDate>null</TransDate><TransType>null</TransType><Complete>false</Complete><Message>null</Message><TransAmount>null</TransAmount><CardType>null</CardType><TransID>null</TransID><TimedOut>null</TimedOut></receipt></response>";
		}
		
		return simplexml_load_string($response);
		
	}
}
