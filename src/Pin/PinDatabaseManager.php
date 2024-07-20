<?php

namespace Compucie\Database\Pin;

use Compucie\Database\DatabaseManager;
use DateTime;

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

    /**
     * Insert an event pin.
     * @throws  mysqli_sql_exception
     */
    public function insertEventPin(int $eventId, DateTime $startAt = null, DateTime $endAt = null): void
    {
        $startAt = is_null($startAt)
            ? (new DateTime())->format(self::SQL_DATETIME_FORMAT)
            : $startAt->format(self::SQL_DATETIME_FORMAT);
        $endAt = is_null($endAt) ? null : $endAt->format(self::SQL_DATETIME_FORMAT);

        $statement = $this->getClient()->prepare("INSERT INTO `events` (`event_id`, `start_at`, `end_at`) VALUES (?, ?, ?)");
        $statement->bind_param(
            "iss",
            $eventId,
            $startAt,
            $endAt,
        );
        $statement->execute();
        $statement->close();
    }

    /**
     * Update an event pin.
     * @throws  mysqli_sql_exception
     */
    public function updateEventPin(int $eventId, DateTime $startAt = null, DateTime $endAt = null): void
    {
        $startAt = is_null($startAt)
            ? (new DateTime())->format(self::SQL_DATETIME_FORMAT)
            : $startAt->format(self::SQL_DATETIME_FORMAT);
        $endAt = is_null($endAt) ? null : $endAt->format(self::SQL_DATETIME_FORMAT);

        $statement = $this->getClient()->prepare("UPDATE `events` SET `start_at` = ?, `end_at` = ? WHERE `event_id` = ?;");
        $statement->bind_param(
            "ssi",
            $startAt,
            $endAt,
            $eventId,
        );
        $statement->execute();
        $statement->close();
    }
}
