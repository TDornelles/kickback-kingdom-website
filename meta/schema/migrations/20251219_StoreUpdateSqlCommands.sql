START TRANSACTION;

ALTER TABLE item
    ADD COLUMN IF NOT EXISTS is_fungible TINYINT NOT NULL DEFAULT 0;

ALTER TABLE trade
    ADD COLUMN IF NOT EXISTS quantity BIGINT NOT NULL DEFAULT 1;

ALTER TABLE loot
    ADD COLUMN IF NOT EXISTS quantity BIGINT NOT NULL DEFAULT 1;

CREATE OR REPLACE VIEW v_item_info AS 
select 
    i.Id AS Id,
    i.type AS `type`,
    i.rarity AS rarity,
    i.media_id_large AS media_id_large,
    i.media_id_small AS media_id_small,
    i.desc AS `desc`,
    i.name AS `name`,
    i.nominated_by_id AS nominated_by_id,
    i.collection_id AS collection_id,
    null AS item_collection_name,
    null AS item_collection_desc,
    i.equipable AS equipable,
    i.equipment_slot AS equipment_slot,
    i.redeemable AS redeemable,
    i.useable AS useable,
    i.is_fungible,
    concat(large_image.Directory,'/',large_image.Id,'.',large_image.extension) AS large_image,
    concat(small_image.Directory,'/',small_image.Id,'.',small_image.extension) AS small_image,
    account_artist.Username AS artist,
    account_artist.Id AS artist_id,
    account_nominator.Username AS nominator,
    account_nominator.Id AS nominator_id,
    large_image.DateCreated AS DateCreated 
from item i 
left join Media large_image on(i.media_id_large = large_image.Id)
left join Media small_image on(i.media_id_small = small_image.Id)
left join account account_artist on(large_image.author_id = account_artist.Id) 
left join account account_nominator on(i.nominated_by_id = account_nominator.Id);

CREATE TABLE IF NOT EXISTS store
(
    ctime datetime(6) not null,
    crand bigint not null,
    name varchar(50) not null,
    locator varchar(50) not null,
    description varchar(200) not null,
    ref_owner_ctime datetime(6) not null,
    ref_owner_crand int not null,
    
    PRIMARY KEY (ctime, crand),
    
    CONSTRAINT fk_store_ref_account_ctime_crand_account_ctime_crand FOREIGN KEY (ref_owner_crand) references account(Id) 
);

CREATE OR REPLACE VIEW v_store AS (
SELECT
    s.ctime,
    s.crand,
    s.name,
    s.locator,
    s.description,
    a.Username as `owner_username`,
    '' as `owner_ctime`,
    a.Id as `owner_crand`
FROM store s
    LEFT JOIN account a ON s.ref_owner_crand = a.Id
);

create table IF NOT EXISTS price
(
    ctime datetime(6) not null,
    crand bigint not null,
    amount int not null,
    currency_code char(3),
    ref_item_ctime datetime,
    ref_item_crand int,
    
    primary key (ctime, crand),
    
    constraint fk_price_ctime_crand_item_ctime_crand FOREIGN KEY (ref_item_crand) REFERENCES item(id)
);

CREATE OR REPLACE VIEW v_price_component AS (
SELECT 
    p.ctime,
    p.crand,
    p.amount,
    p.currency_code,
    p.ref_item_ctime as `item_ctime`,
    p.ref_item_crand as `item_crand`,
    vi.name as `item_name`,
    vi.desc as `item_desc`,
    vi.small_image as `media_path_small`,
    vi.large_image as `media_path_large`,
    CONCAT(vmback.directory, '/', vmback.Id, '.', vmback.extension) as `media_path_back`,
    i.is_fungible as `item_is_fungible`
FROM price p
    LEFT JOIN v_item_info vi on vi.Id = p.ref_item_crand
    LEFT JOIN item i on i.id = p.ref_item_crand
    LEFT JOIN v_media vmback on vmback.Id = i.media_id_back
);

