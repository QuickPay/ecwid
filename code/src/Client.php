<?php

namespace Kameli\Quickpay;

use Kameli\Quickpay\Exceptions\NotFoundException;
use Kameli\Quickpay\Exceptions\QuickpayException;
use Kameli\Quickpay\Exceptions\UnauthorizedException;
use Kameli\Quickpay\Exceptions\ValidationException;

class Client
{
    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var resource
     */
    protected $curl;

    /**
     * @param string $apiKey
     */
    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
        $this->initializeCurl();
    }
	//start changes on 05-Oct-2018
	function getEcwidPayload($app_secret_key, $data) {
		$encryption_key = substr($app_secret_key, 0, 16);
		$json_data = aes_128_decrypt($encryption_key, $data);
		$json_decoded = json_decode($json_data, true);
		return $json_decoded;
	}
	function aes_128_decrypt($key, $data) {
		$base64_original = str_replace(array('-', '_'), array('+', '/'), $data);
		$decoded = base64_decode($base64_original);
		$iv = substr($decoded, 0, 16);
		$payload = substr($decoded, 16);
		$json = openssl_decrypt($payload, "aes-128-cbc", $key, OPENSSL_RAW_DATA, $iv);
		return $json;
	}
	//end changes on 05-Oct-2018
	
    protected function initializeCurl()
    {
        $this->curl = curl_init();

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_HEADER => true,
            CURLOPT_USERPWD => ":{$this->apiKey}",
            CURLOPT_HTTPHEADER => [
                'Accept-Version: v10',
                'Accept: application/json',
            ]
        ];

        curl_setopt_array($this->curl, $options);
    }

    /**
     * Make a request to the Quickpay API
     * @param string $method
     * @param string $path
     * @param array|null $parameters
     * @param bool $raw
     * @return mixed
     * @throws \Kameli\Quickpay\Exceptions\NotFoundException
     * @throws \Kameli\Quickpay\Exceptions\QuickpayException
     * @throws \Kameli\Quickpay\Exceptions\UnauthorizedException
     * @throws \Kameli\Quickpay\Exceptions\ValidationException
     */
    public function request($method, $path, $parameters = [], $raw = false)
    {
        $url = Quickpay::API_URL . ltrim($path,'/');

        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($this->curl, CURLOPT_URL, $url);

        if ($parameters) {
            $files = count(array_filter($parameters, function ($parameter) {
                return $parameter instanceof \CURLFile;
            }));

            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $files ? $parameters : http_build_query($parameters, '', '&'));
        }

        $response = curl_exec($this->curl);

        $statusCode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
        $body = substr($response, - curl_getinfo($this->curl, CURLINFO_CONTENT_LENGTH_DOWNLOAD));
        $json = json_decode($body);

        switch ($statusCode) {
			
			
            case 200:
            case 201:
            case 202:
                if ($raw) {
                    return $body;
                }

                return $json;
            case 400:
                throw new ValidationException($json->message, (array) $json->errors, $json->error_code);
            case 401:
                //throw new UnauthorizedException($json->message);
				//start changes on 05-Oct-2018
				$ecwid_payload = $_POST['data'];
				$client_secret = "68O3l0E7qrI4yLiZWAbHK2FLelCJzNLI";
				$result = getEcwidPayload($client_secret, $ecwid_payload);
				$myReturnUrl = $result['returnUrl'];
				echo "<script>alert('There are some technical issue. Please try again or check your enter details is valid in Quickpay Payment app.')</script>";
				echo "<script> location.replace('$myReturnUrl')</script>";
				//End changes on 05-Oct-2018
				
            case 404:
                if (isset($json->message)) {
                    throw new NotFoundException($json->message);
                } elseif (isset($json->error)) {
                    throw new NotFoundException($json->error);
                }

                throw new NotFoundException(json_encode($json));
        }
        throw new QuickpayException('An invalid response was received from Quickpay', $response, $statusCode);
    }

    /**
     * Make a request to the Quickpay API and get the response as text
     * @param string $method
     * @param string $path
     * @param array|null $parameters
     * @return mixed
     * @throws \Kameli\Quickpay\Exceptions\NotFoundException
     * @throws \Kameli\Quickpay\Exceptions\QuickpayException
     * @throws \Kameli\Quickpay\Exceptions\UnauthorizedException
     * @throws \Kameli\Quickpay\Exceptions\ValidationException
     */
    public function requestRaw($method, $path, $parameters = [])
    {
        return $this->request($method, $path, $parameters, true);
    }
}
