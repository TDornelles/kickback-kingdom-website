<?php

declare(strict_types = 1);

namespace Kickback\Tests\Store;

use Exception;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vStore;
use Kickback\BackendV2\Services\Store\DAOStoreService;
use Kickback\BackendV2\Services\Store\StoreService;
use Kickback\Tests\Tests;
use Kickback\Tests\Stubs\Store\DAO\StubStoreDAOExistsFalse;
use Kickback\Tests\Stubs\Store\DAO\StubStoreDAOSuccess;
use Kickback\Tests\Stubs\Store\DAO\StubStoreDAOFail;
use Kickback\Tests\Stubs\Store\DAO\StubStoreDAOException;

class StoreServiceUnitTests implements Tests
{
    private StoreService $ServiceSuccessDAOStub;
    private StoreService $ServiceExistsFalseDAOStub;
    private StoreService $ServiceFailDAOStub;
    private StoreService $ServiceExceptionDAOStub;

    public function __construct()
    {
        $this->ServiceSuccessDAOStub = new DAOStoreService(new StubStoreDAOSuccess());
        $this->ServiceExistsFalseDAOStub = new DAOStoreService(new StubStoreDAOExistsFalse());
        $this->ServiceFailDAOStub = new DAOStoreService(new StubStoreDAOFail());
        $this->ServiceExceptionDAOStub = new DAOStoreService(new StubStoreDAOException());
    }

    public function runTests() : void
    {
        $this->unittest_getStoreByLocator_validStoreStub_returnsPopulatedStore();
        $this->unittest_getStoreByLocator_nullStoreStub_failResponse();
        $this->unittest_getStoreByLocator_exception_failResponse();
        $this->unittest_doesStoreExistById_true_successResponse();
        $this->unittest_doesStoreExistById_false_successResponse();
        $this->unittest_doesStoreExistById_null_failResponse();
        $this->unittest_doesStoreExistById_exception_failResponse();
        $this->unittest_doesStoreExistByLocator_true_successResponse();
        $this->unittest_doesStoreExistByLocator_false_successResponse();
        $this->unittest_doesStoreExistByLocator_null_failResponse();
        $this->unittest_doesStoreExistByLocator_exception_failResponse();
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

    private function unittest_doesStoreExistById_true_successResponse() : void
    {
        $storeId = new vRecordId('storeCtime', 1);

        $resp = $this->ServiceSuccessDAOStub->doesStoreExistById($storeId);

        if(!$resp->success) throw new Exception("Expected success for exists-by-id with success stub");
        if($resp->data !== true) throw new Exception("Expected data=true for exists-by-id with success stub");
    }

    private function unittest_doesStoreExistById_false_successResponse() : void
    {
        $storeId = new vRecordId('storeCtime', 1);

        $resp = $this->ServiceExistsFalseDAOStub->doesStoreExistById($storeId);

        if(!$resp->success) throw new Exception("Expected success for exists-by-id false-path");
        if($resp->data !== false) throw new Exception("Expected data=false for exists-by-id false-path");
    }

    private function unittest_doesStoreExistById_null_failResponse() : void
    {
        $storeId = new vRecordId('storeCtime', 1);

        $resp = $this->ServiceFailDAOStub->doesStoreExistById($storeId);

        if($resp->success) throw new Exception("Expected failure for exists-by-id null-path");
        if(!is_null($resp->data)) throw new Exception("Expected null data for exists-by-id null-path");
    }

    private function unittest_doesStoreExistById_exception_failResponse() : void
    {
        $storeId = new vRecordId('storeCtime', 1);

        try
        {
            $resp = $this->ServiceExceptionDAOStub->doesStoreExistById($storeId);
        }
        catch(\Throwable $e)
        {
            throw new Exception("Service threw instead of returning failure for exists-by-id exception path", 0, $e);
        }

        if($resp->success) throw new Exception("Expected failure for exists-by-id exception-path");
        if(!is_null($resp->data)) throw new Exception("Expected null data for exists-by-id exception-path");
    }

    private function unittest_doesStoreExistByLocator_true_successResponse() : void
    {
        $resp = $this->ServiceSuccessDAOStub->doesStoreExistByLocator("TEST_STORE");

        if(!$resp->success) throw new Exception("Expected success for exists-by-locator with success stub");
        if($resp->data !== true) throw new Exception("Expected data=true for exists-by-locator with success stub");
    }

    private function unittest_doesStoreExistByLocator_false_successResponse() : void
    {
        $resp = $this->ServiceExistsFalseDAOStub->doesStoreExistByLocator("TEST_STORE");

        if(!$resp->success) throw new Exception("Expected success for exists-by-locator false-path");
        if($resp->data !== false) throw new Exception("Expected data=false for exists-by-locator false-path");
    }

    private function unittest_doesStoreExistByLocator_null_failResponse() : void
    {
        $resp = $this->ServiceFailDAOStub->doesStoreExistByLocator("TEST_STORE");

        if($resp->success) throw new Exception("Expected failure for exists-by-locator null-path");
        if(!is_null($resp->data)) throw new Exception("Expected null data for exists-by-locator null-path");
    }

    private function unittest_doesStoreExistByLocator_exception_failResponse() : void
    {
        try
        {
            $resp = $this->ServiceExceptionDAOStub->doesStoreExistByLocator("TEST_STORE");
        }
        catch(\Throwable $e)
        {
            throw new Exception("Service threw instead of returning failure for exists-by-locator exception path", 0, $e);
        }

        if($resp->success) throw new Exception("Expected failure for exists-by-locator exception-path");
        if(!is_null($resp->data)) throw new Exception("Expected null data for exists-by-locator exception-path");
    }
}

?>
