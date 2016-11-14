<?php
require '../lib/Moneris.php';

$errors = array();

if (! empty($_POST)) {
	// use the testing server for the demo:
	$moneris = Moneris::create(
		array(
			'api_key' => 'xxxxxxxxxxxxxxxxxxx', // Under Admin / Store Settings
			'store_id' => 'monca00yyy',
			'environment' => Moneris::ENV_STAGING
		)
	);

	try {

		// try to make the purchase:
		$result = $moneris->purchase($_POST);

		if ($result->was_successful()) {

			// hooray!
			exit("Hot diggity dog!");

		} else {

			exit();

		}

	} catch (Moneris_Exception $e) {
		$errors[] = $e->getMessage();
	}
}
?>
<html>
<head>
	<title> Outer Frame - Merchant Page</title>

	<script>

		function doMonerisSubmit()
		{
			var monFrameRef = document.getElementById('monerisFrame').contentWindow;
			monFrameRef.postMessage('','https://esqa.moneris.com/HPPtoken/index.php');
			return false;
		}

		var respMsg = function(e)
		{
			var respData = eval("(" + e.data + ")");
			document.getElementById("monerisResponse").innerHTML = e.origin + " SENT " + " - " + respData.responseCode + "-" + respData.dataKey + "-" + respData.errorMessage;

			if (respData.dataKey) {
				document.getElementById('data_key').value = respData.dataKey;
				document.getElementById('form').submit();
			}

		}

		window.onload = function()
		{
			if (window.addEventListener)
			{
				window.addEventListener ("message", respMsg, false);
			}
			else
			{
				if (window.attachEvent)
				{
					window.attachEvent("onmessage", respMsg);
				}
			}
		}
	</script>
</head>
<body>

<div id=monerisResponse></div>

<!-- Get token under Admin / Hosted Tokenization (domain must match URL where iFrame will be located) -->
<iframe id=monerisFrame src="https://esqa.moneris.com/HPPtoken/index.php?id=htZZZZZZZZZZZ&css_body=background:white;&css_textbox=border-width:2px;&css_textbox_pan=width:140px;&display_labels=1&enable_exp=1&css_textbox_exp=width:40px;&enable_cvd=1&css_textbox_cvd=width:40px" frameborder='0' width="200" height="120"></iframe>

<form method="post" id="form">
	<p><input name="amount" type="text" placeholder="Amount" required></p>
	<p><input name="order_id" type="text" placeholder="Order ID" required></p>
	<p><input name="data_key" id="data_key" type="text" placeholder="Data key" readonly></p>
</form>

<input type=button onClick=doMonerisSubmit() value="submit iframe">

</body>
</html>