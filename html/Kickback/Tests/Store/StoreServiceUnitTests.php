<?php

declare(strict_types = 1);

namespace Kickback\Tests\Store;

use Exception;
use Kickback\Backend\Views\vStore;
use Kickback\BackendV2\Services\StoreService;
use Kickback\Tests\Tests;
use Kickback\Tests\Store\Stubs\DAO\StubStoreDAOSuccess;
use Kickback\Tests\Store\Stubs\DAO\StubStoreDAOFail;
use Kickback\Tests\Store\Stubs\DAO\StubStoreDAOException;

class StoreServiceUnitTests implements Tests
{
    private StoreService $ServiceSuccessDAOStub;
    private StoreService $ServiceFailDAOStub;
    private StoreService $ServiceExceptionDAOStub;

    public function __construct()
    {
        $this->ServiceSuccessDAOStub = new StoreService(new StubStoreDAOSuccess());
        $this->ServiceFailDAOStub = new StoreService(new StubStoreDAOFail());
        $this->ServiceExceptionDAOStub = new StoreService(new StubStoreDAOException());
    }

    public function runTests() : void
    {
        $this->unittest_getStoreByLocator_validStoreStub_returnsPopulatedStore();
        $this->unittest_getStoreByLocator_nullStoreStub_failResponse();
        $this->unittest_getStoreByLocator_exception_failResponse();
    }

    private function unittest_getStoreByLocator_validStoreStub_returnsPopulatedStore() : void
    {
        $locator = "TEST_STORE";

        $resp = $this->ServiceSuccessDAOStub->getStoreByLocator($locator);

        if(!$resp->success)
            throw new Exception("Service with success stub returned failure");

        if(is_null($resp->data))
            throw new Exception("Service with success stub returned null data");

        if(!($resp->data instanceof vStore))
            throw new Exception("Service with success stub returned object which is not vStore");
    }

    private function unittest_getStoreByLocator_nullStoreStub_failResponse() : void
    {
        $locator = "TEST_STORE";

        $resp = $this->ServiceFailDAOStub->getStoreByLocator($locator);

        if($resp->success)
            throw new Exception("Service with fail stub returned success");

        if(!is_null($resp->data))
            throw new Exception("Service with fail stub returned non-null data");

        if($resp->data instanceof vStore)
            throw new Exception("Service with fail stub returned a vStore when it should have failed");

        if(property_exists($resp, 'message') &&
            (is_null($resp->message) || trim((string)$resp->message) === ''))
        {
            throw new Exception("Service with fail stub returned failure but message was empty");
        }
    }

    private function unittest_getStoreByLocator_exception_failResponse() : void
    {
        $locator = "TEST_STORE";

        try 
        {
            $resp = $this->ServiceExceptionDAOStub->getStoreByLocator($locator);
        } 
        catch (\Throwable $e) 
        {
            throw new Exception(
                "Service with exception stub threw instead of returning a failure response: " . $e->getMessage(),
                0,
                $e
            );
        }

        if($resp->success)
            throw new Exception("Service with exception stub returned success");

        if(!is_null($resp->data))
            throw new Exception("Service with exception stub returned non-null data");

        if($resp->data instanceof vStore)
            throw new Exception("Service with exception stub returned a vStore when it should have failed");

        if(property_exists($resp, 'message') &&
            (is_null($resp->message) || trim((string)$resp->message) === ''))
        {
            throw new Exception("Service with exception stub returned failure but message was empty");
        }
    }
}

?>
