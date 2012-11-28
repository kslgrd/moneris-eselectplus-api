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
	
	public function capture()
	{
		
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
	
	public function preauth()
	{
		
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
	 *			- expiry_month int 2 digit representation of the expiry month (01-12)
	 * 			- expiry_year int last two digits of the expiry year
	 * 		Required (if AVS is enabled):
	 * 			- avs_street_number string Up to 19 chars combined with street name
	 *			- avs_street_name string 
	 * 			- avs_zipcode
	 * 			- avs_email
	 * 		Required (id CVD is enabled):
	 *			- cvd
	 * 		Optional:
	 *			- description string A description of the purchase, up to 20 chars
	 *			- cust_id string An identifier for the customer, up to 50 chars
	 * @return Moneris_Result
	 */
	public function purchase(array $params)
	{
		$params['type'] = 'purchase';
		$params['crypt_type'] = 7;
		$transaction = $this->transaction($params);
	 	return $this->_process($transaction);
	}
	
	public function reauth()
	{
		
	}
	
	public function refund()
	{
		
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
	 * Get or set the AVS we'll use to determine a successful transaction.
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
	
	public function void($transaction_number, $order_id)
	{
		
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