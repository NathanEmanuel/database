<?php

namespace Compucie\DatabaseTest;

use mysqli;
use mysqli_stmt;
use RuntimeException;

final readonly class DbTestHelper
{
    public function __construct(private mysqli $db) {}

    /**
     * @param string $table
     * @param string $where
     * @param array<mixed> $params
     * @param string $types
     * @return int
     */
    public function rowCount(string $table, string $where = '1=1', array $params = [], string $types = ''): int
    {
        $count = 0;

        $sql = "SELECT COUNT(*) FROM `$table` WHERE $where";
        $statement = $this->db->prepare($sql);
        if ($statement === false) throw new RuntimeException($this->db->error);

        if ($params) {
            if ($types === '') $types = $this->inferTypes($params);
            $this->bindParams($statement, $types, $params);
        }

        $statement->execute();
        $statement->bind_result($count);
        $statement->fetch();
        $statement->close();

        return $count;
    }

    /**
     * @param array<string> $tables
     * @param bool $disableFkChecks
     * @return void
     */
    public function truncateTables(array $tables, bool $disableFkChecks = true): void
    {
        if ($disableFkChecks) $this->db->query("SET FOREIGN_KEY_CHECKS=0");
        foreach ($tables as $t) {
            $t = str_replace('`', '``', $t);
            if (!$this->db->query("TRUNCATE TABLE `$t`")) {
                throw new RuntimeException("TRUNCATE `$t` failed: {$this->db->error}");
            }
        }
        if ($disableFkChecks) $this->db->query("SET FOREIGN_KEY_CHECKS=1");
    }

    /**
     * @param array<mixed> $params
     * @return string
     */
    private function inferTypes(array $params): string
    {
        $types = '';
        foreach ($params as $p) {
            $types .= is_int($p) ? 'i' : (is_float($p) ? 'd' : 's');
        }
        return $types;
    }

    /**
     * @param mysqli_stmt $stmt
     * @param string $types
     * @param array<mixed> $params
     */
    private function bindParams(mysqli_stmt $stmt, string $types, array $params): void
    {
        $refs = array_map(function ($v) {
            return $v;
        }, $params);
        $stmt->bind_param($types, ...$refs);
    }
}
