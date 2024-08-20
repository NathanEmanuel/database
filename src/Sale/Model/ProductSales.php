<?php

namespace Compucie\Database\Sale\Model;

class ProductSales
{
    private array $data = array();
    private int $thisYear;

    public function __construct()
    {
        $this->thisYear = intval((new \DateTime)->format('Y'));
    }

    private function &getDataByWeek(string $key, int $productId, int $week, int $year = null): int|string
    {
        $year = $year ?? $this->thisYear;

        if (!key_exists($productId, $this->data)) {
            $this->data[$productId] = array();
        }

        if (!key_exists($year, $this->data[$productId])) {
            $this->data[$productId][$year] = array();
        }

        if (!key_exists($week, $this->data[$productId][$year])) {
            $this->data[$productId][$year][$week] = array();
        }

        return $this->data[$productId][$year][$week][$key];
    }

    public function setDataByWeek(string $key, int|string $data, int $productId, int $week, int $year = null): void
    {
        $year = $year ?? $this->thisYear;

        $this->data[$productId][$year][$week][$key] = $data;
    }

    public function &getDataByYear(int $productId, int $year = null): array
    {
        $year = $year ?? $this->thisYear;

        if (!key_exists($productId, $this->data)) {
            $this->data[$productId] = array();
        }

        if (!key_exists($year, $this->data[$productId])) {
            $this->data[$productId][$year] = array();
        }

        return $this->data[$productId][$year];
    }

    public function setDataByYear(array $data, int $productId, int $year): void
    {
        $this->data[$productId][$year] = $data;
    }

    public function asJson(): string
    {
        return json_encode($this->data);
    }


    // Quantity


    public function getQuantityByWeek(int $productId, int $week, int $year = null): int
    {
        return $this->getDataByWeek("quantity", $productId, $week, $year);
    }

    public function setQuantityByWeek(int $quantity, int $productId, int $week, int $year = null): void
    {
        $this->setDataByWeek("quantity", $quantity, $productId, $week, $year);
    }


    // Name


    public function getNameByWeek(int $productId, int $week, int $year = null): string
    {
        return $this->getDataByWeek("name", $productId, $week, $year);
    }

    public function setNameByWeek(string $name, int $productId, int $week, int $year = null): void
    {
        $this->setDataByWeek("name", $name, $productId, $week, $year);
    }


    // Unit price


    public function getUnitPriceByWeek(int $productId, int $week, int $year = null): int
    {
        return $this->getDataByWeek("unitPrice", $productId, $week, $year);
    }

    public function setUnitPriceByWeek(int $unitPrice, int $productId, int $week, int $year = null): void
    {
        $this->setDataByWeek("unitPrice", $unitPrice, $productId, $week, $year);
    }
}
