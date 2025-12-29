<?php

namespace Compucie\Database\Event;

use Compucie\Database\DatabaseManager;
use DateTime;
use InvalidArgumentException;
use mysqli_sql_exception;

class EventDatabaseManager extends DatabaseManager
{
    public function createTables(): void
    {
        $statement = $this->getClient()->prepare(
            "CREATE TABLE IF NOT EXISTS `pins` (
                `pin_id` INT NOT NULL AUTO_INCREMENT UNIQUE,
                `event_id` INT NOT NULL,
                `start_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `end_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (pin_id)
            );"
        );
        if ($statement) {
            $statement->execute();
            $statement->close();
        }
    }

    /**
     * Return array of IDs of currently pinned events.
     * @return  int[]   $eventIds
     * @throws  mysqli_sql_exception
     */
    public function getCurrentlyPinnedEventIds(): array
    {
        $rows = $this->executeReadAll(
            "SELECT `event_id`
         FROM `pins`
         WHERE (NOW() BETWEEN `start_at` AND `end_at`)
            OR (`start_at` <= NOW() AND `end_at` IS NULL)"
        );

        return array_map(
            static fn(array $row): int => (int)$row['event_id'],
            $rows
        );
    }

    /**
     * Insert an event pin.
     * @throws  mysqli_sql_exception
     * @throws InvalidArgumentException
     */
    public function insertPin(int $eventId, DateTime $startAt = null, DateTime $endAt = null): int
    {
        if ($endAt !== null && $endAt < ($startAt ?? new DateTime())) {
            throw new InvalidArgumentException('endAt must be after startAt');
        }

        $start = ($startAt ?? new DateTime())
            ->format(self::SQL_DATETIME_FORMAT);

        $end = $endAt?->format(self::SQL_DATETIME_FORMAT);

        return $this->executeCreate(
            'pins',
            ['`event_id`', '`start_at`', '`end_at`'],
            [$eventId, $start, $end],
            'iss'
        );
    }

    /**
     * Update an event pin.
     * @throws  mysqli_sql_exception
     */
    public function updatePin(int $eventId, ?DateTime $startAt = null, ?DateTime $endAt = null): bool
    {
        if ($endAt !== null && $endAt < ($startAt ?? new DateTime())) {
            throw new InvalidArgumentException('endAt must be after startAt');
        }

        $start = ($startAt ?? new DateTime())
            ->format(self::SQL_DATETIME_FORMAT);

        $end = $endAt?->format(self::SQL_DATETIME_FORMAT);

        return $this->executeUpdate(
            'pins',
            'event_id',
            $eventId,
            [
                '`start_at` = ?',
                '`end_at` = ?',
            ],
            [$start, $end],
            'ss'
        );
    }
}
