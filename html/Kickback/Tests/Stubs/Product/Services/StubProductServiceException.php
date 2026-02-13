<?php

declare(strict_types=1);

namespace Kickback\Tests\Stubs\Product\Services;

use Exception;
use Kickback\Backend\Models\Response;
use Kickback\BackendV2\Services\Product\ProductService;

final class StubProductServiceException implements ProductService
{
    public function getProductByLocator(string $productLocator) : Response
    {
        throw new Exception("Stub Exception: getProductByLocator");
    }
}

?>
