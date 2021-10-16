<?php

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
        $this->dbh->query('SET NAMES "utf8" COLLATE "utf8_general_ci"');
    }

    public function query(string $query, array $values = []): DbStatement|int
    {
        return $this->prepare($query)->bind($values)->execute();
    }

    public function prepare(string $query): DbStatement
    {
        return new DbStatement($this, $this->dbh->prepare($query), $query);
    }

    public function getLastInsertId()
    {
        return $this->dbh->lastInsertId();
    }

    public function beginTransaction()
    {
        $this->dbh->beginTransaction();
        return $this;
    }

    public function commit()
    {
        $this->dbh->commit();
    }

    public function rollback()
    {
        $this->dbh->rollback();
    }
}
