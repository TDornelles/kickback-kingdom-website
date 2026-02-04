<?php

declare(strict_types = 1);

namespace Kickback\Tests\Cart;

use Exception;
use Kickback\Backend\Views\vCart;
use Kickback\Backend\Models\RecordId;
use Kickback\BackendV2\Services\Cart\CartService;
use Kickback\BackendV2\Services\Cart\DAOCartService;

use Kickback\Common\Unittesting\AssertException;
use Kickback\Tests\Cart\Stubs\DAO\StubCartDAOException;
use Kickback\Tests\Cart\Stubs\DAO\StubCartDAOFail;
use Kickback\Tests\Cart\Stubs\DAO\StubCartDAOSuccess;
use Kickback\Tests\Tests;

class CartServiceUnitTests implements Tests
{
    private CartService $ServiceSuccessDAOStub;
    private CartService $ServiceFailDAOStub;
    private CartService $ServiceExceptionDAOStub;

    public function __construct()
    {
        $this->ServiceSuccessDAOStub = new DAOCartService(new StubCartDAOSuccess());
        $this->ServiceFailDAOStub = new DAOCartService(new StubCartDAOFail());
        $this->ServiceExceptionDAOStub = new DAOCartService(new StubCartDAOException());
    }

    public function runTests() : void
    {
        $this->unittest_getCartForAccountWithStoreId_validCartStub_returnsPopulatedCart();
        $this->unittest_getCartForAccountWithStoreId_nullCartStub_failResponse();
        $this->unittest_getCartForAccountWithStoreId_exception_failResponse();
        $this->unittest_addProductToCart_successStub_returnsSuccess();
        $this->unittest_addProductToCart_failStub_returnsFailure();
        $this->unittest_addProductToCart_exceptionStub_returnsFailure();
    }

    private function unittest_getCartForAccountWithStoreId_validCartStub_returnsPopulatedCart() : void
    {
        $accountId = new RecordId();
        $storeId = new RecordId();

        $cartResp = $this->ServiceSuccessDAOStub->getCartForAccountWithStoreId($accountId, $storeId);

        if(!$cartResp->success) throw new Exception("Service with success stub returned failure");
        if(is_null($cartResp->data)) throw new Exception("Service with success stub returned null data");
        if(!($cartResp->data instanceof vCart)) throw new Exception("Service with success stub returned object which is not vCart");
    }

    private function unittest_getCartForAccountWithStoreId_nullCartStub_failResponse() : void
    {
        $accountId = new RecordId();
        $storeId = new RecordId();

        $cartResp = $this->ServiceFailDAOStub->getCartForAccountWithStoreId($accountId, $storeId);

        if($cartResp->success) throw new Exception("Service with fail stub returned success");

        if(!is_null($cartResp->data)) throw new Exception("Service with fail stub returned non-null data");
        if($cartResp->data instanceof vCart) throw new Exception("Service with fail stub returned a vCart when it should have failed");

        if(property_exists($cartResp, 'message') && (is_null($cartResp->message) || trim((string)$cartResp->message) === ''))
            throw new Exception("Service with fail stub returned failure but message was empty");
    }

    private function unittest_getCartForAccountWithStoreId_exception_failResponse() : void
    {
        $accountId = new RecordId();
        $storeId = new RecordId();

        try 
        {
            $cartResp = $this->ServiceExceptionDAOStub->getCartForAccountWithStoreId($accountId, $storeId);
        } catch (\Throwable $e) 
        {
            throw new Exception("Service with exception stub threw instead of returning a failure response: " . $e->getMessage(), 0, $e);
        }

        if($cartResp->success) throw new Exception("Service with exception stub returned success");

        if(!is_null($cartResp->data)) throw new Exception("Service with exception stub returned non-null data");
        if($cartResp->data instanceof vCart) throw new Exception("Service with exception stub returned a vCart when it should have failed");

        if(property_exists($cartResp, 'message') && (is_null($cartResp->message) || trim((string)$cartResp->message) === ''))
            throw new Exception("Service with exception stub returned failure but message was empty");
    }

    private function unittest_addProductToCart_successStub_returnsSuccess() : void
    {
        $cartId = new RecordId();
        $productId = new RecordId();

        $resp = $this->ServiceSuccessDAOStub->addProductToCart($cartId, $productId);

        if(!$resp->success) throw new Exception("Service with success stub returned failure");
        if($resp->data !== true) throw new Exception("Service with success stub did not return true data");
    }

    private function unittest_addProductToCart_failStub_returnsFailure() : void
    {
        $cartId = new RecordId();
        $productId = new RecordId();

        $resp = $this->ServiceFailDAOStub->addProductToCart($cartId, $productId);

        if($resp->success) throw new Exception("Service with fail stub returned success");
        if(!is_null($resp->data)) throw new Exception("Service with fail stub returned non-null data");
    }

    private function unittest_addProductToCart_exceptionStub_returnsFailure() : void
    {
        $cartId = new RecordId();
        $productId = new RecordId();

        try
        {
            $resp = $this->ServiceExceptionDAOStub->addProductToCart($cartId, $productId);
        }
        catch (\Throwable $e)
        {
            throw new Exception("Service with exception stub threw instead of returning a failure response: " . $e->getMessage(), 0, $e);
        }

        if($resp->success) throw new Exception("Service with exception stub returned success");
        if(!is_null($resp->data)) throw new Exception("Service with exception stub returned non-null data");
        if(property_exists($resp, 'message') && (is_null($resp->message) || trim((string)$resp->message) === ''))
            throw new Exception("Service with exception stub returned failure but message was empty");
    }
}

?>
