<?php

declare(strict_types=1);

namespace Kickback\BackendV2\Services\Product;

use Kickback\Backend\Models\Response;
use Kickback\Backend\Views\vRecordId;

interface ProductService
{
    /**
     * Returns product information by its locator
     * 
     * @param string $productLocator the product locator
     * @return Response the returned response which contains the product object in its data if the product was found
     */
    public function getProductByLocator(string $productLocator) : Response;
}

?>