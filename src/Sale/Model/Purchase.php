<?php

namespace Compucie\Database\Sale\Model;

use DateTime;

class Purchase
{
    private int $purchaseId;
    private ?DateTime $purchasedAt;
    private ?float $price;

    public function __construct(int $purchaseId, ?DateTime $purchasedAt, ?float $price)
    {
        $this->purchaseId = $purchaseId;
        $this->purchasedAt = $purchasedAt;
        $this->price = $price;
    }

    public function getPurchaseId(): int
    {
        return $this->purchaseId;
    }

    public function getPurchasedAt(): ?DateTime
    {
        return $this->purchasedAt;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }
}