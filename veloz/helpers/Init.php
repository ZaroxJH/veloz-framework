<?php

namespace Veloz\Helpers;

class Init
{
    public static function load(): void
    {
        // Require once web/web.php
        require_once __DIR__ . '/../web/web.php';
    }

    public static function web_path()
    {
        // Returns absolute path for web/web.php
        return __DIR__ . '/../web/web.php';
    }
}