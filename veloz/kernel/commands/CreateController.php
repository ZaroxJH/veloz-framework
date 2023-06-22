<?php

namespace Veloz\Kernel\Commands;

class CreateController
{
    private $root = __DIR__ . '/../../../';
    private $controller_folder = 'app/controllers/';
    private $template_folder = 'veloz/kernel/templates/';

    public function __construct()
    {

    }

    public function handle()
    {
        $this->create_controller();
    }

    private function create_controller()
    {
        echoOutput('Creating controller...', 1);

        $controller_name = readline('Controller name: ');

        $view_name = strtolower(str_replace('Controller', '', $controller_name));

        if (!$controller_name) {
            echoOutput('No controller name provided.', 1);
            sleep(2);
            return $this->help();
        }

        $controller_folder = $this->root . $this->controller_folder;
        $template_folder = $this->root . $this->template_folder;

        if (!is_dir($controller_folder)) {
            echoOutput('Controller folder not found. Exiting...', 1);
            return;
        }

        if (!is_dir($template_folder)) {
            echoOutput('Template folder not found. Exiting...', 1);
            return;
        }

        // Removes any _ from the controller name
        $controller_name = str_replace('_', '', $controller_name);

        if (str_contains($controller_name, 'controller')) {
            $controller_name = str_replace('controller', 'Controller', $controller_name);
        }

        // If the controller name does not end with Controller, add it
        if (!str_ends_with($controller_name, 'Controller')) {
            $controller_name .= 'Controller';
        }

        $controller_name = ucfirst($controller_name);

        // If the controller already exists, exit
        if (file_exists($controller_folder . $controller_name . '.php')) {
            echoOutput('Controller already exists. Exiting...', 1);
            return;
        }

        $controller_file = $this->root . $this->controller_folder . $controller_name . '.php';
        $controller_template = file_get_contents($this->root . $this->template_folder . 'controller_template.php');
        $controller_template = str_replace('ControllerName', $controller_name, $controller_template);
        file_put_contents($controller_file, $controller_template);

        $new_controller = file_get_contents($controller_file);

        // Looks for DummyController and replaces it with the controller name
        $new_controller = str_replace('DummyController', $controller_name, $new_controller);

        // Looks for dummyview and replaces it with the view name
        $new_controller = str_replace('dummyview', $view_name, $new_controller);

        file_put_contents($controller_file, $new_controller);

        echoOutput('Controller created.', 1);

        return;
    }

}