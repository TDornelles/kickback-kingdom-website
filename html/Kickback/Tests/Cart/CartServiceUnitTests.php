<?php

declare(strict_types = 1);

namespace Kickback\Tests\Cart;

use Exception;
use Kickback\Backend\Views\vCart;
use Kickback\Backend\Views\vCartItem;
use Kickback\Backend\Models\RecordId;
use Kickback\BackendV2\Services\Cart\CartService;
use Kickback\BackendV2\Services\Cart\DAOCartService;

use Kickback\Common\Unittesting\AssertException;
use Kickback\Tests\Stubs\Cart\DAO\StubCartDAOException;
use Kickback\Tests\Stubs\Cart\DAO\StubCartDAOFail;
use Kickback\Tests\Stubs\Cart\DAO\StubCartDAOSuccess;
use Kickback\Tests\Stubs\Cart\DAO\StubCartDAOCannotAfford;
use Kickback\Tests\Stubs\Store\DAO\StubStoreDAOSuccess;
use Kickback\Tests\Stubs\Store\DAO\StubStoreDAOFail;
use Kickback\Tests\Stubs\Store\DAO\StubStoreDAOException;
use Kickback\Tests\Tests;

class CartServiceUnitTests implements Tests
{
    private CartService $ServiceSuccessDAOStub;
    private CartService $ServiceFailDAOStub;
    private CartService $ServiceExceptionDAOStub;
    private CartService $ServiceCannotAffordDAOStub;

    public function __construct()
    {
        $this->ServiceSuccessDAOStub = new DAOCartService(new StubCartDAOSuccess(), new StubStoreDAOSuccess());
        $this->ServiceFailDAOStub = new DAOCartService(new StubCartDAOFail(), new StubStoreDAOFail());
        $this->ServiceExceptionDAOStub = new DAOCartService(new StubCartDAOException(), new StubStoreDAOException());
        $this->ServiceCannotAffordDAOStub = new DAOCartService(new StubCartDAOCannotAfford(), new StubStoreDAOSuccess());
    }

