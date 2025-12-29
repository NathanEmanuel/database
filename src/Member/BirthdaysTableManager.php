<?php

namespace Compucie\Database\Member;

use mysqli;
use mysqli_sql_exception;

trait BirthdaysTableManager
{
    protected abstract function getClient(): mysqli;

    protected function createBirthdaysTable(): void
    {
        $statement = $this->getClient()->prepare(
            "CREATE TABLE IF NOT EXISTS `screen_birthdays` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `member_id` INT NOT NULL,
                `date_of_birth` DATE NOT NULL,
                PRIMARY KEY (`id`)
            );"
        );
        if ($statement){
            $statement->execute();
            $statement->close();
        }
    }

    /**
     * Return the member IDs of members whose birthday is today.
     * @return  int[]   The member IDs
     * @throws  mysqli_sql_exception
     */
    public function getMemberIdsWithBirthdayToday(): array
    {
        $rows = $this->executeReadAll(
            "SELECT `member_id`
            FROM `screen_birthdays`
            WHERE DATE_FORMAT(`date_of_birth`, '%m-%d') = DATE_FORMAT(CURRENT_DATE(), '%m-%d')"
        );

        return array_map(
            static fn(array $row): int => (int)$row['member_id'],
            $rows
        );
    }
}
