<?php

namespace Supsign\LaravelMfSoap;

use Config;

class MyFactorySoapApi
{
    protected
        $client = null,
        $request = array(),
        $requiredFields	= array(
        	'ProductUpdate' => ['ProductID', 'ProductNumber', 'InActive', 'ProductType', 'Taxation', 'BaseDecimals', 'SalesQuantity', 'MainSupplier', 'ProductWeight']
        ),
        $response = null;

	public function __construct() {
		$this->request['UserName'] = env('MF_API_LOGIN');
		$this->request['Password'] = env('MF_API_PASSWORD');
		$this->client = new \SoapClient(env('MF_API_WSDL'));

		return $this;
	}

	protected function clearRequestData() {
		foreach ($this->request AS $key => $value) {
			if ($key == 'UserName' OR $key == 'Password') {
				continue;
			}

			unset($this->request[$key]);
		}

		return $this;
	}

	public function createProduct(array $requestData)					//	keys: Product[ProductID, ProductNumber, Name1, Name2, ...]
	{
		if (!isset($requestData['Product']['ProductNumber'])) {
			$requestData['Product']['ProductNumber'] = '*';
		}

		return $this->updateProduct($requestData, true);
	}

	public function getProduct(array $requestData) {					// keys:	ProductID, ProductNumber
		$this->setRequestData($requestData);

		switch (true) {
			case array_key_exists('ProductID', $this->request):
				$this->response = $this->client->GetProductByProductID($this->request);
				return $this->response->GetProductByProductIDResult->Product;

			case array_key_exists('ProductNumber', $this->request):
				$this->response = $this->client->GetProductByProductNumber($this->request);
				return $this->response->GetProductByProductNumberResult->Product;

			default:
				throw new \Exception('missing request parameter', 1);
		}
	}

	public function getProducts(array $requestData) {					// keys:	ChangeDate, Products[ProductID[], ProductNumber[]], GetDocuments
		$this->setRequestData($requestData);

		$this->response = array_key_exists('GetProducts', $this->request)
			? $this->client->GetProductsIndividual($this->request)
			: $this->client->GetProducts($this->request);

		return $this->getResponse()->GetProductsResult->Products->Product;
	}

    public function getResponse() {
        return $this->response;
    }

    protected function setRequestData(array $data)
    {
    	$this
    		->clearRequestData()
    		->request = array_merge($this->request, $data);

    	return $this;
    }

    protected function setUpdateProductDefaultRequestData() {
    	if (!isset($this->request['Product']['ProductID']) AND !isset($this->request['Product']['ProductNumber'])) {
    		throw new \Exception('missing request parameter', 1);
    	}

    	$tmpRequest = $this->request;

		$product = isset($this->request['Product']['ProductID']) 
			? $this->getProduct(['ProductID' => $this->request['Product']['ProductID']])
			: $this->getProduct(['ProductNumber' => $this->request['Product']['ProductNumber']]);

		$this->request = $tmpRequest;

    	foreach ($this->requiredFields['ProductUpdate'] AS $field) {
    		if (!isset($this->request['Product'][$field])) {
    			$this->request['Product'][$field] = $product->{$field};
    		}
    	}

    	return $this;
    }

    public function updateProduct(array $requestData, $put = false)			//	keys: Product[ProductID, ProductNumber, Name1, Name2, ...]
    {    	
		$this->setRequestData($requestData);
		$this->response = $put
			? $this->client->PutProduct($this->request)
			: $this->setUpdateProductDefaultRequestData()->client->UpdateProduct($this->request);

		return $this;
    }

    public function test() {




    	// $this->request['CustomerID'] = 13;
    	// $this->request['ChangeDate'] = '2020-07-01';

		// $this->response = $this->client->GetCustomers($this->request);
		// $this->response = $this->client->GetProducts($this->request);
		// $this->response = $this->client->GetUsers($this->request);

    	return $this;
    }
}