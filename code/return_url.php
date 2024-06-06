<?php
	include('config/config.php');
	include('vendor/autoload.php');
	include('EcwidAPIs.php');
	include('QuickPayAPIs.php');
	use Kameli\Quickpay\Quickpay;
	$ecwidAPI = new EcwidAPIs();
	$quickPayAPI = new QuickPayAPIs();
	
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
	
	//Get Quickpay Payment Details using QuickPay APIs
	$getQuickpayPaymentResponse =  $quickPayAPI->getOrder($qpPaymentId, $merchantApiKey, 'GET');
	
	$log1 = array("get_records" => $_GET,"apiResponse"=>$getQuickpayPaymentResponse,"ecwidOrderId"=>$orderId,"qpyamentid"=>$getQuickpayPaymentResponse->id);
    $logF1 = json_encode($log1);
    file_put_contents('logs/order_placed_'.date("Y-m-d").'.log', $logF1, FILE_APPEND);
	
	if(isset($getQuickpayPaymentResponse) && $qpPaymentId == $getQuickpayPaymentResponse->id){
		$myPaymentId = $getQuickpayPaymentResponse->id;
		$myPaymentAccepted = $getQuickpayPaymentResponse->accepted;
		$lastElementFromOperationArray = end($getQuickpayPaymentResponse->operations);
		$lastQpStatusCode = $lastElementFromOperationArray->qp_status_code;
		$lastQpType = $lastElementFromOperationArray->type;
		$lastOrderAmount = $lastElementFromOperationArray->amount;
		
		$qForCheckOrderExists = mysqli_query($con,"SELECT * FROM orders WHERE storeId='".$getStoreId."' AND orderId='".$orderId."'");
		$countOrderExists = mysqli_num_rows($qForCheckOrderExists);
		if($countOrderExists == 0){
			$addNewOrderQuery = mysqli_query($con,"INSERT INTO orders(storeId, transactionId, paymentToken, orderId, orderRandomNumber, qpPaymentId, qpPaymentType, qpPaymentStatus,orderTotal) VALUES ('".$getStoreId."','".$getTranId."','".$token."','".$orderId."','".$orderRandNumber."','".$qpPaymentId."','".$lastQpType."','".$lastQpStatusCode."','".$lastOrderAmount."')");
		}else{
			$updateExistingOrderQuery = mysqli_query($con,"UPDATE orders SET transactionId='".$getTranId."',paymentToken='".$token."',orderRandomNumber='".$orderRandNumber."',qpPaymentId='".$qpPaymentId."',qpPaymentType='".$lastQpType."',qpPaymentStatus='".$lastQpStatusCode."',orderTotal='".$lastOrderAmount."' WHERE storeId='".$getStoreId."' AND orderId='".$orderId."'");
		}
		
		$crPaymentStatus = "AWAITING_PAYMENT";
		if(isset($lastQpStatusCode) && !empty($lastQpStatusCode)){
		    $qpCode = $lastQpStatusCode;
			
			if($lastQpType === "authorize" || $lastQpType == "authorize"){
				if($qpCode === "20000" || $myPaymentAccepted == true || $myPaymentAccepted == "true"){
					$crPaymentStatus = "PAID";
				}
				if($qpCode === "20200"){
					$crPaymentStatus = "AWAITING_PAYMENT";
				}
				if($qpCode === "40000" || $qpCode === "40001" || $qpCode === "40002" || $qpCode === "40003" || $qpCode === "50000" || $qpCode === "50300" || $qpCode === "30101"){
					$crPaymentStatus = "AWAITING_PAYMENT";
				}
			}
			
			if($lastQpType === "capture" || $lastQpType == "capture"){
				if($myPaymentAccepted == true || $myPaymentAccepted == "true"){
					$crPaymentStatus = "PAID";
				}else{
					if($qpCode === "20000"){
						$crPaymentStatus = "PAID";
					}
					if($qpCode === "20200"){
						$crPaymentStatus = "AWAITING_PAYMENT";
					}
					if($qpCode === "40000" || $qpCode === "40001" || $qpCode === "40002" || $qpCode === "40003" || $qpCode === "50000" || $qpCode === "50300" || $qpCode === "30101"){
						$crPaymentStatus = "AWAITING_PAYMENT";
					}
				}
		    }
		}
		
		$log1 = array("apiOrderId"=>$getQuickpayPaymentResponse->order_id,"ecwidOrderId"=>$orderId,"orderStaus"=>$crPaymentStatus,"paymentCode"=>$qpCode,"paymentType"=>$lastQpType);
        $logF1 = json_encode($log1);
        file_put_contents('logs/order_placed_status_'.date("Y-m-d").'.log', $logF1, FILE_APPEND);
		
		$ecwidReturnURL = "https://app.ecwid.com/custompaymentapps/$getStoreId?orderId=$orderId&clientId=quickpay-payments-mi";

		//Update Payment Status & Transcation In On ECWID Order
		$eUpdatePaymentStatus = array('paymentStatus'=>$crPaymentStatus);
		$ecwidAPI->updateOrder($getStoreId, $getTranId, $token, 'PUT', $eUpdatePaymentStatus);
		
	    //Update TransactionID In On ECWID Order
		$eUpdateTranscationId = array('externalTransactionId'=>"$myPaymentId");
		$ecwidAPI->updateOrder($getStoreId, $orderId, $token, 'PUT', $eUpdateTranscationId);
		
		//Get Order Information From ECWID
		$getOrderDetails = $ecwidAPI->getOrder($getStoreId, $orderId, $token, 'GET');
		
		if($myPaymentId){
		        $vari = array('Ecwid Order Id'=> $getOrderDetails->vendorOrderNumber, 'Ecwid Customer Email'=>$getOrderDetails->email);
    		    $update = $qp->payments()->update($myPaymentId,[
    			    'variables'=>$vari
    		    ]);
		}
		header('Location:'.$ecwidReturnURL);
	}else{
		$ecwidReturnURL = "https://app.ecwid.com/custompaymentapps/$getStoreId?orderId=$orderId&clientId=quickpay-payments-mi";

		//Update Payment Status as CANCELLED On ECWID Order
		$eCancelPaymentStatus = array('paymentStatus'=>'CANCELLED');
		$ecwidAPI->updateOrder($getStoreId, $getTranId, $token, 'PUT', $eCancelPaymentStatus);

		header('Location:'.$ecwidReturnURL);
	}
?>