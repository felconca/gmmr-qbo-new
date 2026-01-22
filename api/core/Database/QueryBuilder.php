<?php

namespace Core\Database;

class QueryBuilder
{
    private $conn;
    private $select = '*';
    private $table = '';
    private $updateTable = '';
    private $updateData = array();
    private $where = array();
    private $joins = '';
    private $orderBy = '';
    private $limit = '';
    private $groupBy = '';
    private $deleteTable = '';

    public function __construct($connection)
    {
        $this->conn = $connection;
    }

    public function SELECT($columns = '*', $table = '')
    {
        $this->select = is_array($columns) ? implode(', ', $columns) : $columns;
        if ($table) $this->table = $table;
        return $this;
    }

    // public function WHERE($conditions)
    // {
    //     foreach ($conditions as $column => $value) {
    //         $escaped = $this->conn->real_escape_string($value);
    //         $this->where[] = "`$column` = '$escaped'";
    //     }
    //     return $this;
    // }

    // public function ORDERBY($column, $direction = 'ASC')
    // {
    //     $this->orderBy = "ORDER BY `$column` $direction";
    //     return $this;
    // }
    public function ORDERBY($column, $direction = 'ASC')
    {
        if (strpos($column, '.') !== false) {
            $this->orderBy = "ORDER BY $column $direction";
        } else {
            $this->orderBy = "ORDER BY `$column` $direction";
        }

        return $this;
    }
    public function GROUPBY($column)
    {
        $columnWrap = $this->wrapColumn($column); // already wrapped
        $this->groupBy = "GROUP BY $columnWrap";   // do NOT add extra backticks
        return $this;
    }


    public function LIMIT($limit, $offset = null)
    {
        $this->limit = $offset !== null ? "LIMIT $offset, $limit" : "LIMIT $limit";
        return $this;
    }

    // -----------------------
    // MAGIC METHOD for JOIN
    // -----------------------
    public function __call($name, $arguments)
    {
        // PHP 5.6: No str_ends_with(). We'll compare manually.
        $callName = strtoupper($name);
        if (substr($callName, -4) === 'JOIN' && count($arguments) === 2) {
            $type = strtoupper(str_replace('JOIN', '', $name)); // e.g., LEFT, RIGHT, INNER, CROSS
            if ($type === '') $type = ''; // plain JOIN if no prefix
            $table = $arguments[0];
            $on = $arguments[1];
            // $this->joins .= " {$type} JOIN `$table` ON $on";
            $this->joins .= " {$type} JOIN " . $this->formatTable($table) . " ON $on";

            return $this;
        }

        throw new \BadMethodCallException("Method {$name} does not exist.");
    }

    private function buildQuery()
    {
        // $sql = "SELECT {$this->select} FROM `{$this->table}`";
        $sql = "SELECT {$this->select} FROM " . $this->formatTable($this->table);


        if (!empty($this->joins)) {
            $sql .= ' ' . $this->joins;
        }

        if (!empty($this->where)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->where);
        }

        if (!empty($this->groupBy)) {
            $sql .= ' ' . $this->groupBy;
        }

        if (!empty($this->orderBy)) {
            $sql .= ' ' . $this->orderBy;
        }

        if (!empty($this->limit)) {
            $sql .= ' ' . $this->limit;
        }

