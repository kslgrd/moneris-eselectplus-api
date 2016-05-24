<?php
class Moneris_Gateway
{
	protected $_api_key;

	protected $_store_id;

	protected $_environment;

	protected $_test_mode = false;

	protected $_require_avs = false;

	/**
	 * Codes that we're willing to accept for AVS.
	 * @var array
	 */
	protected $_successful_avs_codes = array('A','B', 'D', 'M', 'P', 'W', 'X', 'Y', 'Z');

	/**
	 * Codes that we're willing to accept for CVD.
	 * @var array
	 */
	protected $_successful_cvd_codes = array('M', 'Y', 'P', 'S', 'U');

	protected $_require_cvd = true;

	/**
	 * Transaction object.
	 * @var Moneris_Transaction
	 */
	protected $_transaction = null;

	/**
	 * @param string $api_key
	 * @param string $store_id
	 * @param string $environment
	 */
	function __construct($api_key, $store_id, $environment = 'live')
	{
		$this->_api_key = $api_key;
		$this->_store_id = $store_id;
		$this->_environment = $environment;
	}

	/**
	 * Verify a 3D Secure for Visa/Mastercard.
	 *
	 * @param array $params An associative array.
	 * 		Required:
	 *			- PaRes string No idea what this is, but it's a mess of chars.
	 * 			- MD string Information that will be returned to allow you to process the response
	 * @return Moneris_3DSecureResult
	 */
	public function acs(array $params)
	{
		$params['type'] = 'acs';
		$transaction = $this->transaction($params);
	 	return $this->_process($transaction);
	}

	/**
	 * Make a purchase.
	 *
	 * @param array $params An associative array.
	 * 		Required:
	 *			- order_id string A unique transaction ID, up to 50 chars
	 * 			- cc_number int Any non-numeric characters will be stripped
	 *			- amount float
	 * 			- cavv
	 * 			- expdate int 4 digit date YYMM
	 * 			OR
	 *			- expiry_month int 2 digit representation of the expiry month (01-12)
	 * 			- expiry_year int last two digits of the expiry year
	 * 		Required (if AVS is enabled):
	 * 			- avs_street_number string Up to 19 chars combined with street name
	 *			- avs_street_name string
	 * 			- avs_zipcode string Up to 10 chars
	 * 		Optional (if AVS is enabled, Amex/JCB only):
	 * 			- avs_email string Up to 60 chars
	 *			- avs_hostname string Up to 60 chars
	 * 			- avs_browser string Up to 60 chars
	 * 			- avs_shiptocountry string 3 chars
	 *			- avs_method string string 2 chars
	 * 			- avs_merchprodsku string Up to 15 chars
	 * 			- avs_custip string 15 chars
	 * 			- avs_custphone int 10 digits
	 * 		Required (id CVD is enabled):
	 *			- cvd
	 * 		Optional:
	 *			- description string A description of the purchase, up to 20 chars
	 *			- cust_id string An identifier for the customer, up to 50 chars
	 * @return Moneris_Result
	 */
	public function cavv_purchase(array $params)
	{
		$params['type'] = 'cavv_purchase';
		$transaction = $this->transaction($params);
	 	return $this->_process($transaction);
	}

	/**
	 * Set/get the API key.
	 *
	 * @param string $key Optional. If provided, set the value for the key.
	 * @return string|Moneris_Gateway Fluid interface for set operations.
	 */
	public function api_key($key = null)
	{
		if (! is_null($key)) {
			$this->_api_key = $key;
			return $this;
		}
		return $this->_api_key;
	}

	/**
	 * Capture a pre-authorized transaction.
	 *
	 * @param string|Moneris_Transation $transaction_number
	 * @param string $order_id Required if first param isn't an instance of Moneris_Transation
	 * @return void
	 */
	public function capture($transaction_number, $order_id = null, $amount = null)
	{
		if ($transaction_number instanceof Moneris_Transaction) {
			$order_id = $transaction_number->order_id();
			$amount = is_null($amount) ? $transaction_number->amount() : $amount;
			$transaction_number = $transaction_number->number();
		}
		// these have to be in this order!
		$params = array(
			'type' => 'completion',
			'order_id' => $order_id,
			'comp_amount' => $amount,
			'txn_number' => $transaction_number,
			'crypt_type' => 7
		);

		$transaction = $this->transaction($params);
	 	return $this->_process($transaction);
	}


