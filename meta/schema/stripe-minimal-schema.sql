-- ============================================================================
-- Minimal Store Schema for Stripe Checkout Integration
-- ============================================================================
-- This schema provides ONLY what's needed for basic Stripe checkout:
-- - Stores
-- - Products with USD prices
-- - Shopping carts
-- - Cart items
--
-- Created: 2025-11-21
-- Purpose: Clean minimal implementation avoiding broken StoreController
-- ============================================================================

-- ----------------------------------------------------------------------------
-- TABLES
-- ----------------------------------------------------------------------------

-- Store table
CREATE TABLE IF NOT EXISTS `store` (
  `ctime` varchar(50) NOT NULL COMMENT 'Creation timestamp in Y-m-d H:i:s.u format',
  `crand` int(11) NOT NULL COMMENT 'Creation random ID',
  `name` varchar(255) NOT NULL,
  `locator` varchar(255) NOT NULL COMMENT 'URL-friendly unique identifier',
  `description` text DEFAULT NULL,
  `ref_account_ctime` varchar(50) NOT NULL COMMENT 'Owner account ctime',
  `ref_account_crand` int(11) NOT NULL COMMENT 'Owner account ID',
  PRIMARY KEY (`ctime`, `crand`),
  UNIQUE KEY `locator` (`locator`),
  KEY `idx_owner` (`ref_account_crand`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Stores that can sell products';

-- Product table
CREATE TABLE IF NOT EXISTS `product` (
  `ctime` varchar(50) NOT NULL,
  `crand` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `locator` varchar(255) NOT NULL COMMENT 'URL-friendly unique identifier',
  `removed` tinyint(1) NOT NULL DEFAULT 0,
  `ref_store_ctime` varchar(50) NOT NULL,
  `ref_store_crand` int(11) NOT NULL,
  PRIMARY KEY (`ctime`, `crand`),
  UNIQUE KEY `locator` (`locator`),
  KEY `idx_store` (`ref_store_ctime`, `ref_store_crand`),
  KEY `idx_not_removed` (`removed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Products available for purchase';

-- Price component table (supports USD and other currencies)
CREATE TABLE IF NOT EXISTS `price_component` (
  `ctime` varchar(50) NOT NULL,
  `crand` int(11) NOT NULL,
  `amount` bigint(20) NOT NULL COMMENT 'Amount in smallest unit (cents for USD)',
  `currency_code` varchar(10) DEFAULT NULL COMMENT 'USD, EUR, etc',
  `ref_item_ctime` varchar(50) DEFAULT NULL COMMENT 'For item-based pricing',
  `ref_item_crand` int(11) DEFAULT NULL,
  PRIMARY KEY (`ctime`, `crand`),
  KEY `idx_currency` (`currency_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Price components (can be currency or items)';

-- Product-Price link table
CREATE TABLE IF NOT EXISTS `product_price_component_link` (
  `ref_product_ctime` varchar(50) NOT NULL,
  `ref_product_crand` int(11) NOT NULL,
  `ref_price_component_ctime` varchar(50) NOT NULL,
  `ref_price_component_crand` int(11) NOT NULL,
  PRIMARY KEY (`ref_product_ctime`, `ref_product_crand`, `ref_price_component_ctime`, `ref_price_component_crand`),
  KEY `idx_product` (`ref_product_ctime`, `ref_product_crand`),
  KEY `idx_price` (`ref_price_component_ctime`, `ref_price_component_crand`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Links products to their prices';

-- Cart table
CREATE TABLE IF NOT EXISTS `cart` (
  `ctime` varchar(50) NOT NULL,
  `crand` int(11) NOT NULL,
  `ref_account_ctime` varchar(50) NOT NULL,
  `ref_account_crand` int(11) NOT NULL COMMENT 'Account ID who owns cart',
  `ref_store_ctime` varchar(50) NOT NULL,
  `ref_store_crand` int(11) NOT NULL,
  `checked_out` tinyint(1) NOT NULL DEFAULT 0,
  `void` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`ctime`, `crand`),
  KEY `idx_account` (`ref_account_crand`),
  KEY `idx_store` (`ref_store_ctime`, `ref_store_crand`),
  KEY `idx_active` (`checked_out`, `void`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Shopping carts';

-- Cart-Product link table
CREATE TABLE IF NOT EXISTS `cart_product_link` (
  `ctime` varchar(50) NOT NULL,
  `crand` int(11) NOT NULL,
  `ref_cart_ctime` varchar(50) NOT NULL,
  `ref_cart_crand` int(11) NOT NULL,
  `ref_product_ctime` varchar(50) NOT NULL,
  `ref_product_crand` int(11) NOT NULL,
  `removed` tinyint(1) NOT NULL DEFAULT 0,
  `checked_out` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`ctime`, `crand`),
  KEY `idx_cart` (`ref_cart_ctime`, `ref_cart_crand`),
  KEY `idx_product` (`ref_product_ctime`, `ref_product_crand`),
  KEY `idx_active` (`removed`, `checked_out`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Products in carts';

-- Cart-Product-Price link table
CREATE TABLE IF NOT EXISTS `cart_product_price_component_link` (
  `ref_cart_product_link_ctime` varchar(50) NOT NULL,
  `ref_cart_product_link_crand` int(11) NOT NULL,
  `ref_price_component_ctime` varchar(50) NOT NULL,
  `ref_price_component_crand` int(11) NOT NULL,
  `checked_out` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`ref_cart_product_link_ctime`, `ref_cart_product_link_crand`, `ref_price_component_ctime`, `ref_price_component_crand`),
  KEY `idx_cart_product` (`ref_cart_product_link_ctime`, `ref_cart_product_link_crand`),
  KEY `idx_price` (`ref_price_component_ctime`, `ref_price_component_crand`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Snapshot of prices when products added to cart';

-- ----------------------------------------------------------------------------
-- VIEWS
-- ----------------------------------------------------------------------------

-- Store view with owner information
CREATE OR REPLACE VIEW `v_store` AS
SELECT
    s.ctime,
    s.crand,
    s.name,
    s.locator,
    s.description,
    a.Username AS owner_username,
    s.ref_account_ctime AS owner_ctime,
    s.ref_account_crand AS owner_crand
FROM store s
LEFT JOIN account a ON a.Id = s.ref_account_crand;

-- Product view with store information
CREATE OR REPLACE VIEW `v_product` AS
SELECT
    p.ctime,
    p.crand,
    p.name,
    p.description,
    p.locator,
    p.removed,
    s.ctime AS store_ctime,
    s.crand AS store_crand,
    s.name AS store_name,
    s.locator AS store_locator
FROM product p
LEFT JOIN store s ON p.ref_store_ctime = s.ctime AND p.ref_store_crand = s.crand;

-- Price component view
CREATE OR REPLACE VIEW `v_price_component` AS
SELECT
    pc.ctime,
    pc.crand,
    pc.amount,
    pc.currency_code,
    pc.ref_item_ctime AS item_ctime,
    pc.ref_item_crand AS item_crand,
    CASE
        WHEN pc.currency_code IS NOT NULL THEN pc.currency_code
        WHEN i.name IS NOT NULL THEN i.name
        ELSE 'UNKNOWN'
    END AS display_name
FROM price_component pc
LEFT JOIN item i ON pc.ref_item_ctime = i.ctime AND pc.ref_item_crand = i.crand;

-- Cart view
CREATE OR REPLACE VIEW `v_cart` AS
SELECT
    c.ctime,
    c.crand,
    c.checked_out,
    c.void,
    a.Username AS account_username,
    c.ref_account_ctime AS account_ctime,
    c.ref_account_crand AS account_crand,
    s.name AS store_name,
    c.ref_store_ctime AS store_ctime,
    c.ref_store_crand AS store_crand
FROM cart c
LEFT JOIN account a ON a.Id = c.ref_account_crand
LEFT JOIN store s ON c.ref_store_ctime = s.ctime AND c.ref_store_crand = s.crand;

-- Cart item view (products in cart with prices)
CREATE OR REPLACE VIEW `v_cart_item` AS
SELECT
    cpl.ctime,
    cpl.crand,
    cpl.removed,
    cpl.checked_out,
    c.ctime AS cart_ctime,
    c.crand AS cart_crand,
    p.ctime AS product_ctime,
    p.crand AS product_crand,
    p.name AS product_name,
    p.description AS product_description,
    p.locator AS product_locator,
    s.ctime AS store_ctime,
    s.crand AS store_crand,
    s.name AS store_name
FROM cart_product_link cpl
JOIN cart c ON cpl.ref_cart_ctime = c.ctime AND cpl.ref_cart_crand = c.crand
JOIN product p ON cpl.ref_product_ctime = p.ctime AND cpl.ref_product_crand = p.crand
JOIN store s ON p.ref_store_ctime = s.ctime AND p.ref_store_crand = s.crand;

-- Cart product link view
CREATE OR REPLACE VIEW `v_cart_product_link` AS
SELECT
    cpl.ctime,
    cpl.crand,
    cpl.removed,
    cpl.checked_out,
    cpl.ref_cart_ctime AS cart_ctime,
    cpl.ref_cart_crand AS cart_crand,
    cpl.ref_product_ctime AS product_ctime,
    cpl.ref_product_crand AS product_crand,
    p.name AS product_name
FROM cart_product_link cpl
LEFT JOIN product p ON cpl.ref_product_ctime = p.ctime AND cpl.ref_product_crand = p.crand;

-- ============================================================================
-- INDEXES FOR PERFORMANCE
-- ============================================================================
-- Additional indexes beyond the PRIMARY/UNIQUE keys defined above

-- Add index for finding active cart items
CREATE INDEX IF NOT EXISTS idx_cart_active_items ON cart_product_link(ref_cart_ctime, ref_cart_crand, removed, checked_out);

-- Add index for product lookups
CREATE INDEX IF NOT EXISTS idx_product_lookup ON product(locator, removed);

-- Add index for store lookups
CREATE INDEX IF NOT EXISTS idx_store_lookup ON store(locator);
