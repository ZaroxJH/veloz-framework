<?php

namespace Veloz\Core;

use Veloz\Database\DB;

class Model
{
    public $tableName;

    public static function connect()
    {
        // Get class name from called class
        $class = get_called_class();

        // If the class name is not in the app directory, connect to a different database
        if (str_contains($class, 'App') || str_contains($class, 'Veloz')) {
            if (DB::connect()) {
                return true;
            }
        }

        // Connect to the default database
        // TODO: Make this work with multiple databases
        if (DB::connect()) {
            return true;
        }

        return false;
    }

    public static function get_all()
    {
        if (!self::connect()) {
            return false;
        }

        $table = self::getTable();
        $select = self::assignSelect($table);

        $query = "SELECT $select FROM $table";

        return DB::select($query);
    }

    public static function get_by_ids($ids)
    {
        if (!self::connect()) {
            return false;
        }

        $table = self::getTable();
        $select = self::assignSelect($table);

        $ids = implode(', ', $ids);

        $query = "SELECT $select FROM $table WHERE id IN ($ids)";

        $result = DB::select($query);

        $return = [];

        foreach ($result as $item) {
            $return[$item['id']] = $item;
        }

        return $return;
    }

    /**
     * Checks if anything is set in the database.
     */
    public static function exists($search)
    {
        if (!self::connect()) {
            return false;
        }

        $table = self::getTable();

        // Build the WHERE clause of the query
        $whereClause = '';
        $i = 0;

        foreach ($search as $param => $value) {
            $whereClause .= $param . ' = :' . $param;
            if ($i < count($search) - 1) {
                $whereClause .= ' AND ';
            }
            $i++;
        }

        // Build the full query
        $query = "SELECT * FROM $table WHERE $whereClause";

        // Execute the query
        $result = DB::select($query, $search);

        return $result ? $result[0] : false;
    }

    public static function has_any()
    {
        if (!self::connect()) {
            return false;
        }

        $table = self::getTable();

        // Gets the count of the table
        $query = "SELECT COUNT(*) FROM $table";

        // Execute the query
        $result = DB::select($query);

        return $result ? $result[0]['COUNT(*)'] > 0 : false;
    }

    public static function find($param)
    {
        if (!self::connect()) {
            return false;
        }

        $table = self::getTable();
        $select = self::assignSelect($table);

        if (!is_array($param)) {
            if (is_numeric($param)) {
                $param = ['id' => $param];
            } else {
                $param = [$param];
            }
        }

        $where = self::buildWhere($param);

        // Build the full query
        $query = "SELECT $select FROM $table WHERE $where";

        // Execute the query
        $result = DB::select($query, $param);

        if ($result) {
            return (object) $result;
        }

        return false;
    }

    /**
     * Handles pagination
     */
    public static function paginate($perPage, $page = 1)
    {
        if (!self::connect()) {
            return false;
        }

        $table = self::getTable();
        $select = self::assignSelect($table);

        $offset = ($page - 1) * $perPage;

        $query = "SELECT $select FROM $table LIMIT $perPage OFFSET $offset";

        return DB::select($query);
    }

    /**
     * Gets all the rows from the table.
     *
     * $col contains the id from the main table
     * $joinCols The first value is the id name from the joined table, the second contains the column we want to get
     */
    public static function join($joinTables, $col, array $joinCols, $id)
    {
        $table = self::getTable();
        $select = self::assignSelect($table);

        if (!self::connect()) {
            return false;
        }

        $joins = '';
        $selects = '';

        if (!is_array($joinTables)) {
            $joinTables = [$joinTables];
            $col = [$col];
            $joinCols = [$joinCols];
        }

        foreach ($joinTables as $key => $joinTable) {
            $joins .= " JOIN $joinTable ON $table.$col[$key] = $joinTable." . $joinCols[$key][0];
            $selects .= ", $joinTable." . $joinCols[$key][1];
        }

        $query = "SELECT $table.$select $selects FROM $table $joins WHERE $id[0] = :id";

        return new JoinResult($query, ['id' => $id[1]]);
    }

    protected static function select(string $query, array $params = []): array
    {
        return DB::select($query, $params);
    }

