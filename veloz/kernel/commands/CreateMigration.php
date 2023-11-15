<?php

namespace Veloz\Kernel\Commands;

class CreateMigration
{
    private $veloz_root = __DIR__;
    private $root = __DIR__ . '/../../../../../../';
    private $migration_folder = 'database/migrations/';
    private $template_folder = '/../../../veloz/kernel/templates/';

    public function __construct()
    {
        
    }

    public function handle()
    {
        $this->create_migration();
    }

    private function create_migration()
    {
        echoOutput('Creating migration...', 1);

        $migration_name = readline('Migration name: ');

        if (!$migration_name) {
            echoOutput('No migration name provided.', 1);
            sleep(2);
            return $this->help();
        }

        echoOutput('Would you like to point to a specific folder?', 1);
        $specific_folder = readline('Y/n: ');

        if ($specific_folder === 'Y' || $specific_folder === 'y') {
            echoOutput('What folder would you like to point to? (From the root folder)', 1);
            $folder = readline('Folder: ');
            if (!$folder) {
                echoOutput('No folder provided.', 1);
                return;
            }
            $this->root .= $folder;
            if (!str_ends_with($this->root, '/')) {
                $this->root .= '/';
            }
        }

        // Removes any _ from the migration name
        // $migration_name = str_replace('_', '', $migration_name);

        // Checks if database/migrations folder exists
        if (!is_dir($this->root . $this->migration_folder)) {
            echoOutput('Migrations folder ('. $this->root . $this->migration_folder .') not found.', 1);
            echoOutput('Would you like to create a new folder?', 1);
            $create_folder = readline('Y/n: ');
            if ($create_folder === 'Y' || $create_folder === 'y') {
                echoOutput('Would you like to create the folder in the root directory?', 1);
                $create_folder_root = readline('Y/n: ');
                if ($create_folder_root === 'Y' || $create_folder_root === 'y') {
                    // Creates the folder in the root directory
                    mkdir($this->root . $this->migration_folder, 0777, true);
                } else {
                    echoOutput('What folder would you like to create the migrations folder in?', 1);
                    $folder = readline('Folder: ');
                    if (!$folder) {
                        echoOutput('No folder provided.', 1);
                        return;
                    }
                    // Creates the folder in the specified directory
                    mkdir($this->root . $folder . $this->migration_folder, 0777, true);
                    echoOutput('Folder created.', 1);
                }
            } else {
                echoOutput('Folder not created.', 1);
                return;
            }
        }

        if (isset($folder) && $folder) {
            $this->root .= $folder;
            if (!str_ends_with($this->root, '/')) {
                $this->root .= '/';
            }
        }

        $migration_file = $this->root . $this->migration_folder . date('Y_m_d_His') . '_create_' . $migration_name . '_table.php';

        $migration_template = file_get_contents($this->veloz_root . '/../templates/migration_template.php');

        $migration_template = str_replace('MigrationName', $migration_name, $migration_template);

        file_put_contents($migration_file, $migration_template);

        sleep(1);
        echoOutput('Migration created.', 1);

        return;
    }
}