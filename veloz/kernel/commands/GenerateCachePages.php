<?php

namespace Veloz\Kernel\Commands;

use Veloz\Core\PageCaching;

class GenerateCachePages extends PageCaching
{
    private $root = __DIR__ . '/../../../../../..';

    public function __construct()
    {
        
    }

    public function handle($option)
    {
        $this->cache_pages($option);
    }

    private function cache_pages($option)
    {
        if (!isset($option)) {
            echoOutput('No option provided.', 1, 1);
            echoOutput('Enter the option: ', 1);
            $option = readline('Option: ');
            if (!$option) {
                echoOutput('No option provided.', 1);
                return;
            }
        }

        if ($option === 'app') {
            echoOutput('Caching app...', 1);
            $this->cache_app($this->root);
        }

        echoOutput('Page caching completed.', 1, 1);
        return;
    }

}