CREATE TABLE IF NOT EXISTS `transaction`
(
    ctime DATETIME(6) not null,
    crand BIGINT NOT NULL,
    complete TINYINT NOT NULL DEFAULT 0,
    void TINYINT NOT NULL DEFAULT 0,
    `description` VARCHAR(500),
    `transaction_type` VARCHAR(5),
    ref_first_account_ctime DATETIME NOT NULL,
    ref_first_account_crand INT NOT NULL,
    ref_second_account_ctime DATETIME,
    ref_second_account_crand INT NOT NULL,

    PRIMARY KEY (ctime, crand),

    CONSTRAINT fk_transaction_ref_first_account_ctime_crand FOREIGN KEY (ref_first_account_crand) REFERENCES account(Id),
    CONSTRAINT fk_transaction_ref_second_account_ctime_crand FOREIGN KEY (ref_second_account_crand) REFERENCES account(Id)
);

CREATE TABLE IF NOT EXISTS transaction_component
(
    ctime DATETIME(6) NOT NULL,
    crand BIGINT NOT NULL,
    ref_transaction_ctime DATETIME(6) NOT NULL,
    ref_transaction_crand BIGINT NOT NULL,
    ref_transferred_from_ctime DATETIME(6) NOT NULL,
    ref_transferred_from_crand INT NOT NULL,
    ref_transferred_to_ctime DATETIME(6) NOT NULL,
    ref_transferred_to_crand INT NOT NULL,
    amount BIGINT DEFAULT 0,
    loot_id INT,
    currency_code CHAR(3),

    PRIMARY KEY (ctime, crand),

    CONSTRAINT fk_transaction_price_component_transaction_ctime_crand FOREIGN KEY (ref_transaction_ctime, ref_transaction_crand) REFERENCES `transaction`(ctime, crand),

    CONSTRAINT fk_transaction_component_transferred_to_crand_account_id FOREIGN KEY (ref_transferred_to_crand) REFERENCES account(Id),
    CONSTRAINT fk_transaction_component_transferred_from_crand_account_id FOREIGN KEY (ref_transferred_from_crand) REFERENCES account(Id)
);

CREATE TABLE IF NOT EXISTS cart
(
    ctime datetime(6) not null,
    crand bigint not null,
    checked_out boolean not null default 0,
    void boolean not null default 0,
    ref_account_ctime datetime(6) not null,
    ref_account_crand int not null,
    ref_store_ctime datetime(6) not null,
    ref_store_crand bigint not null,
    ref_transaction_ctime datetime,
    ref_transaction_crand int,
    
    primary key (ctime, crand),
    
    UNIQUE KEY unique_cart_ref_account_ctime_crand_ref_store_ctime_crand (ref_account_ctime, ref_account_crand, ref_store_ctime, ref_store_crand),
    
    CONSTRAINT fk_cart_ref_account_ctime_crand_account_ctime_crand FOREIGN KEY (ref_account_crand) REFERENCES account(id),
    CONSTRAINT fk_cart_ref_store_ctime_crand_store_ctime_crand FOREIGN KEY (ref_store_ctime, ref_store_crand) REFERENCES store(ctime, crand)
);

CREATE OR REPLACE VIEW v_cart AS (
SELECT
    c.ctime,
    c.crand,
    a.username as `account_username`,
    s.name as `store_name`,
    s.locator as `store_locator`,
    c.checked_out,
    c.void,
    '' AS `account_ctime`,
    a.Id as `account_crand`,
    s.ctime as `store_ctime`,
    s.crand as `store_crand`,
s.ref_owner_ctime as `store_owner_ctime`,
    s.ref_owner_crand as `store_owner_crand`,
    c.ref_transaction_ctime as `transaction_ctime`,
    c.ref_transaction_crand as `transaction_crand`
FROM cart c
    LEFT JOIN store s on c.ref_store_ctime = s.ctime AND c.ref_store_crand = s.crand
    LEFT JOIN account a on a.id = c.ref_account_crand
);

