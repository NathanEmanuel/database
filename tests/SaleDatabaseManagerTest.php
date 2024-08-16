<?php

namespace Compucie\DatabaseTest;

use Compucie\Database\Sale\SaleDatabaseManager;
use PHPUnit\Framework\TestCase;

class SaleDatabaseManagerTest extends TestCase
{
    private SaleDatabaseManager $dbm;

    protected function setUp(): void
    {
        $env = parse_ini_file(".env", true);
        $this->dbm = new SaleDatabaseManager($env['sale']);
    }

    private function getDbm(): SaleDatabaseManager
    {
        return $this->dbm;
    }

    public function testCreateTables(): void
    {
        $this->getDbm()->createTables();
    }
}
