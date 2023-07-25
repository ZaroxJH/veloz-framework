<?php

namespace Veloz\Kernel\Commands;

class CreateSeeder
{
    private $veloz_root = __DIR__;
    private $root = __DIR__ . '/../../../../../../';
    private $seeder_folder = 'database/seeders/';
    private $template_folder = '/../../../veloz/kernel/templates/';

    public function __construct()
    {
        
    }

    public function handle($name)
    {
        $this->create_seeder($name);
    }

    private function create_seeder($name)
    {
        if (!isset($name)) {
            echoOutput('No seeder name provided.', 1, 1);
            echoOutput('Enter the name of the seeder: ', 1);
            $name = readline('Name: ');
            if (!$name) {
                echoOutput('No seeder name provided.', 1);
                return;
            }
        }

        if (str_contains($name, '.')) {
            // Extract the name, the pattern would be name.path
            $options = explode('.', $name);

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

        echoOutput('Creating seeder with name: ' . $name, 1);
        
        // Sets the path to the seeder
        $path = $this->root . $this->seeder_folder . $name . '.php';

        // Checks if the seeder already exists
        if (file_exists($path)) {
            echoOutput('Seeder already exists.', 1);
            return;
        }

        // Gets the template
        $template = file_get_contents($this->veloz_root . '/../templates/seeder_template.php');

        // Replaces the name in the template
        $template = str_replace('SeederName', $name, $template);

        // Creates the seeder
        file_put_contents($path, $template);

        echoOutput('Seeder created.', 1);

        return;

    }

}