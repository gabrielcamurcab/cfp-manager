<?php

namespace App\Controllers;

use App\Helpers\JwtHelper;
use App\Models\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController
{
    public function login(Request $request, Response $response): Response
    {
        $data = json_decode($request->getBody()->getContents(), true) ?? [];

        if (!isset($data['email'], $data['password']))
        {
            $response->getBody()->write(json_encode(['error' => 'Você precisa preencher email e senha,']));
            return $response->withHeader('Content-type', 'application/json')->withStatus(400);
        }

        $user = User::where('email', $data['email'])->first();

        if (!$user)
        {
            $response->getBody()->write(json_encode(['error' => 'Usuário não encontrado,']));
            return $response->withHeader('Content-type', 'application/json')->withStatus(404);
        }

        if (!password_verify($data['password'], $user->password))
        {
            $response->getBody()->write(json_encode(['error' => 'Senha incorreta,']));
            return $response->withHeader('Content-type', 'application/json')->withStatus(401);
        }

        $accessToken = JwtHelper::generateToken($user->id, 'access');
        $refreshToken = JwtHelper::generateToken($user->id, 'refresh');

        $response->getBody()->write(json_encode([
            'message' => 'Login realizado!',
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken
        ]));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    public function refreshToken(Request $request, Response $response): Response
    {
        $data = json_decode($request->getBody()->getContents(), true) ?? [];

        if (!isset($data['refresh-token']))
        {
            $response->getBody()->write(json_encode(['error' => 'Você precisa preencher o refresh token,']));
            return $response->withHeader('Content-type', 'application/json')->withStatus(400);
        }

        $decoded = JwtHelper::validateToken($data['refresh-token']);

        if (!$decoded) {
            $response->getBody()->write(json_encode(['error' => 'Refresh token inválido,']));
            return $response->withHeader('Content-type', 'application/json')->withStatus(401);
        }

        $accessToken = JwtHelper::generateToken($decoded->sub, 'access');
        $refreshToken = JwtHelper::generateToken($decoded->sub, 'refresh');

        $response->getBody()->write(json_encode([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken
        ]));
        return $response->withHeader('Content-type', 'application/json')->withStatus(200);
    }
}