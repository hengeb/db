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

    public \DateTimeZone $timezoneUtc;

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

        $this->timezoneUtc = new \DateTimeZone('UTC');
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

    public function getInsertableValueAndType(mixed $value): array {
        $type = \PDO::PARAM_STR;
        if (is_bool($value)) {
            $type = \PDO::PARAM_BOOL;
            $value = $value ? 1 : 0;
        } elseif (is_null($value)) {
            $type = \PDO::PARAM_NULL;
        } elseif (is_int($value)) {
            $type = \PDO::PARAM_INT;
        } elseif (is_string($value)) {
            $type = \PDO::PARAM_STR;
        } elseif (is_array($value)) {
            $value = json_encode($value);
            $type = \PDO::PARAM_STR;
        } elseif ($value instanceof \DateTime || $value instanceof \DateTimeImmutable) {
            if ($value->getTimeZone()->getName() !== 'UTC') {
                $value = $value->setTimeZone($this->timezoneUtc);
            }
            $value = $value->format('Y-m-d H:i:s');
            $type = \PDO::PARAM_STR;
        } else {
            throw new \InvalidArgumentException('unsupported type: ' . gettype($value) . (gettype($value) === 'object' ? (' of type ' . $value::class) : ''));
        }
        return [$value, $type];
    }

    public function quote(mixed $value): mixed {
        [$value, $type] = $this->getInsertableValueAndType($value);
        return $this->dbh->quote("$value", $type);
    }
}
