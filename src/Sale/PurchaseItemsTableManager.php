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
            "CREATE TABLE IF NOT EXISTS `purchase_items` (
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

    public function selectProductSalesOfLastWeeks(array $productIds, int $weekCount, ?int $currentWeek = null): ProductSales
    {
        $currentWeek = $currentWeek ?? intval((new \DateTime())->format('W'));

        if (1 > $currentWeek || $currentWeek > 52) throw new WeekDoesNotExistException;
        if (count($productIds) <= 0 || $weekCount <= 0) return array();

        $weekDifference = $weekCount - 1;

        if ($weekDifference < $currentWeek) {
            // all weeks are in this year
            $firstWeekToRetrieve = $currentWeek - $weekCount;
            return $this->selectProductSalesByWeeks($productIds, range($firstWeekToRetrieve, $currentWeek));
        } else {
            // first week(s) are in previous year

            $firstWeekToRetrieve = 52 - ($weekDifference - $currentWeek);
            $thisYear = intval((new \DateTime())->format('Y'));

            $productSalesLastYear = $this->selectProductSalesByWeeks($productIds, range($firstWeekToRetrieve, 52), $thisYear - 1);
            $productSalesThisYear = $this->selectProductSalesByWeeks($productIds, range(1, $currentWeek));

            foreach ($productIds as $productId) {
                $productDataThisYear = $productSalesThisYear->getDataByYear($productId, $thisYear);
                $productSalesLastYear->setDataByYear($productDataThisYear, $productId, $thisYear);
            }

            return $productSalesLastYear;
        }
    }

    /**
     * Return the product sales data for the given products in the given weeks of the given year.
     *
     * @param   int[]   $productId      Product IDs.
     * @param   int[]   $weeks          Week numbers.
     * @param   int     $year           [Optional] The year in which the week number applies.
     * @return  ProductSales
     */
    public function selectProductSalesByWeeks(array $productIds, array $weeks, ?int $year = null): ProductSales
    {
        $productSales = new ProductSales();
        $statement = $this->getClient()->prepare(
            "SELECT SUM(`quantity`) FROM `purchase_items`
            WHERE `product_id` = ?
            AND `purchase_id` IN (SELECT `purchase_id` FROM `purchases` WHERE `purchased_at` BETWEEN ? AND ?);"
        );
        foreach ($productIds as $productId) {
            foreach ($weeks as $week) {
                $weekDates = self::getWeekDates($week, $year);
                $statement->bind_param("iss", $productId, $weekDates['start'], $weekDates['end']);
                $statement->bind_result($quantity);
                $statement->execute();
                $statement->fetch();
                $productSales->setQuantityByWeek($quantity ?? 0, $productId, $week, $year);
            }
        }
        $statement->close();
        return $productSales;
    }

    /**
     * Return an array with the first and last date of the given week in the optionally given year.
     * The current year will be used if the year is not given. The dates are represented as DateTime objects.
     * The first date has key 'start'. The last date has key 'end'.
     */
    private static function getWeekDates(int $week, ?int $year = null): array
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
