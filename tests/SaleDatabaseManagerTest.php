<?php

namespace Compucie\DatabaseTest;

use Compucie\Database\Sale\SaleDatabaseManager;
use PHPUnit\Framework\TestCase;

use function PHPUnit\Framework\assertGreaterThan;

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

    public function testInsertPurchase(): void
    {
        $purchaseId = $this->getDbm()->insertPurchase();
        assertGreaterThan(0, $purchaseId);
    }

    public function testInsertPurchaseItem(): void
    {
        $dbm = $this->getDbm();
        $dbm->insertPurchaseItem(1, 1);
        $dbm->insertPurchaseItem(1, 1, 2);
        $dbm->insertPurchaseItem(1, 1, 3, "3 Cookies");
        $dbm->insertPurchaseItem(1, 1, 4, unitPrice: 0.69);
        $dbm->insertPurchaseItem(1, 1, 5, "3 Cookies", 0.69);
    }
}
