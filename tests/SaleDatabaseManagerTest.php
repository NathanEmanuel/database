<?php

namespace Compucie\DatabaseTest;

use Compucie\Database\Sale\SaleDatabaseManager;
use PHPUnit\Framework\TestCase;

use function PHPUnit\Framework\assertGreaterThan;
use function PHPUnit\Framework\assertSame;

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

    public function testSelectProductSalesByWeek(): void
    {
        $week = 33;
        $dbm = $this->getDbm();
        $productSales = $dbm->selectProductSalesByWeek([1, 2], [$week]);

        assertSame(15, $productSales->getQuantityByWeek(1, $week));
        assertSame("3 Cookies", $productSales->getNameByWeek(1, $week));
        assertSame(69, $productSales->getUnitPriceByWeek(1, $week));

        assertSame(3, $productSales->getQuantityByWeek(2, $week, 2024));
        assertSame("3 Cookies", $productSales->getNameByWeek(2, $week));
        assertSame(69, $productSales->getUnitPriceByWeek(2, $week));
    }

    public function testSelectProductSalesOfLastWeeks(): void
    {
        $currentWeek = 42;
        $weekCount = 8;

        $dbm = $this->getDbm();

        $productSales = $dbm->selectProductSalesOfLastWeeks([1, 2], $weekCount, $currentWeek);
        assertSame(15, $productSales->getQuantityByWeek(1, $currentWeek - 1));
        assertSame("3 Cookies", $productSales->getNameByWeek(1, $currentWeek - 1));
        assertSame(69, $productSales->getUnitPriceByWeek(1, $currentWeek - 1));

        assertSame(3, $productSales->getQuantityByWeek(2, $currentWeek - 1, 2024));
        assertSame("3 Cookies", $productSales->getNameByWeek(2, $currentWeek - 1));
        assertSame(69, $productSales->getUnitPriceByWeek(2, $currentWeek - 1));

        $currentWeek = 4;
        $productSales = $dbm->selectProductSalesOfLastWeeks([1], $weekCount, $currentWeek);
        assertSame(3, $productSales->getQuantityByWeek(1, 52, 2023));
        assertSame("3 Cookies", $productSales->getNameByWeek(1, 52, 2023));
        assertSame(69, $productSales->getUnitPriceByWeek(1, 52, 2023));
    }
}
