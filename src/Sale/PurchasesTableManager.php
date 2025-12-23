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
        $purchasedAt = null;
        $price = null;

        $statement = $this->getClient()->prepare("SELECT * FROM purchases WHERE purchase_id = ?");
        if ($statement) {
            $statement->bind_param("i", $purchaseId);
            $statement->execute();
            $statement->bind_result($purchaseId, $purchasedAt, $price);

            if (!$statement->fetch()) {
                $statement->close();
                throw new Exception("Purchase $purchaseId not found.");
            }
            $statement->close();
        }

        return new Purchase($purchaseId,safeDateTime($purchasedAt),$price);
    }

    /**
     * @throws  mysqli_sql_exception
     */
    public function insertPurchase(): int
    {
        $purchaseId = 0;

        $statement = $this->getClient()->prepare("INSERT INTO `purchases` () VALUES ();");
        if ($statement) {
            $statement->execute();
            $statement = $this->getClient()->prepare("SELECT LAST_INSERT_ID();");
            if ($statement) {
                $statement->bind_result($purchaseId);
                $statement->execute();
                $statement->fetch();
                $statement->close();
            }
        }

        return $purchaseId;
    }

    public function updatePurchase(int $purchaseId, ?DateTime $purchasedAt = null, ?float $price = null, bool $clearPurchaseAt = false, bool $clearPrice = false): int {
        $fields = [];
        $params = [];
        $types  = '';

        // purchased_at
        if ($clearPurchaseAt) {
            $fields[] = 'purchased_at = NULL';
        } elseif ($purchasedAt !== null) {
            $fields[] = 'purchased_at = ?';
            $params[] = $purchasedAt->format(self::SQL_DATETIME_FORMAT);
            $types   .= 's';
        }

        // price
        if ($clearPrice) {
            $fields[] = 'price = NULL';
        } elseif ($price !== null) {
            $fields[] = 'price = ?';
            $params[] = $price;
            $types   .= 'd';
        }

        if ($fields === []) {
            return $purchaseId;
        }

        $sql = sprintf(
            'UPDATE purchases SET %s WHERE purchase_id = ?',
            implode(', ', $fields)
        );

        $statement = $this->getClient()->prepare($sql);

        if ($statement) {
            if ($params !== []) {
                $params[] = $purchaseId;
                $types   .= 'i';
                $statement->bind_param($types, ...$params);
            } else {
                $statement->bind_param('i', $purchaseId);
            }

            $statement->execute();
            $statement->close();
        }

        return $purchaseId;
    }
}
