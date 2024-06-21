<?php

namespace Compucie\DatabaseManagers;

class MemberDatabaseManager extends DatabaseManager
{
    public function __construct(string $configpath)
    {
        parent::__construct($configpath);
    }

    public function getCongressusMemberIdFromCardId(int $cardId)
    {
        $statement = $this->getClient()->prepare("SELECT `congressus_member_id` FROM `rfid` WHERE `card_id` = ?");
        $statement->bind_param("i", $cardId);
        $statement->bind_result($congressusMemberId);
        $statement->execute();
        $statement->close();

        return $congressusMemberId;
    }

    public function isRfidCardRegistered(int $cardId): bool
    {
        $statement = $this->getClient()->prepare("SELECT COUNT(*) FROM `rfid` WHERE `card_id` = ?");
        $statement->bind_param("i", $cardId);
        $statement->bind_result($count);
        $statement->execute();
        $statement->close();

        return $count > 0;
    }
    
    public function insertRfid(int $cardId, int $congressusMemberId, bool $isEmailConfirmed = FALSE)
    {
        $statement = $this->getClient()->prepare("INSERT INTO `rfid` (`card_id`, `congressus_member_id`, `is_email_confirmed`) VALUES (?, ?, ?)");
        $statement->bind_param("iii", $cardId, $congressusMemberId, intval($isEmailConfirmed));
        $statement->execute();
        $statement->close();
    }
}
