<?php

namespace Veloz\Kernel\Commands;

class CreateModel
{
    private $root = __DIR__ . '/../../../';
    private $model_folder = 'app/models/';
    private $template_folder = 'veloz/kernel/templates/';

    public function __construct()
    {

    }

    public function handle()
    {
        $this->create_model();
    }

    private function create_model()
    {
        echoOutput('Creating model...', 1);

        // Get the model name
        $model_name = readline('Model name: ');

        // If no model name is provided, exit
        if (!$model_name) {
            echoOutput('No model name provided.', 1);
            return;
        }
        
        // Get the model and template folder
        $model_folder = $this->root . $this->model_folder;
        $template_folder = $this->root . $this->template_folder;

        // If the model folder does not exist, exit
        if (!is_dir($model_folder)) {
            echoOutput('Model folder not found. Exiting...', 1);
            return;
        }

        // If the template folder does not exist, exit
        if (!is_dir($template_folder)) {
            echoOutput('Template folder not found. Exiting...', 1);
            return;
        }

        // Removes any _ from the model name
        $model_name = str_replace('_', '', $model_name);

        // Capitalize the first letter of the model name
        $model_name = ucfirst($model_name);

        // Get the table name
        $table_name = readline('Table name: ');

        // If no table name is provided, exit
        if (!$table_name) {
            echoOutput('No table name provided.', 1);
            return;
        }

        $table_name = strtolower($table_name);

        $model_file = $model_folder . $model_name . '.php';

        if (file_exists($model_file)) {
            echoOutput('Model already exists. Exiting...', 1);
            return;
        }

        $template = file_get_contents($template_folder . 'model_template.php');

        $template = str_replace('DummyModel', $model_name, $template);
        $template = str_replace('dummy_table', $table_name, $template);

        file_put_contents($model_file, $template);

        echoOutput('Model created successfully.', 1);

        return;
    }
}