<?php

namespace Veloz\Kernel\Commands;

class RunMigrations
{
    private $root = __DIR__ . '/../../../../../../';

    public function __construct()
    {
        
    }

    public function handle()
    {
        $this->run_migrations();
    }

    private function run_migrations()
    {
        echoOutput('Running migrations...', 1);

        // Runs the migrations from the root/database/migrations folder
        $migrations = glob($this->root . 'database/migrations/*.php');

        if (empty($migrations)) {
            echoOutput('No migrations found.', 1);
            sleep(2);
            return $this->help();
        }

        echoOutput('Found ' . count($migrations) . ' migrations.', 1, 1);

        $finalMigrations = [];

        // Sets the pattern to extract the migration name from the file name
        $pattern = '/(?<=create_)(.*)(?=_table)/';

        // Loops through the migrations
        foreach ($migrations as $migration) {
            // Extracts the migration name from the file name
            preg_match($pattern, $migration, $matches);
            $name = $matches[0] ?? null;

            if ($name) {
                // Adds the migration to the final migrations array
                $finalMigrations[$name] = $migration;
            }
        }

        echoOutput('Running ' . count($finalMigrations) . ' migrations...', 1, 1);

        foreach ($finalMigrations as $migration) {
            echoOutput('Running migration: ' . $migration, 1);
            $name = array_search($migration, $finalMigrations);

            try {
                // Requires the migration file
                $migration = require_once $migration;
            } catch (\Throwable $th) {
                echoOutput('Migration failed: ' . $th->getMessage(), 0, 1);
                echoOutput('Attempting to run the next migration...', 1, 2);
                // Try to run the next migration
                continue;
            }

            // Checks if the migrations construct returned true
            if (!$migration) {
                echoOutput('Migration failed', 0, 1);
                echoOutput('Attempting to run the next migration...', 1, 2);
                // Try to run the next migration
                continue;
            }

            echoOutput('Setting migration table to: ' . $name, 1, 1);
            $migration->setTable($name);

            if ($migration->tableExists()) {
                echoOutput('Table already exists.', 1);
                echoOutput('Would you like to overwrite the table? (y/n)', 1);
                $overwrite = readline('Overwrite table? ');

                if ($overwrite == 'y') {
                    echoOutput('Overwriting table...', 1);
                    $migration->delete();
                    $migration->create();
                } else {
                    echoOutput('Skipping migration...', 1);
                    // Try to run the next migration
                    continue;
                }
                // Try to run the next migration
                continue;
            }

            $migration->create();

            echoOutput('Migration complete.', 1, 2);
        }

        echoOutput('All migrations complete.', 1, 2);
        
        return;
    }
}