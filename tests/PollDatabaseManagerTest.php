<?php

namespace Compucie\DatabaseTest;

use Compucie\Database\Poll\PollDatabaseManager;
use PHPUnit\Framework\TestCase;

class PollDatabaseManagerTest extends TestCase
{
    private PollDatabaseManager $dbm;

    protected function setUp(): void
    {
        $env = parse_ini_file(".env", true);
        $this->dbm = new PollDatabaseManager($env['poll']);
    }

    private function getDbm(): PollDatabaseManager
    {
        return $this->dbm;
    }

    function testGetMostRecentlyExpiredPoll(): void
    {
        $poll = $this->getDbm()->getMostRecentlyExpiredPoll();
        $this->assertSame(1, $poll->getId());
    }
}
