<?php
	include('config/config.php');
	include('vendor/autoload.php');
	use Kameli\Quickpay\Quickpay;
	
	function getEcwidPayload($app_secret_key, $data) {
		$encryption_key = substr($app_secret_key, 0, 16);
		$json_data = aes_128_decrypt($encryption_key, $data);
		$json_decoded = json_decode($json_data, true);
		return $json_decoded;
	}
	function aes_128_decrypt($key, $data) {
		$base64_original = str_replace(array('-', '_'), array('+', '/'), $data);
		$decoded = base64_decode($base64_original);
		$iv = substr($decoded, 0, 16);
		$payload = substr($decoded, 16);
		$json = openssl_decrypt($payload, "aes-128-cbc", $key, OPENSSL_RAW_DATA, $iv);
		return $json;
	}
	
	$ecwid_payload = $_POST['data'];
	$client_secret = "aTcEZkGlNBupB76aB7Zxs22VUUfxRpor"; 
	$result = getEcwidPayload($client_secret, $ecwid_payload);
	
	$myStoreId = $result['storeId'];
	$myReturnUrl = $result['returnUrl'];
	$orderId = $result['cart']['order']['orderNumber'];
	$orderTotal = $result['cart']['order']['total'];
	$orderCurrency=$result['cart']['currency'];
	$mySecTok = $result['token'];
	$orderRandNumber = $orderId.rand(1000,99999);
	$myTransId = $result['cart']['order']['referenceTransactionId'];
	$cancelUrl  = $result['returnUrl'];
	
	$qForStoreDetails = mysqli_query($con,"SELECT * FROM merchant_details WHERE mer_ecwid_store_id='".$myStoreId."'");
	$rForCountStore=mysqli_num_rows($qForStoreDetails);
	$rForStoreDetails=mysqli_fetch_assoc($qForStoreDetails);
	
	if($rForCountStore > 0){
		$merchantId = $rForStoreDetails['mer_store_id'];
		$merchantApiKey = $rForStoreDetails['mer_api_key'];
		$merchantPrivateKey = $rForStoreDetails['mer_pw_api'];
		$conUrl = $rForStoreDetails['mer_con_url'];
		$canUrl = $rForStoreDetails['mer_can_url'];
		$callUrl = $rForStoreDetails['mer_call_url'];
		$autoCap = $rForStoreDetails['mer_autocapture'];
		
		$qp = new Quickpay($merchantApiKey, $merchantPrivateKey);

		$payment = $qp->payments()->create([
			'currency' => $orderCurrency,
			'order_id' => $orderRandNumber,
			'merchant_id' => $merchantId
		]);
		
		$quickPayPaymentId = $payment->getId();
		$returnUrl  = "https://portfolio.maven-infotech.com/ecwid_apps/quickpayecwid/return_url.php?id=$myTransId&orId=$orderId&sr_id=$myStoreId&my_tok=$mySecTok&orderrandomnumber=$orderRandNumber&qppaymentid=$quickPayPaymentId";
		
		if($autoCap =="2"){
			$link = $qp->payments()->link($payment->getId(),[
				'amount' => ($orderTotal * 100), 
				'continue_url' => $returnUrl,
				'cancel_url' => $cancelUrl,
				'auto_capture' => true
			]);		
		}else{
			$link = $qp->payments()->link($payment->getId(),[
				'amount' => ($orderTotal * 100), 
				'continue_url' => $returnUrl,
				'cancel_url' => $cancelUrl,
				'auto_capture' => false
			]);	
		}

		$url = $link->getUrl();
		header("Location:".$url);
		
		if ($qp->validateCallback()){
			$payment = $qp->receivePaymentCallback();
			$qp->payments()->captureAmount($payment->getId(), $payment->amount());
		}
		exit();
	}else{
		echo "There are some technical issue. Please try again or check your enter details is valid in merchant details";
		exit;
	}


