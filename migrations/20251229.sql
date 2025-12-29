ALTER TABLE `rfid`
    ADD COLUMN `hashed_activation_token` VARCHAR(255) DEFAULT NULL,
    ADD COLUMN `activation_token_valid_until` DATETIME DEFAULT NULL;