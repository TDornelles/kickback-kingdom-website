<?php

declare(strict_types = 1);

namespace Kickback\Tests\Cart;

use Exception;
use Kickback\Backend\Views\vCart;
use Kickback\Backend\Models\RecordId;
use Kickback\BackendV2\Services\CartService;
use Kickback\Common\Unittesting\AssertException;
use Kickback\Tests\Cart\Stubs\StubCartDAOException;
use Kickback\Tests\Cart\Stubs\StubCartDAOFail;
use Kickback\Tests\Cart\Stubs\StubCartDAOSuccess;
use Kickback\Tests\Tests;

class CartServiceUnitTests implements Tests
{
    private CartService $ServiceSuccessDAOStub;
    private CartService $ServiceFailDAOStub;
    private CartService $ServiceExceptionDAOStub;

    public function __construct()
    {
        $this->ServiceSuccessDAOStub = new CartService(new StubCartDAOSuccess());
        $this->ServiceFailDAOStub = new CartService(new StubCartDAOFail());
        $this->ServiceExceptionDAOStub = new CartService(new StubCartDAOException());
    }

    public function runTests() : void
    {
        $this->unittest_getOrCreateCart_validCartStub_returnsPopulatedCart();
        $this->unittest_getOrCreateCart_nullCartStub_failResponse();
        $this->unittest_getOrCreateCart_exception_failResponse();
    }

    private function unittest_getOrCreateCart_validCartStub_returnsPopulatedCart() : void
    {
        $accountId = new RecordId();
        $storeId = new RecordId();

        $cartResp = $this->ServiceSuccessDAOStub->getCartForAccount($accountId, $storeId);

        if(!$cartResp->success) throw new Exception("Service with success stub returned failure");
        if(is_null($cartResp->data)) throw new Exception("Service with success stub returned null data");
        if(!($cartResp->data instanceof vCart)) throw new Exception("Service with success stub returned object which is not vCart");
    }

    private function unittest_getOrCreateCart_nullCartStub_failResponse() : void
    {
        $accountId = new RecordId();
        $storeId = new RecordId();

        $cartResp = $this->ServiceFailDAOStub->getCartForAccount($accountId, $storeId);

        if($cartResp->success) throw new Exception("Service with fail stub returned success");

        if(!is_null($cartResp->data)) throw new Exception("Service with fail stub returned non-null data");
        if($cartResp->data instanceof vCart) throw new Exception("Service with fail stub returned a vCart when it should have failed");

        if(property_exists($cartResp, 'message') && (is_null($cartResp->message) || trim((string)$cartResp->message) === ''))
            throw new Exception("Service with fail stub returned failure but message was empty");
    }

    private function unittest_getOrCreateCart_exception_failResponse() : void
    {
        $accountId = new RecordId();
        $storeId = new RecordId();

        try 
        {
            $cartResp = $this->ServiceExceptionDAOStub->getCartForAccount($accountId, $storeId);
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
}

?>
