<?php

namespace Compucie\DatabaseTest;

use Compucie\Database\Pin\PinDatabaseManager;
use DateTime;
use PHPUnit\Framework\TestCase;

class PinDatabaseManagerTest extends TestCase
{
    private PinDatabaseManager $dbm;

    protected function setUp(): void
    {
        $env = parse_ini_file(".env", true);
        $this->dbm = new PinDatabaseManager($env['pin']);
    }

    private function getDatabaseManager(): PinDatabaseManager
    {
        return $this->dbm;
    }

    function testInsertEventPin(): void
    {
        $this->getDatabaseManager()->insertEventPin(0);
        $this->getDatabaseManager()->insertEventPin(1, new DateTime);
        $this->getDatabaseManager()->insertEventPin(2, endAt: new DateTime);
        $this->getDatabaseManager()->insertEventPin(3, new DateTime, new DateTime);
    }

    function testUpdateEventPin(): void
    {
        $this->getDatabaseManager()->updateEventPin(0);
        $this->getDatabaseManager()->updateEventPin(1, new DateTime);
        $this->getDatabaseManager()->updateEventPin(2, endAt: new DateTime);
        $this->getDatabaseManager()->updateEventPin(3, new DateTime, new DateTime);
    }
}
