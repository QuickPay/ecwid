<?php
	include('config/config.php');
	
	$merchantId = $_POST['merchantId'];
	$agreementID = $_POST['agreementID'];
	$secret_window_key = $_POST['paymentKey'];
	$cont_url = $_POST['continueUrl'];
	$can_url = $_POST['cancleUrl'];
	$call_url = $_POST['callbackUrl'];
	$storeId = $_POST['storeId'];
	$autoCap = $_POST['autoCap'];
	
	$deleteRec = mysqli_query($con,"DELETE FROM merchant_details WHERE mer_ecwid_store_id='".$storeId."'");
	
	$stmt = mysqli_query($con,"INSERT INTO merchant_details(mer_store_id,mer_api_key,mer_pw_api,mer_con_url,mer_can_url,mer_call_url,mer_ecwid_store_id,mer_autocapture) VALUES('".$merchantId."','".$agreementID."','".$secret_window_key."','".$cont_url."','".$can_url."','".$call_url."','".$storeId."','".$autoCap."')");
	if($stmt){
	   $res="Merchant record inserted successfully:";
	   echo json_encode($res);
	}else{
	   $error="Please try after some time.";
	   echo json_encode($error);
	}

?>