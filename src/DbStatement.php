<?php
declare(strict_types=1);

namespace Hengeb\Db;

use Hengeb\Db\Db;

class DbStatement
{
    private $insertId = null;

    public function __construct(
        private Db $db,
        private \PDOStatement $statement,
        private string $queryString)
    {
    }

    /**
     * @param array $values [name => value, ...]
     *  the type of the value might be: bool, null, int, string, array (will be converted to json), DateTime
     * @throws \InvalidArgumentException if a value has a different type
     * @return $this (for chaining)
     */
    public function bind(array $values): DbStatement
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
                throw new \InvalidArgumentException("`$name` has unsupported data type " . gettype($value));
            }
            $this->statement->bindValue($name, $value, $type);
        }
        return $this;
    }

    /**
     * @throws \Exception if something goes wrong (error message includes the original error message and the query string)
     * @return last insert id or $this if nothing was inserted
     */
    public function execute(): DbStatement|int
    {
        $oldId = $this->db->getLastInsertId();

        try {
            $this->statement->execute();
        } catch (\Exception $e) {
            throw new \Exception(get_class($e) . ': ' . $e->getMessage() . '; Query String was: ' . $this->queryString);
        }

        $newId = $this->db->getLastInsertId();
        $this->insertId = ($oldId === $newId || $newId === false) ? null : $newId;

        return $this->insertId ?? $this;
    }

    public function getAll(): array
    {
        return $this->statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getRow(): array
    {
        return $this->getAll()[0];
    }

    public function getColumn(string $key = ''): array
    {
        $all = $this->getAll();
        if (!count($all)) {
            return [];
        }
        if ($key) {
            if (!isset($all[0][$key]) {
                throw new \UnexpectedValueException('key not found: ' . $key);
            }
            return array_column($all, $key);
        }
        return array_column($all, 0);
    }

    public function get(string $key = '')
    {
        $column = $this->getColumn($key);
        return count($column) ? $column[0] : null;
    }

    public function getRowCount()
    {
        return $this->statement->rowCount();
    }

    public function getId(): ?int
    {
        return $this->insertId;
    }
}
