<?php

namespace Compucie\Database\Sale\Model;

class ProductSales
{
    private array $data = array();
    private int $thisYear;

    public function __construct(
        private int $productId
    ) {
        $this->thisYear = intval((new \DateTime)->format('Y'));
    }

    private function &getDataByWeek(int $week, int $year = null): array
    {
        $year = $year ?? $this->thisYear;

        if (!key_exists($year, $this->data)) {
            $this->data[$year] = array();
        }

        if (!key_exists($week, $this->data[$year])) {
            $this->data[$year][$week] = array();
        }

        return $this->data[$year][$week];
    }

    public function &getDataByYear(int $year = null): array
    {
        $year = $year ?? $this->thisYear;

        if (!key_exists($year, $this->data)) {
            $this->data[$year] = array();
        }

        return $this->data[$year];
    }

    public function setDataByYear(array $data, int $year): void
    {
        $this->data[$year] = $data;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getQuantityByWeek(int $week, int $year = null): int
    {
        return $this->getDataByWeek($week, $year)['quantity'];
    }

    public function setQuantityByWeek(int $quantity, int $week, int $year = null): void
    {
        $this->getDataByWeek($week, $year)['quantity'] = $quantity;
    }

    public function getNameByWeek(int $week, int $year = null): string
    {
        return $this->getDataByWeek($week, $year)['name'];
    }

    public function setNameByWeek(string $name, int $week, int $year = null): void
    {
        $this->getDataByWeek($week, $year)['name'] = $name;
    }

    public function getUnitPriceByWeek(int $week, int $year = null): int
    {
        return $this->getDataByWeek($week, $year)['unitPrice'];
    }

    public function setUnitPriceByWeek(int $unitPrice, int $week, int $year = null): void
    {
        $this->getDataByWeek($week, $year)['unitPrice'] = $unitPrice;
    }
}
