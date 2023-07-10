<?php

namespace Veloz\Core;

use DOMDocument;
use DOMXPath;

class View
{
    protected string $viewPath;
    protected string $appPath;
    protected array $data = [];
    protected string $root = '';
    protected bool $checked = false;

    public function __construct()
    {
        $this->root = $_SERVER['DOCUMENT_ROOT'];
        $this->appPath = $this->root . $_ENV['APP_ROOT'] . 'views';
    }

    /**
     * Includes the view file and returns the content
     *
     * @param $path
     * @param string $view
     * @param $layout
     * @param array $data
     * @return \tidy|string
     * @throws \Exception
     */
    public function include($path, string $view, $layout, array $data = []): string
    {
        // Define the right paths
        $this->viewPath = $this->root . '/'. $path .'/views';

        // Throw an exception when the view is not found
        if (!is_file($this->viewPath . '/' . $view . '.php')) {
            throw new \Exception('View not found');
        }

        // Start output buffering
        ob_start();

        extract($data[0] ?? $data);

        if (isset($titlePrefix)) {
            if (isset($_ENV['TITLE_PREFIX'])) {
                $title .= $_ENV['TITLE_PREFIX'];
            }
        }

        include $this->viewPath . '/' . $view . '.php';

        if (!$layout && $notice = check_notice()) {
            extract($notice);
            include_once $this->appPath . '/partials/notification.php';
        }

        // Store the buffered output in a variable
        $content = ob_get_clean();

        // TODO: allow admin pages to disable the layout
        if ($layout) {
            ob_start();

            include_once $layout;

            if (str_contains($view, 'admin/')) {
                $adminLayout = $this->viewPath . '/' . 'admin/layouts/app.php';
                if (file_exists($adminLayout)) {
                    include_once $adminLayout;
                }
            }

            if ($notice = check_notice()) {
                extract($notice);
                include_once $this->appPath . '/partials/notification.php';
            }

            $content = ob_get_clean();
        }

        if (class_exists('tidy')) {
            $tidy = new \tidy();
            $tidy->parseString($content, [
                'indent' => true,
                'indent-spaces' => 4,
                'wrap' => 200,
            ], 'utf8');
            $tidy->cleanRepair();

            return $tidy;
        }

        return $content;
    }

    public function __get($name)
    {
        return $this->data[$name] ?? null;
    }

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }
}
