<?php

namespace Supsign\LaravelMfSoap;

use Config;

class MyFactorySoapApi
{
	public $client = null;

    protected
        // $client = null,
        $cache = array(),
        $error = array('missing_parameter' => 'missing request parameter'),
        $request = array(),
        $requiredFields	= array(
        	'ProductUpdate' => ['ProductID', 'ProductNumber', 'InActive', 'ProductType', 'Taxation', 'BaseDecimals', 'SalesQuantity', 'MainSupplier', 'ProductWeight']
        ),
        $response = null;

	public function __construct() 
	{
		$this->request['UserName'] = env('MF_SOAP_LOGIN');
		$this->request['Password'] = env('MF_SOAP_PASSWORD');
		$this->client = new \SoapClient(
			env('MF_SOAP_WSDL'),
			[
			    'trace' => true, 
			    'keep_alive' => false,
			    'connection_timeout' => 5000,
			    'cache_wsdl' => WSDL_CACHE_NONE,
			    'compression'   => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | SOAP_COMPRESSION_DEFLATE,
			]
		);

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

	public function getCategories()
	{
		$this->response = $this->client->GetProductGroups($this->request);

		return $this->response->GetProductGroupsResult->ProductGroups->ProductGroup;
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

				if (!isset($this->response->GetProductByProductIDResult->Product)) {
					var_dump($requestData, $this->response);
					die();
				}

				return $this->response->GetProductByProductIDResult->Product;

			case array_key_exists('ProductNumber', $this->request):
				$this->response = $this->client->GetProductByProductNumber($this->request);
				return $this->response->GetProductByProductNumberResult->Product;

			default:
				throw new \Exception($this->error['missing_parameter'], 1);
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

	public function getProductSupplierInformation(array $requestData)
	{
		if (!isset($requestData['SupplierID'])) {
			throw new \Exception('missing request parameter', 1);
		}

		if (isset($this->cache['productSupplierInformation'][$requestData['SupplierID']])) {
			$result = $this->cache['productSupplierInformation'][$requestData['SupplierID']];
		}


		if (isset($requestData['ProductID'])) {
			$productId = $requestData['ProductID'];
			unset($requestData['ProductID']);
		}

		if (!isset($result)) {
			$result = $this->cache['productSupplierInformation'][$requestData['SupplierID']] = $this
				->setRequestData($requestData)
				->client
					->GetProductSupplierInformations($this->request)->GetProductSupplierInformationsResult;
		}

		if (!isset($result->ProductSupplierInformations) || (is_object($result->ProductSupplierInformations) && !isset($result->ProductSupplierInformations->ProductSupplierInformation))) {
			return [];
		}

		if (!is_array($result->ProductSupplierInformations->ProductSupplierInformation)) {
			return [$result->ProductSupplierInformations->ProductSupplierInformation];
		}

		if (isset($productId)) {
			$subResult = [];

			foreach ($result->ProductSupplierInformations->ProductSupplierInformation AS $entry) {
				if ($productId == $entry->ProductID) {
					return $entry;
				}
			}

			return $subResult;
		}


		return $result->ProductSupplierInformations->ProductSupplierInformation;
	}

	public function getProductSuppliers(array $requestData) 
	{
		$this->setRequestData(['Product' => $requestData]);

    	if (!isset($this->request['Product']['ProductID']) AND !isset($this->request['Product']['ProductNumber'])) {
    		throw new \Exception($this->error['missing_parameter'], 1);
    	}

    	$this->response = $this
    		->setProductUpdateDefaultRequestData()
    		->client
    			->GetProductSuppliers($this->request);

    	return $this->response;
	}

    public function getResponse() 
    {
        return $this->response;
    }

    public function getSalesOrder(array $requestData) 
    {
    	if (!isset($requestData['OrderID'])) {
    		throw new \Exception($this->error['missing_parameter'], 1);
    	}

		$this
			->setRequestData($requestData)
			->response = $this->client->GetSalesOrder($this->request);

		return $this->response->GetSalesOrderResult->Order;
    }

    public function getSalesOrders(array $requestData) 
    {
    	if (!isset($requestData['OrderDate'])) {
    		throw new \Exception($this->error['missing_parameter'], 1);
    	}

		$this
			->setRequestData($requestData)
			->response = $this->client->GetSalesOrders($this->request);

		return $this->response->GetSalesOrdersResult->Orders->Order;
    }

    public function getSalesOrderPosition(array $requestData) 
    {
    	$salesOrder = $this->getSalesOrder(['OrderID' => $requestData['OrderID']]);

    	foreach ($salesOrder->OrderPositions AS $orderPosition) {
    		if ($orderPosition->OrderPosID == $requestData['OrderPositionID']) {
    			return $orderPosition;
    		}
    	}
 
 		throw new \Exception('position not found', 1);
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

	public function getTaxFactorByKey($key) {
		return $this->getTaxRateByKey($key) / 100 + 1;
	}

	public function getTaxRateByKey($key)
	{
		foreach ($this->getTaxations() AS $taxation) {
			if ($taxation->TaxKey == $key) {
				return $taxation->TaxRate;
			}
		}

		throw new \Exception('taxRate not found', 1);
	}

	public function getTaxations()
	{
		if (isset($this->cache['taxation'])) {
			return $this->cache['taxation'];
		}

		$this->response = $this->client->GetTaxations($this->request);

		return $this->cache['taxation'] = $this->response->GetTaxationsResult->Taxations->Taxation;
	}

	protected function addRequestData(array $data)
	{
    	$this->request = array_merge($this->request, $data);

    	return $this;
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
    		throw new \Exception($this->error['missing_parameter'], 1);
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
    	$this->setRequestData(['Product' => $requestData]);

    	if (isset($requestData['ProductDescs'])) {
    		$this->addRequestData(['ProductDescs' => array($requestData['ProductDescs'])]);
    	}

    	if (isset($requestData['Attributes'])) {
    		$this->addRequestData(['Attributes' => array($requestData['Attributes'])]);
    	}

		$this->response = $put
			? $this->client->PutProduct($this->request)
			: $this->setProductUpdateDefaultRequestData()->client->UpdateProduct($this->request);

		return $this;
    }

    public function updateProductSupplierInformation(array $requestData)
    {
    	$this
    		->setRequestData(['ProductSupplierInformation' => $requestData])
			->response = $this->client->PutProductSupplierInformation($this->request);

		return $this;
    }
}