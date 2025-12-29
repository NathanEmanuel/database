<?php

namespace Compucie\Database\Sale;

use Compucie\Database\Sale\Model\Product;
use mysqli;
use mysqli_sql_exception;
use Throwable;

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
        if ($statement) {
            $statement->execute();
            $statement->close();
        }
    }

    /**
     * @param Product[] $products
     * @throws Throwable
     * @throws mysqli_sql_exception
     */
    public function updateProductsTable(array $products): void
    {
        if ($products === []) {
            return;
        }

        $sql = "
        INSERT INTO `products` (`product_id`, `product_name`, `unit_price`)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE
            `product_name` = VALUES(`product_name`),
            `unit_price` = VALUES(`unit_price`)
        ";

        $client = $this->getClient();
        $statement = $client->prepare($sql);
        if ($statement === false) {
            throw new mysqli_sql_exception($client->error);
        }

        $client->begin_transaction();

        try {
            foreach ($products as $product) {
                $unitPrice = $product->getUnitPriceCents() / 100;

                $id = $product->getId();
                $name = $product->getName();
                $statement->bind_param(
                    'isd',
                    $id,
                    $name,
                    $unitPrice
                );

                $statement->execute();
            }

            $client->commit();
        } catch (Throwable $e) {
            $client->rollback();
            $statement->close();
            throw $e;
        }

        $statement->close();
    }
}