	/**
	 * Are we using AVS?
	 *
	 * @return bool
	 */
	public function check_avs()
	{
		return $this->_require_avs;
	}

	/**
	 * Do the CVD digits need to be checked?
	 *
	 * @return bool
	 */
	public function check_cvd()
	{
		return $this->_require_cvd;
	}

	/**
	 * Get/set the API environment.
	 *
	 * @param string $environment
	 * @return void
	 * @author Keith Silgard
	 */
	public function environment($environment = null)
	{
		if (! is_null($environment)) {
			$this->_environment = $environment;
			return $this;
		}
		return $this->_environment;
	}

	public function errors()
	{
		return $this->transaction()->errors();
	}

	/**
	 * Pre-authorize a purchase.
	 *
	 * @param array $params An associative array.
	 * 		Required:
	 *			- order_id string A unique transaction ID, up to 50 chars
	 * 			- cc_number int Any non-numeric characters will be stripped
	 *			- amount float
	 * 			- expdate int 4 digit date YYMM
	 * 			OR
	 *			- data_key string A token/data key, used with Hosted Tokenization
	 *			- amount float
	 * 			OR
	 *			- expiry_month int 2 digit representation of the expiry month (01-12)
	 * 			- expiry_year int last two digits of the expiry year
	 * 		Required (if AVS is enabled):
	 * 			- avs_street_number string Up to 19 chars combined with street name
	 *			- avs_street_name string
	 * 			- avs_zipcode string Up to 10 chars
	 * 		Optional (if AVS is enabled, Amex/JCB only):
	 * 			- avs_email string Up to 60 chars
	 *			- avs_hostname string Up to 60 chars
	 * 			- avs_browser string Up to 60 chars
	 * 			- avs_shiptocountry string 3 chars
	 *			- avs_method string string 2 chars
	 * 			- avs_merchprodsku string Up to 15 chars
	 * 			- avs_custip string 15 chars
	 * 			- avs_custphone int 10 digits
	 * 		Required (id CVD is enabled):
	 *			- cvd
	 * 		Optional:
	 *			- description string A description of the purchase, up to 20 chars
	 *			- cust_id string An identifier for the customer, up to 50 chars
	 * @return Moneris_Result
	 */
	public function preauth(array $params)
	{
		$params['type'] = (isset($params['data_key'])) ? 'res_preauth_cc' : 'preauth';
		$params['crypt_type'] = 7;
		$transaction = $this->transaction($params);
	 	return $this->_process($transaction);
	}

	/**
	 * Make a purchase.
	 *
	 * @param array $params An associative array.
	 * 		Required:
	 *			- order_id string A unique transaction ID, up to 50 chars
	 * 			- cc_number int Any non-numeric characters will be stripped
	 *			- amount float
	 * 			- expdate int 4 digit date YYMM
	 * 			OR
	 *			- data_key string A token/data key, used with Hosted Tokenization
	 *			- amount float
	 * 			OR
	 *			- expiry_month int 2 digit representation of the expiry month (01-12)
	 * 			- expiry_year int last two digits of the expiry year
	 * 		Required (if AVS is enabled):
	 * 			- avs_street_number string Up to 19 chars combined with street name
	 *			- avs_street_name string
	 * 			- avs_zipcode string Up to 10 chars
	 * 		Optional (if AVS is enabled, Amex/JCB only):
	 * 			- avs_email string Up to 60 chars
	 *			- avs_hostname string Up to 60 chars
	 * 			- avs_browser string Up to 60 chars
	 * 			- avs_shiptocountry string 3 chars
	 *			- avs_method string string 2 chars
	 * 			- avs_merchprodsku string Up to 15 chars
	 * 			- avs_custip string 15 chars
	 * 			- avs_custphone int 10 digits
	 * 		Required (id CVD is enabled):
	 *			- cvd
	 * 		Optional:
	 *			- description string A description of the purchase, up to 20 chars
	 *			- cust_id string An identifier for the customer, up to 50 chars
	 * @return Moneris_Result
	 */
	public function purchase(array $params)
	{
		$params['type'] = (isset($params['data_key'])) ? 'res_purchase_cc' : 'purchase';
		$params['crypt_type'] = 7;
		$transaction = $this->transaction($params);
	 	return $this->_process($transaction);
	}

