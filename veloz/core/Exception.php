<?php

namespace Veloz\Core;

use Veloz\Core\View;

class Exception
{
    public function __construct()
    {

    }

    public function success($message)
    {
        $this->message($message, 'success');
    }

    public function error($message)
    {
        $this->message($message, 'error');
    }

    public function warning($message)
    {
        $this->message($message, 'warning');
    }

    protected function message($message, $type)
    {
        $_SESSION[$_ENV['APP_NAME']]['notice'] = [
            'message' => $message,
            'type' => $type,
        ];
    }
}