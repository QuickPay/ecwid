<?php   
	include('config/config.php');
	include('vendor/autoload.php');
    include('EcwidAPIs.php');
    use Kameli\Quickpay\Quickpay;
    $ecwidAPI = new EcwidAPIs();
	
	$wehookResponse = file_get_contents('php://input');
    $wehookResponseJsonData = json_decode($wehookResponse);
    $wbStoreId = $wehookResponseJsonData->storeId;
    $wbOrderId = $wehookResponseJsonData->entityId; 
    $wbEventType = $wehookResponseJsonData->eventType; 
    $wbEventId = $wehookResponseJsonData->eventId;
    
    function getOrderDetailsEcwid($ecStoreId, $ecOrderId, $ecToken){
        $getOrderDetails ='https://app.ecwid.com/api/v3/'.$ecStoreId.'/orders/'.$ecOrderId.'?token='.$ecToken;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$getOrderDetails);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json;charset=utf-8'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $orderResponse = curl_exec($ch);
        curl_close ($ch);
        return json_decode($orderResponse);
    }
    
    if(isset($wbEventType)){ 
        if($wbEventType == "order.updated"){
                $webhookResponseLog = array("get_records" => $wehookResponseJsonData,"New Order Status"=>$wehookResponseJsonData->data->newFulfillmentStatus,"storeId"=>$wbStoreId,"orderId"=>$wbOrderId);
                $webhookResponseLogJsonArray = json_encode($webhookResponseLog);
                file_put_contents('logs/webhook_resposne_'.date("Y-m-d").'.log', $webhookResponseLogJsonArray, FILE_APPEND);
                
                $qForGetSecretToken = mysqli_query($con,'select storeId,orderId,qpPaymentId,orderTotal,paymentToken from orders WHERE storeId="'.$wbStoreId.'" ORDER BY id desc LIMIT 1');
                $countNumberOfStore = mysqli_num_rows($qForGetSecretToken);
                if($countNumberOfStore > 0){
                    $rForGetSecretToken = mysqli_fetch_assoc($qForGetSecretToken);
                    $ecSecretToken = $rForGetSecretToken['paymentToken'];
                    
                    //GET ORDER INFORMATION FROM ECWID
                    $getEcwidOrderDetails = $ecwidAPI->getOrder($wbStoreId, $wbOrderId, $ecSecretToken, 'GET');
                    $ecwidInternalOrderId = $getEcwidOrderDetails->internalId;
                    $ecwidOrderTotal = $getEcwidOrderDetails->total;
                    
                    if($ecwidInternalOrderId != ""){
                        $qForGetOrderDetails = mysqli_query($con,'select storeId,orderId,qpPaymentId,orderTotal,paymentToken from orders where storeId="'.$wbStoreId.'" AND orderId="'.$ecwidInternalOrderId.'"');
                        $countOrderDetails = mysqli_num_rows($qForGetOrderDetails);
                        if($countOrderDetails  > 0){
                            $newOrderStatus = $wehookResponseJsonData->data->newFulfillmentStatus;
                            
                            //Get QuickPay Order Information
                            $rForGetOrderDetails = mysqli_fetch_assoc($qForGetOrderDetails);
                            $qpPaymentId = $rForGetOrderDetails['qpPaymentId'];
                            //$qpOrderTotal = $rForGetOrderDetails['orderTotal'];
                            $qpOrderTotal = number_format($ecwidOrderTotal,2);
                            $qpOrderTotal = str_replace(",","",$qpOrderTotal);
                            $qpOrderTotal =  ($qpOrderTotal * 100);
                            $ecSecretToken = $rForGetOrderDetails['paymentToken'];
                            
                            //Get Ecwid & Quickpay Information
                            $qForGetQuickpayConfiguration = mysqli_query($con,'select mer_api_key,mer_ecwid_store_id,mer_isApiEnabled from merchant_details where mer_ecwid_store_id="'.$wbStoreId.'"');
                            $rForGetQuickpayConfiguration = mysqli_fetch_assoc($qForGetQuickpayConfiguration);
                            $qpApiKey = $rForGetQuickpayConfiguration['mer_api_key'];
                            $isAPIActive = $rForGetQuickpayConfiguration['mer_isApiEnabled'];
                            
                            if($qpPaymentId != "" && $qpApiKey != "" && ($isAPIActive === 1 || $isAPIActive === "1")){
                                
                                if($newOrderStatus == "SHIPPED"){
                                    //Captured the payment in the quickpay && Its works with authorized payment status only in quickpay if any of the order does not have authorize payment status so its not working.
                                    
                                    $quickpay = new Quickpay($qpApiKey);
                                    try {
                                        $capture = $quickpay->payments()->capture($qpPaymentId, ['amount' => $qpOrderTotal]);
                                        if ($capture) {
                                            $shippedSuccessLog = array("storeId"=>$wbStoreId,"orderId"=>$wbOrderId,"ecwidInternalId"=>$ecwidInternalOrderId,"paymentId"=>$qpPaymentId,"total"=>$qpOrderTotal,"response"=>$capture->operations);
                                            $shippedSuccessLogJson = json_encode($shippedSuccessLog);
                                            file_put_contents('logs/shipped_success_'.date("Y-m-d").'.log', $shippedSuccessLogJson, FILE_APPEND);
                                        } else {
                                            $shippedFailedLog = array("storeId"=>$wbStoreId,"orderId"=>$wbOrderId,"ecwidInternalId"=>$ecwidInternalOrderId,"paymentId"=>$qpPaymentId,"total"=>$qpOrderTotal,"response"=>$capture->getMessage());
                                            $shippedFailedLogJson = json_encode($shippedFailedLog);
                                            file_put_contents('logs/shipped_failed_'.date("Y-m-d").'.log', $shippedFailedLogJson, FILE_APPEND);
                                        }
                                    } catch (Exception $e) {
                                        $shippedErrorLog = array("storeId"=>$wbStoreId,"orderId"=>$wbOrderId,"ecwidInternalId"=>$ecwidInternalOrderId,"paymentId"=>$qpPaymentId,"total"=>$qpOrderTotal,"response"=>$e->getMessage());
                                        $shippedErrorLogJson = json_encode($shippedErrorLog);
                                        file_put_contents('logs/shipped_error_'.date("Y-m-d").'.log', $shippedErrorLogJson, FILE_APPEND);
                                    }
                                }
                                
                                if($newOrderStatus == "WILL_NOT_DELIVER"){
                                    //Cancelled the payment in the quickpay && Its works with only approve order status in the quickpay if any of the order does not have approve order status so its not working.
                                    $quickpay = new Quickpay($qpApiKey);
                                    try {
                                        $canceled = $quickpay->payments()->cancel($qpPaymentId);
                                        if ($canceled) {
                                            $canceledSuccessLog = array("storeId"=>$wbStoreId,"orderId"=>$wbOrderId,"ecwidInternalId"=>$ecwidInternalOrderId,"paymentId"=>$qpPaymentId);
                                            $canceledSuccessLogJson = json_encode($canceledSuccessLog);
                                            file_put_contents('logs/delivery_cancel_success_'.date("Y-m-d").'.log', $canceledSuccessLogJson, FILE_APPEND);
                                        } else {
                                            $canceledFailedLog = array("storeId"=>$wbStoreId,"orderId"=>$wbOrderId,"ecwidInternalId"=>$ecwidInternalOrderId,"paymentId"=>$qpPaymentId,"response"=>$canceled->getMessage());
                                            $canceledFailedLogJson = json_encode($canceledFailedLog);
                                            file_put_contents('logs/delivery_cancel_failed_'.date("Y-m-d").'.log', $canceledFailedLogJson, FILE_APPEND);
                                        }
                                    } catch (Exception $e) {
                                        $canceledErrorLog = array("storeId"=>$wbStoreId,"orderId"=>$wbOrderId,"ecwidInternalId"=>$ecwidInternalOrderId,"paymentId"=>$qpPaymentId,"response"=>$e->getMessage());
                                        $canceledErrorLogJson = json_encode($canceledErrorLog);
                                        file_put_contents('logs/delivery_cancel_error_'.date("Y-m-d").'.log', $canceledErrorLogJson, FILE_APPEND);
                                    }
                                }
                                
                                if($newOrderStatus == "RETURNED"){
                                    //Refund the the payment in the quickpay && Its work with captured order payment status in the quickpay if any of the order does not have captured order payment status so its not working.
                                    $quickpay = new Quickpay($qpApiKey);
                                    try {
                                        $refunded = $quickpay->payments()->refund($qpPaymentId, ['amount' => $qpOrderTotal]);
                                        if ($refunded) {
                                            $refundedSuccessLog = array("storeId"=>$wbStoreId,"orderId"=>$wbOrderId,"ecwidInternalId"=>$ecwidInternalOrderId,"paymentId"=>$qpPaymentId,"total"=>$qpOrderTotal,"response"=>$refunded);
                                            $refundedSuccessLogJson = json_encode($refundedSuccessLog);
                                            file_put_contents('logs/refunded_success_'.date("Y-m-d").'.log', $refundedSuccessLogJson, FILE_APPEND);
                                        } else {
                                            $refundedFailedLog = array("storeId"=>$wbStoreId,"orderId"=>$wbOrderId,"ecwidInternalId"=>$ecwidInternalOrderId,"paymentId"=>$qpPaymentId,"total"=>$qpOrderTotal,"response"=>$refunded->getMessage());
                                            $refundedFailedLogJson = json_encode($refundedFailedLog);
                                            file_put_contents('logs/refunded_cancel_failed_'.date("Y-m-d").'.log', $refundedFailedLogJson, FILE_APPEND);
                                        }
                                    } catch (Exception $e) {
                                        $refundedErrorLog = array("storeId"=>$wbStoreId,"orderId"=>$wbOrderId,"ecwidInternalId"=>$ecwidInternalOrderId,"paymentId"=>$qpPaymentId,"total"=>$qpOrderTotal,"response"=>$e->getMessage());
                                        $refundedErrorLogJson = json_encode($refundedErrorLog);
                                        file_put_contents('logs/refunded_cancel_error_'.date("Y-m-d").'.log', $refundedErrorLogJson, FILE_APPEND);
                                    }
                                    
                                }
                            }
                        }
                    }
                }
        }
    }
	
?>