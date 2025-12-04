-- Add product identifiers to shipment order pool and manifest tables
ALTER TABLE shipment_order_pool
    ADD COLUMN product_ctime DATETIME(6) NULL AFTER crand,
    ADD COLUMN product_crand BIGINT NULL AFTER product_ctime,
    MODIFY item_id INT NULL,
    ADD INDEX idx_shipment_order_pool_product (product_ctime, product_crand);

ALTER TABLE shipment_manifest
    ADD COLUMN product_ctime DATETIME(6) NULL AFTER tracking_number,
    ADD COLUMN product_crand BIGINT NULL AFTER product_ctime,
    MODIFY item_id INT NULL,
    ADD INDEX idx_shipment_manifest_product (product_ctime, product_crand);

-- Best-effort migration from legacy item-based rows to product identifiers
UPDATE shipment_order_pool sop
INNER JOIN product p ON p.crand = sop.item_id
SET sop.product_ctime = p.ctime,
    sop.product_crand = p.crand
WHERE (sop.product_ctime IS NULL OR sop.product_crand IS NULL);

UPDATE shipment_manifest sm
INNER JOIN product p ON p.crand = sm.item_id
SET sm.product_ctime = p.ctime,
    sm.product_crand = p.crand
WHERE (sm.product_ctime IS NULL OR sm.product_crand IS NULL);

-- Add foreign key relationships where available
ALTER TABLE shipment_order_pool
    ADD CONSTRAINT fk_shipment_order_pool_product
        FOREIGN KEY (product_ctime, product_crand) REFERENCES product (ctime, crand);

ALTER TABLE shipment_manifest
    ADD CONSTRAINT fk_shipment_manifest_product
        FOREIGN KEY (product_ctime, product_crand) REFERENCES product (ctime, crand);
