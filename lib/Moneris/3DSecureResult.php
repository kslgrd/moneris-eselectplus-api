<?php 
/**
 * Holy shit, this is a kludge. Need to refactor to support different result types... 
 */
class Moneris_3DSecureResult extends Moneris_Result
{
	protected $_is_enrolled = false;
	
	/**
	 * If the card isn't enrolled, we may still be eligable for protection (response code 'N')
	 * @return string A string number though, so that's cool.
	 */
	public function fallback_encryption_type()
	{
		return 'N' == $this->response()->message ? '6' : '7';
	}
	
	/**
	 * Is the provided card enrolled in the 3D Secure program.
	 * @return bool
	 */
	public function is_enrolled()
	{
		return $this->_is_enrolled;
	}
	
	/**
	 * Moneris reference number.
	 * @return string
	 */
	public function reference_number()
	{
		return $this->response()->PaReq;
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
		return $this->response()->message;
	}
	
	/**
	 * Moneris' response message.
	 *
	 * @return string
	 */
	public function response_message()
	{
		return $this->response()->message;
	}
	
	public function submit_url()
	{
		return $this->response()->ACSUrl;
	}
	
	public function term_url()
	{
		return $this->response()->TermUrl;
	}
	
	/**
	 * Validate the response from Moneris to see if it was successful.
	 *
	 * @return Moneris_Result
	 */
	public function validate_response()
	{
		$response = $this->response();
		$gateway = $this->transaction()->gateway();
		
		// did the transaction go through?
		if ('Error' == $response->type) {
			$this->error_code(Moneris_Result::ERROR)
				->was_successful(false);
			return $this;
		}
		
		$this->was_successful("true" == $response->success);
		if ($this->was_successful() && isset($response->message)) {
			$this->_is_enrolled = 'Y' == $response->message;
		}
		return $this;
	}
	
	public function value()
	{
		$response = $this->response();
		return isset($response->PaReq) ? $response->PaReq : $response->cavv;
	}
}
