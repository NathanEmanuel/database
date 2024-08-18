<?php

namespace Compucie\Database\Sale\Model;

class ProductSales
{
    private array $data = array();

    public function __construct(
        private int $productId
    ) {}

    private function &getDataEntry(int $index): array
    {
        if (!key_exists($index, $this->data)) {
            $this->data[$index] = array();
        }
        return $this->data[$index];
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getQuantity(int $index): int
    {
        return $this->getDataEntry($index)['quantity'];
    }

    public function setQuantity(int $index, int $quantity): void
    {
        $this->getDataEntry($index)['quantity'] = $quantity;
    }

    public function getName(int $index): string
    {
        return $this->getDataEntry($index)['name'];
    }

    public function setName(int $index, string $name): void
    {
        $this->getDataEntry($index)['name'] = $name;
    }

    public function getUnitPrice(int $index): int
    {
        return $this->getDataEntry($index)['unitPrice'];
    }

    public function setUnitPrice(int $index, int $unitPrice): void
    {
        $this->getDataEntry($index)['unitPrice'] = $unitPrice;
    }
}
