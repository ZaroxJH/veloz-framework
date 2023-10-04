<?php 

namespace Veloz\Core;

use Veloz\Core\Router;
use Veloz\Web\Request;

class PageCaching
{
    public static function cache_app($root): void
    {
        $app_root = $root . $_ENV['APP_ROOT'];
        $cache_path = $app_root . 'resources/cache/pages/';
        $view_path = $app_root . 'views/';
        $routes = $app_root . 'routes/web.php';
        
        // Checks if path exists
        if (!is_dir($app_root)) {
            echoOutput('App folder not found. In: ' . $app_root, 1);
            return;
        }

        // Checks path for cache folder
        if (!is_dir($cache_path)) {
            echoOutput('Cache folder not found. In: ' . $cache_path, 1);
            echoOutput('Creating cache folder...', 1);
            mkdir($cache_path, 0777, true);
        }

        // Checks all folders and if they exist
        if (!is_dir($view_path)) {
            echoOutput('Views folder not found. In: ' . $view_path, 1);
            return;
        }

        // Checks if routes file exists
        if (!is_file($routes)) {
            echoOutput('Routes file not found. In: ' . $routes, 1);
            return;
        }

        $router = new Router();
        require_once $routes;

        $routes = $router->getRoutes();

        $get_routes = [];

        foreach ($routes as $route) {
            if ($route['method'] === 'GET') {
                array_push($get_routes, $route);
            }
        }

        $user_agent = 'Veloz/Caching';

        foreach ($get_routes as $get_route) {
            echoOutput('Caching route: ' . $_ENV['APP_URL'] . $get_route['filtered'], 1);

            // Tries sending an HTTP request to the first route
            $request = Request::send([
                'url' => $_ENV['APP_URL'] . $get_route['filtered'],
                'method' => 'GET',
                'headers' => [
                    'User-Agent' => $user_agent,
                ],
            ]);

            if (!$request) {
                echoOutput('Failed to send request to ' . $get_route['url'], 1);
                return;
            }

            $response = Request::get_response();

            $html = $response['data'];

            // Get current timestamp
            $timestamp = time();
            $hash = md5($get_route['filtered']);
    
            $filename = $cache_path . $hash . '.html';

            // Create the file
            file_put_contents($filename, $html);

            echoOutput('Cached route: ' . $_ENV['APP_URL'] . $get_route['filtered'], 1);
        }
    }
}
    