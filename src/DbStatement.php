<?php
declare(strict_types=1);

namespace Hengeb\Db;

use Hengeb\Db\Db;

class DbStatement
{
    private ?string $insertId = null;
    private ?array $allRows = null;

    private Db $db;
    private \PDOStatement $statement;
    private string $queryString;

    public function __construct(Db $db, \PDOStatement $statement, string $queryString)
    {
        $this->db = $db;
        $this->statement = $statement;
        $this->queryString = $queryString;
    }

    /**
     * @param array $values [name => value, ...]
     *  the type of the value might be: bool, null, int, string, array (will be converted to json), DateTime
     * @throws \InvalidArgumentException if a value has a different type
     * @return $this (for chaining)
     */
    public function bind(array $values): self
    {
        foreach ($values as $name => $value) {
            $type = null;
            if (is_bool($value)) {
                $type = \PDO::PARAM_BOOL;
            } elseif (is_null($value)) {
                $type = \PDO::PARAM_NULL;
            } elseif (is_int($value)) {
                $type = \PDO::PARAM_INT;
            } elseif (is_string($value)) {
                $type = \PDO::PARAM_STR;
            } elseif (is_array($value)) {
                $value = json_encode($value);
                $type = \PDO::PARAM_STR;
            } elseif (is_object($value) && get_class($value) === 'DateTime') {
                $value = $value->format('Y-m-d H:i:s');
                $type = \PDO::PARAM_STR;
            } else {
                throw new \InvalidArgumentException("`$name` has unsupported type " . gettype($value));
            }
            $this->statement->bindValue($name, $value, $type);
        }
        return $this;
    }

    /**
     * @throws \Exception if something goes wrong (error message includes the original error message and the query string)
     * @return last insert id or $this if nothing was inserted
     */
    public function execute(): self
    {
        $this->allRows = null;

        try {
            $this->statement->execute();
        } catch (\Exception $e) {
            throw new \Exception(get_class($e) . ': ' . $e->getMessage() . '; Query String was: ' . $this->queryString);
        }

        $this->insertId = $this->db->getLastInsertId();

        return $this;
    }

    public function getAll(): array
    {
        if ($this->allRows === null) {
            $this->allRows = $this->statement->fetchAll(\PDO::FETCH_ASSOC);
        }
        return $this->allRows;
    }

    public function getRow(): array
    {
        $rows = $this->getAll();
        return $rows ? $rows[0] : [];
    }

    public function getColumn(string $key = ''): array
    {
        if ($this->getRowCount() === 0) {
            return [];
        }
        if (!$key) {
            return $this->statement->fetchAll(\PDO::FETCH_COLUMN, 0);
        }
        $all = $this->getAll();
        if (!isset($all[0][$key])) {
            throw new \UnexpectedValueException('key not found: ' . $key);
        }
        return array_column($all, $key);
    }

    /**
     * @return mixed
     */
    public function get(string $key = '')
    {
        $column = $this->getColumn($key);
        return count($column) ? $column[0] : null;
    }

    public function getRowCount(): int
    {
        return $this->statement->rowCount();
    }

    /**
     * @throws \LogicException when no row was inserted by this statement
     */
    public function getInsertId(): string
    {
        if ($this->insertId === null) {
            throw new \LogicException('getInsertId was called but nothing was inserted.');
        }
        return $this->insertId;
    }
}
