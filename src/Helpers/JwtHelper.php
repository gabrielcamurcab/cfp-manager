<?php

namespace App\Helpers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtHelper
{
    public static function generateToken($userId, $type = 'access')
    {
        $config = require __DIR__ . '/../Config/jwt.php';
        $expiration = $type === 'access' ? $config['expiration_access'] : $config['expiration_refresh'];

        $payload = [
            'iss' => $config['issuer'],
            'aud' => $config['audience'],
            'iat' => time(),
            'exp' => time() + $expiration,
            'sub' => $userId,
            'type' => $type
        ];

        return JWT::encode($payload, $config['secret'], 'HS256');
    }

    public static function validateToken($token)
    {
        try {
            $config = require __DIR__ . '/../Config/jwt.php';
            return JWT::decode($token, new Key($config['secret'], 'HS256'));
        } catch (\Exception $e) {
            return null;
        }
    }
}