<?php

namespace Compucie\Database\Sale\Model;

use JsonSerializable;

/**
 * Data structure:
 * 
 * productId
 *     year
 *         week
 *             quantity
 *             name
 *             unitPrice
 */
class ProductSales implements JsonSerializable
{
    private array $data = array();
    private int $thisYear;

    public function __construct()
    {
        $this->thisYear = intval((new \DateTime)->format('Y'));
    }


    // JsonSerializable

    public function jsonSerialize(): mixed
    {
        return json_encode($this->data);
    }


    // Main getters

    private function &getData(): array
    {
        return $this->data;
    }

    private function &getByProductId(int $productId)
    {
        $data = &$this->getData();
        if (!key_exists($productId, $data)) {
            $data[$productId] = array();
        }
        return $this->data[$productId];
    }

    public function &getDataByYear(int $productId, int $year = null): array
    {
        $year = $year ?? $this->thisYear;

        $data = &$this->getByProductId($productId);

        if (!key_exists($year, $data)) {
            $data[$year] = array();
        }

        return $data[$year];
    }

    public function &getDataByWeek(string $key, int $productId, int $week, int $year = null): int|string
    {
        $year = $year ?? $this->thisYear;

        $data = &$this->getDataByYear($productId, $year);

        if (!key_exists($week, $data)) {
            $data[$week] = array();
        }

        return $data[$week][$key];
    }


    // Main setters

    public function setDataByYear(array $data, int $productId, int $year): void
    {
        $this->data[$productId][$year] = $data;
    }

    public function setDataByWeek(string $key, int|string $data, int $productId, int $week, int $year = null): void
    {
        $year = $year ?? $this->thisYear;

        $dataByYear = &$this->getDataByYear($productId, $year);
        $dataByYear[$week][$key] = $data;
    }


    // Quantity getter/setter

    public function getQuantityByWeek(int $productId, int $week, int $year = null): int
    {
        return $this->getDataByWeek("quantity", $productId, $week, $year);
    }

    public function setQuantityByWeek(int $quantity, int $productId, int $week, int $year = null): void
    {
        $this->setDataByWeek("quantity", $quantity, $productId, $week, $year);
    }


    // Name getter/setter

    public function getNameByWeek(int $productId, int $week, int $year = null): string
    {
        return $this->getDataByWeek("name", $productId, $week, $year);
    }

    public function setNameByWeek(string $name, int $productId, int $week, int $year = null): void
    {
        $this->setDataByWeek("name", $name, $productId, $week, $year);
    }


    // Unit price getter/setter

    public function getUnitPriceByWeek(int $productId, int $week, int $year = null): int
    {
        return $this->getDataByWeek("unitPrice", $productId, $week, $year);
    }

    public function setUnitPriceByWeek(int $unitPrice, int $productId, int $week, int $year = null): void
    {
        $this->setDataByWeek("unitPrice", $unitPrice, $productId, $week, $year);
    }
}
