<?php

namespace Veloz\Core;

class Middleware
{
    public static function register(Router $router, $middleware)
    {
        $router->middlewareRegister($middleware->name, function() use ($middleware) {
            return $middleware->handle();
        });
    }
}