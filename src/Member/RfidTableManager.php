<?php

namespace Compucie\Database\Member;

use DateTime;
use mysqli;

trait RfidTableManager
{
    protected abstract function getClient(): mysqli;

    protected function createRfidTable(): void
    {
        $statement = $this->getClient()->prepare(
            "CREATE TABLE `rfid` (
                `card_id` VARCHAR(14) NOT NULL,
                `congressus_member_id` INT NOT NULL,
                `hashed_activation_token` VARCHAR(255) DEFAULT NULL,
                `activation_token_valid_until` DATETIME DEFAULT NULL,
                `is_email_confirmed` BOOLEAN NOT NULL DEFAULT FALSE,
                `last_used_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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
        $statement = $this->getClient()->prepare("SELECT COUNT(*) FROM `rfid` WHERE `card_id` = ? AND `is_email_confirmed` = TRUE");
        $statement->bind_param("s", $cardId);
        $statement->bind_result($count);
        $statement->execute();
        $statement->fetch();
        $statement->close();

        return $count > 0;
    }

    /**
     * Get hashed activation token info for a given card ID.
     * @param   string      $cardId     ID of the card
     * @return  array|null              Array with 'hashed_activation_token' and 'activation_token_valid_until' or null if not found
     * @throws  mysqli_sql_exception
     * @throws  ActivationTokenNotFoundException
     */
    public function getActivationTokenInfo(string $cardId): array
    {
        $statement = $this->getClient()->prepare("SELECT `hashed_activation_token`, `activation_token_valid_until` FROM `rfid` WHERE `card_id` = ?");
        $statement->bind_param("s", $cardId);
        $statement->bind_result($hashedActivationToken, $activationTokenValidUntil);
        $statement->execute();
        $statement->fetch();
        $statement->close();

        if (is_null($hashedActivationToken) || is_null($activationTokenValidUntil)) {
            throw new ActivationTokenNotFoundException();
        }

        return [
            'hashed_activation_token' => $hashedActivationToken,
            'activation_token_valid_until' => new DateTime($activationTokenValidUntil),
        ];
    }

    /**
     * Register a card by inserting the card ID and associated member ID.
     * @param   string      $cardId                 ID of the card
     * @param   int         $congressusMemberId     Congressus member ID
     * @param   string      $hashedActivationToken  Hashed activation token for email confirmation
     * @param   DateTime    $activationTokenValidUntil   Expiration date of the activation token
     * @param   bool        $isEmailConfirmed       Whether the member confirmed their registration
     * @throws  mysqli_sql_exception
     */
    public function insertRfid(string $cardId, int $congressusMemberId, string $hashedActivationToken, DateTime $activationTokenValidUntil, bool $isEmailConfirmed = FALSE): void
    {
        $statement = $this->getClient()->prepare("INSERT INTO `rfid` (`card_id`, `congressus_member_id`, `hashed_activation_token`, `activation_token_valid_until`, `is_email_confirmed`) VALUES (?, ?, ?, ?, ?)");
        $statement->bind_param("sissi", $cardId, $congressusMemberId, $hashedActivationToken, $activationTokenValidUntil->format("Y-m-d H:i:s"), $isEmailConfirmed);
        $statement->execute();
        $statement->close();
    }

    /**
     * Activate a card by setting is_email_confirmed to TRUE and clearing activation token fields.
     * @param   string      $cardId     ID of the card to activate
     * @throws  mysqli_sql_exception
     */
    public function activateCard(string $cardId): void
    {
        $statement = $this->getClient()->prepare("UPDATE `rfid` SET `is_email_confirmed` = TRUE, `hashed_activation_token` = NULL, `activation_token_valid_until` = NULL WHERE `card_id` = ?");
        $statement->bind_param("s", $cardId);
        $statement->execute();
        $statement->close();
    }

    /**
     * Update the last used timestamp of a card to the current time.
     * @param   string      $cardId     ID of the card to update
     * @throws  mysqli_sql_exception
     */
    public function updateLastUsedAt(string $cardId): void
    {
        $statement = $this->getClient()->prepare("UPDATE `rfid` SET `last_used_at` = CURRENT_TIMESTAMP WHERE `card_id` = ?");
        $statement->bind_param("s", $cardId);
        $statement->execute();
        $statement->close();
    }

    /**
     * Delete a member's activated card registrations.
     * @param   int     $congressusMemberId     Member whose registrations to delete
     * @throws  mysqli_sql_exception
     */
    public function deleteMembersRfidRegistrations(int $congressusMemberId): void
    {
        $statement = $this->getClient()->prepare("DELETE FROM `rfid` WHERE `congressus_member_id` = ? AND `is_email_confirmed` = TRUE");
        $statement->bind_param("i", $congressusMemberId);
        $statement->execute();
        $statement->close();
    }
}

class ActivationTokenNotFoundException extends \Exception {}
