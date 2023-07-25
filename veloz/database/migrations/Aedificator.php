<?php

namespace Veloz\Database\Migrations;

use Veloz\Database\DB;

class Aedificator
{
    private string $table;
    public string $databaseError = 'unknown';
    private $lastResult;
    private array $connectVariables = [
        'DB_HOST',
        'DB_USERNAME',
        'DB_PASSWORD',
        'DB_NAME',
        'DB_PORT',
    ];

    public function init()
    {
        if (!$this->connect()) {
            echoOutput('An error occured while trying to connect to the database: ' . $this->databaseError, 1);

            if (str_contains($this->databaseError, 'Unknown database')) {
                echoOutput('The database was not found. Do you want to create it? (y/n)', 1);
                $answer = readline();

                if ($answer === 'y') {
                    if (DB::createDatabase()) {
                        echoOutput('Database created successfully', 1);
                        return true;
                    }

                    echoOutput('An error occured while trying to create the database: ' . DB::$databaseError, 1);
                    return false;
                }
            }

            if (str_contains($this->databaseError, 'Connection refused')) {
                echoOutput('An error occured while trying to connect to the database: ' . $this->databaseError, 1);
                echoOutput('If your host is correct, check your port settings', 1);
                return false;
            }

            if (str_contains($this->databaseError, 'Access denied')) {
                echoOutput('An error occured while trying to connect to the database: ' . $this->databaseError, 1);
                echoOutput('Please check your credentials and make sure they are correct', 1);
                return false;
            }

            return false;
        }
        echoOutput('Connected to the database: ' . $_ENV['DB_NAME'], 1);
        return true;
    }

    /**
     * Connects to the database
     */
    private function connect() : bool
    {
        $connectVariables = $this->connectVariables;

        foreach ($connectVariables as $variable) {
            if (!isset($_ENV[$variable])) {
                $this->databaseError = 'The ' . $variable . ' variable was not found in the .env file.';
                return false;
            }

            $credentials[$variable] = $_ENV[$variable];
        }

        // Call the connect function from the parent class
        if (!DB::connect($credentials)) {
            $this->databaseError = DB::$databaseError;
            return false;
        }

        return true;
    }

    /**
     * Removes all data from the table
     * @param array $data
     */
    public function clearTable()
    {
        $query = "TRUNCATE TABLE {$this->table}";
        return $this->query($query);
    }

    /**
     * Creates the table, using the data provided
     */
    public function create(array $data)
    {
        if(!$this->createTable()) {
            echoOutput('An error occured while trying to create the table: ' . $this->databaseError, 1);
            return false;
        }

        $this->createColumns($data);
    }

    /**
     * Creates the table
     */
    private function createTable()
    {
        echoOutput("Creating {$this->table} table");
        return $this->query("CREATE TABLE IF NOT EXISTS {$this->table} (id INT AUTO_INCREMENT PRIMARY KEY)");
    }

    /**
     * Creates the columns
     * @param array $data ex: 'created_at' => ['type' => 'timestamp','default' => 'CURRENT_TIMESTAMP',]
     */
    private function createColumns(array $data)
    {
        echoOutput("Creating columns for {$this->table} table", 1);
        foreach ($data as $column => $columnData) {
            echoOutput("Creating {$column} column");
            $this->createColumn($column, $columnData);
        }
    }

    /**
     * Creates a column
     * @param string $column
     * @param array $columnData
     */
    private function createColumn(string $column, array $columnData)
    {
        $type = $columnData['type'];
        $default = $columnData['default'] ?? null;
        $length = $columnData['length'] ?? null;
        $nullable = $columnData['nullable'] ?? false;
        $unique = $columnData['unique'] ?? false;
        $unsigned = $columnData['unsigned'] ?? false;

        $query = "ALTER TABLE {$this->table} ADD COLUMN {$column} {$type}";

        if ($length) {
            $query .= "({$length})";
        }

        if ($nullable) {
            $query .= " NULL";
        } else {
            $query .= " NOT NULL";
        }

        if ($default) {
            $query .= " DEFAULT {$default}";
        }

        if ($unique) {
            $query .= " UNIQUE";
        }

        if ($unsigned) {
            $query .= " UNSIGNED";
        }

        $this->query($query);
    }

    public function setTable($table)
    {
        $this->table = $table;
    }

    /**
     * Checks if the table exists
     * @param string $table
     */
    public function tableExists(string $table = null)
    {
        $table = $this->table ?? $table;
        $query = "SELECT 1 FROM {$this->table} LIMIT 1";

        try {
            DB::query($query);
        } catch (\PDOException $e) {
            $this->databaseError = $e->getMessage();
            return false;
        }

        return true;
    }

    /**
     * Deletes the table
     */
    public function deleteTable()
    {
        $query = "DROP TABLE {$this->table}";
        return $this->query($query);
    }

    /**
     * fills the table with data
     * 
     */
    public function fillTable(array $data)
    {
        echoOutput('Filling table with ' . count($data) . ' rows', 1, 1);

        $startTime = microtime(true);

        foreach ($data as $row) {
            $this->fillRow($row);
        }

        $endTime = microtime(true);

        echoOutput('Finished in ' . round($endTime - $startTime, 2) . ' seconds', 1, 1);

        return true;
    }

    /**
     * Fills a row
     * @param array $row ex: 'option_name' => 'site_name', 'option_value' => 'Velovox'
     */
    private function fillRow(array $row)
    {
        $columns = [];
        $values = [];

        foreach ($row as $column => $value) {
            $columns[] = $column;
            $values[] = "'" . $value . "'";
        }

        $columns = implode(', ', $columns);
        $values = implode(', ', $values);

        $query = "INSERT INTO {$this->table} ({$columns}) VALUES ({$values})";

        echoOutput('Inserting values: ' . $values, 0);

        return $this->query($query);
    }

    /**
     * Checks if a table has content
     * 
     */
    public function hasContent()
    {
        $query = "SELECT * FROM {$this->table} LIMIT 1";

        try {
            DB::query($query);
        } catch (\PDOException $e) {
            $this->databaseError = $e->getMessage();
            return false;
        }

        return true;
    }

    private function query(string $query)
    {
        try {
            DB::query($query);
        } catch (\PDOException $e) {
            $this->databaseError = $e->getMessage();
            return false;
        }

        return true;
    }

}