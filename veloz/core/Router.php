<?php

declare(strict_types=1);

namespace Veloz\Core;

class Router
{
    private string $appUrl;
    public array $action;
    private string $path;
    
    protected array $routes = [];
    protected array $middleware = [];
    protected array $subdomains = [];
    protected $subFolder = '';
    protected $numeric = false;

    public function __construct()
    {
        if ($_ENV['APP_SUBFOLDER']) {
            $this->subFolder = $_ENV['APP_SUBFOLDER'];
        }

        $this->appUrl = $_ENV['APP_URL'];

        // If there is a third / in the url, remove it and everything after it
        if (substr_count($_ENV['APP_URL'], '/') > 2) {
            $this->appUrl = str_replace('/' . $_ENV['APP_NAME'], '', $_ENV['APP_URL']);
        }

        if (str_contains($this->appUrl, 'http://')) {
            $this->appUrl = str_replace('http://', '', $this->appUrl);
        } elseif (str_contains($this->appUrl, 'https://')) {
            $this->appUrl = str_replace('https://', '', $this->appUrl);
        }
    }

    public function add(string $method, string $pattern, $callback = null, $allowAll = null, string $projectDirectory = null, $subdomain = null)
    {
        // Use a regular expression to match the entire url, not just part of it
        $full = '#^' . $this->subFolder . $pattern . '$#';

        $this->routes[] = [
            'method' => $method,
            'pattern' => $full,
            'filtered' => $pattern,
            'callback' => $callback,
            'middleware' => [],
            'allowAll' => $allowAll,
            'projectDirectory' => $projectDirectory,
            'subDomain' => $subdomain,
        ];

        return $this;
    }

    public function group(...$routes)
    {
        foreach ($routes as $route) {
            $this->add($route[0], $route[1], $route[2]);
        }

        return $this;
    }

    public function allowAll($path, $realpath, $subdomain, $middleware = null) {
        $this->add('GET', $path, null, true, $realpath, $subdomain)->middleware($middleware);
    }

    public function middlewareRegister(string $name, callable $callback): void
    {
        $this->middleware[$name] = $callback;
    }

    public function middleware($middleware = null)
    {
        // Handles the middleware
        if (is_string($middleware)) {
            $this->routes[count($this->routes) - 1]['middleware'][] = $middleware;
        }

        // Handles the middleware if it was used on a group
        if (is_array($middleware)) {
            foreach ($middleware as $m) {
                $this->routes[count($this->routes) - 1]['middleware'][] = $m;
            }
        }
    }

    public function match(string $method, string $uri): string|array|bool|null
    {
        foreach ($this->routes as $route) {
            if (!$route['allowAll']) {
                if ($route['method'] === $method && preg_match($route['pattern'], $uri, $matches)) {
                    return $this->handleLoad($route, $method, $uri, $matches);
                }

                if ($this->numeric) {
                    if (str_contains($route['filtered'], '{slug}')) {

                        $uri = substr($uri, 0, strrpos($uri, '/'));
                        $filtered = str_replace('/{slug}', '', $route['filtered']);

                        if (str_contains($uri, $_ENV['APP_NAME'])) {
                            $uri = str_replace('/' . $_ENV['APP_NAME'], '', $uri);
                        }

                        // If uri and filtered are the same, then we have a match
                        if ($uri === $filtered) {
                            return $this->handleLoad($route, $method, $uri, [$this->numeric]);
                        }
                    }
                    continue;
                }

                $uri = rtrim($uri, '/');
                $lastChar = substr($uri, -1);

                if (is_numeric($lastChar)) {
                    $this->numeric = $lastChar;
                }
            } else {
                if (preg_match($route['pattern'], $uri, $matches)) {
                    return $this->handleLoad($route, $method, $uri);
                }
            }
        }
        return null;
    }

    public function registerSubdomain(string $domain): void
    {
        $main = str_replace(['http://', 'https://'], '', $_ENV['APP_URL']);
        $this->subdomains[] = $domain . '.' . $main;
    }

    private function handleLoad($route, $method, $uri, $matches = [])
    {
        // Execute middleware
        foreach ($route['middleware'] as $middleware) {
            if (!in_array($middleware, array_keys($this->middleware))) {
                throw new \Exception('Middleware not found. Check the middleware name in the respective class.');
            }
            $this->middleware[$middleware]();
        }

        // Extract controller class and action from callback string
        [$controllerClass, $action] = explode('@', $route['callback'], 2);
        if (!class_exists($controllerClass)) {
            throw new \Exception("Invalid controller: $controllerClass");
        }

        if (!method_exists($controllerClass, $action)) {
            throw new \Exception("Action for: $controllerClass@$action");
        }

        // Extract placeholders from the URL
        $placeholders = array_filter($matches, 'is_numeric', ARRAY_FILTER_USE_KEY);

        // Pass the placeholders as arguments to the controller action
        return [
            'controller' => $controllerClass,
            'action' => $action,
            'arguments' => array_values($placeholders),
        ];
    }

    public function dispatch(string $method, string $uri): string|bool
    {
        // // Checks if a subdomain was requested
        // if ($_SERVER['HTTP_HOST'] != $this->appUrl) {
        //     // Requested subdomain was not registered
        //     return $this->set404();
        // }

        $match = $this->match($method, $uri);

        if ($match) {
            $controllerClass = $match['controller'];
            $action = $match['action'];
            $arguments = $match['arguments'];

            // Create an instance of the controller class
            $controller = new $controllerClass();

            // Call the action method on the controller and pass the arguments
            return $controller->$action(...$arguments);
        }

        return $this->set404();
    }

    private function load404(): bool
    {
        $this->path = $_SERVER['DOCUMENT_ROOT'] . $_ENV['APP_ROOT'] . 'views/404.php';

        // Look for a 404 page in a views folder anywhere inside app
        return file_exists($this->path);
    }

    private function set404()
    {
        // Return a 404 response
        http_response_code(404);

        if ($this->load404()) {
            ob_start();
            include $this->path;
            return ob_get_clean();
        }

        return '404 Not Found';
    }

}