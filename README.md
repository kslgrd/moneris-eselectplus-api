Moneris-eSELECTplus-API
===

The PHP API Moneris supplies for eSelectPlus is a terrible mess that throws warnings like they were candy at a parade. This is a simple replacement for people who like PHP5, and hate errors.

**Note:** as of right now, I've only writting support for purchase, verify, pre-auth, capture, and refund operations. I've also included support for AVS/CVD verification.

Get Started
---

The first step is to set up your config, and get your Moneris gateway object:

	$config = array(
		'api_key' => 'yesguy',
		'store_id' => 'store1',
		'environment' => Moneris::ENV_TESTING
	);
	$moneris = Moneris::create($config);
	
There are some handy optional params for the config as well:

	$config = array(
		'api_key' => 'yesguy',
		'store_id' => 'store1',
		'environment' => Moneris::ENV_TESTING,
		// optional:
		'require_avs' => true, // default: false
		'avs_codes' => array('A','B', 'D', 'M', 'P', 'W', 'X', 'Y', 'Z'), // default
		'require_cvd' => true, // default: true
		'cvd_codes' => array('M', 'Y', 'P', 'S', 'U') // default
	);
	
To make a purchase is pretty straight forward:

	// set up $moneris like we did ^^ up there
	$params = array(
		'cc_number' => '4242424242424242',
		'order_id' => 'test' . date("dmy-G:i:s"),
		'amount' => '20.00',
		'expiry_month' => date('m', $time),
		'expiry_year' => date('y', $time)
	);
	$result = $moneris->purchase($params);
	
The result object lets you know how everything went:

	$result->was_successful(); // did the transaction work?
	$result->failed_avs(); // did the transaction pass the AVS check?
	$result->failed_cvd(); // did the transaction pass the CVD check?
	$result->error_message(); // if something went wrong, what was it?

A common workflow might look like this:

	$errors = array();
	$result = $moneris->purchase($params);
	
	if ($result->was_successful()) {
		// HOORAY! Party like it's 1999.
	} else {
		$errors[] = $result->error_message();
	}
	
There's one caveat though! If a transaction fails AVS/CVD, you still have to void it! An easy way to work around that is to verify the card first!

	$errors = array();
	$verification_result = $moneris->verify($params);
	
	if ($verification_result->was_successful() && $verification_result->passed_avs() && $verification_result->passed_cvd()) {
		
		$purchase_result = $moneris->purchase($params);
	
		if ($purchase_result->was_successful()) {
			// HOORAY! Party like it's 1999.
		} else {
			$errors[] = $result->error_message();
		}
		
	} 
	
Or: 

	$errors = array();
	$purchase_result = $moneris->purchase($params);
	
	if ($purchase_result->was_successful() && ( $purchase->failed_avs() || $purchase_result->failed_cvd() )) {
		$errors[] = $purchase_result->error_message();
		$void = $moneris->void($purchase_result->transaction());
	} else if (! $purchase_result->was_successful()) {
		$errors[] = $purchase_result->error_message();
	} else {
		// OMG we're rich!
	}

You can view the transaction details via the transaction object. 

	$result = $moneris->purchase($params);
	$transaction = $result->transaction();
	
You can learn some nift stuff from it, as well as see the XML returned by Moneris.

	$transaction->number(); // receipt->TransID from the Moneris XML response
	$transaction->amount(); // amount processed
	$transaction->response(); // the SimpleXMLElement from the parsed Moneris response.
	
The capture, void, and refund methods can all accept a transaction object as the first param:

	$result = $moneris->purchase($params);
	$moneris->refund($result->transaction()); // refund the full purchase
	$moneris->refund($result->transaction(), null, '5.00'); // refund $5.00
	// OR
	$moneris->refund($result->transaction()->number(), $params['order_id'], $params['amount']); // refund the full purchase
	$moneris->refund($result->transaction()->number(), $params['order_id'], '5.00'); // refund $5.00
	
	$result = $moneris->preauth($params);
	$moneris->capture($result->transaction());
	// OR
	$moneris->capture($result->transaction()->number(), $params['order_id'], $params['amount']);
	
	$result = $moneris->purchase($params);
	$moneris->void($result->transaction());
	// OR
	$moneris->void($result->transaction()->number(), $params['order_id']);
	

Lemme know if you have any questions. @ironkeith on the Twitters.
	
	
	