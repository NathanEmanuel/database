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
     * Return array of IDs of currently pinned events.
     * @return  int[]   $eventIds
     * @throws  mysqli_sql_exception
     */
    public function getCurrentlyPinnedEventIds(): array
    {
        $statement = $this->getClient()->prepare("SELECT `event_id` FROM events WHERE (NOW() BETWEEN start_at AND end_at) OR (NOW() > start_at AND end_at IS NULL);");
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