    protected static function insert(array $params): int
    {
        $table = self::getTable();

        if (!self::connect()) {
            return false;
        }

        $columns = implode(', ', array_keys($params));
        $values = implode(', ', array_fill(0, count($params), '?'));
        $query = "INSERT INTO $table ($columns) VALUES ($values)";

        return DB::insert($query, array_values($params));
    }

    public static function update(array $params, int $id)
    {
        if (!self::connect()) {
            return false;
        }

        $table = self::getTable();

        $set = '';

        foreach ($params as $param => $value) {
            $set .= $param . ' = :' . $param;
            $set .= ', ';
        }

        $set = rtrim($set, ', ');

        $query = "UPDATE $table SET $set WHERE id = :id";

        $params['id'] = $id;

        return DB::update($query, $params);
    }

    public static function delete($params): int
    {
        if (!self::connect()) {
            return false;
        }

        $table = self::getTable();

        if (!is_array($params)) {
            $params = [$params];
        }

        // If the array only consists of a value and not a key => value pair
        if (count($params) === 1 && isset($params[0]) && is_numeric($params[0])) {
            $params = ['id' => $params[0]];
        }

        $whereClause = self::buildWhere($params);
        $query = "DELETE FROM $table WHERE $whereClause";

        return DB::delete($query, $params);
    }

    public static function deleteByDate($params, $expirationDate): int
    {
        if (!self::connect()) {
            return false;
        }

        $table = self::getTable();

        if (!is_array($params)) {
            $params = [$params];
        }


        if (isset($params[0]) && is_numeric($params[0])) {
            if (count($params) === 1 && is_numeric($params[0])) {
                $params = ['id' => $params[0]];
            }
        }

        $whereClause = self::buildWhere($params);
        $query = "DELETE FROM $table WHERE $whereClause AND expires < :expirationDate";

        // If the expiration date is a timestamp, convert it to a date
        if (is_numeric($expirationDate)) {
            $expirationDate = date('Y-m-d H:i:s', $expirationDate);
        }

        $params['expirationDate'] = $expirationDate;

        return DB::delete($query, $params);
    }

    private static function getTable()
    {
        $model = get_called_class();

        try {
            $reflection = new \ReflectionClass(get_called_class());
        } catch (\ReflectionException $e) {
            echo $e;
            exit();
        }

        return $reflection->getProperty('table')->getValue(new $model);
    }

    private static function buildWhere($params)
    {
        $whereClause = '';

        foreach ($params as $param => $value) {
            $whereClause .= $param . ' = :' . $param;
            $whereClause .= ' AND ';
        }

        $whereClause = rtrim($whereClause, ' AND ');

        return $whereClause;
    }

    public static function where($column, $value, $select = null)
    {
        $table = self::getTable();

        if (!self::connect()) {
            return false;
        }

        if (!is_array($column)) {
            $column = [$column];
            $value = [$value];
        }

        $select = $select ?? '*';

        // Combine the columns and values into a single array
        $params = array_combine($column, $value);

        $whereClause = self::buildWhere($params);

        $query = "SELECT $select FROM $table WHERE $whereClause";

        return new JoinResult($query, $params);
    }

    private static function assignSelect(mixed $table)
    {
        $select = '*';

        // TODO: get columns from corresponding class

        if ($table === 'users') {
            $select = 'id, name, first_name, last_name, email, default_car, default_gas_station, language, created_at, updated_at';
        }

        return $select;
    }

}

class JoinResult {
    private $query;
    private $limit;
    private $params;
    public function __construct($query, $params = []) {
        $this->query = $query;
        $this->params = $params;
    }
    public function desc($col)
    {
        $this->query .= " ORDER BY $col DESC";
        return $this;
    }
    public function limit($limit) {
        if (!is_int($limit)) {
            throw new \Exception('Limit must be an integer');
        }
        $this->limit = $limit;
        return $this;
    }
    public function get($key = false) {
        if ($this->limit) {
            $this->query .= " LIMIT $this->limit";
        }

        if ($key) {
            return DB::select($this->query, $this->params)[0];
        }

        return DB::select($this->query, $this->params);
    }
    public function first() {
        $result = DB::select($this->query, $this->params);
        return $result[0];
    }
}