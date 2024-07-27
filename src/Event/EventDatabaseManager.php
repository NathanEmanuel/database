<?php

namespace Compucie\Database\Event;

use Compucie\Database\DatabaseManager;
use DateTime;

class EventDatabaseManager extends DatabaseManager
{
    public function createTables()
    {
        $statement = $this->getClient()->prepare(
            "CREATE TABLE `pins` (
                `pin_id` INT NOT NULL AUTO_INCREMENT UNIQUE,
                `event_id` INT NOT NULL,
                `start_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `end_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (pin_id)
            );"
        );
        $statement->execute();
        $statement->close();
    }

    /**
     * Return array of IDs of currently pinned events.
     * @return  int[]   $eventIds
     * @throws  mysqli_sql_exception
     */
    public function getCurrentlyPinnedEventIds(): array
    {
        $statement = $this->getClient()->prepare("SELECT `event_id` FROM `pins` WHERE (NOW() BETWEEN start_at AND end_at) OR (NOW() > start_at AND end_at IS NULL);");
        $statement->bind_result($eventId);
        $statement->execute();
        $eventIds = array();
        while ($statement->fetch()) {
            $eventIds[] = $eventId;
        }
        $statement->close();

        return $eventIds;
    }

    /**
     * Insert an event pin.
     * @throws  mysqli_sql_exception
     */
    public function insertPin(int $eventId, DateTime $startAt = null, DateTime $endAt = null): void
    {
        $startAt = is_null($startAt)
            ? (new DateTime())->format(self::SQL_DATETIME_FORMAT)
            : $startAt->format(self::SQL_DATETIME_FORMAT);
        $endAt = is_null($endAt) ? null : $endAt->format(self::SQL_DATETIME_FORMAT);

        $statement = $this->getClient()->prepare("INSERT INTO `pins` (`event_id`, `start_at`, `end_at`) VALUES (?, ?, ?)");
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
    public function updatePin(int $eventId, DateTime $startAt = null, DateTime $endAt = null): void
    {
        $startAt = is_null($startAt)
            ? (new DateTime())->format(self::SQL_DATETIME_FORMAT)
            : $startAt->format(self::SQL_DATETIME_FORMAT);
        $endAt = is_null($endAt) ? null : $endAt->format(self::SQL_DATETIME_FORMAT);

        $statement = $this->getClient()->prepare("UPDATE `pins` SET `start_at` = ?, `end_at` = ? WHERE `event_id` = ?;");
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
