<?php

namespace App\Controllers;
use App\Models\Community;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CommunityController
{
    public function create(Request $request, Response $response): Response
    {
        $userId = $request->getAttribute('userId');

        if (!$userId) {
            $response->getBody()->write(json_encode(['error' => 'Usuário não autenticado']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $data = json_decode($request->getBody()->getContents(), true) ?? [];

        if (!isset($data['name'], $data['website'], $data['bio'], $data['city'], $data['uf'], $data['tags'])) {
            $response->getBody()->write(json_encode(['error' => 'Campos obrigatórios não preenchidos']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $community = Community::create([
                'owner_id' => $userId,
                'name' => $data['name'],
                'website' => $data['website'],
                'bio' => $data['bio'],
                'city' => $data['city'],
                'uf' => $data['uf'],
                'tags' => json_encode(['tags' => $data['tags']])
            ]);

            $response->getBody()->write(json_encode([
                'message' => 'Comunidade criada com sucesso!',
                'community' => $community
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'Erro ao criar comunidade', 'details' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}