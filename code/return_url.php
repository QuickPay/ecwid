<?php
	include('config/config.php');
	include('vendor/autoload.php');
	use Kameli\Quickpay\Quickpay;
	
	$getTranId = $_GET['id'];
	$getStoreId = $_GET['sr_id'];
	$token = $_GET['my_tok'];
	$orderId = $_GET['orId'];
	$orderRandNumber = $_GET['orderrandomnumber'];
	$qpPaymentId = $_GET['qppaymentid'];
	
	$qForStoreDetails = mysqli_query($con,"SELECT mer_api_key,mer_pw_api FROM merchant_details WHERE mer_ecwid_store_id='".$getStoreId."'");
	$rForCountStore=mysqli_num_rows($qForStoreDetails);
	$rForStoreDetails=mysqli_fetch_array($qForStoreDetails);
	$merchantApiKey = $rForStoreDetails['mer_api_key'];
	$merchantPrivateKey = $rForStoreDetails['mer_pw_api'];
	$qp = new Quickpay($merchantApiKey, $merchantPrivateKey);
	
	$ch1 = curl_init();
	curl_setopt($ch1, CURLOPT_URL, "https://api.quickpay.net/payments/$qpPaymentId");
	curl_setopt($ch1, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch1, CURLOPT_CUSTOMREQUEST, 'GET');
	curl_setopt($ch1, CURLOPT_USERPWD, '' . ':' . $merchantApiKey);
	$headers = array();
	$headers[] = 'Content-Type: application/json';
	$headers[] = 'Accept-Version: v10';
	curl_setopt($ch1, CURLOPT_HTTPHEADER, $headers);
	$resultPayment = curl_exec($ch1);
	if (curl_errno($ch1)) {
		echo 'Error:' . curl_error($ch1);
	}
	curl_close($ch1);
	$myPaymentResult = json_decode($resultPayment);
	
	$log1 = array("get_records" => $_GET,"apiResponse"=>$myPaymentResult,"ecwidOrderId"=>$orderId,"qpyamentid"=>$myPaymentResult->id);
    $logF1 = json_encode($log1);
    file_put_contents('logs/order_placed_'.date("Y-m-d").'.log', $logF1, FILE_APPEND);
	
	if(isset($myPaymentResult) && $qpPaymentId == $myPaymentResult->id){
		$myPaymentId = $myPaymentResult->id;
		$myPaymentAccepted = $myPaymentResult->accepted;
		$lastElementFromOperationArray = end($myPaymentResult->operations);
		$lastQpStatusCode = $lastElementFromOperationArray->qp_status_code;
		
		$crPaymentStatus = "AWAITING_PAYMENT";
		if(isset($lastQpStatusCode) && !empty($lastQpStatusCode)){
		    $qpCode = $lastQpStatusCode;
		    
		    if($qpCode === "20000" || $myPaymentAccepted == true || $myPaymentAccepted == "true"){
		        $crPaymentStatus = "PAID";
		    }
		    
		    if($qpCode === "20200"){
		        $crPaymentStatus = "AWAITING_PAYMENT";
		    }
		    
		    if($qpCode === "40000" || $qpCode === "40001" || $qpCode === "40002" || $qpCode === "40003" || $qpCode === "50000" || $qpCode === "50300" || $qpCode === "30101"){
		        $crPaymentStatus = "CANCELLED";
		    }
			
		}
		
		$log1 = array("apiOrderId"=>$myPaymentResult->order_id,"ecwidOrderId"=>$orderId,"orderStaus"=>$crPaymentStatus,"paymentCode"=>$qpCode);
        $logF1 = json_encode($log1);
        file_put_contents('logs/order_placed_status_'.date("Y-m-d").'.log', $logF1, FILE_APPEND);
		
		$retUrl = "https://app.ecwid.com/custompaymentapps/$getStoreId?orderId=$orderId&clientId=quickpay-payments-mi";
		$url = "https://app.ecwid.com/api/v3/$getStoreId/orders/$getTranId?token=$token";
		$data = array('paymentStatus'=>$crPaymentStatus);
		$data_json = json_encode($data);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($data_json)));
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$responsePayment = curl_exec($ch);
		curl_close($ch);
		
		$orderUrl = "https://app.ecwid.com/api/v3/$getStoreId/orders/$orderId?token=$token";
		$dataOrder = array('externalTransactionId'=>"$myPaymentId");
		$dataJson = json_encode($dataOrder);
		$ch1 = curl_init();
		curl_setopt($ch1, CURLOPT_URL, $orderUrl);
		curl_setopt($ch1, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($dataJson)));
		curl_setopt($ch1, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($ch1, CURLOPT_POSTFIELDS, $dataJson);
		curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
		$response  = curl_exec($ch1);
		curl_close($ch1);
	
		$qOrderDetails = file_get_contents("https://app.ecwid.com/api/v3/$getStoreId/orders/$orderId?token=$token");
		$myOrderReq = json_decode($qOrderDetails);
		
		if($myPaymentId){
		        $vari = array('Ecwid Order Id'=> $myOrderReq->vendorOrderNumber, 'Ecwid Customer Email'=>$myOrderReq->email);
    		    $update = $qp->payments()->update($myPaymentId,[
    			    'variables'=>$vari
    		    ]);
		}
		
		header('Location:'.$retUrl); 
		
	}else{
		
		$retUrl = "https://app.ecwid.com/custompaymentapps/$getStoreId?orderId=$orderId&clientId=quickpay-payments-mi";
		$url = "https://app.ecwid.com/api/v3/$getStoreId/orders/$getTranId?token=$token";
		$data = array('paymentStatus'=>'CANCELLED');
		$data_json = json_encode($data);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($data_json)));
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response  = curl_exec($ch);
		curl_close($ch);
		header('Location:'.$retUrl);  
		
	}
	

?>