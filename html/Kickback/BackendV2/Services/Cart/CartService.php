<?php

declare(strict_types=1);

namespace Kickback\BackendV2\Services;

use Kickback\Backend\Models\Response;
use Kickback\Backend\Views\vRecordId;

interface CartService
{
    /**
    * Gets a cart for an account
    * Either selects an already existing cart or creates a new one
    * 
    * Additionally gets all items in the cart
    * @param vRecordId $accountId the account id to get the cart for
    * @param vRecordId $storeId the store to get the cart for
    * @return Response $resp the response containg the found vCart object in the data field
    */
    public function GetCartForAccountWithStoreId(vRecordId $accountId, vRecordId $storeId) : Response;

    /**
    * Gets a cart for an account
    * Either selects an already existing cart or creates a new one
    * 
    * Additionally gets all items in the cart
    * @param vRecordId $accountId the account id to get the cart for
    * @param string $storeLocator the locator of the store to get the cart for
    * @return Response $resp the response containg the found vCart object in the data field
    */
    public function GetCartForAccountWithStoreLocator(vRecordId $accountId, string $storeLocator) : Response;
}

?>