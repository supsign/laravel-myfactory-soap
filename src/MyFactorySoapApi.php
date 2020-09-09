<?php

namespace Supsign\LaravelMfSoap;

use Config;

class MyFactorySoapApi
{
    private
        $wsdl        = null,
        $client      = null,
        $response    = null,
        $authParams  = array(),
        $loginParams = array('trace' => 1);

	public function __construct() {
		$this->wsdl = env('MF_API_WSDL');
		$this->loginParams['login'] = env('MF_API_LOGIN');
		$this->loginParams['password'] = env('MF_API_PASSWORD');

		$this->client = new \SoapClient($this->wsdl, $this->loginParams);

		var_dump($this->client);
	}




}