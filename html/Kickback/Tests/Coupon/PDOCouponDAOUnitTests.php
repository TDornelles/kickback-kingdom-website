<?php

declare(strict_types=1);

namespace Kickback\Tests\Coupon;

use Exception;
use Kickback\Backend\Views\vCartItem;
use Kickback\Backend\Views\vCoupon;
use Kickback\BackendV2\DAO\Coupon\PDOCouponDAO;
use Kickback\Tests\Tests;
use LogicException;
use ReflectionClass;

final class PDOCouponDAOUnitTests implements Tests
{
    private PDOCouponDAO $dao;

    public function __construct()
    {
        $reflection = new ReflectionClass(PDOCouponDAO::class);
        $this->dao = $reflection->newInstanceWithoutConstructor();
    }

    public function runTests() : void
    {
        $this->unittest_removeCartProductCoupons_withoutCoupon_returnsTrue();
        $this->unittest_removeCartProductCoupons_couponWithoutAssignment_throwsLogicException();
    }

    private function unittest_removeCartProductCoupons_withoutCoupon_returnsTrue() : void
    {
        $cartProduct = new vCartItem('cartProduct', 1);
        $cartProduct->coupon = null;
        $cartProduct->couponGroupAssignmentId = null;

        $result = $this->dao->removeCartProductCoupons($cartProduct);

        if($result !== true) throw new Exception("Expected true when no coupon is attached to cart product");
    }

    private function unittest_removeCartProductCoupons_couponWithoutAssignment_throwsLogicException() : void
    {
        $cartProduct = new vCartItem('cartProduct', 1);
        $cartProduct->coupon = new vCoupon('coupon', 1);
        $cartProduct->couponGroupAssignmentId = null;

        try
        {
            $this->dao->removeCartProductCoupons($cartProduct);
        }
        catch(LogicException $e)
        {
            return;
        }

        throw new Exception("Expected LogicException when coupon exists but couponGroupAssignmentId is null");
    }
}

