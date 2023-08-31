<?php

declare(strict_types=1);

namespace Veloz\Core;

class Router
{
    private string $appUrl;
    public array $action;
    private string $path;
    public string|null $defaultMiddlewareMethod;
    public bool $allowQueryParams = true;
    
    protected array $routes = [];
    protected array $groupIdentifier = [];
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

        $this->defaultMiddlewareMethod = null;
    }

    public function add(string $method, string $pattern, $callback = null, $group = false, $allowAll = null, string $projectDirectory = null, $subdomain = null)
    {
        // Use a regular expression to match the entire url, not just part of it
        $full = '#^' . $this->subFolder . $pattern . '$#';

        $this->routes[] = [
            'method' => $method,
            'pattern' => $full,
            'filtered' => $pattern,
            'callback' => $callback,
            'middleware' => [],
            'group' => $group,
            'allowAll' => $allowAll,
            'projectDirectory' => $projectDirectory,
            'subDomain' => $subdomain,
        ];

        return $this;
    }

    public function group(...$routes)
    {
        // Creates a new group identifier
        $identifier = rand(1000000000000000, 9999999999999999);

        $i = 0;
        while (in_array($identifier, $this->groupIdentifier) && $i < 100) {
            $identifier = rand(1000000000000000, 9999999999999999);
            $i++;
        }

        // Add the identifier to the array
        $this->groupIdentifier[] = $identifier;

        foreach ($routes as $route) {
            $this->add($route[0], $route[1], $route[2], $identifier);
        }

        return $this;
    }

    public function allowAll($path, $realpath, $subdomain, $middleware = null) {
        $this->add('GET', $path, null, true, $realpath, $subdomain)->middleware($middleware);
    }

    public function middlewareRegister(string $name, callable $callback, $methodName): void
    {
        $this->middleware[$name]['methods'][$methodName] = $callback;
    }    

    public function middleware($middleware = null, $method = null)
    {
        // Handles the middleware
        if (is_string($middleware)) {
            if ($method) {
                $middleware .= ':' . $method;
            }
            
            // Checks if the last route has a group
            if ($this->routes[count($this->routes) - 1]['group']) {
                // Adds the middleware to all routes with this group identifier
                foreach ($this->routes as $key => $route) {
                    if ($route['group'] === $this->routes[count($this->routes) - 1]['group']) {
                        $this->routes[$key]['middleware'][] = $middleware;
                    }
                }
                return;
            }

            $this->routes[count($this->routes) - 1]['middleware'] = [$middleware];
        }

        // Handles the middleware if it was used as a group
        if (is_array($middleware)) {
            if (!is_array($method) && $method !== null) {
                throw new \Exception('Invalid middleware method configuration. Method should be an array or nothing.');
            }
    
            $middlewareCount = count($middleware);
            $methodCount = count($method);
    
            if ($methodCount > $middlewareCount) {
                throw new \Exception('Invalid middleware method configuration. More methods provided than middleware.');
            }
    
            for ($i = 0; $i < $middlewareCount; $i++) {
                $middlewareName = $middleware[$i];
                $middlewareMethod = ($i < $methodCount) ? $method[$i] : null;
    
                if ($middlewareMethod) {
                    $middlewareName .= ':' . $middlewareMethod;
                }
    
                $this->routes[count($this->routes) - 1]['middleware'][] = $middlewareName;
            }
        }
    }

    public function set_global_middleware($middleware = null, $method = null, $exclude = null)
    {
        if ($exclude) {
            $uri = $_SERVER['REQUEST_URI'];

            foreach ($exclude as $key => $value) {
                if ($uri === $value) {
                    return;
                }
            }
        }

        $middlewareName = $middleware;
        $middlewareMethod = $method ?? $this->defaultMiddlewareMethod;

        if (!in_array($middlewareName, array_keys($this->middleware))) {
            throw new \Exception('Middleware "'.$middlewareName.'" not found. Check the middleware name in the respective class.');
        }

        foreach ($this->middleware[$middlewareName]['methods'] as $method => $callback) {
            // Checks if the middlewareMethod matches any of the methods in the middleware class
            if ($middlewareMethod === $method) {
                // Executes callback
                $callback();
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

                // Check if query parameters are allowed and handle query parameters
                if ($this->allowQueryParams && parse_url($uri, PHP_URL_QUERY)) {
                    if (preg_match($route['pattern'], parse_url($uri, PHP_URL_PATH), $matches)) {
                        return $this->handleLoad($route, $method, $uri, $matches);
                    }
                }

                $uri = rtrim($uri, '/');
                $lastChar = substr($uri, -1);

                if (is_numeric($lastChar)) {
                    $this->numeric = $lastChar;
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
            $middlewareName = $middleware;
            $middlewareMethod = $this->defaultMiddlewareMethod;
    
            if (str_contains($middleware, ':')) {
                [$middlewareName, $middlewareMethod] = explode(':', $middleware);
            }
    
            if (!in_array($middlewareName, array_keys($this->middleware))) {
                throw new \Exception('Middleware "'.$middlewareName.'" not found. Check the middleware name in the respective class.');
            }

            foreach ($this->middleware[$middlewareName]['methods'] as $method => $callback) {
                // Checks if the middlewareMethod matches any of the methods in the middleware class
                if ($middlewareMethod === $method) {
                    // Executes callback
                    $callback();
                }
            }
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