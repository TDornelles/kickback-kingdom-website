<?php

declare(strict_types=1);

namespace Kickback\BackendV2\Controllers;

use Exception;
use Kickback\Backend\Models\Response;
use Kickback\Backend\Views\vAccount;
use Kickback\BackendV2\Services\StoreService;
use Kickback\BackendV2\Services\DAOStoreService;

class StoreController
{
    private StoreService $storeService;

    public function __construct(?StoreService $storeService = null)
    {
        $this->storeService = $storeService ?? new DAOStoreService();
    }

    /**
     * Gets a store by its matching locator, retuning the store within the data of the response,
     * all of the products and their information, 
     * and the HTTP code of the request
     * 
     * @param ?string $jsonRequest the serialized json request eg:
     *      {
     *          "storeLocator" : "locator"
     *      }
     * 
     * @param ?Response $response the response object passed by reference as an out parameter
     * 
     * @return int the returned HTTP code of the request
     */
    public function getStoreByLocator(?string $jsonRequest, ?Response &$response) : int
    {
        $response = new Response(false, "Unkown error in getting store by locator", null);

        try
        {
            $assocRequest = json_decode($jsonRequest, true);

            if(!$assocRequest->key_exists("storeLocator"))
            {
                $response->message = "Key \"storeLocator\" is missing from request body";
                return 400;
            }

            $storeLocator = $assocRequest["storeLocator"];

            $getStoreByLocatorResp = $this->storeService->getStoreByLocator($storeLocator);

            if(!$getStoreByLocatorResp->success)
            {
                $response->message = "Failed to get store by locator \"$storeLocator\"";
                return 500;
            }

            $response->success = true;
            $response->message = "Successfully retreived store by locator \"$storeLocator\"";
            $response->data = $getStoreByLocatorResp->data;

            return 200;
        }
        catch(Exception $e)
        {
            $response->message = "Exception caught while getting store by locator";
            return 500;
        }
    }

    /**
     * Checks if a store exists by its matching locator, retuning the state of existence within the response's data field
     * and the HTTP code of the request
     * 
     * @param ?string $jsonRequest the serialized json request eg:
     *      {
     *          "storeLocator" : "locator"
     *      }
     * 
     * @param ?Response $response the response object passed by reference as an out parameter
     * 
     * @return int the returned HTTP code of the request
     */
    public function doesStoreExistByLocator(?string $jsonRequest, ?Response &$response) : int
    {
        $response = new Response(false, "Unkown error in checking if store exists by locator", null);

        try
        {
            $assocRequest = json_decode($jsonRequest, true);

            if(!$assocRequest->key_exists("storeLocator"))
            {
                $response->message = "Key \"storeLocator\" is missing from request body";
                return 400;
            }

            $storeLocator = $assocRequest["storeLocator"];

            $doesStoreExistByLocatorResp = $this->storeService->doesStoreExistByLocator($storeLocator);

            if(!$doesStoreExistByLocatorResp->success)
            {
                $response->message = "Failed to check if store exists by locator \"$storeLocator\"";
                return 500;
            }

            $response->success = true;
            $response->message = $doesStoreExistByLocatorResp->data ? "Store exists with locator \"$storeLocator\"" : "Store does not exist with locator \"$storeLocator\"";
            $response->data = $doesStoreExistByLocatorResp->data;

            return 200;
        }
        catch(Exception $e)
        {
            $response->message = "Exception caught while checking if store exists by locator";
            return 500;
        }
    }
}

?>