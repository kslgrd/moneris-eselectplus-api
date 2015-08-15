<?php
/**
 * Mostly takes care of validation.
 */
class Moneris_Transaction
{

	/**
	 * @var array
	 */
	protected $_errors = array();

	/**
	 * @var Moneris
	 */
	protected $_gateway;

	/**
	 * @var array
	 */
	protected $_params;

	/**
	 * @var SimpleXMLElement
	 */
	protected $_response;

	/**
	 * The result object for this transaction.
	 * @var Moneris_Result
	 */
	protected $_result = null;

	/**
	 * @param Moneris_Gateway $gateway
	 */
	public function __construct(Moneris_Gateway $gateway, array $params = array(), $prepare_params = true)
	{
		$this->gateway($gateway);
		$this->_params = $prepare_params ? $this->prepare($params) : $params;
	}

	/**
	 * The amount for this transaction.
	 * Only available for some transaction types.
	 *
	 * @return string|null
	 */
	public function amount()
	{
		if (isset($this->_params['amount']))
			return $this->_params['amount'];
		return null;
	}

	/**
	 * Check that required params have been provided.
	 *
	 * @return bool
	 */
	public function is_valid()
	{
		$params = $this->_params;
		$errors = array();

		if (empty($params))
			$errors[] = 'No params provided.';

		if (isset ($params['type'])) {
			switch ($params['type']) {
				case 'cavv_purchase':
				case 'purchase':
				case 'preauth':
				case 'card_verification':

					if (! isset($params['order_id'])) $errors[] = 'Order ID not provided';
					if (! isset($params['pan'])) $errors[] = 'Credit card number not provided';
					if (! isset($params['amount'])) $errors[] = 'Amount not provided';
					if (! isset($params['expdate'])) $errors[] = 'Expiry date not provided';

					if ($this->gateway()->check_avs()) {

						if (! isset($params['avs_street_number'])) $errors[] = 'Street number not provided';
						if (! isset($params['avs_street_name'])) $errors[] = 'Street name not provided';
						if (! isset($params['avs_zipcode'])) $errors[] = 'Zip/postal code not provided';

						//@TODO email is Amex/JCB only...
						//if (! isset($params['avs_email'])) $errors[] = 'Email not provided';

					}

					if ($this->gateway()->check_cvd()) {
						if (! isset($params['cvd'])) $errors[] = 'CVD not provided';
					}

					break;

				case 'purchasecorrection':
					if (! isset($params['order_id']) || '' == $params['order_id']) $errors[] = 'Order ID not provided';
					if (! isset($params['txn_number']) || '' == $params['txn_number']) $errors[] = 'Transaction number not provided';
					break;

				case 'completion':
					if (! isset($params['comp_amount']) || '' === $params['comp_amount']) $errors[] = 'Amount not provided';
					if (! isset($params['order_id']) || '' == $params['order_id']) $errors[] = 'Order ID not provided';
					if (! isset($params['txn_number']) || '' == $params['txn_number']) $errors[] = 'Transaction number not provided';
					break;

				case 'refund':
					if (! isset($params['amount']) || '' == $params['amount']) $errors[] = 'Amount not provided';
					if (! isset($params['order_id']) || '' == $params['order_id']) $errors[] = 'Order ID not provided';
					if (! isset($params['txn_number']) || '' == $params['txn_number']) $errors[] = 'Transaction number not provided';
					break;
				case 'txn':
					if (! isset($params['xid'])) $errors[] = 'Order ID not provided';
					if (! isset($params['pan'])) $errors[] = 'Credit card number not provided';
					if (! isset($params['amount'])) $errors[] = 'Amount not provided';
					if (! isset($params['expdate'])) $errors[] = 'Expiry date not provided';
					if (! isset($params['MD'])) $errors[] = 'Merchant details "MD" not provided';
					if (! isset($params['merchantUrl'])) $errors[] = 'Merchant URL not provided';

					// force the sort here... because why not... fuck it, I give up. Moneris are just the fucking worst, and I
					// have lost my will to try and program well around their insanity.
					$this->_params = array(
						'type' => 'txn',
						'xid' => $params['xid'],
						'amount' => $params['amount'],
						'pan' => $params['pan'],
						'expdate' => $params['expdate'],
						'MD' => $params['MD'],
						'merchantUrl' => $params['merchantUrl'],
						'accept' => $params['accept'],
						'userAgent' => $params['userAgent'],
						// params that go unmentioned in the docs, but are seemingly required?
						'currency' => ' ',
						'recurFreq' => ' ',
						'recurEnd' => ' ',
						'install' => ' '
					);

					break;
				case 'acs':
					if (! isset($params['PaRes'])) $errors[] = 'PaRes not provided';
					if (! isset($params['MD'])) $errors[] = 'Merchant details "MD" not provided';
					break;
				default:
					$errors[] = $params['type'] . ' is not a support transaction type';
			}
		} else {
			$errors[] = 'Transaction type not provided';
		}

		$this->errors($errors);
		return empty($errors);
	}

