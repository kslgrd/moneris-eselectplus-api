<?php
if (! defined('ROOT')) {
	define('ROOT', dirname(dirname(__FILE__)));
}
require_once ROOT . '/simpletest/autorun.php';
require_once ROOT . '/lib/Moneris.php';

/**
 *
 */
class TestVbvMcsc extends UnitTestCase
{

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

	public function testTxn()
	{
		$gateway = $this->_gateway();

		$xid = sprintf("%'920d", rand()); // this HAS TO BE EXACTLY 20 chars in length, or it won't work!
		$pan = '4242424242424242';
		$purchase_amount = '1.01';
		$expiry = date('ym', strtotime('+6 months'));

		$params = array(
			'order_id' => $xid,
			'cc_number' => $pan,
			'amount' => $purchase_amount,
			'expdate' => $expiry,
			'merchantUrl' => 'https://esqa.moneris.com/mpistore/mpistore.php',

			'accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
			'userAgent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.78.2 (KHTML, like Gecko) Version/7.0.6 Safari/537.78.2',

			'MD' => http_build_query(
				array(
					"xid" => $xid, //MD is merchant data that can be passed along
					"pan" => $pan,
					"expiry" => $expiry,
					"amount" => $purchase_amount
				),
				'',
				'&amp;'
			)

		);

		$result = $gateway->txn($params);
		$this->assertIsA($result, 'Moneris_3DSecureResult');
		$this->assertTrue($result->is_enrolled());
	}

	/**
	 * Get a dang gateway!
	 *
	 * @return Moneris_Gateway
	 */
	protected function _gateway($params = array())
	{
		$default_params = array(
			'api_key' => 'hurgle',
			'store_id' => 'moneris',
			'environment' => Moneris::ENV_TESTING
		);
		return Moneris::create(array_merge($default_params, $params));
	}
}
