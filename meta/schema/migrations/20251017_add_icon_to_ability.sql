ALTER TABLE ability
    ADD COLUMN `icon` varchar(64) DEFAULT NULL AFTER `name`,
    ADD UNIQUE KEY `ability_icon_uq` (`icon`);
