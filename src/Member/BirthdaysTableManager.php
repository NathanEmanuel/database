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
        $memberId = 0;
        $memberIds = array();

        $statement = $this->getClient()->prepare(
            "SELECT `member_id` FROM `screen_birthdays`
            WHERE DAY(date_of_birth) = DAY(CURRENT_DATE())
		    AND MONTH(date_of_birth) = MONTH(CURRENT_DATE());"
        );
        if ($statement) {
            $statement->bind_result($memberId);
            $statement->execute();

            while ($statement->fetch()) {
                $memberIds[] = $memberId;
            }

            $statement->close();
        }

        return $memberIds;
    }
}
