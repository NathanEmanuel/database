<?php

namespace Compucie\DatabaseTest;

use Compucie\Database\Event\EventDatabaseManager;
use mysqli;

final class TestableEventDatabaseManager extends EventDatabaseManager
{
    public function client(): mysqli
    {
        return $this->getClient();
    }
}