	public function reauth()
	{

	}

	/**
	 * Refund a transaction.
	 *
	 * @param string|Moneris_Transation $transaction_number
	 * @param string $order_id Required if first param isn't an instance of Moneris_Transation
	 * @return void
	 */
	public function refund($transaction_number, $order_id = null, $amount = null)
	{
		if ($transaction_number instanceof Moneris_Transaction) {
			$order_id = $transaction_number->order_id();
			$amount = is_null($amount) ? $transaction_number->amount() : $amount;
			$transaction_number = $transaction_number->number();
		}
		// the order of these params matters for some amazingly insane reason:
		$params = array(
			'type' => 'refund',
			'order_id' => $order_id,
			'amount' => $amount,
			'txn_number' => $transaction_number,
			'crypt_type' => 7
		);

		$transaction = $this->transaction($params);
	 	return $this->_process($transaction);
	}


	public function result()
	{
		return $this->transaction()->result();
	}

	/**
	 * Require address verification.
	 * Transaction will require additional AVS params.
	 *
	 * @param bool $require_it
	 * @return Moneris_Gateway Fluid interface
	 * @author Keith Silgard
	 */
	public function require_avs($require_it = true)
	{
		$this->_require_avs = $require_it;
		return $this;
	}

	/**
	 * Require card validation digits.
	 * Transaction will require additional CVD params.
	 *
	 * @param bool $require_it
	 * @return Moneris_Gateway Fluid interface
	 * @author Keith Silgard
	 */
	public function require_cvd($require_it = true)
	{
		$this->_require_cvd = $require_it;
		return $this;
	}

	/**
	 * Set/get the store ID.
	 *
	 * @param string $id Optional. If provided, set the value for the id.
	 * @return string|Moneris_Gateway Fluid interface for set operations.
	 */
	public function store_id($id = null)
	{
		if (! is_null($id)) {
			$this->_store_id = $id;
			return $this;
		}
		return $this->_store_id;
	}

	/**
	 * Get or set the AVS codes we'll use to determine a successful transaction.
	 *
	 * @param array $codes
	 * @return array|Moneris_Gateway Fluid interface for set operations
	 * @author Keith Silgard
	 */
	public function successful_avs_codes(array $codes = null)
	{
		if (! is_null($codes)) {
			$this->_successful_avs_codes = $codes;
			return $this;
		}
		return $this->_successful_avs_codes;
	}

	/**
	 * Get or set the CVD codes we'll use to determine a successful transaction.
	 *
	 * @param array $codes
	 * @return array|Moneris_Gateway Fluid interface for set operations
	 * @author Keith Silgard
	 */
	public function successful_cvd_codes(array $codes = null)
	{
		if (! is_null($codes)) {
			$this->_successful_cvd_codes = $codes;
			return $this;
		}
		return $this->_successful_cvd_codes;
	}

	/**
	 * Get a transaction object!
	 *
	 * @param bool $force_new Always return a new transaction.
	 * @return Moneris_Transaction
	 */
	public function transaction(array $params = null)
	{
		if (is_null($this->_transaction) || ! is_null($params))
			return $this->_transaction = new Moneris_Transaction($this, $params);
		return $this->_transaction;
	}

