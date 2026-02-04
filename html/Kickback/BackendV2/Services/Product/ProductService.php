<?php

declare(strict_types=1);

namespace Kickback\BackendV2\Services\Cart;

use Kickback\Backend\Models\Response;
use Kickback\Backend\Views\vRecordId;

interface ProductService
{
    public function getProductByLocator(string $productLocator) : Response;
}

?>