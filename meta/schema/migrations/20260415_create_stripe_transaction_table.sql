-- Migration: Create stripe_transaction table
-- Records each Stripe checkout session initiated for a cart

CREATE TABLE `stripe_transaction` (
  `id`                    INT          NOT NULL AUTO_INCREMENT,
  `ref_cart_ctime`        datetime(6)  NOT NULL,
  `ref_cart_crand`        bigint(20)   NOT NULL,
  `stripe_transaction_id` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_stripe_transaction_cart` (`ref_cart_ctime`, `ref_cart_crand`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
