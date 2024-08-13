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
    private static ?Db $instance = null;

    /**
     * @param array $configuration e.g. ["host" => "example.org", "port" => 3306, "database" => "my_database", "user" => "john.doe", "password" => "secret"]
     * @throws \RuntimeException if connection fails
     */
    public function __construct($configuration)
    {
        $this->dbh = new \PDO('mysql:host=' . $configuration['host'] . ';port=' . $configuration['port'] . ';dbname=' . $configuration['database'], $configuration['user'], $configuration['password']);
        $this->dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->dbh->query('SET NAMES "utf8mb4" COLLATE "utf8mb4_unicode_ci"');
        $this->dbh->query('SET time_zone = "+00:00"');
    }

    public static function getInstance(): self
    {
        if (self::$instance) {
            return self::$instance;
        }

        if (!getenv('MYSQL_USER') || !getenv('MYSQL_PASSWORD')) {
            throw new \RuntimeException('Db::getInstance() called but MYSQL_USER and MYSQL_PASSWORD env variables are not set');
        }

        $configuration = [
            'host' => getenv('MYSQL_HOST') ?: 'localhost',
            'port' => getenv('MYSQL_PORT') ?: 3306,
            'user' => getenv('MYSQL_USER'),
            'password' => getenv('MYSQL_PASSWORD'),
            'database' => getenv('MYSQL_DATABASE') ?: 'database',
        ];

        self::$instance = new self($configuration);
        return self::$instance;
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
