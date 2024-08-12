<?php

namespace Compucie\Database\Member;

use Compucie\Database\DatabaseManager;
use Exception;

class MemberDatabaseManager extends DatabaseManager
{
    use BirthdaysTableManager;
    use RfidTableManager;

    public function createTables(): void
    {
        $this->createBirthdaysTable();
        $this->createRfidTable();
    }
}

class CardNotRegisteredException extends Exception {}
