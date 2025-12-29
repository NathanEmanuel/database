<?php

namespace Compucie\Database\Member;

use Compucie\Database\Member\Exceptions\ActivationTokenNotFoundException;
use Compucie\Database\Member\Exceptions\CardNotRegisteredException;
use DateTime;
use mysqli;
use mysqli_sql_exception;

trait RfidTableManager
{
    protected abstract function getClient(): mysqli;

    protected function createRfidTable(): void
    {
        $statement = $this->getClient()->prepare(
            "CREATE TABLE IF NOT EXISTS `rfid` (
                `card_id` VARCHAR(14) NOT NULL,
                `congressus_member_id` INT NOT NULL,
                `hashed_activation_token` VARCHAR(255) DEFAULT NULL,
                `activation_token_valid_until` DATETIME DEFAULT NULL,
                `is_email_confirmed` BOOLEAN NOT NULL DEFAULT FALSE,
                `last_used_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`card_id`)
            );"
        );
        if ($statement){
            $statement->execute();
            $statement->close();
        }
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
        $row = $this->executeReadOne(
            "SELECT `congressus_member_id`
         FROM `rfid`
         WHERE `card_id` = ?",
            [$cardId],
            "s"
        );

        if ($row === null || (int)$row['congressus_member_id'] === 0) {
            throw new CardNotRegisteredException();
        }

        return (int)$row['congressus_member_id'];
    }

    /**
     * Return whether the given card is activated.
     * @param   string      $cardId     ID of the card
     * @return  bool                    Whether the card is activated
     * @throws  mysqli_sql_exception
     */
    public function isRfidCardActivated(string $cardId): bool
    {
        return $this->executeReadOne(
            "SELECT 1
            FROM `rfid`
            WHERE `card_id` = ?
                AND `is_email_confirmed` = TRUE
            LIMIT 1",
            [$cardId],
            "s"
        ) !== null;
    }


    /**
     * Get hashed activation token info for a given card ID.
     * @param string $cardId ID of the card
     * @return  array              Array with 'hashed_activation_token' and 'activation_token_valid_until' or null if not found
     * @throws  mysqli_sql_exception
     * @throws  ActivationTokenNotFoundException
     * @throws Exception
     */
    public function getActivationTokenInfo(string $cardId): array
    {
        $row = $this->executeReadOne(
            "SELECT `hashed_activation_token`, `activation_token_valid_until`
            FROM `rfid`
            WHERE `card_id` = ?",
            [$cardId],
            "s"
        );

        if (
            $row === null ||
            $row['hashed_activation_token'] === null ||
            $row['activation_token_valid_until'] === null
        ) {
            throw new ActivationTokenNotFoundException();
        }

        return [
            'hashed_activation_token' => (string)$row['hashed_activation_token'],
            'activation_token_valid_until' => new DateTime(
                (string)$row['activation_token_valid_until']
            ),
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
    public function insertRfid(string $cardId, int $congressusMemberId, string $hashedActivationToken, DateTime $activationTokenValidUntil, bool $isEmailConfirmed = false): bool
    {
        return $this->executeCreate(
                'rfid',
                ['`card_id`','`congressus_member_id`','`hashed_activation_token`','`activation_token_valid_until`','`is_email_confirmed`'],
                [$cardId, $congressusMemberId, $hashedActivationToken, $activationTokenValidUntil->format(self::SQL_DATETIME_FORMAT), (int)$isEmailConfirmed],
                'sissi'
            ) !== -1;
    }

    /**
     * Activate a card by setting is_email_confirmed to TRUE and clearing activation token fields.
     * @param   string      $cardId     ID of the card to activate
     * @throws  mysqli_sql_exception
     */
    public function activateCard(string $cardId): bool
    {
        return $this->executeUpdate(
            'rfid',
            'card_id',
            $cardId,
            ['`is_email_confirmed` = TRUE','`hashed_activation_token` = NULL','`activation_token_valid_until` = NULL'],
        );
    }

    /**
     * Update the last used timestamp of a card to the current time.
     * @param   string      $cardId     ID of the card to update
     * @throws  mysqli_sql_exception
     */
    public function updateLastUsedAt(string $cardId): bool
    {
        return $this->executeUpdate(
            'rfid',
            'card_id',
            $cardId,
            ['`last_used_at` = CURRENT_TIMESTAMP']
        );
    }

    /**
     * Delete a member's activated card registrations.
     * @param   int     $congressusMemberId     Member whose registrations to delete
     * @throws  mysqli_sql_exception
     */
    public function deleteMembersActivatedRegistrations(int $congressusMemberId): bool
    {
        return $this->executeDelete(
            'rfid',
            'congressus_member_id',
            $congressusMemberId,
            ['`is_email_confirmed` = TRUE']
        );
    }
}
