<?php

namespace Compucie\Database\Sale;

use Compucie\Database\Sale\Model\Product;
use mysqli;

trait ProductsTableManager
{
    protected abstract function getClient(): mysqli;

    protected function createProductsTable(): void
    {
        $statement = $this->getClient()->prepare(
            "CREATE TABLE IF NOT EXISTS products (
                product_id INT NOT NULL AUTO_INCREMENT,
                product_name VARCHAR(255) NOT NULL,
                unit_price DECIMAL(10,2) DEFAULT NULL,
                PRIMARY KEY (product_id)
            );"
        );
        $statement->execute();
        $statement->close();
    }

    /**
     * @param Product[] $products
     */
    public function updateProductsTable(array $products): void
    {
        $statement = $this->getClient()->prepare(
            "INSERT INTO products (product_id, product_name, unit_price)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE
            product_name = VALUES(product_name),
            unit_price = VALUES(unit_price);
            "
        );

        foreach ($products as $product) {
            $unitPriceCents = $product->getUnitPriceCents() / 100;
            $statement->bind_param(
                "isd",
                $product->getId(),
                $product->getName(),
                $unitPriceCents,
            );
            $statement->execute();
        }

        $statement->close();
    }
}
