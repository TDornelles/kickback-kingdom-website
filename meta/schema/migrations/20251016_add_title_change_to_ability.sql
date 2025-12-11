ALTER TABLE ability
    ADD COLUMN `title_change` varchar(25) NOT NULL DEFAULT '' AFTER `level_multiplier`;

ALTER TABLE item_ability
    DROP FOREIGN KEY `item_ability_ability_fk`,
    ADD CONSTRAINT `item_ability_ability_fk`
        FOREIGN KEY (`ability_id`) REFERENCES `ability` (`Id`)
        ON DELETE CASCADE ON UPDATE CASCADE;
