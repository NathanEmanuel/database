<?php

namespace Compucie\Database\Sale\Model;

use JsonSerializable;

class Product implements JsonSerializable
{
    private int $id;
    private string $name;
    private ?int $unitPriceCents;

    public function __construct(int $id, string $name, ?int $unitPriceCents = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->unitPriceCents = $unitPriceCents;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getUnitPriceCents(): ?int
    {
        return $this->unitPriceCents;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'unitPriceCents' => $this->unitPriceCents,
        ];
    }
}
