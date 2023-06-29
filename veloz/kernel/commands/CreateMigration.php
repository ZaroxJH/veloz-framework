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

        // Removes any _ from the migration name
        $migration_name = str_replace('_', '', $migration_name);

        // Checks if database/migrations folder exists
        if (!is_dir($this->root . $this->migration_folder)) {
            echoOutput('Migrations folder not found. Exiting...', 1);
            return;
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