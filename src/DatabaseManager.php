<?php

namespace Compucie\Database;

use mysqli;
use mysqli_sql_exception;
use mysqli_stmt;

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

    /**
     * @param string $table
     * @param array $fields
     * @param array $params
     * @param string $types
     * @return int indication if the update has succeeded any number or -1 for failed
     */
    protected function executeCreate(
        string $table,
        array $fields,
        array $params,
        string $types
    ): int {
        $createdId = -1;

        if ($fields === []) {
            return $createdId;
        }

        $sql = sprintf(
            'INSERT INTO `%s` (%s) VALUES (%s)',
            str_replace('`', '``', $table),
            implode(', ', $fields),
            str_repeat('?, ', count($fields)-1) . ' ?'
        );

        $statement = $this->getClient()->prepare($sql);
        if ($statement === false) {
            throw new mysqli_sql_exception($this->getClient()->error);
        }

        $statement->bind_param($types, ...$this->refValues($params));

        $statement->execute();
        $checkCreated = $statement->insert_id;
        if (is_string($checkCreated)) {
            return $createdId;
        } else {
            $createdId = $checkCreated;
        }
        $statement->close();

        return $createdId;
    }

    /**
     * Execute a SELECT and return exactly one row (or null).
     * @param string      $sql    Full SELECT query with ? placeholders.
     * @param list<mixed> $params Parameters for placeholders.
     * @param string      $types  mysqli bind_param types (e.g. "isd").
     * @return array<string, mixed>|null associative row, or null if not found
     * @throws mysqli_sql_exception
     */
    protected function executeReadOne(string $sql, array $params = [], string $types = ''): ?array
    {
        $statement = $this->getClient()->prepare($sql);
        if ($statement === false) {
            throw new mysqli_sql_exception($this->getClient()->error);
        }

        if ($params !== []) {
            if ($types === '') {
                throw new mysqli_sql_exception('executeReadOne: types are required when parameters are given.');
            }
            $statement->bind_param($types, ...$this->refValues($params));
        }

        $statement->execute();

        $result = $statement->get_result();
        if ($result === false) {
            $statement->close();
            throw new mysqli_sql_exception('executeReadOne: mysqlnd is required');
        }

        $row = $result->fetch_assoc() ?: null;

        $result->free();
        $statement->close();

        return $row;
    }

    /**
     * Execute a SELECT and return all rows.
     * @param string      $sql      Full SELECT query with ? placeholders.
     * @param list<mixed> $params   Parameters for placeholders.
     * @param string      $types    mysqli bind_param types (e.g. "isd").
     * @return list<array<string, mixed>> list of associative rows
     * @throws mysqli_sql_exception
     */
    protected function executeReadAll(string $sql, array $params = [], string $types = ''): array
    {
        $statement = $this->getClient()->prepare($sql);
        if ($statement === false) {
            throw new mysqli_sql_exception($this->getClient()->error);
        }

        if ($params !== []) {
            if ($types === '') {
                throw new mysqli_sql_exception('executeReadAll: types are required when parameters are given.');
            }
            $statement->bind_param($types, ...$this->refValues($params));
        }

        $statement->execute();

        $result = $statement->get_result();
        if ($result === false) {
            $statement->close();
            throw new mysqli_sql_exception('executeReadAll: mysqlnd is required');
        }

        $rows = $result->fetch_all(MYSQLI_ASSOC);

        $result->free();
        $statement->close();

        return $rows;
    }

    /**
     * Execute update for given data.
     * @param string       $table       The selected table
     * @param string       $idColumn    The name of the id column in the table
     * @param int          $id          The id to update in the table
     * @param list<string> $fields      SQL fragments like "col = ?" or "col = NULL"
     * @param list<mixed>  $params      Parameters for the ? placeholders, in order
     * @param string       $types       mysqli bind_param types, matching $params (e.g. "sd")
     * @return bool indication if the update has succeeded
     * @throws mysqli_sql_exception
     */
    protected function executeUpdate(
        string $table,
        string $idColumn,
        int $id,
        array $fields = [],
        array $params = [],
        string $types = ""
    ): bool {
        if ($id <= 0) {
            throw new mysqli_sql_exception('executeUpdate: id must be greater than 0.');
        }

        if ($fields === []) {
            return false;
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
        $updated = $this->checkAffectedRows($statement);
        $statement->close();

        return $updated;
    }

    /**
     * @param string    $table
     * @param string    $idColumn
     * @param int       $id
     * @return bool indication if the deletion has succeeded
     * @throws mysqli_sql_exception
     */
    protected function executeDelete(
        string $table,
        string $idColumn,
        int $id,
        array $conditions = [],
    ): bool {
        if ($id <= 0) {
            throw new mysqli_sql_exception('executeDelete: id must be greater than 0.');
        }

        $sql = sprintf(
            'DELETE FROM `%s` WHERE `%s` = ?',
            str_replace('`', '``', $table),
            str_replace('`', '``', $idColumn)
        );

        if ($conditions !== []) {
            $sql .= ' AND ' . implode(' AND ', $conditions);
        }

        $statement = $this->getClient()->prepare($sql);
        if ($statement === false) {
            throw new mysqli_sql_exception($this->getClient()->error);
        }

        $statement->bind_param("i", $id);
        $statement->execute();
        $deleted = $this->checkAffectedRows($statement);
        $statement->close();

        return $deleted;
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

    /**
     * Check if the statement has effected a row.
     * @param mysqli_stmt $statement
     * @return bool
     */
    public function checkAffectedRows(mysqli_stmt $statement): bool
    {
        $affectedRows = $statement->affected_rows;
        if (is_string($affectedRows)) {
            return false;
        }
        return $affectedRows > 0;
    }
}
