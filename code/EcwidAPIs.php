<?php
	class EcwidAPIs{

		public function getOrder($eStoreId, $eOrderId, $eToken, $eAPIMethod){
			$ecwidCurl = curl_init();
			curl_setopt($ecwidCurl, CURLOPT_URL, "https://app.ecwid.com/api/v3/$eStoreId/orders/$eOrderId");
			curl_setopt($ecwidCurl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ecwidCurl, CURLOPT_CUSTOMREQUEST, $eAPIMethod);
			$headers = array();
			$headers[] = 'Authorization: Bearer '.$eToken;
			$headers[] = 'Accept: application/json';
			curl_setopt($ecwidCurl, CURLOPT_HTTPHEADER, $headers);
			$result = curl_exec($ecwidCurl);
			if (curl_errno($ecwidCurl)) {
			    echo 'Error:' . curl_error($ecwidCurl);
			}
			curl_close($ecwidCurl);
			$jsonOrderResponse = json_decode($result);
			return $jsonOrderResponse;
		}

		public function updateOrder($eStoreId, $eOrderId, $eToken, $eAPIMethod, $eParameters){
			$eParameters = json_encode($eParameters);
			$ecwidCurl = curl_init();
			curl_setopt($ecwidCurl, CURLOPT_URL, "https://app.ecwid.com/api/v3/$eStoreId/orders/$eOrderId");
			curl_setopt($ecwidCurl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ecwidCurl, CURLOPT_CUSTOMREQUEST, $eAPIMethod);
			curl_setopt($ecwidCurl, CURLOPT_POSTFIELDS, $eParameters);
			$ecwidHeaders = array();
			$ecwidHeaders[] = 'Authorization: Bearer '.$eToken;
			$ecwidHeaders[] = 'Accept: application/json';
			$ecwidHeaders[] = 'Content-Type: application/json';
			$ecwidHeaders[] = 'Content-Length: ' . strlen($eParameters);
			curl_setopt($ecwidCurl, CURLOPT_HTTPHEADER, $ecwidHeaders);

			$result = curl_exec($ecwidCurl);
			if (curl_errno($ecwidCurl)) {
				echo 'Error:' . curl_error($ecwidCurl);
			}
			curl_close($ecwidCurl);
			//return true;
		}

	}
?>