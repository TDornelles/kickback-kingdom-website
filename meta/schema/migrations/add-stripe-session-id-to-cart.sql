-- Migration: Add stripe_session_id column to cart table
-- This supports tracking Stripe Checkout sessions for USD payments

ALTER TABLE `cart`
  ADD COLUMN `stripe_session_id` varchar(255) DEFAULT NULL
  AFTER `ref_transaction_crand`;

-- Update v_cart view to include stripe_session_id
CREATE OR REPLACE VIEW `v_cart` AS (
  SELECT
    `c`.`ctime` AS `ctime`,
    `c`.`crand` AS `crand`,
    `a`.`Username` AS `account_username`,
    `s`.`name` AS `store_name`,
    `s`.`locator` AS `store_locator`,
    `c`.`checked_out` AS `checked_out`,
    `c`.`void` AS `void`,
    '' AS `account_ctime`,
    `a`.`Id` AS `account_crand`,
    `s`.`ctime` AS `store_ctime`,
    `s`.`crand` AS `store_crand`,
    `s`.`ref_owner_ctime` AS `store_owner_ctime`,
    `s`.`ref_owner_crand` AS `store_owner_crand`,
    `c`.`ref_transaction_ctime` AS `transaction_ctime`,
    `c`.`ref_transaction_crand` AS `transaction_crand`,
    `c`.`stripe_session_id` AS `stripe_session_id`
  FROM ((`cart` `c`
    LEFT JOIN `store` `s` ON (`c`.`ref_store_ctime` = `s`.`ctime` AND `c`.`ref_store_crand` = `s`.`crand`))
    LEFT JOIN `account` `a` ON (`a`.`Id` = `c`.`ref_account_crand`))
);