	/**
	 * Perform a 3D Secure verification for Visa/Mastercard.
	 * I guess this will fail spectacularly if you try it with Amex, etc, so don't.
	 *
	 * @param array $params An associative array.
	 * 		Required:
	 *			- xid|order_id string A unique transaction ID, up to 50 chars. Use either key, but not both.
	 * 			- cc_number int Any non-numeric characters will be stripped
	 *			- amount float
	 * 			- expdate int 4 digit date YYMM
	 * 			- MD string Information that will be returned to allow you to process the response
	 *			- merchantUrl string URL responses will be sent to
	 * 		Optional (will be added if you skip them):
	 *			- accept string MIME types accepted by the browser
	 *			- userAgent string Browser user-agent
	 * @return Moneris_3DSecureResult
	 */
	public function txn(array $params)
	{
		$params['type'] = 'txn';
		if (! isset($params['accept']))
			$params['accept'] = $_SERVER['HTTP_ACCEPT'];
		if (! isset($params['userAgent']))
			$params['userAgent'] = $_SERVER['HTTP_USER_AGENT'];

		if (isset($params['order_id'])) {
			$params['xid'] = $params['order_id'];
			unset($params['order_id']);
		}

		$transaction = $this->transaction($params);
	 	return $this->_process($transaction);
	}

	/**
	 * Whether or not we're running tests!
	 * For unit tests only, not the Moneris staging server tests.
	 *
	 * @param bool $use_it
	 * @return Moneris Fluid interface
	 */
	public function use_test_mode($use_it = true)
	{
		$this->_test_mode = $use_it;
		return $this;
	}

	/**
	 * Validate CVD and or AVS prior to attempting a purchase.
	 *
	 * @param array $params An associative array.
	 * 		Required:
	 *			- order_id string A unique transaction ID, up to 50 chars
	 * 			- cc_number int Any non-numeric characters will be stripped
	 *			- amount float
	 * 			- expdate int 4 digit date YYMM
	 * 			OR
	 *			- expiry_month int 2 digit representation of the expiry month (01-12)
	 * 			- expiry_year int last two digits of the expiry year
	 * 		Required (if AVS is enabled):
	 * 			- avs_street_number string Up to 19 chars combined with street name
	 *			- avs_street_name string
	 * 			- avs_zipcode string Up to 10 chars
	 * 		Optional (if AVS is enabled, Amex/JCB only):
	 * 			- avs_email string Up to 60 chars
	 *			- avs_hostname string Up to 60 chars
	 * 			- avs_browser string Up to 60 chars
	 * 			- avs_shiptocountry string 3 chars
	 *			- avs_method string string 2 chars
	 * 			- avs_merchprodsku string Up to 15 chars
	 * 			- avs_custip string 15 chars
	 * 			- avs_custphone int 10 digits
	 * 		Required (id CVD is enabled):
	 *			- cvd
	 * 		Optional:
	 *			- description string A description of the purchase, up to 20 chars
	 *			- cust_id string An identifier for the customer, up to 50 chars
	 * @return Moneris_Result
	 */
	public function verify(array $params)
	{
		$params['type'] = 'card_verification';
		$params['crypt_type'] = 7;
		$transaction = $this->transaction($params);
	 	return $this->_process($transaction);
	}

	/**
	 * Void a transaction.
	 *
	 * @param string|Moneris_Transation $transaction_number
	 * @param string $order_id Required if first param isn't an instance of Moneris_Transation
	 * @return void
	 */
	public function void($transaction_number, $order_id = null)
	{
		if ($transaction_number instanceof Moneris_Transaction) {
			$order_id = $transaction_number->order_id();
			$transaction_number = $transaction_number->number();
		}
		$params = array(
			'type' => 'purchasecorrection',
			'order_id' => $order_id,
			'txn_number' => $transaction_number,
			'crypt_type' => 7
		);

		$transaction = $this->transaction($params);
	 	return $this->_process($transaction);
	}

	/**
	 * Submit a transaction to the Moneris API and see how it works out!
	 *
	 * @param Moneris_Transaction $transaction
	 * @return Moneris_Result
	 */
	protected function _process(Moneris_Transaction $transaction)
	{
		return Moneris_Processor::process($transaction);
	}

}