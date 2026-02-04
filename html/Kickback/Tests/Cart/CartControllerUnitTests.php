<?php

declare(strict_types=1);

namespace Kickback\Tests\Cart;

use Exception;
use Kickback\Backend\Models\Response;
use Kickback\Backend\Views\vAccount;
use Kickback\BackendV2\Controllers\CartController;
use Kickback\Tests\Tests;
use Kickback\Tests\Cart\Stubs\Services\StubCartServiceSuccess;
use Kickback\Tests\Cart\Stubs\Services\StubCartServiceFail;
use Kickback\Tests\Cart\Stubs\Services\StubCartServiceException;
use Kickback\Tests\Product\Stubs\Services\StubProductServiceSuccess;
use Kickback\Tests\Product\Stubs\Services\StubProductServiceFail;
use Kickback\Tests\Product\Stubs\Services\StubProductServiceException;

final class CartControllerUnitTests implements Tests
{
    private CartController $controllerSuccess;
    private CartController $controllerFail;
    private CartController $controllerException;
    private CartController $controllerProductFail;
    private CartController $controllerProductException;

    public function __construct()
    {
        $this->controllerSuccess   = new CartController(new StubCartServiceSuccess(), new StubProductServiceSuccess());
        $this->controllerFail      = new CartController(new StubCartServiceFail(), new StubProductServiceSuccess());
        $this->controllerException = new CartController(new StubCartServiceException(), new StubProductServiceSuccess());
        $this->controllerProductFail = new CartController(new StubCartServiceSuccess(), new StubProductServiceFail());
        $this->controllerProductException = new CartController(new StubCartServiceSuccess(), new StubProductServiceException());
    }

    public function runTests() : void
    {
        $this->unittest_getCart_storeLocator_success_returns200();
        $this->unittest_getCart_storeLocator_missingKey_returns400();
        $this->unittest_getCart_storeLocator_serviceFail_returns500();
        $this->unittest_getCart_storeLocator_serviceException_returns500();
        $this->unittest_addProduct_success_returns200();
        $this->unittest_addProduct_missingKey_returns400();
        $this->unittest_addProduct_invalidProductId_returns400();
        $this->unittest_addProduct_serviceFail_returns500();
        $this->unittest_addProduct_serviceException_returns500();
        $this->unittest_addProductByLocator_success_returns200();
        $this->unittest_addProductByLocator_missingKey_returns400();
        $this->unittest_addProductByLocator_productServiceFail_returns500();
        $this->unittest_addProductByLocator_productServiceException_returns500();
        $this->unittest_addProductByLocator_cartServiceFail_returns500();
        $this->unittest_addProductByLocator_cartServiceException_returns500();
    }

    private function makeAccount() : vAccount
    {
        return new vAccount('testCtime', -1);
    }

    private function makeProductIdPayload() : array
    {
        return ["ctime" => "productCtime", "crand" => 123];
    }

    private function makeProductLocatorPayload() : array
    {
        return ["productLocator" => "TEST_PRODUCT"];
    }

    private function unittest_getCart_storeLocator_success_returns200() : void
    {
        $account = $this->makeAccount();
        $json = json_encode(["storeLocator" => "TEST_STORE"]);

        $resp = null;
        $code = $this->controllerSuccess->getCartForAccountWithStoreLocator($account, $json, $resp);

        if($code !== 200) throw new Exception("Expected 200, got $code");
        if(is_null($resp)) throw new Exception("Response out-param was null");
        if(!$resp->success) throw new Exception("Expected success=true");
        if(is_null($resp->data)) throw new Exception("Expected data not null");
    }

    private function unittest_getCart_storeLocator_missingKey_returns400() : void
    {
        $account = $this->makeAccount();
        $json = json_encode([]); // missing storeLocator

        $resp = null;
        $code = $this->controllerSuccess->getCartForAccountWithStoreLocator($account, $json, $resp);

        if($code !== 400) throw new Exception("Expected 400, got $code");
        if(is_null($resp)) throw new Exception("Response out-param was null");
        if($resp->success) throw new Exception("Expected success=false");
    }

    private function unittest_getCart_storeLocator_serviceFail_returns500() : void
    {
        $account = $this->makeAccount();
        $json = json_encode(["storeLocator" => "TEST_STORE"]);

        $resp = null;
        $code = $this->controllerFail->getCartForAccountWithStoreLocator($account, $json, $resp);

        if($code !== 500) throw new Exception("Expected 500, got $code");
        if(is_null($resp)) throw new Exception("Response out-param was null");
        if($resp->success) throw new Exception("Expected success=false");
    }

    private function unittest_getCart_storeLocator_serviceException_returns500() : void
    {
        $account = $this->makeAccount();
        $json = json_encode(["storeLocator" => "TEST_STORE"]);

        $resp = null;

        // If controller doesn't catch Throwable, Errors will bubble. Catch here to fail cleanly.
        try {
            $code = $this->controllerException->getCartForAccountWithStoreLocator($account, $json, $resp);
        } catch (\Throwable $t) {
            throw new Exception("Controller threw instead of returning 500: " . $t->getMessage(), 0, $t);
        }

        if($code !== 500) throw new Exception("Expected 500, got $code");
        if(is_null($resp)) throw new Exception("Response out-param was null");
        if($resp->success) throw new Exception("Expected success=false");
    }

