<?php

namespace Compucie\Database\Sale;

use mysqli;

trait PurchaseItemsTableManager
{
    protected abstract function getClient(): mysqli;

    protected function createPurchaseItemsTable(): void
    {
        $statement = $this->getClient()->prepare(
            "CREATE TABLE `purchase_items` (
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
}
