<?php

namespace Compucie\Database\Sale;

use Compucie\Database\Sale\Model\ProductSales;
use mysqli;

trait PurchaseItemsTableManager
{
    protected abstract function getClient(): mysqli;

    protected function createPurchaseItemsTable(): void
    {
        $statement = $this->getClient()->prepare(
            "CREATE TABLE `purchase_items` (
                `purchase_item_id` INT NOT NULL UNIQUE AUTO_INCREMENT,
                `purchase_id` INT NOT NULL,
                `product_id` INT NOT NULL,
                `quantity` INT NOT NULL DEFAULT 1,
                `name` VARCHAR(255) DEFAULT NULL,
                `unit_price` DECIMAL(10,2) DEFAULT NULL,
                PRIMARY KEY (`purchase_item_id`),
                FOREIGN KEY (`purchase_id`) REFERENCES purchases(`purchase_id`)
            );"
        );
        $statement->execute();
        $statement->close();
    }

    public function insertPurchaseItem(int $purchaseId, int $productId, int $quantity = 1, ?string $name = null, ?float $unitPrice = null): void
    {
        $statement = $this->getClient()->prepare("INSERT INTO `purchase_items` (`purchase_id`, `product_id`, `quantity`, `name`, `unit_price`) VALUES (?, ?, ?, ?, ?);");
        $statement->bind_param("iiisd", $purchaseId, $productId, $quantity, $name, $unitPrice);
        $statement->execute();
        $statement->close();
    }

    public function selectProductSalesOfLastWeeks(array $productIds, int $weekCount, int $currentWeek = null): array
    {
        if (1 > $currentWeek || $currentWeek > 52) throw new WeekDoesNotExistException;
        if (count($productIds) <= 0 || $weekCount <= 0) return array();

        $weekDifference = $weekCount - 1;
        $currentWeek = $currentWeek ?? intval((new \DateTime())->format('W'));

        if ($weekDifference < $currentWeek) {
            // all weeks are in this year
            $firstWeekToRetrieve = $currentWeek - $weekCount;
            return $this->selectProductSalesByWeek($productIds, range($firstWeekToRetrieve, $currentWeek));
        } else {
            // first week(s) are in previous year
            $firstWeekToRetrieve = 52 - ($weekDifference - $currentWeek);
            $thisYear = intval((new \DateTime())->format('Y'));
            $productSalesLastYear = $this->selectProductSalesByWeek($productIds, range($firstWeekToRetrieve, 52), $thisYear - 1);
            $productSalesThisYear = $this->selectProductSalesByWeek($productIds, range(1, $currentWeek));
            
            // merge this year into last year and return result
            foreach ($productIds as $id) {
                $dataThisYear = $productSalesThisYear[$id]->getDataByYear();
                $productSalesLastYear[$id]->setDataByYear($dataThisYear, $thisYear);
            }
            return $productSalesLastYear;
        }
    }

    /**
     * Return the product sales data for the given products in the given weeks of the given year.
     *
     * @param   int[]   $productIds     Array of product IDs.
     * @param   int[]   $weeks          Array of week numbers.
     * @param   int     $year           [Optional] The year in which the week numbers apply.
     * @return  Model\ProductSales[]
     */
    public function selectProductSalesByWeek(array $productIds, array $weeks, int $year = null): array
    {
        $statement = $this->getClient()->prepare("SELECT SUM(`quantity`), `name`, `unit_price` FROM `purchase_items` WHERE `product_id` = ? AND `purchase_id` IN (SELECT `purchase_id` FROM `purchases` WHERE `purchased_at` BETWEEN ? AND ?);");
        $productSalesArray = array();
        foreach ($productIds as $productId) {
            $productSales = new ProductSales($productId);
            $productSalesArray[$productId] = $productSales;
            foreach ($weeks as $week) {
                $weekDates = self::getWeekDates($week, $year);
                $statement->bind_param("iss", $productId, $weekDates['start'], $weekDates['end']);
                $statement->bind_result($quantity, $name, $unitPrice);
                $statement->execute();
                $statement->fetch();
                $productSales->setQuantityByWeek(($quantity ?? 0), $week, $year);
                $productSales->setNameByWeek(($name ?? 0), $week, $year);
                $productSales->setUnitPriceByWeek((($unitPrice ?? 0) * 100), $week, $year);
            }
        }
        $statement->close();
        return $productSalesArray;
    }

    private static function getWeekDates(int $week, int $year = null): array
    {
        $year = $year ?? intval(date("Y"));
        $dto = new \DateTime();
        $dto->setISODate($year, $week);
        $dates['start'] = $dto->format('Y-m-d');
        $dto->modify('+6 days');
        $dates['end'] = $dto->format('Y-m-d');
        return $dates;
    }
}

class WeekDoesNotExistException extends \Exception {}
