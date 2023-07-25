<?php

namespace Veloz\Helpers;

use Veloz\Models\User;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception\BadFormatException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Key;
use Veloz\Models\Session;

class Auth
{
    protected static $user;
    private static int $userId;

    /**
     * Checks if the user is logged in.
     *
     * @return bool
     */
    public static function check(): bool
    {
        // First we check if the session_id is in the session
        if (!isset($_SESSION[$_ENV['APP_NAME']]['user']['sessionId'])) {
            return false;
        }

        // Now we check if the session exists in the database
        if (!Session::exists(['user_id' => Auth::id(), 'session_id' => $_SESSION[$_ENV['APP_NAME']]['user']['sessionId']])) {
            return false;
        }

        if (!isset($_SESSION[$_ENV['APP_NAME']]['user']) && !self::user()) {
            return false;
        }

        return true;
    }

    public static function user(): ?object
    {
        if (!is_null(self::$user)) {
            return (object) self::$user ?? self::$user;
        }

        $id = self::id();

        // Try getting the user from the database
        $user = User::join('role_user', 'id', ['user_id', 'role_id'], ['users.id', $id])
            ->get();

        if (is_null($user)) {
            return null;
        }

        if (isset($user[0])) {
            $user = (object) $user[0];
        }
        self::$user = (object) $user ?? $user;

        // Return the user
        return self::$user;
    }

    public static function id()
    {
        return veloz_session('user.id');
    }

    public static function isAdmin(): bool
    {
        return match (self::user()->role_id) {
            2, 3 => true,
            default => false,
        };
    }

    public static function set($user): void
    {
        veloz_session_set('user', [
            'id' => $user['id'] ?? $_SESSION[$_ENV['APP_NAME']]['user']['id'] ?? '',
            'name' => $user['name'] ?? "",
            'sessionId' => $user['sessionId'] ?? $_SESSION[$_ENV['APP_NAME']]['user']['sessionId'] ?? '',
            'role_id' => $user['role_id'] ?? $_SESSION[$_ENV['APP_NAME']]['user']['role_id'] ?? '',
        ]);
    }

    public static function loginWithToken(): void
    {
        $user = self::$user;

        $sessionId = session_create_id();
        $user['sessionId'] = $sessionId;

        // Store the session in the database
        Session::store($user['id'], $sessionId);

        // Store the session identifier in the session and in the database
        self::set($user);
    }

    /**
     * @throws BadFormatException
     * @throws EnvironmentIsBrokenException
     */
    public static function try_login($credentials, $remember): bool
    {
        // Check if the user exists
        $user = User::exists(['email' => $credentials['email']]);

        if ($user) {
            if (password_verify($credentials['password'], $user['password'])) {
                // Generate a unique session identifier
                $sessionId = session_create_id();

                if ($remember) {
                    $rememberToken = bin2hex(random_bytes(32));
                    // Retrieve the encryption key from the .env file
                    $encryptionKey = $_ENV['ENCRYPTION_KEY'];
                    $key = Key::loadFromAsciiSafeString($encryptionKey);
                    // Encrypt the session identifier
                    $encryptedToken = Crypto::encrypt($rememberToken, $key);
                    // Store the encrypted session identifier as the user's remember token in a cookie
                    setcookie("remember_token", $encryptedToken, time() + (14*24*60*60), "/");
                    // Store the unencrypted session identifier as the user's remember_token in the database
                    User::update(['remember_token' => $rememberToken], $user['id']);
                }

                // Store the session identifier in the session and in the database
                $user['sessionId'] = $sessionId;
                Session::store($user['id'], $sessionId);
                self::set($user);
                unset ($_SESSION[$_ENV['APP_NAME']]['email']);
                return true;
            }
        }
        veloz_session_set('email', $credentials['email']);
        return false;
    }

    public static function checkRememberToken()
    {
        if (!Auth::check()) {
            // Retrieve the encryption key from the .env file
            $encryptionKey = $_ENV['ENCRYPTION_KEY'];
            $key = Key::loadFromAsciiSafeString($encryptionKey);

            // Check if the remember_token cookie is set
            if (isset($_COOKIE['remember_token'])) {
                // Decrypt the remember token
                try {
                    $rememberToken = Crypto::decrypt($_COOKIE['remember_token'], $key);
                } catch (\Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException $e) {
                    // If the token is tampered or invalid, return false
                    return false;
                }

                // Check if the remember_token matches the user's remember_token in the database
                $user = User::join('role_user', 'id', ['user_id', 'role_id'], ['users.remember_token', $rememberToken])
                    ->get();

                if ($user) {
                    self::$user = $user;
                    return true;
                }
            }
        }
        return false;
    }

    private static function generateKey()
    {
        $key = Key::createNewRandomKey();
        return $key->saveToAsciiSafeString();
    }

    public static function name()
    {
        return self::user('name');
    }

    public static function email()
    {
        return self::user('email');
    }
}
