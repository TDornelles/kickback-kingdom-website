<?php

declare(strict_types=1);

namespace Kickback\Tests\Stubs\Product\Services;

use Kickback\Backend\Models\Response;
use Kickback\BackendV2\Services\Product\ProductService;

final class StubProductServiceFail implements ProductService
{
    public function getProductByLocator(string $productLocator) : Response
    {
        return new Response(false, "Stub fail", null);
    }
}

?>
