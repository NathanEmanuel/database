<?php

namespace Compucie\Database;

use mysqli;
use mysqli_sql_exception;

abstract class DatabaseManager
{
    const SQL_DATETIME_FORMAT = "Y-m-d H:i:s";

    private mysqli $client;

    /**
     * @param array<string> $config
     */
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

    protected function executeCreate(string $table): void
    {

    }

    protected function executeRead(string $table): void
    {

    }

    /**
     * @param list<string> $fields SQL fragments like "col = ?" or "col = NULL"
     * @param list<mixed>  $params Parameters for the ? placeholders, in order
     * @param string       $types  mysqli bind_param types, matching $params (e.g. "sd")
     * @throws mysqli_sql_exception
     */
    protected function executeUpdate(
        string $table,
        string $idColumn,
        int $id,
        array $fields,
        array $params,
        string $types
    ): void {
        if ($fields === []) {
            return;
        }

        $sql = sprintf(
            'UPDATE `%s` SET %s WHERE `%s` = ?',
            str_replace('`', '``', $table),
            implode(', ', $fields),
            str_replace('`', '``', $idColumn)
        );

        $statement = $this->getClient()->prepare($sql);
        if ($statement === false) {
            throw new mysqli_sql_exception($this->getClient()->error);
        }

        if ($params !== []) {
            $params[] = $id;
            $types   .= 'i';
            $statement->bind_param($types, ...$this->refValues($params));
        } else {
            $statement->bind_param('i', $id);
        }

        $statement->execute();
        $statement->close();
    }

    /**
     * @param string $table
     * @param string $idColumn
     * @param int $id
     * @return bool
     * @throws mysqli_sql_exception
     */
    protected function executeDelete(string $table, string $idColumn, int $id): bool
    {
        $deleted = 0;

        $sql = sprintf(
            'DELETE FROM `%s` WHERE `%s` = ?',
            str_replace('`', '``', $table),
            str_replace('`', '``', $idColumn)
        );

        $statement = $this->getClient()->prepare($sql);
        if ($statement === false) {
            throw new mysqli_sql_exception($this->getClient()->error);
        }

        $statement->bind_param("i", $id);
        $statement->execute();
        $affectedRows = $statement->affected_rows;
        if ($affectedRows !== "") {
            $deleted = $affectedRows;
        }
        $statement->close();

        return $deleted > 0;
    }

    /**
     * @param list<mixed> $arr
     * @return array<int, mixed>
     */
    private function refValues(array &$arr): array
    {
        $refs = [];
        foreach ($arr as $k => &$v) {
            $refs[$k] = &$v;
        }
        return $refs;
    }
}