        return $sql;
    }

    public function get()
    {
        $query = $this->buildQuery();
        $result = $this->conn->query($query);

        if (!$result) {
            throw new \Exception("MySQL Error: " . $this->conn->error . "\nQuery: $query");
        }

        $rows = array();
        while ($row = $result->fetch_object()) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function first()
    {
        $results = $this->LIMIT(1)->get();
        return isset($results[0]) ? $results[0] : null;
    }



    public function reset()
    {
        $this->select = '*';
        $this->table = '';
        $this->where = array();
        $this->joins = '';
        $this->orderBy = '';
        $this->limit = '';
        $this->groupBy = '';
        return $this;
    }

    /**
     * Insert a new record
     *
     * @param string $table Table name
     * @param array $data  Associative array of column => value
     * @return int|bool     Inserted ID on success, false on failure
     */
    public function INSERT($table, array $data)
    {
        $columns = implode('`, `', array_keys($data));

        $valuesArr = array();
        foreach (array_values($data) as $val) {
            $valuesArr[] = "'" . $this->conn->real_escape_string($val) . "'";
        }

        $values = implode(', ', $valuesArr);

        $sql = "INSERT INTO `$table` (`$columns`) VALUES ($values)";

        $result = $this->conn->query($sql);

        if (!$result) {
            throw new \Exception("MySQL Insert Error: " . $this->conn->error . "\nQuery: $sql");
        }

        return $this->conn->insert_id; // Return the inserted ID
    }
    /**
     * Start an update on a table with data
     *
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @return $this
     */
    public function UPDATE($table, array $data)
    {
        if (empty($table) || empty($data)) {
            throw new \Exception("Table and data are required for update");
        }

        $this->updateTable = $table;
        $this->updateData = $data;

        return $this; // allow chaining with WHERE()
    }

    /**
     * Start a delete on a table
     *
     * @param string $table Table name
     * @return $this
     */
    public function DELETE($table)
    {
        if (empty($table)) {
            throw new \Exception("Table name is required for delete");
        }

        $this->deleteTable = $table;

        return $this; // allow chaining with WHERE()
    }


    /**
     * Build WHERE clause (shared)
     *
     * Executes delete if deleteTable is set.
     *
     * @param array $conditions
     * @return mixed $this for SELECT, affected_rows for UPDATE/DELETE
     */
    public function WHERE($conditions, array $bindings = array())
    {
        // Initialize where only if empty (allows multiple WHERE calls)
        if (!is_array($this->where)) {
            $this->where = array();
        }
        if (is_string($conditions)) {
            $this->where[] = $conditions;

            // Store bindings (optional but recommended)
            if (!empty($bindings)) {
                foreach ($bindings as $value) {
                    $this->bindings[] = $value;
                }
            }
        } elseif (is_array($conditions)) {
            foreach ($conditions as $column => $value) {
                $col = $this->wrapColumn($column);
                if (is_null($value)) {
                    $this->where[] = "$col IS NULL";
                } else {
                    $escaped = $this->conn->real_escape_string($value);
                    $this->where[] = "$col = '$escaped'";
                }
            }
        } else {
            throw new \InvalidArgumentException("WHERE expects array or raw SQL string");
        }

        if ($this->updateTable && !empty($this->updateData)) {
            $set = array();
            foreach ($this->updateData as $column => $value) {
                $escaped = $this->conn->real_escape_string($value);
                $set[] = "`$column` = '$escaped'";
            }

            $sql = "UPDATE `{$this->updateTable}` 
                SET " . implode(', ', $set) . "
                WHERE " . implode(' AND ', $this->where);

            $result = $this->conn->query($sql);
            $affectedRows = $this->conn->affected_rows;

            // reset state
            $this->updateTable = '';
            $this->updateData = array();
            $this->where = array();

            if (!$result) {
                throw new \Exception("MySQL Update Error: {$this->conn->error}\nQuery: $sql");
            }

            return $affectedRows;
        }

        if ($this->deleteTable) {
            $sql = "DELETE FROM `{$this->deleteTable}` 
                WHERE " . implode(' AND ', $this->where);

            $result = $this->conn->query($sql);
            $affectedRows = $this->conn->affected_rows;

            // reset state
            $this->deleteTable = '';
            $this->where = array();

            if (!$result) {
                throw new \Exception("MySQL Delete Error: {$this->conn->error}\nQuery: $sql");
            }

            return $affectedRows;
        }

        // SELECT chaining
        return $this;
    }
    public function OR_WHERE($conditions)
    {
        if (!is_array($this->where)) {
            $this->where = array();
        }

        if (is_string($conditions)) {
            $this->where[] = "OR ($conditions)";
        } elseif (is_array($conditions)) {
            $orParts = array();
            foreach ($conditions as $column => $value) {
                if (is_null($value)) {
                    $orParts[] = "`$column` IS NULL";
                } else {
                    $escaped = $this->conn->real_escape_string($value);
                    $orParts[] = "`$column` = '$escaped'";
                }
            }
            $this->where[] = "OR (" . implode(' AND ', $orParts) . ")";
        } else {
            throw new \InvalidArgumentException("OR_WHERE expects array or raw SQL string");
        }

        return $this;
    }
    public function AND_WHERE($conditions)
    {
        if (!is_array($this->where)) {
            $this->where = array();
        }

        if (is_string($conditions)) {
            $this->where[] = "AND ($conditions)";
        } elseif (is_array($conditions)) {
            $andParts = array();
            foreach ($conditions as $column => $value) {
                if (is_null($value)) {
                    $andParts[] = "`$column` IS NULL";
                } else {
                    $escaped = $this->conn->real_escape_string($value);
                    $andParts[] = "`$column` = '$escaped'";
                }
            }
            $this->where[] = "AND (" . implode(' AND ', $andParts) . ")";
        } else {
            throw new \InvalidArgumentException("AND_WHERE expects array or raw SQL string");
        }

        return $this;
    }

    public function WHERE_IN($column, array $values)
    {
        if (empty($values)) {
            // Prevent invalid SQL: IN ()
            $this->where[] = "0 = 1";
            return $this;
        }

        $escaped = array();
        foreach ($values as $v) {
            if (is_numeric($v)) {
                $escaped[] = $v;
            } else {
                $escaped[] = "'" . $this->conn->real_escape_string($v) . "'";
            }
        }

        $columnSql = $this->wrapColumn($column);

        $this->where[] = "$columnSql IN (" . implode(',', $escaped) . ")";
        return $this;
    }

    public function WHERE_NOT_IN($column, array $values)
    {
        if (empty($values)) {
            // If no values, the condition is always true
            $this->where[] = "1 = 1";
            return $this;
        }

        $escaped = array();
        foreach ($values as $v) {
            if (is_numeric($v)) {
                $escaped[] = $v;
            } else {
                $escaped[] = "'" . $this->conn->real_escape_string($v) . "'";
            }
        }

        $columnSql = $this->wrapColumn($column);

        $this->where[] = "$columnSql NOT IN (" . implode(',', $escaped) . ")";
        return $this;
    }


    public function WHERE_BETWEEN($column, $start, $end)
    {
        $startEscaped = $this->conn->real_escape_string($start);
        $endEscaped   = $this->conn->real_escape_string($end);

        // Handle table alias (p.TranDate → `p`.`TranDate`)
        if (strpos($column, '.') !== false) {
            $parts = explode('.', $column, 2);
            $table = $parts[0];
            $col = $parts[1];
            $columnSql = "`$table`.`$col`";
        } else {
            $columnSql = $this->wrapColumn($column);
        }

        $this->where[] = "$columnSql BETWEEN '$startEscaped' AND '$endEscaped'";
        return $this;
    }

    private function formatTable($table)
    {
        // db.table alias OR table alias
        if (preg_match('/^(.+?)\s+(\w+)$/', $table, $m)) {
            $tableName = $m[1];
            $alias     = $m[2];

            if (strpos($tableName, '.') !== false) {
                $parts = explode('.', $tableName, 2);
                $db = $parts[0];
                $tbl = $parts[1];
                return "`$db`.`$tbl` $alias";
            }

            return "`$tableName` $alias";
        }

        // db.table (no alias)
        if (strpos($table, '.') !== false) {
            $parts = explode('.', $table, 2);
            $db = $parts[0];
            $tbl = $parts[1];
            return "`$db`.`$tbl`";
        }

        // plain table
        return "`$table`";
    }

    private function wrapColumn($column)
    {
        if (strpos($column, '.') !== false) {
            $parts = explode('.', $column, 2);
            $table = $parts[0];
            $col = $parts[1];
            return "`$table`.`$col`";
        }
        return "`$column`";
    }
}
