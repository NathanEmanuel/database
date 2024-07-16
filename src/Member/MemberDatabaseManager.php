<?php

namespace Compucie\Database\Member;

use Compucie\Database\DatabaseManager;
use Exception;

class MemberDatabaseManager extends DatabaseManager
{
    public function __construct(array $config)
    {
        parent::__construct($config);
    }

    public function createTables()
    {
        $statement = $this->getClient()->prepare(
            "CREATE TABLE `rfid` (
                `card_id` VARCHAR(14) NOT NULL,
                `congressus_member_id` INT NOT NULL,
                `is_email_confirmed` BOOLEAN NOT NULL DEFAULT FALSE,
                PRIMARY KEY (`card_id`)
            );"
        );
        $statement->execute();
        $statement->close();
    }

    /**
     * Return the Congressus member ID of the member who registered the given card.
     * @param   string      $cardId     ID of the registered card
     * @return  int                     Congressus member ID
     * @throws  mysqli_sql_exception
     * @throws  CardNotRegisteredException
     */
    public function getCongressusMemberIdFromCardId(string $cardId): int
    {
        $statement = $this->getClient()->prepare("SELECT `congressus_member_id` FROM `rfid` WHERE `card_id` = ?");
        $statement->bind_param("s", $cardId);
        $statement->bind_result($congressusMemberId);
        $statement->execute();
        $statement->fetch();
        $statement->close();

        if (is_null($congressusMemberId)) {
            throw new CardNotRegisteredException;
        }

        return $congressusMemberId;
    }

    /**
     * Return whether the given card is registered.
     * @param   string      $cardId     ID of the card
     * @return  bool                    Whether the card is registered
     * @throws  mysqli_sql_exception
     */
    public function isRfidCardRegistered(string $cardId): bool
    {
        $statement = $this->getClient()->prepare("SELECT COUNT(*) FROM `rfid` WHERE `card_id` = ?");
        $statement->bind_param("s", $cardId);
        $statement->bind_result($count);
        $statement->execute();
        $statement->fetch();
        $statement->close();

        return $count > 0;
    }
    
    /**
     * Register a card by inserting the card ID and associated member ID.
     * @param   string      $cardId                 ID of the card
     * @param   int         $congressusMemberId     Congressus member ID
     * @param   bool        $isEmailConfirmed       Whether the member confirmed their registration
     * @throws  mysqli_sql_exception
     */
    public function insertRfid(string $cardId, int $congressusMemberId, bool $isEmailConfirmed = FALSE): void
    {
        $statement = $this->getClient()->prepare("INSERT INTO `rfid` (`card_id`, `congressus_member_id`, `is_email_confirmed`) VALUES (?, ?, ?)");
        $statement->bind_param("sii", $cardId, $congressusMemberId, intval($isEmailConfirmed));
        $statement->execute();
        $statement->close();
    }

    /**
     * Delete a member's card registrations.
     * @param   int     $congressusMemberId     Member whose registrations to delete
     * @throws  mysqli_sql_exception
     */
    public function deleteMembersRfidRegistrations(int $congressusMemberId): void
    {
        $statement = $this->getClient()->prepare("DELETE FROM `rfid` WHERE `congressus_member_id` = ?");
        $statement->bind_param("i", intval($congressusMemberId));
        $statement->execute();
        $statement->close();
    }
}

class CardNotRegisteredException extends Exception
{
}
