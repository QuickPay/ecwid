<?php
	include('config/config.php');
	$var1 = $_POST['myCurrId'];
	$getvalue="SELECT * FROM merchant_details WHERE mer_ecwid_store_id='".ltrim($var1)."'";
	$result=mysqli_query($con,$getvalue);
	$rowcount=mysqli_num_rows($result);
	if($rowcount > 0){
		while($row=mysqli_fetch_array($result)){
			$getRes[] = $row;
		}
		echo json_encode($getRes);
	}else{
		echo '0';
	}
?>