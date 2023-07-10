<?php

namespace Veloz\Core;

use Veloz\Core\View;

class Exception
{
    public bool $fade;

    public function set($message, $type, $fade)
    {
        $this->message($message, $type, $fade);
    }

    // public function success($message)
    // {
    //     $this->message($message, 'success');
    // }

    // public function error($message)
    // {
    //     $this->message($message, 'error');
    // }

    // public function warning($message)
    // {
    //     $this->message($message, 'warning');
    // }

    protected function message($message, $type, $fade = false)
    {
        $_SESSION[$_ENV['APP_NAME']]['notice'] = [
            'message' => $message,
            'type' => $type,
            'fade' => $fade,
        ];
    }
}