CREATE TABLE IF NOT EXISTS product
(
    ctime datetime(6) not null,
    crand bigint not null,
    `name` varchar(50) not null,
    `description` varchar(200),
    `removed` tinyint not null DEFAULT(0),
    locator varchar(50) not null,
    tag varchar(50) not null,
    categories JSON not null,
    ref_store_ctime datetime(6) not null,
    ref_store_crand bigint not null,
    ref_media_id_large INT,
    ref_media_id_small INT,
    ref_media_id_back INT,
    
    PRIMARY KEY (ctime, crand),
    
    CONSTRAINT fk_product_ref_store_ctime_crand_store_ctime_crand FOREIGN KEY (ref_store_ctime, ref_store_crand) REFERENCES store(ctime, crand),
    CONSTRAINT fk_product_ref_media_id_large_media_id FOREIGN KEY (ref_media_id_large) REFERENCES Media(id),
    CONSTRAINT fk_product_ref_media_id_small_media_id FOREIGN KEY (ref_media_id_small) REFERENCES Media(id),
    CONSTRAINT fk_product_ref_media_id_back_media_id FOREIGN KEY (ref_media_id_back) REFERENCES Media(id)
);

CREATE TABLE IF NOT EXISTS product_loot_link (
    ctime DATETIME(6) NOT NULL,
    crand BIGINT NOT NULL,
    ref_product_ctime DATETIME(6) NOT NULL,
    ref_product_crand BIGINT NOT NULL,
    ref_loot_ctime DATETIME(6) NOT NULL,
    ref_loot_crand INT NOT NULL,
    removed TINYINT NOT NULL DEFAULT 0,
    quantity BIGINT NOT NULL DEFAULT 1,
    
    PRIMARY KEY (ctime, crand),
    
    CONSTRAINT fk_product_loot_link_product_ctime_crand FOREIGN KEY (ref_product_ctime, ref_product_crand) REFERENCES product(ctime, crand),
    CONSTRAINT fk_product_loot_link_loot_ctime_crand FOREIGN KEY (ref_loot_crand) REFERENCES loot(Id) ##Cannot constrain to dateObtained as ctime since (id, dateobtained) is not an indexed key
);

CREATE OR REPLACE VIEW v_product_loot_link AS (
    SELECT 
    ctime,
    crand,
    ref_product_ctime AS `product_ctime`,
    ref_product_crand AS `product_crand`,
    ref_loot_crand AS `loot_crand`,
    ref_loot_ctime AS `loot_ctime`,
    removed AS `removed`,
    quantity as `quantity`
    FROM product_loot_link
);

CREATE TABLE IF NOT EXISTS loot_reservation (
    ctime DATETIME(6) NOT NULL,
    crand BIGINT(11) NOT NULL,
    ref_loot_ctime DATETIME NOT NULL,
    ref_loot_crand INT NOT NULL,
    quantity BIGINT NOT NULL DEFAULT 1,
    expiry_time DATETIME,
    close_time DATETIME,
    
    PRIMARY KEY (ctime, crand),
    CONSTRAINT fk_loot_reservation_loot_ctime_crand FOREIGN KEY (ref_loot_crand) REFERENCES loot(id)
);

CREATE OR REPLACE VIEW v_loot_reservation AS (
    SELECT
    ctime,
    crand,
    ref_loot_ctime as `loot_ctime`,
    ref_loot_crand as `loot_crand`,
    quantity,
    expiry_time,
    close_time
    FROM loot_reservation
);

CREATE TABLE IF NOT EXISTS product_reservation (
    ctime DATETIME(6) NOT NULL,
    crand BIGINT NOT NULL,
    ref_cart_ctime DATETIME(6) NOT NULL,
    ref_cart_crand BIGINT NOT NULL,
    ref_product_ctime DATETIME(6) NOT NULL,
    ref_product_crand BIGINT NOT NULL,
    quantity BIGINT NOT NULL DEFAULT 1,
    expiry_time DATETIME,
    close_time DATETIME,

    PRIMARY KEY(ctime, crand),

    CONSTRAINT fk_loot_reservation_cart_ctime_crand FOREIGN KEY (ref_cart_ctime, ref_cart_crand) REFERENCES cart(ctime, crand),
    CONSTRAINT fk_loot_reservation_product_ctime_crand FOREIGN KEY (ref_product_ctime, ref_product_crand) REFERENCES product(ctime, crand)  
);

