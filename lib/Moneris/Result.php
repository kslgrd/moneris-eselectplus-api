<?php
/**
 * Provides details on how the transaction went.
 */
class Moneris_Result
{
	
	const ERROR_INVALID_POST_DATA = 0;
	
	const ERROR_FAILED_ATTEMPT = -1;
	const ERROR_CREATE_TRANSACTION_RECORD = -2;
	const ERROR_GLOBAL_ERROR_RECEIPT = -3;

	const ERROR_SYSTEM_UNAVAILABLE = -14;
	const ERROR_CARD_EXPIRED = -15;
	const ERROR_INVALID_CARD = -16;
	const ERROR_INSUFFICIENT_FUNDS = -17;
	const ERROR_PREAUTH_FULL = -18;
	const ERROR_DUPLICATE_TRANSACTION = -19;
	const ERROR_DECLINED = -20;
	const ERROR_NOT_AUTHORIZED = -21;

	const ERROR_CVD = -4;
	const ERROR_CVD_NO_MATCH = -5;
	const ERROR_CVD_NOT_PROCESSED = -6;
	const ERROR_CVD_MISSING = -7;
	const ERROR_CVD_NOT_SUPPORTED = -8;

	const ERROR_AVS = -9;
	const ERROR_AVS_POSTAL_CODE = -10;
	const ERROR_AVS_ADDRESS = -11;
	const ERROR_AVS_NO_MATCH = -12;
	const ERROR_AVS_TIMEOUT = -13;

	const ERROR_POST_FRAUD = -22;

	/**
	 * @var Moneris_Transaction
	 */
	protected $_transaction;

	/**
	 * @var int
	 */
	protected $_error_code = null;
	
	/**
	 * @var array
	 */
	protected $_errors = array();
	
	/**
	 * Some nicer error messages.
	 * @var array
	 */
	protected $_messages = array(

		self::ERROR_FAILED_ATTEMPT => 'Failed attempt',
		self::ERROR_CREATE_TRANSACTION_RECORD => 'Unable to create transaction record',
		self::ERROR_GLOBAL_ERROR_RECEIPT => 'An error has occurred',

		self::ERROR_SYSTEM_UNAVAILABLE => 'Payments are temporarily unavailable',
		self::ERROR_CARD_EXPIRED => 'Credit card expired',
		self::ERROR_INVALID_CARD => 'Invalid credit card provided',
		self::ERROR_INSUFFICIENT_FUNDS => 'Insufficient funds',
		self::ERROR_PREAUTH_FULL => 'Pre-auth is full',
		self::ERROR_DUPLICATE_TRANSACTION => 'Duplicate transaction attempt',
		self::ERROR_DECLINED => 'Declined',
		self::ERROR_NOT_AUTHORIZED => 'Not authorized',

		self::ERROR_CVD => 'Invalid CVD provided',
		self::ERROR_CVD_NO_MATCH => 'Provided CVD did not match',
		self::ERROR_CVD_NOT_PROCESSED => 'CVD not processed',
		self::ERROR_CVD_MISSING => 'CVD not provided',
		self::ERROR_CVD_NOT_SUPPORTED => 'CVD not supported',

		self::ERROR_AVS => 'Address verification failed',
		self::ERROR_AVS_POSTAL_CODE => 'Incorrect postal code supplied',
		self::ERROR_AVS_ADDRESS => 'Incorrect address supplied',
		self::ERROR_AVS_NO_MATCH => 'Provided address did not match',
		self::ERROR_AVS_TIMEOUT => 'Address verification timed out',

		self::ERROR_POST_FRAUD => 'Suspected POST fraud'
		
	);
	
	/**
	 * @var bool
	 */
	protected $_was_successful = null;

	/**
	 * @param Moneris_Transaction $transaction 
	 */
	public function __construct(Moneris_Transaction $transaction)
	{
		$this->_transaction = $transaction;
	}

	/**
	 * Get or set the error code.
	 *
	 * @param int $code 
	 * @return int|Moneris_Result Fluid interface for set operations.
	 */
	public function error_code($code = null)
	{
		if (! is_null($code)) {
			$this->_error_code = $code;
			return $this;
		}
		return $this->_error_code;
	}
	
	/**
	 * Get a "nice" error message.
	 *
	 * @return string
	 */
	public function error_message()
	{
		$code = $this->error_code();
		if (isset($this->_messages[$code]))
			return $this->_messages[$code];
		
		return 'Unknown error';
	}
	
	/**
	 * Get any errors that may have occurred.
	 *
	 * @return array
	 */
	public function errors()
	{
		return $this->transaction()->errors();
	}
	
	/**
	 * Get the transaction object for this result.
	 *
	 * @param Moneris_Transaction $transaction 
	 * @return Moneris_Result|Moneris_Transaction Fluid interface for set operations
	 */
	public function transaction(Moneris_Transaction $transaction = null)
	{
		if (! is_null($transaction)) {
			$this->_transaction = $transaction;
			return $this;
		}
		return $this->_transaction;
	}
	
	/**
	 * Was the transaction successfully processed?
	 *
	 * @param bool $was_it Optional. Return value if not provided
	 * @return bool|Moneris_Result Fluid interface for set operations
	 */
	public function was_successful($was_it = null)
	{
		if (! is_null($was_it)) {
			$this->_was_successful = $was_it;
			return $this;
		}
		return $this->_was_successful;
	}
}
