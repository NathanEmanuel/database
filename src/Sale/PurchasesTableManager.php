<?php

namespace Compucie\Database\Sale;

use mysqli;

trait PurchasesTableManager
{
    protected abstract function getClient(): mysqli;

    protected function createPurchasesTable(): void
    {
        $statement = $this->getClient()->prepare(
            "CREATE TABLE IF NOT EXISTS `purchases` (
                `purchase_id` INT NOT NULL UNIQUE AUTO_INCREMENT,
                `purchased_at` DATETIME DEFAULT NOW(),
                `price` DECIMAL(10,2) DEFAULT NULL,
                PRIMARY KEY (`purchase_id`)
            );"
        );
        $statement->execute();
        $statement->close();
    }

    public function insertPurchase(): int
    {
        $statement = $this->getClient()->prepare("INSERT INTO `purchases` () VALUES ();");
        $statement->execute();
        $statement = $this->getClient()->prepare("SELECT LAST_INSERT_ID();");
        $statement->bind_result($purchaseId);
        $statement->execute();
        $statement->fetch();
        $statement->close();
        return $purchaseId;
    }
}
