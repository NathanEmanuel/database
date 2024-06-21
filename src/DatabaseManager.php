<?php

namespace Compucie\DatabaseManagers;

use Exception;
use mysqli;

abstract class DatabaseManager
{
    private mysqli $client;

    public function __construct(string $configpath)
    {

        if (!file_exists($configpath)) {
            throw new FileNotFoundException();
        }
        $config = parse_ini_file($configpath);

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
