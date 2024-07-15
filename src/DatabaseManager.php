<?php

namespace Compucie\Database;

use mysqli;

abstract class DatabaseManager
{
    private mysqli $client;

    protected function __construct(array $config)
    {
        $this->client = new mysqli(...$config);
    }

    protected function getClient()
    {
        return $this->client;
    }

    /**
     * Create all tables used by this database manager.
     * @throws  mysqli_sql_exception
     */
    abstract public function createTables();
}
