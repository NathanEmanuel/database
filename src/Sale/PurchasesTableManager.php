<?php

namespace Compucie\Database\Sale;

use Compucie\Database\Sale\Model\Purchase;
use DateTime;
use Exception;
use mysqli;
use mysqli_sql_exception;
use function Compucie\Database\safeDateTime;

trait PurchasesTableManager
{
    protected abstract function getClient(): mysqli;

    /**
     * @throws  mysqli_sql_exception
     */
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
        if ($statement){
            $statement->execute();
            $statement->close();
        }
    }

    /**
     * @throws  mysqli_sql_exception
     * @throws Exception
     */
    public function getPurchase(int $purchaseId): Purchase
    {
        $row = $this->executeReadOne(
            "SELECT *
            FROM `purchases`
            WHERE `purchase_id` = ?",
            [$purchaseId],
            "i"
        );

        if ($row === null) {
            throw new Exception("Purchase $purchaseId not found.");
        }

        return new Purchase(
            (int)$row['purchase_id'],
            safeDateTime((string)$row['purchased_at']),
            (float)$row['price']
        );
    }

    /**
     * @throws  mysqli_sql_exception
     * @throws Exception
     */
    public function insertPurchase(): int
    {
        $id = $this->executeCreate('purchases', [], [], '');

        if ($id === -1) {
            throw new Exception('Failed to create purchase');
        }

        return $id;
    }

    public function updatePurchase(
        int $purchaseId,
        ?DateTime $purchasedAt = null,
        ?float $price = null,
        bool $clearPurchaseAt = false,
        bool $clearPrice = false
    ): bool {
        $fields = [];
        $params = [];
        $types  = '';

        if ($clearPurchaseAt) {
            $fields[] = 'purchased_at = NULL';
        } elseif ($purchasedAt !== null) {
            $fields[] = 'purchased_at = ?';
            $params[] = $purchasedAt->format(self::SQL_DATETIME_FORMAT);
            $types   .= 's';
        }

        if ($clearPrice) {
            $fields[] = 'price = NULL';
        } elseif ($price !== null) {
            $fields[] = 'price = ?';
            $params[] = $price;
            $types   .= 'd';
        }

        return $this->executeUpdate('purchases', 'purchase_id', $purchaseId, $fields, $params, $types);
    }
}
