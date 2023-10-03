<?php

namespace Veloz\Kernel\Commands;

use Veloz\Core\ModuleLoader;

class UpdateModules extends ModuleLoader
{
    private $root = __DIR__ . '/../../../../../../';

    public function __construct()
    {
        
    }

    public function handle()
    {
        $this->update_modules();
    }

    private function update_modules()
    {
        echoOutput('Updating modules...', 1);

        // Calls the register_modules function from the ModuleLoader class
        $this->register_modules();

        echoOutput('Modules updated.', 1);

        sleep(2);

        return;
    }

}