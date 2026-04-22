<?php

declare(strict_types=1);

namespace Kickback\BackendV2\Services\Product;

use Exception;

use Kickback\Backend\Models\Response;
use Kickback\BackendV2\DAO\Product\PDOProductDAO;
use Kickback\BackendV2\DAO\Product\ProductDAO;

class DAOProductService implements ProductService
{
    private ProductDAO $productDAO;

    public function __construct(?ProductDAO $productDAO = null)
    {
        $this->productDAO = is_null($productDAO) ? new PDOProductDAO() : $productDAO;
    }  
    
    public function getProductByLocator(string $productLocator) : Response
    {
        $resp = new Response(false, "unkown error in getting product by locator", null);

        try
        {
            $product = $this->productDAO->getProductByLocator($productLocator);

            if (is_null($product))
            {
                $resp->message = "Failed to get product by locator \"$productLocator\"";
                return $resp;
            }

            $resp->success = true;
            $resp->message = "Successfully retrieved product by locator \"$productLocator\"";
            $resp->data = $product;
        }
        catch(Exception $e)
        {
            $resp->message = "Exception caught while trying to get product by locator \"$productLocator\" : $e";
        }

        return $resp;
    }
}

?>
