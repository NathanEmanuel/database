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
        $productId = 1;
        $week = 33;
        $dbm = $this->getDbm();
        $productSales = $dbm->selectProductSalesByWeek([$productId], [$week]);
        assertSame($productId, $productSales[$productId]->getProductId());
        assertSame(15, $productSales[$productId]->getQuantityByWeek($week));
        assertSame("3 Cookies", $productSales[$productId]->getNameByWeek($week));
        assertSame(69, $productSales[$productId]->getUnitPriceByWeek($week));
    }

    public function testSelectProductSalesOfLastWeeks(): void
    {
        $productId = 1;
        $currentWeek = 34;
        $weekCount = 8;

        $dbm = $this->getDbm();

        $productSales = $dbm->selectProductSalesOfLastWeeks([$productId], $weekCount, $currentWeek);
        assertSame(1, count($productSales));
        assertSame(15, $productSales[$productId]->getQuantityByWeek(33));
        assertSame("3 Cookies", $productSales[$productId]->getNameByWeek(33));
        assertSame(69, $productSales[$productId]->getUnitPriceByWeek(33));

        $currentWeek = 4;
        $productSales = $dbm->selectProductSalesOfLastWeeks([$productId], $weekCount, $currentWeek);
        assertSame(1, count($productSales));
        assertSame(3, $productSales[$productId]->getQuantityByWeek(52, 2023));
        assertSame("3 Cookies", $productSales[$productId]->getNameByWeek(52, 2023));
        assertSame(69, $productSales[$productId]->getUnitPriceByWeek(52, 2023));
    }
}
