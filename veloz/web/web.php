<?php

use Veloz\Core\Router;
use Veloz\Core\Middleware;
use Veloz\Middlewares\AuthMiddleware;

$router = new Router();

Middleware::register($router, new AuthMiddleware());

$router->group(
    ['GET', '/veloz/json/get-bind-data', 'Veloz\Modules\Vivencia\Core@get_bind_data_json'],
)->middleware('logged_in', 'loggedIn');