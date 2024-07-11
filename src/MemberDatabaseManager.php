<?php

namespace Compucie\DatabaseManagers;

use Exception;

class MemberDatabaseManager extends DatabaseManager
{
    public function __construct(string $configpath)
    {
        parent::__construct($configpath);
    }

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
    
    public function insertRfid(string $cardId, int $congressusMemberId, bool $isEmailConfirmed = FALSE): void
    {
        $statement = $this->getClient()->prepare("INSERT INTO `rfid` (`card_id`, `congressus_member_id`, `is_email_confirmed`) VALUES (?, ?, ?)");
        $statement->bind_param("sii", $cardId, $congressusMemberId, intval($isEmailConfirmed));
        $statement->execute();
        $statement->close();
    }
}

class CardNotRegisteredException extends Exception
{
}