CREATE OR REPLACE VIEW v_product_reservation AS
(SELECT 
    pr.ctime,
    pr.crand,
    pr.ref_cart_ctime,
    pr.ref_cart_crand,
    pr.ref_product_ctime,
    pr.ref_product_crand,
    pr.quantity,
    pr.expiry_time,
    pr.close_time
FROM product_reservation pr
);

CREATE OR REPLACE VIEW v_product_reservation_total AS
(
    SELECT
    pr.ref_product_ctime AS product_ctime,
    pr.ref_product_crand AS product_crand,
    SUM(pr.quantity)     AS amount_reserved,
    COALESCE((
        SELECT SUM(pll.quantity)
        FROM product_loot_link AS pll
        WHERE pll.ref_product_ctime = pr.ref_product_ctime
            AND pll.ref_product_crand = pr.ref_product_crand
            AND pll.removed = 0
    ), 0) - SUM(pr.quantity) AS amount_available
    FROM product_reservation pr
    WHERE pr.expiry_time > NOW() AND pr.close_time IS NULL
    GROUP BY
    pr.ref_product_ctime,
    pr.ref_product_crand
);

CREATE OR REPLACE VIEW v_product AS (
    SELECT
        p.ctime,
        p.crand, 
        p.name,
        p.description,
        p.locator,
        CAST(
            COALESCE(
                (SELECT SUM(pl.quantity)
                FROM v_product_loot_link pl
                WHERE pl.product_ctime = p.ctime 
                AND pl.product_crand = p.crand 
                AND pl.removed = 0), 0
            ) AS SIGNED
        ) AS stock,
        CAST((COALESCE(
                (SELECT SUM(pl.quantity)
                FROM v_product_loot_link pl
                WHERE pl.product_ctime = p.ctime 
                AND pl.product_crand = p.crand 
                AND pl.removed = 0), 0
            ) - COALESCE(prt.amount_reserved, 0)) AS SIGNED) AS amount_available,
        p.removed,
        s.name AS `store_name`,
        s.locator AS `store_locator`,
        p.tag,
        p.categories,
        s.description AS `store_description`,
        s.owner_username AS `store_owner_username`,
        s.owner_ctime AS `store_owner_ctime`,
        s.owner_crand AS `store_owner_crand`,
        s.ctime as `store_ctime`,
        s.crand as `store_crand`,
        mlarge.mediaPath AS `large_media_media_path`,
        msmall.mediaPath AS `small_media_media_path`,
        mback.mediaPath AS `back_media_media_path`
    FROM product p 
    LEFT JOIN v_product_reservation_total prt ON prt.product_ctime = p.ctime AND prt.product_crand = p.crand
    LEFT JOIN v_store s ON s.ctime = p.ref_store_ctime AND s.crand = p.ref_store_crand
    LEFT JOIN v_media mback ON mback.id = p.ref_media_id_back
    LEFT JOIN v_media mlarge ON mlarge.id = p.ref_media_id_large
    LEFT JOIN v_media msmall ON msmall.id = p.ref_media_id_small
);

CREATE OR REPLACE VIEW v_loot_item AS
SELECT
lt.Id                AS Id,
lt.opened            AS opened,
lt.account_id        AS account_id,
lt.item_id           AS item_id,
lt.quest_id          AS quest_id,
it.media_id_small    AS media_id_small,
it.media_id_large    AS media_id_large,
it.media_id_back     AS media_id_back,
it.type              AS loot_type,
it.`desc`            AS `desc`,
it.rarity            AS rarity,
lt.dateObtained      AS dateObtained,
lt.container_loot_id AS container_loot_id,
lt.quantity          AS quantity,
GREATEST(
    0,
    lt.quantity
    - COALESCE((
        SELECT SUM(pll.quantity)
        FROM v_product_loot_link AS pll
        JOIN v_product AS vp
            ON vp.ctime = pll.product_ctime
        AND vp.crand = pll.product_crand
        WHERE vp.store_owner_crand = lt.account_id
            AND pll.loot_crand = lt.Id
            AND pll.removed = 0
        ), 0)
    - COALESCE((
        SELECT SUM(vlr.quantity)
        FROM v_loot_reservation AS vlr
        WHERE vlr.loot_crand = lt.Id
        ), 0)
) AS quantity_available
FROM loot AS lt
JOIN item AS it
ON it.Id = lt.item_id;

