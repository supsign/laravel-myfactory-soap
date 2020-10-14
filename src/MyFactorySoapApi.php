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

	public function __construct() 
	{
		$this->request['UserName'] = env('MF_SOAP_LOGIN');
		$this->request['Password'] = env('MF_SOAP_PASSWORD');
		$this->client = new \SoapClient(env('MF_SOAP_WSDL'));

		return $this;
	}

	protected function clearRequestData() 
	{
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

	public function getAddresses(array $requestData = []) 				// keys:	AddressID, AddressNumber, Name1, Name2, ....
	{				
		$this
			->setRequestData(['AddressCondition' => $requestData])
			->response = $this->client->GetAddresses($this->request);	//	gibts nicht, lol?

		return $this->response;
	}

	public function getCountries()
	{
		$this->response = $this->client->GetCountries($this->request);

		return $this->response->GetCountriesResult->Countries->Country;
	}

	public function getDiscountLists()
	{
		$this->response = $this->client->GetDiscountLists($this->request);

		return $this->response->GetDiscountListsResult->DiscountLists->DiscountList;
	}

	public function getMainSuppliers() 
	{
		$this->response = $this->client->GetMainSuppliers($this->request);

		return $this->response->GetMainSuppliersResult->Suppliers->Supplier;
	}

	public function getPaymentConditions()
	{
		$this->response = $this->client->GetPaymentConditions($this->request);

		return $this->response->GetPaymentConditionsResult->PaymentConditions->PaymentCondition;
	}

	public function getPriceLists()
	{
		$this->response = $this->client->GetPriceLists($this->request);

		return $this->response->GetPriceListsResult->PriceLists->PriceList;
	}

	public function getProduct(array $requestData) 						// keys:	ProductID, ProductNumber
	{					
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

	public function getProducts(array $requestData) 					// keys:	ChangeDate, Products[ProductID[], ProductNumber[]], GetDocuments
	{					
		$this->setRequestData($requestData);

		$this->response = array_key_exists('GetDocuments', $this->request)
			? $this->client->GetProductsIndividual($this->request)
			: $this->client->GetProducts($this->request);

		return $this->getResponse()->GetProductsResult->Products->Product;
	}

	public function getProductStockInfos(array $requestData)
	{
		$this
			->setRequestData($requestData)
			->response = $this->client->GetProductStockInfos($this->request);

		return $this->response;
	}

	public function getProductSuppliers(array $requestData) 
	{
		$this->setRequestData(['Product' => $requestData]);

    	if (!isset($this->request['Product']['ProductID']) AND !isset($this->request['Product']['ProductNumber'])) {
    		throw new \Exception('missing request parameter', 1);
    	}

    	$this->response = $this->setProductUpdateDefaultRequestData()->client->GetProductSuppliers($this->request);

    	return $this->response;
	}

    public function getResponse() 
    {
        return $this->response;
    }

    public function getSalesOrders(array $requestData) 
    {
		$this
			->setRequestData($requestData)
			->response = $this->client->GetSalesOrders($this->request);

		return $this->response->GetSalesOrdersResult->Orders->Order;
    }

	public function getShippingConditions()
	{
		$this->response = $this->client->GetShippingConditions($this->request);

		return $this->response->GetShippingConditionsResult->ShipmentConditions->ShipmentCondition;
	}

	public function getSuppliers() 
	{
		$this->response = $this->client->GetSuppliers($this->request);

		return $this->response->GetSuppliersResult->Suppliers->Supplier;
	}

	public function getTaxations()
	{
		$this->response = $this->client->GetTaxations($this->request);

		return $this->response->GetTaxationsResult->Taxations->Taxation;
	}

    protected function setRequestData(array $data)
    {
    	$this
    		->clearRequestData()
    		->request = array_merge($this->request, $data);

    	return $this;
    }

    protected function setProductUpdateDefaultRequestData() 
    {
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
		$this
			->setRequestData(['Product' => $requestData])
			->response = $put
				? $this->client->PutProduct($this->request)
				: $this->setProductUpdateDefaultRequestData()->client->UpdateProduct($this->request);

		return $this;
    }
}