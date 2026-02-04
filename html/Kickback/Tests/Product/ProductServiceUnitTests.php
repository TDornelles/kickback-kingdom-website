<?php

declare(strict_types=1);

namespace Kickback\Tests\Product;

use Exception;
use Kickback\Backend\Views\vProduct;
use Kickback\BackendV2\Services\Cart\DAOProductService;
use Kickback\BackendV2\Services\Cart\ProductService;
use Kickback\Tests\Product\Stubs\DAO\StubProductDAOSuccess;
use Kickback\Tests\Product\Stubs\DAO\StubProductDAOFail;
use Kickback\Tests\Product\Stubs\DAO\StubProductDAOException;
use Kickback\Tests\Tests;

final class ProductServiceUnitTests implements Tests
{
    private ProductService $serviceSuccessDAOStub;
    private ProductService $serviceFailDAOStub;
    private ProductService $serviceExceptionDAOStub;

    public function __construct()
    {
        $this->serviceSuccessDAOStub = new DAOProductService(new StubProductDAOSuccess());
        $this->serviceFailDAOStub = new DAOProductService(new StubProductDAOFail());
        $this->serviceExceptionDAOStub = new DAOProductService(new StubProductDAOException());
    }

    public function runTests() : void
    {
        $this->unittest_getProductByLocator_validProductStub_returnsPopulatedProduct();
        $this->unittest_getProductByLocator_nullProductStub_failResponse();
        $this->unittest_getProductByLocator_exception_failResponse();
    }

    private function unittest_getProductByLocator_validProductStub_returnsPopulatedProduct() : void
    {
        $locator = "TEST_PRODUCT";

        $resp = $this->serviceSuccessDAOStub->getProductByLocator($locator);

        if(!$resp->success) throw new Exception("Service with success stub returned failure");
        if(is_null($resp->data)) throw new Exception("Service with success stub returned null data");
        if(!($resp->data instanceof vProduct)) throw new Exception("Service with success stub returned object which is not vProduct");
    }

    private function unittest_getProductByLocator_nullProductStub_failResponse() : void
    {
        $locator = "TEST_PRODUCT";

        $resp = $this->serviceFailDAOStub->getProductByLocator($locator);

        if($resp->success) throw new Exception("Service with fail stub returned success");
        if(!is_null($resp->data)) throw new Exception("Service with fail stub returned non-null data");
        if($resp->data instanceof vProduct) throw new Exception("Service with fail stub returned a vProduct when it should have failed");

        if(property_exists($resp, 'message') && (is_null($resp->message) || trim((string)$resp->message) === ''))
            throw new Exception("Service with fail stub returned failure but message was empty");
    }

    private function unittest_getProductByLocator_exception_failResponse() : void
    {
        $locator = "TEST_PRODUCT";

        try
        {
            $resp = $this->serviceExceptionDAOStub->getProductByLocator($locator);
        }
        catch (\Throwable $e)
        {
            throw new Exception("Service with exception stub threw instead of returning a failure response: " . $e->getMessage(), 0, $e);
        }

        if($resp->success) throw new Exception("Service with exception stub returned success");
        if(!is_null($resp->data)) throw new Exception("Service with exception stub returned non-null data");
        if($resp->data instanceof vProduct) throw new Exception("Service with exception stub returned a vProduct when it should have failed");

        if(property_exists($resp, 'message') && (is_null($resp->message) || trim((string)$resp->message) === ''))
            throw new Exception("Service with exception stub returned failure but message was empty");
    }
}

?>
