<?php

namespace App\Controllers;

use App\Helpers\JwtHelper;
use App\Models\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserController
{
    public function create(Request $request, Response $response): Response
    {
        $data = json_decode($request->getBody()->getContents(), true) ?? [];

        if (!isset($data['email'], $data['phone'], $data['name'], $data['password'])) {
            $response->getBody()->write(json_encode(['error' => 'Campos obrigatórios não preenchidos']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $user = User::create([
                'email' => $data['email'],
                'phone' => $data['phone'],
                'name' => $data['name'],
                'bio' => $data['bio'] ?? null,
                'link_aggregator' => $data['link_aggregator'] ?? null,
                'password' => password_hash($data['password'], PASSWORD_BCRYPT),
            ]);

            $accessToken = JwtHelper::generateToken($user->id, 'access');
            $refreshToken = JwtHelper::generateToken($user->id, 'refresh');

            $response->getBody()->write(json_encode([
                'message' => 'Usuário criado',
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'Erro ao criar usuário', 'details' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

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

    public function update(Request $request, Response $response): Response
    {
        // Verificar se o userId foi passado corretamente
        $userId = $request->getAttribute('userId');

        if (!$userId) {
            $response->getBody()->write(json_encode(['error' => 'Usuário não autenticado']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        // Use parsedBody em vez de getContents()
        $data = $request->getParsedBody();

        // Se estiver vazio, tente ler o corpo diretamente, mas apenas uma vez
        if (empty($data)) {
            $data = json_decode($request->getBody()->getContents(), true) ?? [];
        }

        if (empty($data)) {
            $response->getBody()->write(json_encode(['error' => 'Nenhum dado para atualizar']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $allowedFields = ['name', 'phone', 'bio', 'link_aggregator'];
        $updateData = array_filter($data, fn($key) => in_array($key, $allowedFields), ARRAY_FILTER_USE_KEY);

        if (empty($updateData)) {
            $response->getBody()->write(json_encode(['error' => 'Nenhum campo permitido para atualização']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $user = User::find($userId);

            if (!$user) {
                $response->getBody()->write(json_encode(['error' => 'Usuário não encontrado']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            foreach ($updateData as $key => $value) {
                $user->$key = $value;
            }

            $user->save();

            $response->getBody()->write(json_encode([
                'message' => 'Dados do usuário atualizados com sucesso',
            ]));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'Erro ao atualizar dados', 'details' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}