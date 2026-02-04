<?php

declare(strict_types=1);

namespace Kickback\Tests\Product\Stubs\Services;

use Kickback\Backend\Models\Response;
use Kickback\Backend\Views\vProduct;
use Kickback\BackendV2\Services\Cart\ProductService;

final class StubProductServiceSuccess implements ProductService
{
    public function getProductByLocator(string $productLocator) : Response
    {
        $product = new vProduct('testProductCtime', -1);
        $product->locator = $productLocator;

        return new Response(true, "Stub success", $product);
    }
}

?>
