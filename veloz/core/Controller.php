<?php

namespace Veloz\Core;

class Controller extends View
{
    protected function view(string $view, array $data = [], $includeLayout = true): string|bool
    {
        $layout = null;
        
        $path = strtolower($_ENV['APP_ROOT']);

        // Checks if there is either a / at beginning or end of the path, and removes it
        if (substr($path, 0, 1) === '/') {
            $path = substr($path, 1);
        }

        if (substr($path, -1) === '/') {
            $path = substr($path, 0, -1);
        }

        $class = explode('\\', get_called_class());

        if (isset($class[0])) {
            if ($class[0] !== 'App') {
                $path = strtolower($class[0] . '/' . $class[1]);
                if (file_exists(dirname(__DIR__, 2) . '/'. $path .'/config.php')) {
                    include dirname(__DIR__, 2) . '/'. $path .'/config.php';
                }
            }
        }

        if ($includeLayout) {
            $layout = dirname(__DIR__, 2) . $_ENV['APP_ROOT'] . 'views/layouts/app.php';
        }

        return $this->include($path, $view, $layout, $data);
    }

    protected function load(string $view, array $data = []): string|bool
    {
        // Check if the request is an AJAX request
        if (!$this->isAjax()) {
            // If it's not an AJAX request, return an error message
            redirect('/');
        }

        // Since we are just returning the view, we can set the second parameter of the `view` function as false
        return $this->view($view, $data, false);
    }

    protected function isAjax(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }

}