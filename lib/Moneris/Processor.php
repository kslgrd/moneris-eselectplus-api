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
	 * @var string
	 */
	static protected $_error_response = "<?xml version=\"1.0\"?><response><receipt><ReceiptId>Global Error Receipt</ReceiptId><ReferenceNum>null</ReferenceNum><ResponseCode>null</ResponseCode><ISO>null</ISO> <AuthCode>null</AuthCode><TransTime>null</TransTime><TransDate>null</TransDate><TransType>null</TransType><Complete>false</Complete><Message>null</Message><TransAmount>null</TransAmount><CardType>null</CardType><TransID>null</TransID><TimedOut>null</TimedOut></receipt></response>";
	
	/**
	 * Get the API config.
	 *
	 * @param string $environment 
	 * @return array
	 */
	static public function config($environment)
	{
		if ($environment != Moneris::ENV_LIVE) {
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
			$result = new Moneris_Result($transaction);
			$result->was_successful(false);
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
		$params = $transaction->params();
		// frig... this MPI stuff is leaking gross code everywhere... needs to be refactored
		if (in_array($params['type'], array('txn', 'acs'))) 
			$config['url'] = '/mpi/servlet/MpiServlet';
		
		$url = $config['protocol'] . '://' .
			   $config['host'] . ':' .
			   $config['port'] .
			   $config['url'];
		
		$xml = str_replace(' </', '</', $transaction->to_xml());
		
		//var_dump($xml);
		
		// this is pulled directly from mpgClasses.php
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
		curl_setopt($ch, CURLOPT_TIMEOUT, $config['timeout']);
		curl_setopt($ch, CURLOPT_USERAGENT, $config['api_version']);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		
		$response = curl_exec($ch);
		curl_close($ch);
		
		// if the response fails for any reason, just use some stock XML
		// also taken directly from mpgClasses:
		if (! $response) {
			return simplexml_load_string(self::$_error_response);
		}
		
		$xml = @simplexml_load_string($response);
		
		// they sometimes return HTML formatted Apache errors... NICE.
		if ($xml === false) {
			return simplexml_load_string(self::$_error_response);
		}
		// force fail AVS for testing
		//$xml->receipt->AvsResultCode = 'N';
		
		// force fail CVD for testing
		//$xml->receipt->CvdResultCode = '1N';
		
		//var_dump($xml);
		
		return $xml;
		
	}
}
