<?php

namespace Veloz\Kernel\Commands;

class GenerateAdminKey
{
    public function __construct()
    {
        
    }

    public function handle()
    {
        $this->generate_admin_key();
    }

    public function generate_admin_key()
    {
        if (!$this->env_var_exists('ADMIN_KEY')) {
            $this->generate_env_var('ADMIN_KEY');
        }

        echoOutput('Generating admin key...', 1);

        $key = bin2hex(random_bytes(32));

        $this->write_to_env('ADMIN_KEY=', $key);

        sleep(1);
        echoOutput('Admin key generated.', 1);

        return;
    }

    private function env_var_exists($var)
    {
        return isset($_ENV[$var]);
    }

    private function generate_env_var($var)
    {
        echoOutput('No ' . $var . ' variable found.', 1);
        sleep(1);
        echoOutput('Generating ' . $var . ' variable...', 1);
        file_put_contents(__DIR__ . '/../../.env', PHP_EOL . $var . '=', FILE_APPEND);
        sleep(1);
        echoOutput($var . ' variable generated.', 1);
    }

    private function write_to_env($key, $value)
    {
        // fopen the env
        $env = fopen(__DIR__ . '/../../../.env', 'r+');
        // Get the contents of the env
        $contents = fread($env, filesize(__DIR__ . '/../../../.env'));
        // Find the key
        $env_key = strpos($contents, $key);
        // Move the pointer to the key
        fseek($env, $env_key);
        // Write the new key
        fwrite($env, $key . $value);
    }
}