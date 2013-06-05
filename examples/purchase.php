<?php
require '../lib/Moneris.php';

$errors = array();

if (! empty($_POST)) {
	var_dump($_POST);exit;
	// use the testing server for the demo:
	$moneris = Moneris::create(
		array(
			'api_key' => 'yesguy',
			'store_id' => 'store1',
			'environment' => Moneris::ENV_STAGING
		)
	);
	
	// generate a unique transaction ID:
	$_POST['transaction']['order_id'] = uniqid('ks_api', true);
	
	try {
		
		// try to make the purchase:
		$result = $moneris->purchase($_POST['transaction']);
		
		if ($result->was_successful()) {
		
			// hooray! 
			die("Hot diggity dog!");
		
		} else {
		
			$errors = $result->errors();
	
		}
		
	} catch (Moneris_Exception $e) {
		$errors[] = $e->getMessage();
	}
	
}


?>
<!DOCTYPE HTML>
<html lang="en-US">
<head>
	<meta charset="UTF-8">
	<title>Simple Purchase | Keith Silgard's Moneris API</title>
</head>
<body>
	

	<?php if (! empty($errors)): ?>
		<div style="background: #fcc; border: 1px solid #c00; padding: 10px; margin: 10px 2px;">
			<ul>
			<?php foreach ($errors as $error): ?>
				<li><?= $error; ?></li>
			<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<form action="purchase.php" method="post" accept-charset="utf-8">
		
		<fieldset>
			<legend>Simple Moneris Purchase</legend>
			
			<label for="cc_number">Credit Card Number</label>
			<input type="text" name="transaction[cc_number]" value="4242424242424242" id="cc_number">
			
			<label for="cvd">CVD</label>
			<input type="text" name="transaction[cvd]" value="123" id="cvd">
			
			<label for="amount">Amount</label>
			<input type="text" name="transaction[amount]" value="20.00" id="amount">
			
			<label>Expires</label>
			<select name="transaction[expiry_month]" id="expiry_month">
				<option value="01">01 - January</option>
				<option value="02">02 - February</option>
				<option value="03">03 - March</option>
				<option value="04">04 - April</option>
				<option value="05">05 - May</option>
				<option value="06">06 - June</option>
				<option value="07">07 - July</option>
				<option value="08">08 - August</option>
				<option value="09">09 - September</option>
				<option value="10">10 - October</option>
				<option value="11">11 - November</option>
				<option value="12">12 - December</option>
			</select>
			
			<select name="transaction[expiry_year]" id="expiry_year">
				<?php 
				$year = (int) date('Y');
				for ($i = $year; $i < $year + 20; $i++ ): ?>
					<option value="<?= substr((string) $i, -2); ?>"><?= $i; ?></option>
				<?php endfor; ?>
			</select>
			
			<button type="submit">Complete Purchase</button>
			
		</fieldset>
		
		
		
	</form>
</body>
</html>