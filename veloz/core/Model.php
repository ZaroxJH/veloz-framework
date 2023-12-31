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

    public static function create($params)
    {
        if (!self::connect()) {
            return false;
        }

        $table = self::getTable();

        // Gets fillables variable from the model
        $fillable = (new static)->fillable;
        
        // Checks if the fillable variable is set
        if (!isset($fillable)) {
            throw new \Exception('Fillable variable is not set in the model');
        }

        // Checks if the fillable variable is an array
        if (!is_array($fillable)) {
            throw new \Exception('Fillable variable is not an array');
        }

        // Checks if the fillable variable is empty
        if (empty($fillable)) {
            throw new \Exception('Fillable variable is empty');
        }

        // Checks if the params variable is an array
        if (!is_array($params)) {
            throw new \Exception('Params variable is not an array');
        }

        // Checks if the keys from the params array match the fillable variable
        foreach ($params as $key => $value) {
            if (!in_array($key, $fillable)) {
                throw new \Exception('Key ' . $key . ' is not in the fillable variable');
            }
        }

        // Inserts the data into the database
        $id = self::insert($params);

        // If the insert was successful, return the id
        if ($id) {
            return $id;
        }

        return false;
    }

    public static function get_all($set_as_key = false)
    {
        if (!self::connect()) {
            return false;
        }

        $table = self::getTable();
        $select = self::assignSelect($table);

        $query = "SELECT $select FROM $table";

        $result = DB::select($query);

        if ($set_as_key || is_string($set_as_key)) {
            $return = [];

            foreach ($result as $item) {
                $return[$item[$set_as_key]] = $item;
            }

            return $return;
        }

        return $result;
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

    public static function find($param, $array = false)
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
            if ($array) {
                return $result[0] ?? false;
            }
            
            return (object) $result;
        }

        return false;
    }

    /**
     * Handles pagination
     */
    public static function paginate($perPage)
    {
        if (!self::connect()) {
            return false;
        }
    
        $table = self::getTable();
        $select = self::assignSelect($table);
    
        $page = 1;
    
        $validate = validate_get([
            'page' => 'numeric'
        ]);
    
        if ($validate) {
            $page = $_GET['page'];
        }
    
        $offset = ($page - 1) * $perPage;
    
        $countQuery = "SELECT COUNT(*) as total FROM $table";
        $countResult = DB::select($countQuery);

        if (is_array($countResult)) {
            $totalCount = $countResult[0]['total'];
        } else {
            $totalCount = $countResult->total;
        }
    
        $query = "SELECT $select FROM $table LIMIT $perPage OFFSET $offset";
    
        $paginationData = [
            'total' => $totalCount,
            'per_page' => $perPage,
            'current_page' => $page,
            'page_amount' => ceil($totalCount / $perPage),
        ];

        set_pagination_data($paginationData);
    
        return DB::select($query);
    }

    public static function paginate_where(array $params, $perPage, $ids = [])
    {
        if (!self::connect()) {
            return false;
        }
    
        $table = self::getTable();
        $select = self::assignSelect($table);
    
        $page = 1;
    
        $validate = validate_get([
            'page' => 'numeric'
        ]);
    
        if ($validate) {
            $page = $_GET['page'];
        }
    
        $offset = ($page - 1) * $perPage;

        $whereClause = '';
        $in = [];

        if (!empty($ids)) {
            // This means there is a list of id's that we want to get
            foreach ($ids as $key => $id) {
                $in[] = $id;
            }
        }
    
        $whereClause = self::buildWhere($params, $in);

        $countQuery = "SELECT COUNT(*) as total FROM $table WHERE $whereClause";
        $countResult = DB::select($countQuery, $params);

        if (is_array($countResult)) {
            $totalCount = $countResult[0]['total'];
        } else {
            $totalCount = $countResult->total;
        }
    
        $query = "SELECT $select FROM $table WHERE $whereClause LIMIT $perPage OFFSET $offset";
    
        $paginationData = [
            'total' => $totalCount,
            'per_page' => $perPage,
            'current_page' => $page,
            'page_amount' => ceil($totalCount / $perPage),
        ];

        set_pagination_data($paginationData);
    
        return DB::select($query, $params);
    }
        
    /**
     * Gets all the rows from the table.
     *
     * $col contains the id from the main table
     * $joinCols The first value is the id name from the joined table, the second contains the column we want to get
     */
    public static function join($joinTables, $col, array $joinCols, $id = false)
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

        if (!$id) {
            $query = "SELECT $table.$select $selects FROM $table $joins";
            return new JoinResult($query);
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

    public static function update_where(array $params, array $where)
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

        $whereClause = self::buildWhere($where);

        $query = "UPDATE $table SET $set WHERE $whereClause";

        $params = array_merge($params, $where);

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

    private static function buildWhere($params, $in = [], $like = false)
    {
        $whereClause = '';

        if (!$like) {
            foreach ($params as $param => $value) {
                $whereClause .= $param . ' = :' . $param;
                $whereClause .= ' AND ';
            }
        } else {
            foreach ($params as $param => $value) {
                // Checks if this is the last value
                if (next($params) === false) {
                    $whereClause .= $param . ' LIKE :' . $param;
                    $whereClause .= ' AND ';
                    continue;
                } else {
                    $whereClause .= $param . ' = :' . $param;
                    $whereClause .= ' AND ';
                }
            }
        }

        $whereClause = rtrim($whereClause, ' AND ');

        if (!empty($in)) {
            $whereClause .= ' AND id IN (' . implode(', ', $in) . ')';
        }

        return $whereClause;
    }

    public static function where($column, $value, $select = null, $options = [])
    {
        $table = self::getTable();

        if (!self::connect()) {
            return false;
        }

        $like = false;
        $in = [];

        if (isset($options['like'])) {
            $like = $options['like'];

            // Checks if strength was set
            if (isset($options['strength'])) {
                $strength = $options['strength'];

                // Checks if strength is a string
                if (!is_string($strength)) {
                    throw new \Exception('Strength must be a string');
                }

                // Checks if strength is a valid value
                if (!in_array($strength, ['start', 'end', 'both'])) {
                    throw new \Exception('Strength must be either start, end or both');
                }

                // Checks if the value is a string
                // if (!is_string($value)) {
                //     throw new \Exception('Value must be a string');
                // }
            }
        }

        if (isset($options['in'])) {
            $in = $options['in'];
        }

        if (!$column === false && !$value === false) {
            if (!is_array($column)) {
                $column = [$column];
                $value = [$value];
            }
            if ($like) {
                // Gets last value from the array
                $last_value = $value[count($value) - 1];

                // Checks if the strength is start
                if ($strength === 'start') {
                    $last_value = $last_value . '%';
                }

                // Checks if the strength is end
                if ($strength === 'end') {
                    $last_value = '%' . $last_value;
                }

                // Checks if the strength is both
                if ($strength === 'both') {
                    $last_value = '%' . $last_value . '%';
                }

                // Sets the last value to the new value
                $value[count($value) - 1] = $last_value;
            }

            $select = $select ?? '*';

            // Combine the columns and values into a single array
            $params = array_combine($column, $value);

            $whereClause = self::buildWhere($params, [], $like);
        } else {
            $select = $select ?? '*';
            $whereClause = '1 = 1';
            $params = [];
        }

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
    public function asc($col)
    {
        $this->query .= " ORDER BY $col ASC";
        return $this;
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