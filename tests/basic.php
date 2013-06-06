<?php 
if (! defined('ROOT')) {
	define('ROOT', dirname(dirname(__FILE__)));
}
require_once ROOT . '/simpletest/autorun.php';
require_once ROOT . '/lib/Moneris.php';

/**
 * 
 */
class TestBasic extends UnitTestCase
{	
	protected $_store = 'store3';
	
	/**
	 * You can use this to only run one test at a time for debugging.
	 *
	 * @return array
	 */
	public function getTests()
	{
		//return array("testVoid");
		return parent::getTests();
	}
	
	public function testCapture()
	{
		$gateway = $this->_gateway(array('require_cvd' => false));
		$result = $this->_preauth($gateway);
		$this->assertTrue($result->was_successful());
		
		$transaction = $result->transaction();
		$capture_result = $gateway->capture($transaction);
		
		$this->assertIsA($capture_result, 'Moneris_Result');
		$this->assertTrue($capture_result->was_successful());
	}
	
	public function testPreAuth()
	{
		$gateway = $this->_gateway(array('require_cvd' => false));
		$result = $this->_preauth($gateway);
		
		$this->assertIsA($result, 'Moneris_Result');
		$this->assertTrue($result->was_successful());
	}
	
	public function testPurchase()
	{
		$gateway = $this->_gateway(array('require_cvd' => false));
		$result = $this->_purchase($gateway);
		$this->assertIsA($result, 'Moneris_Result');
		$this->assertTrue($result->was_successful());
	}
	
	public function testPurchaseEFraud()
	{
		$gateway = $this->_gateway(array('store_id' => 'store5', 'require_avs' => true));
		
		$time = strtotime('+2 months');
		$params = array(
			'order_id' => uniqid('testing', true),
			'amount' => '10.30',
			'cc_number' => '4242424242424242',
			'expiry_month' => date('m', $time),
			'expiry_year' => date('y', $time),
			'avs_street_number' => '201',
			'avs_street_name' => 'Michigan Ave',
			'avs_zipcode' => 'M1M1M1',
			'cvd' => '198'
		);
		
		$result = $gateway->purchase($params);
		
		$this->assertIsA($result, 'Moneris_Result');
		$this->assertTrue($result->was_successful());
		$this->assertFalse($result->failed_avs());
		$this->assertFalse($result->failed_cvd());
	}
	
	public function testRefund()
	{
		$gateway = $this->_gateway(array('require_cvd' => false));
		$result = $this->_purchase($gateway);
		$this->assertTrue($result->was_successful());
		
		$transaction = $result->transaction();
		$refund_result = $gateway->refund($transaction);
		
		$this->assertIsA($refund_result, 'Moneris_Result');
		$this->assertTrue($refund_result->was_successful());
	}
	
	public function testVerify()
	{
		$gateway = $this->_gateway(array('store_id' => 'store5', 'require_avs' => true));
		
		$time = strtotime('+2 months');
		$params = array(
			'order_id' => uniqid('testing', true),
			'amount' => '10.30',
			'cc_number' => '4242424242424242',
			'expiry_month' => date('m', $time),
			'expiry_year' => date('y', $time),
			'avs_street_number' => '201',
			'avs_street_name' => 'Michigan Ave',
			'avs_zipcode' => 'M1M1M1',
			'cvd' => '198'
		);
		
		$result = $gateway->verify($params);
		$this->assertIsA($result, 'Moneris_Result');
		$this->assertTrue($result->was_successful());
		$this->assertFalse($result->failed_avs());
		$this->assertFalse($result->failed_cvd());
	}
	
	public function testVoid()
	{
		$gateway = $this->_gateway(array('require_cvd' => false));
		$result = $this->_purchase($gateway);
		
		$this->assertTrue($result->was_successful());
		
		$transaction = $result->transaction();
		$void_result = $gateway->void($transaction);
		
		$this->assertIsA($void_result, 'Moneris_Result');
		$this->assertTrue($void_result->was_successful());
	}
	
	/**
	 * Get a dang gateway!
	 *
	 * @return Moneris_Gateway
	 */
	protected function _gateway($params = array())
	{
		$default_params = array(
			'api_key' => 'yesguy',
			'store_id' => $this->_store,
			'environment' => Moneris::ENV_TESTING
		);
		return Moneris::create(array_merge($default_params, $params));
	}
	
	/**
	 * Make a purchase!
	 *
	 * @return Moneris_Result
	 */
	protected function _preauth($gateway)
	{
		$time = strtotime('+2 months');
		$params = array(
			'cc_number' => '4242424242424242',
			'order_id' => 'test' . date("dmy-G:i:s"),
			'amount' => '12.00',
			'expiry_month' => date('m', $time),
			'expiry_year' => date('y', $time)
		);
		return $gateway->preauth($params);
	}
	
	/**
	 * Make a purchase!
	 *
	 * @return Moneris_Result
	 */
	protected function _purchase($gateway)
	{
		$time = strtotime('+2 months');
		$params = array(
			'cc_number' => '4242424242424242',
			'order_id' => 'test' . date("dmy-G:i:s"),
			'amount' => '20.00',
			'expiry_month' => date('m', $time),
			'expiry_year' => date('y', $time)
		);
		return $gateway->purchase($params);
	}
}
