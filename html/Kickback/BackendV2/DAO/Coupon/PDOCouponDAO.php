<?php

declare(strict_types = 1);

namespace Kickback\BackendV2\DAO\Coupon;

use LogicException;
use Kickback\Backend\Views\vCartItem;
use Kickback\BackendV2\Persistance\Database;

class PDOCouponDAO implements CouponDAO
{
    private Database $pdo;

    public function __construct(?Database $pdo = null)
    {
        $this->pdo = $pdo ?? new Database();
    }

    public function removeCartProductCoupons(vCartItem $cartProduct) : bool
    {
        if (is_null($cartProduct->coupon))
        {
            return true;
        }

        if (is_null($cartProduct->couponGroupAssignmentId))
        {
            throw new LogicException("Coupon group assignment id cannot be null if a coupon has been applied to the cart product");
        }

        $conn = $this->pdo->getConnection();
        if ($conn === null)
        {
            return false;
        }

        $sql = "UPDATE coupon_cart_product_link SET removed = 1 WHERE coupon_assignment_group_ctime = ? AND coupon_assignment_group_crand = ?;";
        $stmt = $conn->prepare($sql);
        if ($stmt === false)
        {
            return false;
        }

        $result = $stmt->execute([
            $cartProduct->couponGroupAssignmentId->ctime,
            $cartProduct->couponGroupAssignmentId->crand
        ]);

        return $result !== false;
    }
}

?>
