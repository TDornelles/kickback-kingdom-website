<?php

declare(strict_types = 1);

namespace Kickback\BackendV2\Services;

use Exception;
use Kickback\Backend\Models\Response;
use Kickback\Backend\Views\vRecordId;
use Kickback\BackendV2\DAO\PDOStoreDAO;
use Kickback\BackendV2\DAO\StoreDAO;

class DAOStoreService implements StoreService
{
    private StoreDAO $dao;

    public function __construct(?StoreDAO $storeDAO = null)
    {
        $this->dao = $storeDAO ?? new PDOStoreDAO();
    }

    public function getStoreByLocator(string $locator): Response
    {
        $resp = new Response(false, "Unkown error in getting store by locator \"$locator\"", null);

        try
        {
            $store = $this->dao->getStoreByLocator($locator);

            if(is_null($store))
            {
                $resp->message = "Failed to get store by locator \"$locator\"";
                return $resp;
            }

            $resp->success = true;
            $resp->message = "Successfully Retrieved Store by locator \"$locator\"";
            $resp->data = $store;        
        }
        catch(Exception $e)
        {
            $resp->message = "Exception caught while trying to get store by locator \"$locator\" : $e";
        }

        return $resp;
    }

    public function doesStoreExistById(vRecordId $storeId) : Response
    {
        $resp = new Response(false, "Unkown error in verifying store's existence by id");

        try
        {
            $storeExists = $this->dao->doesStoreExistById($storeId);

            if(is_null($storeExists))
            {
                $resp->message = "Failed to verify store's existence by id";
                return $resp;
            }

            $resp->success = true;
            $resp->message = $storeExists ? "Store exists" : "Store does not exist";
            $resp->data = $storeExists;
        }
        catch(Exception $e)
        {
            $resp->message = "Exception caugh while trying to verify store's existence by id : $e";
        }

        return $resp;
    }

    public function doesStoreExistByLocator(string $storeLocator) : Response
    {
        $resp = new Response(false, "Unkown error in verifying store's existence by locator");

        try
        {
            $storeExists = $this->dao->doesStoreExistByLocator($storeLocator);

            if(is_null($storeExists))
            {
                $resp->message = "Failed to verify store's existence by locator";
                return $resp;
            }

            $resp->success = true;
            $resp->message = $storeExists ? "Store exists" : "Store does not exist";
            $resp->data = $storeExists;
        }
        catch(Exception $e)
        {
            $resp->message = "Exception caugh while trying to verify store's existence by locator : $e";
        }

        return $resp;
    } 
}

?>