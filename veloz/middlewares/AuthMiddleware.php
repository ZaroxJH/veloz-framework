<?php

namespace Veloz\Middlewares;

use Veloz\Helpers\Auth;

class AuthMiddleware
{
    public string $name = 'logged_in';

    public function loggedIn()
    {
        if (Auth::check()) {
            return;
        }

        redirect($_ENV['APP_URL']);
    }

    public function notLoggedIn()
    {
        if (!Auth::check()) {
            return;
        }

        redirect('/');
    }
}