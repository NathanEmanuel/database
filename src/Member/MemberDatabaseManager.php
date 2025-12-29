<?php

namespace Compucie\Database\Member;

use Compucie\Database\DatabaseManager;

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
