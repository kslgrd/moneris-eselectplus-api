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
		'timeout' => '60',
		'replacement' => ''
	);

	/**
	 * Tags to be replaced for us store
	 * @var array
	 */
	private static $_tagsToReplace = array(
		'purchase', 'refund', 'ind_refund', 'preauth',
		'completion', 'purchasecorrection', 'forcepost', 'cavv_preauth',
		'cavv_purchase', 'track2_purchase', 'track2_refund', 'track2_ind_refund',
		'track2_preauth', 'track2_completion', 'track2_purchasecorrection',
		'track2_forcepost', 'ach_debit', 'ach_reversal', 'ach_credit',
		'ach_fi_enquiry', 'pinless_debit_purchase', 'pinless_debit_refund',
		'batchclose', 'opentotals', 'recur_update'
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
		switch ($environment) {
			case Moneris::ENV_LIVE:
			case Moneris::ENV_LIVE_CA:
				self::$_config['host'] = 'www3.moneris.com';
				self::$_config['url'] = '/gateway2/servlet/MpgRequest';
				self::$_config['replacement'] = '';
				break;
			case Moneris::ENV_STAGING:
			case Moneris::ENV_TESTING:
			case Moneris::ENV_STAGING_CA:
			case Moneris::ENV_TESTING_CA:
				self::$_config['host'] = 'esqa.moneris.com';
				self::$_config['url'] = '/gateway2/servlet/MpgRequest';
				self::$_config['replacement'] = '';
				break;
			case Moneris::ENV_LIVE_US:
				self::$_config['host'] = 'esplus.moneris.com';
				self::$_config['url'] = '/gateway_us/servlet/MpgRequest';
				self::$_config['replacement'] = 'us_';
				break;
			case Moneris::ENV_STAGING_US:
			case Moneris::ENV_TESTING_US:
				self::$_config['host'] = 'esplusqa.moneris.com';
				self::$_config['url'] = '/gateway_us/servlet/MpgRequest';
				self::$_config['replacement'] = 'us_';
				break;
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
			$config['url'] = '/mpi/servlet/MpiServlet'; // This url is the same for us store. No need to care

		$url = $config['protocol'] . '://' .
			   $config['host'] . ':' .
			   $config['port'] .
			   $config['url'];

		$xml = str_replace(' </', '</', $transaction->to_xml());

		if ( ! empty($config['replacement'])) {
			$r = $config['replacement'];
			foreach (self::$_tagsToReplace as $tag) {

				$xml = str_replace('<' . $tag . '>', '<' . $r . $tag . '>', $xml);
				$xml = str_replace('</' . $tag . '>', '</' . $r . $tag . '>', $xml);
			}
		}

		//var_dump($url, $xml);

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