CREATE OR REPLACE VIEW v_loot_reservation_total AS (
    SELECT
        lr.ref_loot_ctime as 'loot_ctime',
        lr.ref_loot_crand as 'loot_crand',
        SUM(lr.quantity) as 'quantity_reserved',
        (SUM(vli.quantity)- SUM(lr.quantity)) as 'quantity_available'
    FROM loot_reservation lr JOIN v_loot_item vli ON lr.ref_loot_crand = vli.Id
    WHERE lr.close_time IS NULL AND expiry_time > NOW()
    GROUP BY lr.ref_loot_crand, lr.ref_loot_crand
);

create table IF NOT EXISTS product_price_component_link
(
    ctime datetime(6) not null,
    crand bigint not null,
    ref_product_ctime datetime(6) not null,
    ref_product_crand bigint not null,
    ref_price_component_ctime datetime(6) not null,
    ref_price_component_crand bigint not null,
    void tinyint not null,
    
    primary key (ref_product_ctime, ref_product_crand, ref_price_component_ctime, ref_price_component_crand),
    
    CONSTRAINT fk_productPriceLink_ref_product_ctime_crand_product_ctime_crand FOREIGN KEY (ref_product_ctime, ref_product_crand) REFERENCES product(ctime, crand),
    CONSTRAINT fk_productPriceLink_ref_price_comp_ctime_crand_price_ctime_crand FOREIGN KEY (ref_price_component_ctime, ref_price_component_crand) REFERENCES price(ctime, crand)
);

CREATE OR REPLACE VIEW v_product_price_component_link AS (
SELECT 
    ppl.ctime,
    ppl.crand,
    ppl.ref_product_ctime as `product_ctime`,
    ppl.ref_product_crand as `product_crand`,
    vp.ctime as `price_component_ctime`,
    vp.crand as `price_component_crand`,
    vp.amount,
    vp.currency_code,
    vp.item_ctime,
    vp.item_crand,
    vp.item_name, 
    vp.item_desc,
    vp.media_path_small,
    vp.media_path_large,
    vp.media_path_back
FROM product_price_component_link ppl
JOIN v_price_component vp ON vp.ctime = ppl.ref_price_component_ctime AND vp.crand = ppl.ref_price_component_crand
);

CREATE TABLE IF NOT EXISTS coupon(
	ctime DATETIME(6) NOT NULL,
    crand BIGINT NOT NULL,
    `code` VARCHAR(8),
    description VARCHAR(300),
    required_quantity_of_product BIGINT NOT NULL DEFAULT 1,
    ref_product_ctime DATETIME(6) NOT NULL,
    ref_product_crand BIGINT NOT NULL,
    times_used INT NOT NULL DEFAULT 0,
    max_times_used INT,
    max_times_used_per_account INT,
    expiry_time DATETIME(6),
    removed TINYINT NOT NULL DEFAULT 0,
    
    PRIMARY KEY(ctime, crand),
    
    UNIQUE INDEX idx_coupon_code (`code`),
    
    CONSTRAINT fk_coupon_product_ctime_crand FOREIGN KEY (ref_product_ctime, ref_product_crand) REFERENCES product(ctime, crand)
);

CREATE OR REPLACE VIEW v_coupon AS 
select 
    c.ctime AS ctime,
    c.crand AS crand,
    c.code AS code,
    c.description AS description,
    c.required_quantity_of_product AS required_quantity_of_product,
    c.ref_product_ctime AS product_ctime,
    c.ref_product_crand AS product_crand,
    p.ref_store_ctime AS store_ctime,
    p.ref_store_crand AS store_crand,
    c.times_used AS times_used,
    c.max_times_used AS max_times_used,
    c.max_times_used_per_account AS max_times_used_per_account,
    c.expiry_time AS expiry_time,
    c.removed AS removed
