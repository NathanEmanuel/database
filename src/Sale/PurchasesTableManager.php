<?php

namespace Compucie\Database\Sale;

trait PurchasesTableManager
{
    protected function createPurchasesTable(): void
    {
        $statement = $this->getClient()->prepare(
            "CREATE TABLE `purchases` (
                `purchase_id` INT NOT NULL UNIQUE AUTO_INCREMENT,
                `purchased_at` DATETIME DEFAULT NOW(),
                `price` DECIMAL(10,2) DEFAULT NULL,
                PRIMARY KEY (`purchase_id`)
            );"
        );
        $statement->execute();
        $statement->close();
    }
}
