<?php

namespace Veloz\Kernel;

class Kernel
{
    private string $commandsRoot;
    private string $root = '';

    private array $validCommands = [
        'generate' => [
            'admin_key',
        ],
        'create' => [
            'migration',
            'controller',
            'model',
            'seeder',
        ],
        'run' => [
            'migrations',
            'seeders',
            'seeder',
        ],
        'update' => [
            'modules',
        ],
        'help',
    ];

    public function __construct()
    {
        // Navigates to the root folder
        // chdir($this->root);

        $this->loadEnv();
        $this->commandsRoot = 'Veloz\\Kernel\\Commands\\';
    }

    public function loadEnv()
    {
        // Throws an error if the .env file is not found
        if (!file_exists('.env')) {
            echoOutput('The .env file was not found, in directory: ' . getcwd());
            echoOutput('Have you tried running cp .env.example .env in the root directory?', 1);
            echoOutput('Exiting...', 1, 2);
            exit;
        }

        // If the env was not loaded, load it
        $dotenv = \Dotenv\Dotenv::createImmutable(getcwd());
        $dotenv->load();
    }

    private function help()
    {
        echoOutput('Veloz CLI Help', true);

        echoOutput('Here is a list of available arguments:');
        foreach ($this->validCommands as $command => $args) {
            // Check if the command has arguments
            if (is_array($args)) {
                echoOutput('  ' . $command . ':');
                foreach ($args as $arg) {
                    echoOutput('    ' . $arg);
                }
            } else {
                echoOutput('  ' . $args);
            }
        }
    }

    public function handleCommand($cmd)
    {
        if (!$cmd) {
            echoOutput('No command provided.');
            return $this->help();
        }

        if ($cmd === 'help') {
            return $this->help();
        }

        // Seperates the command from the options
        $options = explode(' ', $cmd);

        // Set the option to be a string
        $options = $options[1] ?? null;

        // Seperates the command from the arguments
        $cmd = explode(':', $cmd);

        // Get the command
        $command = $cmd[0];

        // Get the arguments
        $args = $cmd[1] ?? null;

        // Check if the command is valid
        if (!array_key_exists($command, $this->validCommands)) {
            echoOutput('Invalid command.', 1);
            return $this->help();
        }

        // Filters all characters after the first space
        $args = explode(' ', $args)[0];

        // Check if the arguments are valid
        if ($args) {
            $args = explode(',', $args);

            foreach ($args as $arg) {
                if (!in_array($arg, $this->validCommands[$command])) {
                    echoOutput('Invalid argument.', 1);
                    return $this->help();
                }
            }
        }

        $this->runCommand($command, $args, $options);

        return 'Exiting...';
    }

    private function runCommand($command, $args, $options = null)
    {
        // Check if a function exists for the command
        $function = $command . '_' . $args[0] ?? null;
        $function = ucfirst($function);

        // Removes all underscores and makes the first letter of each word uppercase
        $function = str_replace('_', '', ucwords($function, '_'));

        if ($function) {
            $class = '' . $function;
            // Checks if the class exists
            if (class_exists($this->commandsRoot . $class)) {
                $class = $this->commandsRoot . $class;
                $class = new $class;
                return $class->handle($options);
            } 
        }

        echoOutput('No option provided.', 1);
        sleep(2);
        return $this->help();
    }
}