from coupon c JOIN product p ON c.ref_product_ctime = p.ctime AND c.ref_product_crand = p.crand;

CREATE TABLE IF NOT EXISTS cart_product_link (
    ctime datetime(6) not null,
    crand bigint not null,
    removed boolean not null default 0,
    checked_out boolean not null default 0,
    ref_cart_ctime datetime(6) not null,
    ref_cart_crand bigint not null,
    ref_product_ctime datetime(6) not null,
    ref_product_crand bigint not null,
    ref_coupon_ctime DATETIME(6),
    ref_coupon_crand BIGINT,
    
PRIMARY KEY (ctime, crand),
    
CONSTRAINT fk_cart_product_link_cart_ctime_crand FOREIGN KEY (ref_cart_ctime, ref_cart_crand) REFERENCES cart(ctime, crand),
CONSTRAINT fk_cart_product_link_product_ctime_crand FOREIGN KEY (ref_product_ctime, ref_product_crand) REFERENCES product(ctime, crand)
);

CREATE TABLE IF NOT EXISTS coupon_cart_product_link(
	ctime DATETIME(6) NOT NULL,
    crand BIGINT NOT NULL,
    ref_coupon_ctime DATETIME(6) NOT NULL,
    ref_coupon_crand BIGINT NOT NULL,
    ref_cart_product_link_ctime DATETIME(6) NOT NULL,
    ref_cart_product_link_crand BIGINT NOT NULL,
    coupon_assignment_group_ctime DATETIME(6) NOT NULL,
    coupon_assignment_group_crand BIGINT NOT NULL,
    removed TINYINT NOT NULL DEFAULT 0,
    checked_out TINYINT NOT NULL DEFAULT 0,
    
    PRIMARY KEY(ctime, crand),

    UNIQUE INDEX idx_coupon_cart_product_link_coupon_ctime_crand_group_id (coupon_assignment_group_ctime, coupon_assignment_group_crand),

    CONSTRAINT fk_coupon_cart_product_link_coupon_ctime_crand FOREIGN KEY (ref_coupon_ctime, ref_coupon_crand) REFERENCES coupon(ctime, crand),
    CONSTRAINT fk_coupoin_cart_product_link_cart_product_link_ctime_crand FOREIGN KEY (ref_cart_product_link_ctime, ref_cart_product_link_crand) REFERENCES cart_product_link(ctime, crand)
);

CREATE OR REPLACE VIEW v_cart_product_link AS (
SELECT
    cplink.ctime,
    cplink.crand,
    vp.name as `product_name`,
    vp.description as `product_description`,
    vp.locator as `product_locator`,
    cplink.removed,
    cplink.checked_out,
    vc.account_username,
    vc.store_name,
    vp.store_locator,
    cplink.ref_cart_ctime as `cart_ctime`,
    cplink.ref_cart_crand as `cart_crand`,
    cplink.ref_product_ctime as `product_ctime`,
    cplink.ref_product_crand as `product_crand`,
    vc.account_ctime,
    vc.account_crand,
    vc.store_ctime,
    vc.store_crand,
    vp.large_media_media_path,
    vp.small_media_media_path,
    vp.back_media_media_path,
    vcu.ctime as `coupon_ctime`,
    vcu.crand as `coupon_crand`,
    vcu.code as `coupon_code`,
    vcu.description as `coupon_description`,
    vcu.required_quantity_of_product as `coupon_required_quantity_of_product`,
    vcu.times_used as `coupon_times_used`,
    vcu.max_times_used as `coupon_max_times_used`,
    vcu.max_times_used_per_account as `coupon_max_times_used_per_account`,
    vcu.expiry_time as `coupon_expiry_time`,
    vcu.removed as 'coupon_removed'
FROM cart_product_link cplink
    JOIN v_product vp on cplink.ref_product_ctime = vp.ctime and cplink.ref_product_crand = vp.crand
    JOIN v_cart vc on cplink.ref_cart_ctime = vc.ctime and cplink.ref_cart_crand = vc.crand
    LEFT JOIN coupon_cart_product_link ccpl ON ccpl.ref_cart_product_link_ctime = cplink.ctime AND ccpl.ref_cart_product_link_crand = cplink.crand 
    LEFT JOIN v_coupon vcu ON vcu.ctime = ccpl.ref_coupon_ctime AND vcu.crand = ccpl.ref_coupon_crand
);




