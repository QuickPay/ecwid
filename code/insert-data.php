<?php
	include('config/config.php');
	
	$merchantId = $_POST['merchantId'];
	$agreementID = $_POST['agreementID'];
	$secret_window_key = $_POST['paymentKey'];
	$ecwidPublicKey = $_POST['ecwidPublicKey'];
	$ecwidSecretKey = $_POST['ecwidSecretKey'];
	$storeId = $_POST['storeId'];
	$autoCap = $_POST['autoCap'];
	$isAPI = $_POST['isAPIEnabled'];
	
	$deleteRec = mysqli_query($con,"DELETE FROM merchant_details WHERE mer_ecwid_store_id='".$storeId."'");
	
	$stmt = mysqli_query($con,"INSERT INTO merchant_details(mer_store_id,mer_api_key,mer_pw_api,ecwid_publickey,ecwid_secretkey,mer_ecwid_store_id,mer_autocapture,mer_isApiEnabled) VALUES('".$merchantId."','".$agreementID."','".$secret_window_key."','".$ecwidPublicKey."','".$ecwidSecretKey."','".$storeId."','".$autoCap."','".$isAPI."')");
	if($stmt){
	   $res="Merchant record inserted successfully:";
	   echo json_encode($res);
	}else{
	   $error="Please try after some time.";
	   echo json_encode($error);
	}

?>