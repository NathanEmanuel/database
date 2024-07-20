<?php

namespace Compucie\Database\Pin;

use Compucie\Database\DatabaseManager;

class PinDatabaseManager extends DatabaseManager
{
    public function createTables()
    {
        $statement = $this->getClient()->prepare(
            "CREATE TABLE `events` (
                `pin_id` INT NOT NULL AUTO_INCREMENT,
                `event_id` INT NOT NULL,
                `start_at` DATETIME NOT NULL DEFAULT UTC_TIMESTAMP(),
                `end_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (pin_id)
            );"
        );
        $statement->execute();
        $statement->close();
    }
}
