<?php

namespace Compucie\Database\Member;

use mysqli;

trait BirthdaysTableManager
{
    protected abstract function getClient(): mysqli;

    protected function createBirthdaysTable(): void
    {
        $statement = $this->getClient()->prepare(
            "CREATE TABLE `screen_birthdays` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `member_id` INT NOT NULL,
                `date_of_birth` DATE NOT NULL,
                PRIMARY KEY (`id`)
            );"
        );
        $statement->execute();
        $statement->close();
    }
}
