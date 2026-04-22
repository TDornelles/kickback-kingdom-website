<?php

declare(strict_types = 1);

namespace Kickback\BackendV2\DAO\Coupon;

use Kickback\Backend\Views\vCartItem;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vProduct;

interface CouponDAO
{
    /**
     * Removes the coupons applied to a cart product
     * 
     * @param vCartItem $cartProduct the cart product for which to remove the applied coupons
     * 
     * @return bool true on success, false on failure
     */
    public function removeCartProductCoupons(vCartItem $cartProduct) : bool;
}

?>
