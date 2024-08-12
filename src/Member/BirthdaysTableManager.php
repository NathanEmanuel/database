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

    /**
     * Return the member IDs of members whose birthday is today.
     * @return  int[]   The member IDs
     * @throws  mysqli_sql_exception
     */
    public function getMemberIdsWithBirthdayToday(): array
    {
        $statement = $this->getClient()->prepare(
            "SELECT `member_id` FROM `screen_birthdays`
            WHERE DAY(date_of_birth) = DAY(CURRENT_DATE())
		    AND MONTH(date_of_birth) = MONTH(CURRENT_DATE());"
        );
        $statement->bind_result($memberId);
        $statement->execute();

        $memberIds = array();
        while ($statement->fetch()) {
            $memberIds[] = $memberId;
        }

        $statement->close();
        return $memberIds;
    }
}
