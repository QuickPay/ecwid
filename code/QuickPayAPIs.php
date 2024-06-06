<?php
	class QuickPayAPIs{

		public function getOrder($qpPaymentId, $apiKey, $apiMethod){
			$qpCurl = curl_init();
			curl_setopt($qpCurl, CURLOPT_URL, "https://api.quickpay.net/payments/$qpPaymentId");
			curl_setopt($qpCurl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($qpCurl, CURLOPT_CUSTOMREQUEST, $apiMethod);
			curl_setopt($qpCurl, CURLOPT_USERPWD, '' . ':' . $apiKey);
			$headers = array();
			$headers[] = 'Content-Type: application/json';
			$headers[] = 'Accept-Version: v10';
			curl_setopt($qpCurl, CURLOPT_HTTPHEADER, $headers);
			$result = curl_exec($qpCurl);
			if (curl_errno($qpCurl)) {
				echo 'Error:' . curl_error($qpCurl);
			}
			curl_close($qpCurl);
			$resultInJsonFormat = json_decode($result);
			return $resultInJsonFormat;
		}
		
	}
?>