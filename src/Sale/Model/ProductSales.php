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
 */
class ProductSales implements JsonSerializable
{
    private array $data = array();
    private int $presentYear;

    public function __construct()
    {
        $this->presentYear = intval((new \DateTime)->format('Y'));
    }


    // JsonSerializable

    public function jsonSerialize(): mixed
    {
        return $this->data;
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

    public function &getDataByYear(int $productId, ?int $year = null): array
    {
        $year = $year ?? $this->presentYear;

        $data = &$this->getByProductId($productId);

        if (!key_exists($year, $data)) {
            $data[$year] = array();
        }

        return $data[$year];
    }

    public function &getDataByWeek(string $key, int $productId, int $week, ?int $year = null): int|string
    {
        $year = $year ?? $this->presentYear;

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

    public function setDataByWeek(string $key, int|string $data, int $productId, int $week, ?int $year = null): void
    {
        $year = $year ?? $this->presentYear;

        $dataByYear = &$this->getDataByYear($productId, $year);
        $dataByYear[$week][$key] = $data;
    }


    // Quantity getter/setter

    public function getQuantityByWeek(int $productId, int $week, ?int $year = null): int
    {
        return $this->getDataByWeek("quantity", $productId, $week, $year);
    }

    public function setQuantityByWeek(int $quantity, int $productId, int $week, ?int $year = null): void
    {
        $this->setDataByWeek("quantity", $quantity, $productId, $week, $year);
    }


    // Merge

    public static function merge(ProductSales $first, ProductSales $second): ProductSales
    {
        $result = new ProductSales();
        foreach ($first->getData() as $productId => $productData) {
            foreach ($productData as $year => $productDataYear) {
                $result->setDataByYear($productDataYear + $second->getDataByYear($productId, $year), $productId, $year);
            }
        }
        foreach ($second->getData() as $productId => $productData) {
            foreach ($productData as $year => $productDataYear) {
                $result->setDataByYear($productDataYear + $first->getDataByYear($productId, $year), $productId, $year);
            }
        }
        return $result;
    }
}
