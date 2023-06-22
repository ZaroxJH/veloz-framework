<?php

namespace Veloz\Kernel\Commands;

class CreateMigration
{
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

        $migration_file = __DIR__ . '/../../../database/migrations/' . date('Y_m_d_His') . '_create_' . $migration_name . '_table.php';

        $migration_template = file_get_contents(__DIR__ . '/../templates/migration_template.php');

        $migration_template = str_replace('MigrationName', $migration_name, $migration_template);

        file_put_contents($migration_file, $migration_template);

        sleep(1);
        echoOutput('Migration created.', 1);

        return;
    }
}