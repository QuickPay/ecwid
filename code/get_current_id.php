<?php
	include('config/config.php');
	$var1 = $_POST['myCurrId'];
	$getvalue="SELECT mer_ecwid_store_id,mer_store_id,mer_api_key,mer_pw_api,mer_autocapture,mer_isApiEnabled FROM merchant_details WHERE mer_ecwid_store_id='".ltrim($var1)."'";
	$result=mysqli_query($con,$getvalue);
	$rowcount=mysqli_num_rows($result);
	if($rowcount > 0){
	    $getEcwidStoreDetails = mysqli_fetch_assoc($result);
		echo json_encode($getEcwidStoreDetails);
	}else{
		echo '0';
	}
?>