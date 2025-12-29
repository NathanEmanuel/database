<?php

namespace Compucie\DatabaseTest;

use Compucie\Database\Poll\PollDatabaseManager;
use mysqli;

final class TestablePollDatabaseManager extends PollDatabaseManager
{
    public function client(): mysqli
    {
        return $this->getClient();
    }
}