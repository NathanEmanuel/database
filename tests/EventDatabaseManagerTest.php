<?php

namespace Compucie\DatabaseTest;

use Compucie\Database\Event\EventDatabaseManager;
use DateTime;
use PHPUnit\Framework\TestCase;

class EventDatabaseManagerTest extends TestCase
{
    private EventDatabaseManager $dbm;

    protected function setUp(): void
    {
        $env = parse_ini_file(".env", true);
        $this->dbm = new EventDatabaseManager($env['event']);
    }

    private function getDatabaseManager(): EventDatabaseManager
    {
        return $this->dbm;
    }

    public function testInsertPin(): void
    {
        $this->getDatabaseManager()->insertPin(0);
        $this->getDatabaseManager()->insertPin(1, new DateTime);
        $this->getDatabaseManager()->insertPin(2, endAt: new DateTime);
        $this->getDatabaseManager()->insertPin(3, new DateTime, new DateTime);
    }

    public function testUpdatePin(): void
    {
        $this->getDatabaseManager()->updatePin(0);
        $this->getDatabaseManager()->updatePin(1, new DateTime);
        $this->getDatabaseManager()->updatePin(2, endAt: new DateTime);
        $this->getDatabaseManager()->updatePin(3, new DateTime, new DateTime);
    }

    public function testGetCurrentlyPinnedEventIds(): void
    {
        $eventIds = $this->getDatabaseManager()->getCurrentlyPinnedEventIds();
        $this->assertSame(2, count($eventIds));
    }
}