    public function runTests() : void
    {
        $this->unittest_getCartForAccountWithStoreId_validCartStub_returnsPopulatedCart();
        $this->unittest_getCartForAccountWithStoreId_nullCartStub_failResponse();
        $this->unittest_getCartForAccountWithStoreId_exception_failResponse();
        $this->unittest_getCartForAccountWithStoreLocator_validCartStub_returnsPopulatedCart();
        $this->unittest_getCartForAccountWithStoreLocator_nullStoreStub_failResponse();
        $this->unittest_getCartForAccountWithStoreLocator_exception_failResponse();
        $this->unittest_addProductToCart_successStub_returnsSuccess();
        $this->unittest_addProductToCart_failStub_returnsFailure();
        $this->unittest_addProductToCart_exceptionStub_returnsFailure();
        $this->unittest_removeProductFromCart_successStub_returnsSuccess();
        $this->unittest_removeProductFromCart_failStub_returnsFailure();
        $this->unittest_removeProductFromCart_exceptionStub_returnsFailure();
        $this->unittest_checkoutCart_successStub_returnsSuccess();
        $this->unittest_checkoutCart_failStub_returnsFailure();
        $this->unittest_checkoutCart_exceptionStub_returnsFailure();
        $this->unittest_checkoutCart_cannotAfford_returnsFailureWithFalseData();
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

    private function unittest_getCartForAccountWithStoreLocator_validCartStub_returnsPopulatedCart() : void
    {
        $accountId = new RecordId();
        $storeLocator = "TEST_STORE";

        $cartResp = $this->ServiceSuccessDAOStub->getCartForAccountWithStoreLocator($accountId, $storeLocator);

        if(!$cartResp->success) throw new Exception("Service with success stub returned failure");
        if(is_null($cartResp->data)) throw new Exception("Service with success stub returned null data");
        if(!($cartResp->data instanceof vCart)) throw new Exception("Service with success stub returned object which is not vCart");
    }

    private function unittest_getCartForAccountWithStoreLocator_nullStoreStub_failResponse() : void
    {
        $accountId = new RecordId();
        $storeLocator = "TEST_STORE";

        $cartResp = $this->ServiceFailDAOStub->getCartForAccountWithStoreLocator($accountId, $storeLocator);

        if($cartResp->success) throw new Exception("Service with fail stub returned success");
        if(!is_null($cartResp->data)) throw new Exception("Service with fail stub returned non-null data");
        if($cartResp->data instanceof vCart) throw new Exception("Service with fail stub returned a vCart when it should have failed");
    }

    private function unittest_getCartForAccountWithStoreLocator_exception_failResponse() : void
    {
        $accountId = new RecordId();
        $storeLocator = "TEST_STORE";

        try
        {
            $cartResp = $this->ServiceExceptionDAOStub->getCartForAccountWithStoreLocator($accountId, $storeLocator);
        }
        catch(\Throwable $e)
        {
            throw new Exception("Service with exception stub threw instead of returning a failure response: " . $e->getMessage(), 0, $e);
        }

        if($cartResp->success) throw new Exception("Service with exception stub returned success");
        if(!is_null($cartResp->data)) throw new Exception("Service with exception stub returned non-null data");
        if($cartResp->data instanceof vCart) throw new Exception("Service with exception stub returned a vCart when it should have failed");
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

    private function unittest_removeProductFromCart_successStub_returnsSuccess() : void
    {
        $cartProduct = new vCartItem('testCtime', -1);

        $resp = $this->ServiceSuccessDAOStub->removeProductFromCart($cartProduct);

        if(!$resp->success) throw new Exception("Service with success stub returned failure");
        if($resp->data !== true) throw new Exception("Service with success stub did not return true data");
    }

    private function unittest_removeProductFromCart_failStub_returnsFailure() : void
    {
        $cartProduct = new vCartItem('testCtime', -1);

        $resp = $this->ServiceFailDAOStub->removeProductFromCart($cartProduct);

        if($resp->success) throw new Exception("Service with fail stub returned success");
        if(!is_null($resp->data)) throw new Exception("Service with fail stub returned non-null data");
    }

    private function unittest_removeProductFromCart_exceptionStub_returnsFailure() : void
    {
        $cartProduct = new vCartItem('testCtime', -1);

        try
        {
            $resp = $this->ServiceExceptionDAOStub->removeProductFromCart($cartProduct);
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

    private function unittest_checkoutCart_successStub_returnsSuccess() : void
    {
        $accountId = new RecordId();
        $storeLocator = "TEST_STORE";

        $resp = $this->ServiceSuccessDAOStub->checkoutCart($accountId, $storeLocator);

        if(!$resp->success) throw new Exception("Service with success stub returned failure");
        if($resp->data !== true) throw new Exception("Service with success stub did not return true data");
    }

    private function unittest_checkoutCart_failStub_returnsFailure() : void
    {
        $accountId = new RecordId();
        $storeLocator = "TEST_STORE";

        $resp = $this->ServiceFailDAOStub->checkoutCart($accountId, $storeLocator);

        if($resp->success) throw new Exception("Service with fail stub returned success");
        if(!is_null($resp->data)) throw new Exception("Service with fail stub returned non-null data");
    }

    private function unittest_checkoutCart_exceptionStub_returnsFailure() : void
    {
        $accountId = new RecordId();
        $storeLocator = "TEST_STORE";

        try
        {
            $resp = $this->ServiceExceptionDAOStub->checkoutCart($accountId, $storeLocator);
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

    private function unittest_checkoutCart_cannotAfford_returnsFailureWithFalseData() : void
    {
        $accountId = new RecordId();
        $storeLocator = "TEST_STORE";

        $resp = $this->ServiceCannotAffordDAOStub->checkoutCart($accountId, $storeLocator);

        if($resp->success) throw new Exception("Service with cannot-afford stub returned success");
        if($resp->data !== false) throw new Exception("Service with cannot-afford stub did not return false data");
    }
}

?>
