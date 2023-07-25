<?php

namespace Veloz\Kernel\Commands;

class RunSeeder
{
    private $root = __DIR__ . '/../../../../../../';
    private $seeder_folder = 'database/seeders/';

    public function __construct()
    {
        
    }

    public function handle($options)
    {
        $this->run_seeder($options);
    }

    private function run_seeder($options)
    {
        if (!isset($options)) {
            echoOutput('No seeder name provided.', 1, 1);
            echoOutput('Enter the name of the seeder: ', 1);
            $name = readline('Name: ');
            if (!$name) {
                echoOutput('No seeder name provided.', 1);
                return;
            }
        }

        if (str_contains($options, '.')) {
            // Extract the name, the pattern would be name.path
            $options = explode('.', $options);

            // Set the name to the first option
            $name = $options[0];

            // Set the path to the second option
            $path = $options[1];

            if (!str_ends_with($path, '/')) {
                $path .= '/';
            }

            $this->root .= $path;

            echoOutput('Setting custom path to ' . $path, 1, 1);
        }

        // Checks if the folder exists
        if (!file_exists($this->root . $this->seeder_folder)) {
            echoOutput('Folder not found, creating...', 1);
            mkdir($this->root . $this->seeder_folder, 0777, true);
        }

        // Sets the name to first letter uppercase
        $name = ucfirst($name);

        if (!str_contains($name, 'Seeder')) {
            $name .= 'Seeder';
        }

        $seeder = $this->root . $this->seeder_folder . $name . '.php';

        // Checks if the file exists
        if (!file_exists($seeder)) {
            echoOutput('Seeder "'.$name . '.php'.'" not found.', 1, 1);
            return;
        }

        try {
            // Requires the seeder file
            $seeder = require_once $seeder;
        } catch (\Throwable $th) {
            echoOutput('Seeding failed: ' . $th->getMessage(), 1, 1);
            // Try to run the next migration
            return;
        }

        $seeder->setTable($seeder->table_name);

        if(!$seeder->tableExists($seeder->table_name)) {
            echoOutput('Table "'.$seeder->table_name.'" not found.', 1, 1);
            return;
        }

        if ($seeder->hasContent()) {
            echoOutput('Table "'.$seeder->table_name.'" already has content.', 1, 1);
            echoOutput('Would you like to overwrite all existing data? (Y/n).', 1, 1);

            $overwrite = readline('Overwrite data? ');

            if (strtolower($overwrite) !== 'y') {
                echoOutput('Exiting...', 1, 1);
                return;
            }

            // Clear and overwrite
            echoOutput('Clearing table "'.$seeder->table_name.'"...', 1, 1);
            
            if (!$seeder->clearTable()) {
                echoOutput('An error occured while trying to clear the table.', 1, 1);
                return;
            }

            $this->seed($seeder);
            return;
        }

        $this->seed($seeder);
        return;

    }

    private function seed($seeder)
    {
        echoOutput('Seeding table "'.$seeder->table_name.'"...', 1, 1);

        if (!$seeder->create()) {
            echoOutput('An error occured while trying to seed the table: ' . $seeder->error(), 1, 1);
            return;
        }

        echoOutput('Seeding complete.', 1, 1);
        return;
    }

}