	/**
	 * Get or set errors.
	 *
	 * @param array $errors
	 * @return array|Moneris_Result Fluid interface for set operations.
	 */
	public function errors(array $errors = null)
	{
		if (! is_null($errors)) {
			$this->_errors = $errors;
			return $this;
		}
		return $this->_errors;
	}

	/**
	 * Get or set the gateway object.
	 *
	 * @param Moneris_Gateway $gateway Optional.
	 * @return Moneris_Gateway|Moneris_Transaction Fluid interface for set operations
	 */
	public function gateway(Moneris_Gateway $gateway = null)
	{
		if (! is_null($gateway)) {
			$this->_gateway = $gateway;
			return $this;
		}
		return $this->_gateway;
	}

	/**
	 * The transaction number (only available for transaction that have been processed).
	 *
	 * @return string|null
	 */
	public function number()
	{
		if (is_null($this->_response))
			return null;
		return (string) $this->_response->receipt->TransID;
	}

	/**
	 * The order ID for this transaction.
	 * Only available for some transaction types.
	 *
	 * @return string|null
	 */
	public function order_id()
	{
		if (isset($this->_params['order_id']))
			return $this->_params['order_id'];
		return null;
	}

	/**
	 * Get or some some params! Like a boss!
	 *
	 * @param array $params
	 * @return array|Moneris_Transaction Fluid interface on set operations.
	 */
	public function params(array $params = null, $prepare_params = true)
	{
		if (! is_null($params)) {
			$this->_params = $prepare_params ? $this->prepare($params) : $params;
			return $this;
		}
		return $this->_params;
	}

	/**
	 * Clean up transaction parameters.
	 *
	 * @param array $params
	 * @return array Cleaned up parameters
	 */
	public function prepare(array $params)
	{
		foreach ($params as $k => $v) {
			if (is_string($v))
				$params[$k] = trim($v); // remove whitespace
			if ('' == $params[$k]) unset($params[$k]); // remove optional params
		}

		// amount has to include a penny value, or the transaction will fail:
		if (isset($params['amount']) && false === strpos($params['amount'], '.')) {
			$params['amount'] .= '.00';
		}

		if (isset($params['cc_number'])) {
			$params['pan'] = preg_replace('/\D/', '', $params['cc_number']);
			unset($params['cc_number']);
		}

		if (isset($params['description'])) {
			$params['dynamic_descriptor'] = $params['description'];
			unset($params['description']);
		}

		if (isset($params['expiry_month']) && isset($params['expiry_year']) && ! isset($params['expdate'])) {
			$params['expdate'] = sprintf('%02d%02d', $params['expiry_year'], $params['expiry_month']);
			unset($params['expiry_year'], $params['expiry_month']);
		}

		return $params;
	}

	/**
	 * Get or set the response.
	 *
	 * @param SimpleXMLElement $response
	 * @return SimpleXMLElement|Moneris_Transaction Fluid interface for set operations
	 */
	public function response(SimpleXMLElement $response = null)
	{
		if (! is_null($response)) {
			$this->_response = $response;
			return $this;
		}
		return $this->_response;
	}

	/**
	 * Convert the transaction params into XML.
	 *
	 * @return string XML formatted transaction params
	 */
	public function to_xml()
	{
		$gateway = $this->gateway();
		$params = $this->params();

		// starting to get UGLY...
		$request_type = in_array($params['type'], array('txn', 'acs')) ? 'MpiRequest' : 'request';

		$xml = new SimpleXMLElement("<$request_type/>");
		$xml->addChild('store_id', $gateway->store_id());
		$xml->addChild('api_token', $gateway->api_key());

		$type = $xml->addChild($params['type']);
		$type_allows_efraud = in_array($params['type'], array('purchase', 'preauth', 'card_verification', 'cavv_purchase', 'cavv_preauth'));
		// prevent type from being included below when we all all of the optional params:
		unset($params['type']);

		if ($gateway->check_cvd() && $type_allows_efraud) {
			$cvd = $type->addChild('cvd_info');
			$cvd->addChild('cvd_indicator', '1');
			$cvd->addChild('cvd_value', $params['cvd']);
			unset($params['cvd']);
		}

		if ($gateway->check_avs() && $type_allows_efraud) {
			$avs = $type->addChild('avs_info');
			foreach ($params as $key => $value) {
				if (substr($key, 0, 4) != 'avs_')
					continue;
				$avs->addChild($key, $value);
				unset($params[$key]);
			}

		}

		// include all optional params:
		foreach ($params as $key => $value) {
			$type->addChild($key, $value);
		}

		return $xml->asXML();
	}

	/**
	 * Was this transaction a huge success?
	 *
	 * @param SimpleXMLElement $response
	 * @return Moneris_Result
	 */
	public function validate_response(SimpleXMLElement $response)
	{
		$this->response($response);
		$result = Moneris_Result::factory($this);
		$result->validate_response();
		return $result;
	}

}
