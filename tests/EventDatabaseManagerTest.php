<?php

namespace Compucie\DatabaseTest;

use DateTime;
use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertSame;

class EventDatabaseManagerTest extends TestCase
{
    private TestableEventDatabaseManager $dbm;
    protected DbTestHelper $dbh;

    protected function setUp(): void
    {
        $env = parse_ini_file('.env', true);
        $this->dbm = new TestableEventDatabaseManager($env['event']);
        $this->dbm->createTables();
        $this->dbh = new DbTestHelper($this->dbm->client());

        $this->dbh->truncateTables(['pins']);
    }

    protected function tearDown(): void
    {
        $this->dbh->truncateTables(['pins']);
    }

    public function testInsertPin(): void
    {
        $date1 = new DateTime();
        $date2 = new DateTime();
        $date3 = new DateTime();
        $date4 = new DateTime();

        $this->dbm->insertPin(0);
        $this->dbm->insertPin(1, $date1);
        $this->dbm->insertPin(2, endAt: $date2);
        $this->dbm->insertPin(3, $date3, $date4);

        assertSame(4, $this->dbh->rowCount('pins'));
        assertSame(1, $this->dbh->rowCount(
            'pins', 'event_id = 0 AND pin_id = 1 AND start_at IS NOT NULL AND end_at IS NULL')
        );
        assertSame(1, $this->dbh->rowCount(
            'pins', 'event_id = 1 AND pin_id = 2 AND start_at = ? AND end_at IS NULL',[$date1->format('Y-m-d H:i:s')])
        );
        assertSame(1, $this->dbh->rowCount(
            'pins', 'event_id = 2 AND pin_id = 3 AND start_at IS NOT NULL AND end_at = ?',[$date2->format('Y-m-d H:i:s')])
        );
        assertSame(1, $this->dbh->rowCount(
            'pins', 'event_id = 3 AND pin_id = 4 AND start_at = ? AND end_at = ?',[$date3->format('Y-m-d H:i:s'), $date4->format('Y-m-d H:i:s')])
        );
    }

    public function testUpdatePin(): void
    {
        $date1 = new DateTime();
        $date2 = new DateTime();
        $date3 = new DateTime();
        $date4 = new DateTime();

        $this->dbm->insertPin(0);
        $this->dbm->insertPin(1, $date1);
        $this->dbm->insertPin(2, endAt: $date2);
        $this->dbm->insertPin(3, $date3, $date4);

        $date5 = new DateTime();
        $date6 = new DateTime();
        $date7 = new DateTime();
        $date8 = new DateTime();

        $this->dbm->updatePin(0);
        $this->dbm->updatePin(1, $date5);
        $this->dbm->updatePin(2, endAt: $date6);
        $this->dbm->updatePin(3, $date7, $date8);

        assertSame(4, $this->dbh->rowCount('pins'));
        assertSame(1, $this->dbh->rowCount(
            'pins', 'event_id = 0 AND pin_id = 1 AND start_at IS NOT NULL AND end_at IS NULL')
        );
        assertSame(1, $this->dbh->rowCount(
            'pins', 'event_id = 1 AND pin_id = 2 AND start_at = ? AND end_at IS NULL',[$date5->format('Y-m-d H:i:s')])
        );
        assertSame(1, $this->dbh->rowCount(
            'pins', 'event_id = 2 AND pin_id = 3 AND start_at IS NOT NULL AND end_at = ?',[$date6->format('Y-m-d H:i:s')])
        );
        assertSame(1, $this->dbh->rowCount(
            'pins', 'event_id = 3 AND pin_id = 4 AND start_at = ? AND end_at = ?',[$date7->format('Y-m-d H:i:s'), $date8->format('Y-m-d H:i:s')])
        );
    }

    public function testGetCurrentlyPinnedEventIds(): void
    {
        $date1 = new DateTime();
        $date2 = new DateTime();
        $date3 = new DateTime();
        $date4 = new DateTime();

        $this->dbm->insertPin(0);
        $this->dbm->insertPin(1, $date1);
        $this->dbm->insertPin(2, endAt: $date2);
        $this->dbm->insertPin(3, $date3, $date4);

        $eventIds = $this->dbm->getCurrentlyPinnedEventIds();
        assertSame(2, count($eventIds));
    }
}