CREATE TABLE IF NOT EXISTS cart_product_price_component_link (
    ctime DATETIME(6) NOT NULL,
    crand BIGINT NOT NULL,  
    ref_cart_product_link_ctime DATETIME(6) NOT NULL,
    ref_cart_product_link_crand BIGINT NOT NULL,
    ref_price_component_ctime DATETIME (6) NOT NULL,
    ref_price_component_crand BIGINT NOT NULL,
    removed TINYINT NOT NULL DEFAULT 0,
    checked_out TINYINT NOT NULL DEFAULT 0,
    
    PRIMARY KEY(ctime, crand),
    
    CONSTRAINT fk_cart_product_price_link_cart_product_link_ctime_crand FOREIGN KEY (ref_cart_product_link_ctime, ref_cart_product_link_crand) REFERENCES cart_product_link(ctime, crand),
    CONSTRAINT fk_cart_product_price_link_price_citme_crand FOREIGN KEY (ref_price_component_ctime, ref_price_component_crand) REFERENCES price(ctime, crand)
);


CREATE OR REPLACE VIEW v_cart_product_price_component_link AS
SELECT
ctime,
crand,
ref_cart_product_link_ctime as `cart_product_link_ctime`,
ref_cart_product_link_crand as `cart_product_link_crand`,
ref_price_component_ctime as `price_component_ctime`,
ref_price_component_crand as `price_component_crand`,
removed,
checked_out
FROM cart_product_price_component_link;

CREATE OR REPLACE VIEW v_cart_item AS (
SELECT
    cplink.ctime as 'cart_product_link_ctime',
    cplink.crand as 'cart_product_link_crand',
    cplink.ref_cart_ctime as 'cart_ctime',
    cplink.ref_cart_crand as 'cart_crand',
    pplink.removed,
    pplink.checked_out,
    vprod.ctime as 'product_ctime',
    vprod.crand as 'product_crand',
    vprod.name as 'product_name',
    vprod.description as 'product_description',
    vprod.locator as 'product_locator',
    vprod.small_media_media_path as 'product_small_media_path',
    vprod.large_media_media_path as 'product_large_media_path',
    vprod.back_media_media_path as 'product_back_media_path',
    vprod.stock as 'product_stock',
    vprice.ctime as 'price_component_ctime',
    vprice.crand as 'price_component_crand',
    vprice.amount as 'price_component_amount',
    vprice.currency_code as 'price_component_currency_code',
    vprice.item_name as 'price_component_item_name',
    vprice.item_desc as'price_component_item_desc',
    vprice.media_path_small as 'price_component_media_path_small',
    vprice.media_path_large as 'price_component_media_path_large',
    vprice.media_path_back as 'price_component_media_path_back',
    vprice.item_ctime as 'price_component_item_ctime',
    vprice.item_crand as 'price_component_item_crand',
    vprice.item_is_fungible as 'price_component_item_is_fungible',
    vcu.ctime as `coupon_ctime`,
    vcu.crand as `coupon_crand`,
    vcu.code as `coupon_code`,
    vcu.description as `coupon_description`,
    vcu.required_quantity_of_product as `coupon_required_quantity_of_product`,
    vcu.times_used as `coupon_times_used`,
    vcu.max_times_used as `coupon_max_times_used`,
    vcu.max_times_used_per_account as `coupon_max_times_used_per_account`,
    vcu.expiry_time as `coupon_expiry_time`,
    vcu.removed as 'coupon_removed',
    ccpl.coupon_assignment_group_ctime,
    ccpl.coupon_assignment_group_crand
FROM cart_product_link cplink
    JOIN cart c ON cplink.ref_cart_ctime = c.ctime AND cplink.ref_cart_crand = c.crand
    JOIN v_product vprod ON cplink.ref_product_ctime = vprod.ctime AND cplink.ref_product_crand = vprod.crand
    JOIN cart_product_price_component_link pplink ON cplink.ctime = pplink.ref_cart_product_link_ctime AND cplink.crand = pplink.ref_cart_product_link_crand
    JOIN v_price_component vprice ON pplink.ref_price_component_ctime = vprice.ctime AND pplink.ref_price_component_crand = vprice.crand
    LEFT JOIN coupon_cart_product_link ccpl ON ccpl.ref_cart_product_link_ctime = cplink.ctime AND ccpl.ref_cart_product_link_crand = cplink.crand 
    LEFT JOIN v_coupon vcu ON vcu.ctime = ccpl.ref_coupon_ctime AND vcu.crand = ccpl.ref_coupon_crand
);

