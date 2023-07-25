<?php

namespace Veloz\Core;

class Controller extends View
{
    protected function view(string $view, array $data = [], $includeLayout = true): string|bool
    {
        $layout = null;
        $root = $_SERVER['DOCUMENT_ROOT'];
        
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
                if (file_exists($root . '/'. $path .'/config.php')) {
                    include $root . '/'. $path .'/config.php';
                }
            }
        }

        $layoutFile = 'app.php';

        if ($includeLayout) {
            if (is_string($includeLayout)) {
                $layoutFile = $includeLayout;
            }
            $layout = $root . $_ENV['APP_ROOT'] . 'views/layouts/' . $layoutFile;
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

    protected function c_view(string $view, array $data = [], $includeLayout = true): string|bool
    {
        $layout = null;
        $root = $_SERVER['DOCUMENT_ROOT'];

        if (!isset($_ENV['C_ROOT'])) {
            throw new \Exception('C_ROOT is not set in .env');
        }
        
        $ori_path = strtolower($_ENV['C_ROOT']);
        $path = $ori_path;
        $namespace = ucfirst($path);

        // Checks if there is either a / at beginning or end of the path, and removes it
        if (substr($path, 0, 1) === '/') {
            $path = substr($path, 1);
        }

        if (substr($path, -1) === '/') {
            $path = substr($path, 0, -1);
        }

        $class = explode('\\', get_called_class());

        if (isset($class[0])) {
            if ($class[0] !== ucfirst($path)) {
                $path = strtolower($class[0] . '/' . $class[1]);
                if (file_exists($root . '/'. $path .'/config.php')) {
                    include $root . '/'. $path .'/config.php';
                }
            }
        }

        $layoutFile = 'app.php';

        if ($includeLayout) {
            if (is_string($includeLayout)) {
                if (!str_ends_with($includeLayout, '.php')) {
                    $includeLayout .= '.php';
                }
                $layoutFile = $includeLayout;
            }
            $layout = $root . $_ENV['C_ROOT'] . 'views/layouts/' . $layoutFile;
        }

        $this->set_new_path($ori_path);
        return $this->include($path, $view, $layout, $data);
    }

}