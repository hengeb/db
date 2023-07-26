<?php
declare(strict_types=1);

namespace Hengeb\Db;

use Hengeb\Db\DbStatement;

/**
* database connection
*/
class Db
{
    private $dbh = null;

    /**
     * @param array $configuration e.g. ["host" => "example.org", "port" => 3306, "database" => "my_database", "user" => "john.doe", "password" => "secret"]
     * @throws \RuntimeException if connection fails
     */
    public function __construct($configuration)
    {
        $this->dbh = new \PDO('mysql:host=' . $configuration['host'] . ';port=' . $configuration['port'] . ';dbname=' . $configuration['database'], $configuration['user'], $configuration['password']);
        $this->dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->dbh->query('SET NAMES "utf8mb4" COLLATE "utf8mb4_unicode_ci"');
    }

    public function query(string $query, array $values = []): DbStatement
    {
        return $this->prepare($query)->bind($values)->execute();
    }

    public function prepare(string $query): DbStatement
    {
        return new DbStatement($this, $this->dbh->prepare($query), $query);
    }

    public function getLastInsertId(): ?string
    {
        $id = $this->dbh->lastInsertId();
        return ($id === false || $id === '0') ? null : $id;
    }

    public function beginTransaction(): self
    {
        $this->dbh->beginTransaction();
        return $this;
    }

    public function isInTransaction(): bool
    {
        return $this->dbh->inTransaction();
    }

    public function commit(): void
    {
        $this->dbh->commit();
    }

    public function rollBack(): void
    {
        $this->dbh->rollBack();
    }
}
