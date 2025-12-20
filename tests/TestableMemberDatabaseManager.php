<?php

namespace Compucie\DatabaseTest;

use Compucie\Database\Member\MemberDatabaseManager;
use mysqli;

final class TestableMemberDatabaseManager extends MemberDatabaseManager
{
    public function client(): mysqli
    {
        return $this->getClient();
    }
}