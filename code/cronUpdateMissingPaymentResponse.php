<?php
	include('vendor/autoload.php');
	include('config/config.php');
	include('QuickPayAPIs.php');
	use Kameli\Quickpay\Quickpay;
	$quickpayAPI = new QuickPayAPIs();

	$qForGetOrders = mysqli_query($con, "SELECT storeId, qpPaymentId, orderId, orderEmail, orderNumber,createdAt FROM orders WHERE createdAt >= NOW() - INTERVAL 1 DAY");
	$count = mysqli_num_rows($qForGetOrders);

	if($count > 0){
		while ($order = mysqli_fetch_assoc($qForGetOrders)) {
			$eStoreId = $order['storeId'];
			$qPaymentId = $order['qpPaymentId'];
			$eOrderId = $order['orderId'];
			$eOrderEmail = $order['orderEmail'];
			$eOrderNumber = $order['orderNumber'];
			$qAPIKey = "";
			$qAPIMethod = "GET";

			if($eStoreId != "" && $qPaymentId != ""){
				$qForGetAPIKey = mysqli_query($con, "SELECT mer_api_key,mer_pw_api FROM merchant_details WHERE mer_ecwid_store_id = '".$eStoreId."'");
				$rForGetAPIKey = mysqli_fetch_array($qForGetAPIKey);	
				$qAPIKey = $rForGetAPIKey['mer_api_key'];
				$qPWAPIKey = $rForGetAPIKey['mer_pw_api'];
				$qp = new Quickpay($qAPIKey, $qPWAPIKey);

				$getOrderByPaymentId = $quickpayAPI->getOrder($qPaymentId, $qAPIKey, $qAPIMethod);

				if(empty($getOrderByPaymentId->variables)){
					if(isset($getOrderByPaymentId->message) && !empty($getOrderByPaymentId->message)){
						$log1 = array("ecwid_store_id"=>$eStoreId,"ecwid_order_number"=>$eOrderNumber, "quickpay_payment_id" => $qPaymentId);
        				$logF1 = json_encode($log1);
        				file_put_contents('logs/cron_update_failed_response_'.date("Y-m-d").'.log', $logF1, FILE_APPEND);
					}else{
						if($qPaymentId){
							$variables = array('Ecwid Order Id'=> $eOrderNumber, 'Ecwid Customer Email'=>$eOrderEmail);
							$update = $qp->payments()->update($qPaymentId,[
								'variables'=>$variables
							]);

							$log2 = array("cron_payment_update_status" => $update,"ecwid_store_id"=>$eStoreId,"ecwid_order_number"=>$eOrderNumber, "quickpay_payment_id" => $qPaymentId);
							$logF2 = json_encode($log2);
							file_put_contents('logs/cron_update_success_response_'.date("Y-m-d").'.log', $logF2, FILE_APPEND);
						}
					}
				}
			}
		}
	}
?>