    private function unittest_addProduct_success_returns200() : void
    {
        $account = $this->makeAccount();
        $json = json_encode(["productId" => $this->makeProductIdPayload()]);

        $resp = null;
        $code = $this->controllerSuccess->addProductToCart($account, $json, $resp);

        if($code !== 200) throw new Exception("Expected 200, got $code");
        if(is_null($resp)) throw new Exception("Response out-param was null");
        if(!$resp->success) throw new Exception("Expected success=true");
        if($resp->data !== true) throw new Exception("Expected data=true");
    }

    private function unittest_addProduct_missingKey_returns400() : void
    {
        $account = $this->makeAccount();
        $json = json_encode([]);

        $resp = null;
        $code = $this->controllerSuccess->addProductToCart($account, $json, $resp);

        if($code !== 400) throw new Exception("Expected 400, got $code");
        if(is_null($resp)) throw new Exception("Response out-param was null");
        if($resp->success) throw new Exception("Expected success=false");
    }

    private function unittest_addProduct_invalidProductId_returns400() : void
    {
        $account = $this->makeAccount();
        $json = json_encode(["productId" => "invalid"]);

        $resp = null;
        $code = $this->controllerSuccess->addProductToCart($account, $json, $resp);

        if($code !== 400) throw new Exception("Expected 400, got $code");
        if(is_null($resp)) throw new Exception("Response out-param was null");
        if($resp->success) throw new Exception("Expected success=false");
    }

    private function unittest_addProduct_serviceFail_returns500() : void
    {
        $account = $this->makeAccount();
        $json = json_encode(["productId" => $this->makeProductIdPayload()]);

        $resp = null;
        $code = $this->controllerFail->addProductToCart($account, $json, $resp);

        if($code !== 500) throw new Exception("Expected 500, got $code");
        if(is_null($resp)) throw new Exception("Response out-param was null");
        if($resp->success) throw new Exception("Expected success=false");
    }

    private function unittest_addProduct_serviceException_returns500() : void
    {
        $account = $this->makeAccount();
        $json = json_encode(["productId" => $this->makeProductIdPayload()]);

        $resp = null;

        try {
            $code = $this->controllerException->addProductToCart($account, $json, $resp);
        } catch (\Throwable $t) {
            throw new Exception("Controller threw instead of returning 500: " . $t->getMessage(), 0, $t);
        }

        if($code !== 500) throw new Exception("Expected 500, got $code");
        if(is_null($resp)) throw new Exception("Response out-param was null");
        if($resp->success) throw new Exception("Expected success=false");
    }

    private function unittest_addProductByLocator_success_returns200() : void
    {
        $account = $this->makeAccount();
        $json = json_encode($this->makeProductLocatorPayload());

        $resp = null;
        $code = $this->controllerSuccess->addProductToCartByProductLocator($account, $json, $resp);

        if($code !== 200) throw new Exception("Expected 200, got $code");
        if(is_null($resp)) throw new Exception("Response out-param was null");
        if(!$resp->success) throw new Exception("Expected success=true");
        if($resp->data !== true) throw new Exception("Expected data=true");
    }

    private function unittest_addProductByLocator_missingKey_returns400() : void
    {
        $account = $this->makeAccount();
        $json = json_encode([]);

        $resp = null;
        $code = $this->controllerSuccess->addProductToCartByProductLocator($account, $json, $resp);

        if($code !== 400) throw new Exception("Expected 400, got $code");
        if(is_null($resp)) throw new Exception("Response out-param was null");
        if($resp->success) throw new Exception("Expected success=false");
    }

    private function unittest_addProductByLocator_productServiceFail_returns500() : void
    {
        $account = $this->makeAccount();
        $json = json_encode($this->makeProductLocatorPayload());

        $resp = null;
        $code = $this->controllerProductFail->addProductToCartByProductLocator($account, $json, $resp);

        if($code !== 500) throw new Exception("Expected 500, got $code");
        if(is_null($resp)) throw new Exception("Response out-param was null");
        if($resp->success) throw new Exception("Expected success=false");
    }

    private function unittest_addProductByLocator_productServiceException_returns500() : void
    {
        $account = $this->makeAccount();
        $json = json_encode($this->makeProductLocatorPayload());

        $resp = null;

        try {
            $code = $this->controllerProductException->addProductToCartByProductLocator($account, $json, $resp);
        } catch (\Throwable $t) {
            throw new Exception("Controller threw instead of returning 500: " . $t->getMessage(), 0, $t);
        }

        if($code !== 500) throw new Exception("Expected 500, got $code");
        if(is_null($resp)) throw new Exception("Response out-param was null");
        if($resp->success) throw new Exception("Expected success=false");
    }

    private function unittest_addProductByLocator_cartServiceFail_returns500() : void
    {
        $account = $this->makeAccount();
        $json = json_encode($this->makeProductLocatorPayload());

        $resp = null;
        $code = $this->controllerFail->addProductToCartByProductLocator($account, $json, $resp);

        if($code !== 500) throw new Exception("Expected 500, got $code");
        if(is_null($resp)) throw new Exception("Response out-param was null");
        if($resp->success) throw new Exception("Expected success=false");
    }

    private function unittest_addProductByLocator_cartServiceException_returns500() : void
    {
        $account = $this->makeAccount();
        $json = json_encode($this->makeProductLocatorPayload());

        $resp = null;

        try {
            $code = $this->controllerException->addProductToCartByProductLocator($account, $json, $resp);
        } catch (\Throwable $t) {
            throw new Exception("Controller threw instead of returning 500: " . $t->getMessage(), 0, $t);
        }

        if($code !== 500) throw new Exception("Expected 500, got $code");
        if(is_null($resp)) throw new Exception("Response out-param was null");
        if($resp->success) throw new Exception("Expected success=false");
    }
}

?>
