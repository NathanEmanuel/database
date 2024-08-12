<?php

namespace Compucie\Database;

use mysqli;

abstract class DatabaseManager
{
    const SQL_DATETIME_FORMAT = "Y-m-d H:i:s";

    private mysqli $client;

    public function __construct(array $config)
    {
        $this->client = new mysqli(...$config);
    }

    protected function getClient(): mysqli
    {
        return $this->client;
    }

    /**
     * Create all tables used by this database manager.
     * @throws  mysqli_sql_exception
     */
    abstract public function createTables(): void;
}