CREATE TABLE IF NOT EXISTS trade (
id int(11) NOT NULL AUTO_INCREMENT,
from_account_id int(11) DEFAULT NULL,
to_account_id int(11) DEFAULT NULL,
loot_id int(11) DEFAULT NULL,
trade_date timestamp NOT NULL DEFAULT utc_timestamp(),
from_account_obtain_date datetime DEFAULT NULL,
quantity bigint DEFAULT 1 NOT NULL,
PRIMARY KEY (id)
);


CREATE TABLE IF NOT EXISTS coupon_account_use(
	ctime DATETIME(6) NOT NULL,
    crand BIGINT NOT NULL,
    ref_coupon_ctime DATETIME(6) NOT NULL,
    ref_coupon_crand BIGINT NOT NULL,
    ref_account_ctime DATETIME(6) NOT NULL,
    ref_account_crand INT NOT NULL,
    times_used INT NOT NULL DEFAULT 0,
    
    PRIMARY KEY(ctime, crand),
    CONSTRAINT fk_coupon_account_use_coupon_ctime_crand FOREIGN KEY (ref_coupon_ctime, ref_coupon_crand) REFERENCES coupon(ctime, crand),
    CONSTRAINT fk_coupon_account_use_account_ctime_crand FOREIGN KEY (ref_account_crand) REFERENCES account(Id)
);

CREATE OR REPLACE VIEW v_coupon_account_use AS(
SELECT 
    cu.ctime,
    cu.crand,
    c.ctime as `coupon_ctime`,
    c.crand as `coupon_crand`,
    c.code,
    c.description,
    c.required_quantity_of_product,
    c.ref_product_ctime as `product_ctime`,
    c.ref_product_crand as `product_crand`,
    c.times_used,
    c.max_times_used_per_account,
    (c.max_times_used_per_account - cu.times_used) as `remaining_uses`,
    cu.times_used as `account_times_used`,
    c.expiry_time,
    c.removed,
    cu.ref_account_ctime as `account_ctime`,
    cu.ref_account_crand as `account_crand`
    FROM coupon_account_use cu
    JOIN coupon c ON cu.ref_coupon_ctime = c.ctime AND cu.ref_coupon_crand = c.crand
);

CREATE TABLE IF NOT EXISTS coupon_price_link(
    ctime DATETIME(6) NOT NULL,
    crand BIGINT NOT NULL,
    ref_coupon_ctime DATETIME(6) NOT NULL,
    ref_coupon_crand BIGINT NOT NULL,
    ref_price_ctime DATETIME(6) NOT NULL,
    ref_price_crand BIGINT NOT NULL,
    
    PRIMARY KEY (ctime, crand),
    CONSTRAINT fk_coupon_price_link_coupon_ctime_crand FOREIGN KEY coupon(ref_coupon_ctime, ref_coupon_crand) REFERENCES coupon(ctime, crand),
    CONSTRAINT fk_coupon_price_link_price_ctime_crand FOREIGN KEY price(ref_price_ctime, ref_price_crand) REFERENCES price(ctime, crand)
);


COMMIT;