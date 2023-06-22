<?php

namespace Veloz\Models;

use Veloz\Core\Model;

class Session extends Model
{
    protected string $table = 'sessions';

    public static function check($userId, $sessionId): bool
    {
        $session = self::exists(['user_id' => $userId, 'session_id' => $sessionId]);

        if (!is_array($session) || empty($session)) {
            return false;
        }

        return true;
    }

    public static function destroy($userId): void
    {
        self::delete(['user_id' => $userId]);
    }

    public static function store($userId, $sessionId, $expirationDate = null)
    {
        self::destroy($userId);

        if (!is_null($expirationDate)) {
            $expirationDate = date('Y-m-d H:i:s', $expirationDate);
        }

        self::insert([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'expires' => $expirationDate,
        ]);
    }
}