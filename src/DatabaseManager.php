<?php

namespace Compucie\Database;

use Exception;
use mysqli;

abstract class DatabaseManager
{
    private mysqli $client;

    public function __construct(array $config)
    {
        $this->client = new mysqli(...$config);
    }

    public function getClient()
    {
        return $this->client;
    }
}

class FileNotFoundException extends Exception
{
}

class CouldNotInsertException extends Exception
{
}
