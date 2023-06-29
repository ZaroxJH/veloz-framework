<?php

namespace Veloz\Core;

class Middleware
{
    public static function register(Router $router, $middleware)
    {
        // Old way
        // $router->middlewareRegister($middleware->name, function() use ($middleware) {
        //     return $middleware->handle();
        // });

        try {
            $middlewareMethods = get_class_methods($middleware);
            $middlewareMethods = array_diff($middlewareMethods, ['__construct']);
        } catch (\Throwable $th) {
            throw new \Exception('Something went wrong while registering middleware.');
        }

        // Loops through the methods and sets them differently
        foreach ($middlewareMethods as $method) {
            $router->middlewareRegister($middleware->name, function() use ($middleware, $method) {
                return $middleware->$method();
            }, $method);
        }
    }
}