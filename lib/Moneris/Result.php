<?php
/**
 * Provides details on how the transaction went.
 */
class Moneris_Result
{

	const ERROR = -23;
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
	 * Whether or not this result failed AVS.
	 * @var bool
	 */
	protected $_failed_avs = false;

	/**
	 * Whether or not this result failed CVD.
	 * @var bool
	 */
	protected $_failed_cvd = false;

	/**
	 * Some nicer error messages.
	 * @var array
	 */
	protected $_messages = array(

		self::ERROR_FAILED_ATTEMPT => 'Failed attempt',
		self::ERROR_CREATE_TRANSACTION_RECORD => 'Unable to create transaction record',
		self::ERROR_GLOBAL_ERROR_RECEIPT => 'An error has occurred with our payment provider',

		self::ERROR_SYSTEM_UNAVAILABLE => 'Payments are temporarily unavailable',
		self::ERROR_CARD_EXPIRED => 'Credit card expired',
		self::ERROR_INVALID_CARD => 'Invalid credit card provided',
		self::ERROR_INSUFFICIENT_FUNDS => 'Insufficient funds',
		self::ERROR_PREAUTH_FULL => 'Pre-auth is full',
		self::ERROR_DUPLICATE_TRANSACTION => 'Duplicate transaction attempt',
		self::ERROR_DECLINED => 'Declined',
		self::ERROR_NOT_AUTHORIZED => 'Not authorized',

		self::ERROR_CVD => 'Invalid security code provided',
		self::ERROR_CVD_NO_MATCH => 'Provided security code did not match',
		self::ERROR_CVD_NOT_PROCESSED => 'Security code not processed',
		self::ERROR_CVD_MISSING => 'Security code not provided',
		self::ERROR_CVD_NOT_SUPPORTED => 'Security code not supported',

		self::ERROR_AVS => 'Address verification failed',
		self::ERROR_AVS_POSTAL_CODE => 'Incorrect postal code supplied',
		self::ERROR_AVS_ADDRESS => 'Incorrect address supplied',
		self::ERROR_AVS_NO_MATCH => 'Provided address did not match',
		self::ERROR_AVS_TIMEOUT => 'Address verification timed out',

		self::ERROR_POST_FRAUD => 'Suspected POST fraud',

		self::ERROR => 'An error has occurred'

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
	 * Return the appropriate result type based off of the transaction.
	 * @return Moneris_Result|Moneris_3DSecureResult
	 */
	static public function factory(Moneris_Transaction $transaction)
	{
		$params = $transaction->params();
		if (in_array($params['type'], array('txn', 'acs'))) {
			return new Moneris_3DSecureResult($transaction);
		} else {
			return new Moneris_Result($transaction);
		}
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
	 * Did this result fail AVS?
	 *
	 * @param bool $failed
	 * @return bool|Moneris_Results Fluid interface on set operations.
	 */
	public function failed_avs($failed = null)
	{
		if (is_null($failed)) {
			return $this->_failed_avs;
		}
		$this->_failed_avs = (bool) $failed;
		return $this;
	}

	/**
	 * Did this result fail CVD?
	 *
	 * @param bool $failed
	 * @return bool|Moneris_Results Fluid interface on set operations.
	 */
	public function failed_cvd($failed = null)
	{
		if (is_null($failed)) {
			return $this->_failed_cvd;
		}
		$this->_failed_cvd = (bool) $failed;
		return $this;
	}

	/**
	 * Did this result pass an AVS check?
	 *
	 * @return bool
	 */
	public function passed_avs()
	{
		return ! $this->_failed_avs;
	}

	/**
	 * Did this result pass an CVD check?
	 *
	 * @return bool
	 */
	public function passed_cvd()
	{
		return ! $this->_failed_cvd;
	}

	/**
	 * Moneris reference number.
	 *
	 * @return void
	 * @author Keith Silgard
	 */
	public function reference_number()
	{
		$response = $this->transaction()->response();
		return is_null($response) ? '' : $response->receipt->ReferenceNum;
	}

	/**
	 * The response from Moneris.
	 *
	 * @return SimpleXmlObject
	 */
	public function response()
	{
		return $this->transaction()->response();
	}

	/**
	 * Moneris' response code.
	 *
	 * @return string
	 */
	public function response_code()
	{
		$response = $this->transaction()->response();
		return is_null($response) ? '' : $response->receipt->ResponseCode;
	}

	/**
	 * Moneris' response message.
	 *
	 * @return string
	 */
	public function response_message()
	{
		$response = $this->transaction()->response();
		return is_null($response) ? '' : $response->receipt->Message;
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
	 * Validate the response from Moneris to see if it was successful.
	 *
	 * @return Moneris_Result
	 */
	public function validate_response()
	{
		$receipt = $this->transaction()->response()->receipt;
		$gateway = $this->transaction()->gateway();

		// did the transaction go through?
		if ('Global Error Receipt' == $receipt->ReceiptId) {

			$this->error_code(Moneris_Result::ERROR_GLOBAL_ERROR_RECEIPT)
				->was_successful(false);
			return $this;
		}

		// was it a successful transaction?
		// any response code greater than 49 is an error code:
		if ((int) $receipt->ResponseCode >= 50) {

			// trying to make some sense of this... grouping them as best as I can:
			switch ($receipt->ResponseCode) {
				case '050':
				case '074':
				case 'null':
					$this->error_code(Moneris_Result::ERROR_SYSTEM_UNAVAILABLE);
					break;
				case '051':
				case '482':
				case '484':
					$this->error_code(Moneris_Result::ERROR_CARD_EXPIRED);
					break;
				case '075':
					$this->error_code(Moneris_Result::ERROR_INVALID_CARD);
					break;
				case '076':
				case '079':
				case '080':
				case '081':
				case '082':
				case '083':
					$this->error_code(Moneris_Result::ERROR_INSUFFICIENT_FUNDS);
					break;
				case '077':
					$this->error_code(Moneris_Result::ERROR_PREAUTH_FULL);
					break;
				case '078':
					$this->error_code(Moneris_Result::ERROR_DUPLICATE_TRANSACTION);
					break;
				case '476':
				case '478':
				case '479':
				case '480':
				case '481':
				case '483':
					$this->error_code(Moneris_Result::ERROR_DECLINED);
					break;
				case '485':
					$this->error_code(Moneris_Result::ERROR_NOT_AUTHORIZED);
					break;
				case '486':
				case '487':
				case '489':
				case '490':
					$this->failed_cvd(true);
					$this->error_code(Moneris_Result::ERROR_CVD);
					break;
				default:
					$this->error_code(Moneris_Result::ERROR);

			}

			return $this->was_successful(false);

		}

		// if the transaction used AVS, we need to know if it was successful, and void the transaction if it wasn't:
		if ($gateway->check_avs()
			&& isset($receipt->AvsResultCode)
			&& 'null' !== (string) $receipt->AvsResultCode
			&& ! in_array($receipt->AvsResultCode, $gateway->successful_avs_codes())) {

			// see if we can't provide a nice, detailed error response:
			switch ($receipt->AvsResultCode) {
				case 'B':
				case 'C':
					$this->error_code(Moneris_Result::ERROR_AVS_POSTAL_CODE);
					break;
				case 'G':
				case 'I':
				case 'P':
				case 'S':
				case 'U':
				case 'Z':
					$this->error_code(Moneris_Result::ERROR_AVS_ADDRESS);
					break;
				case 'N':
					$this->error_code(Moneris_Result::ERROR_AVS_NO_MATCH);
					break;
				case 'R':
					$this->error_code(Moneris_Result::ERROR_AVS_TIMEOUT);
					break;
				default:
					$this->error_code(Moneris_Result::ERROR_AVS);
			}


			$this->failed_avs(true);
			return $this->was_successful(false);
		}


		// if the transaction used CVD, we need to know if it was successful, and void the transaction if it wasn't:
		$this_code = isset($receipt->CvdResultCode) ? (string) $receipt->CvdResultCode : null;
		if ($gateway->check_cvd()
			&& ! is_null($this_code)
			&& 'null' !== $this_code
			&& ! in_array($this_code[1], $gateway->successful_cvd_codes())) {

			$this->error_code(Moneris_Result::ERROR_CVD)->failed_cvd(true);
			return $this->was_successful(false);
		}

		return $this->was_successful(true);
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
