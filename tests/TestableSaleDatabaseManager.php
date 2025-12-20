<?php

namespace Compucie\DatabaseTest;

use Compucie\Database\Sale\SaleDatabaseManager;
use mysqli;

final class TestableSaleDatabaseManager extends SaleDatabaseManager
{
    public function client(): mysqli
    {
        return $this->getClient();
    }
}