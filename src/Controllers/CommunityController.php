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

    public function getAll(Request $request, Response $response): Response
    {
        $page = $request->getQueryParams()["page"] ?? 1;
        $search = $request->getQueryParams()["search"] ?? null;

        $limit = 10;
        $offset = $limit * ($page-1);

        $query = Community::join('users', 'users.id', '=', 'community.owner_id')
            ->where('community.active', 1)
            ->select(
                'community.id',
                'community.owner_id',
                'community.name',
                'community.website',
                'community.city',
                'community.uf',
                'community.tags',
                'community.bio',
                'users.name as owner_name' // Pegando o nome do owner
            );

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('community.name', 'LIKE', "%{$search}%")
                    ->orWhere('community.bio', 'LIKE', "%{$search}%")
                    ->orWhere('users.name', 'LIKE', "%{$search}%"); // Permite buscar pelo nome do owner
            });
        }

        $data = $query->limit($limit)->offset($offset)->get()->toArray();

        $hasMore = Community::where('active', 1)
            ->skip($offset + $limit)
            ->take(1)
            ->exists();

        $response->getBody()->write(json_encode([
            'data' => $data,
            'page' => $page,
            'hasMore' => $hasMore
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    public function getMy(Request $request, Response $response): Response
    {
        $userId = $request->getAttribute('userId');

        if (!$userId) {
            $response->getBody()->write(json_encode(['error' => 'Usuário não autenticado']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $query = Community::join('users', 'users.id', '=', 'community.owner_id')
            ->where('community.active', 1)
            ->select(
                'community.id',
                'community.name',
                'community.website',
                'community.city',
                'community.uf',
                'community.tags',
                'community.bio',
            );

        $data = $query->where('owner_id', $userId)->get()->first();

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $userId = $request->getAttribute('userId');
        $communityId = $args['id'];

        if (!$userId) {
            $response->getBody()->write(json_encode(['error' => 'Usuário não autenticado']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $data = json_decode($request->getBody()->getContents(), true) ?? [];

        if (empty($data)) {
            $response->getBody()->write(json_encode(['error' => 'Nenhum dado para atualizar']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $allowedFields = ['name', 'website', 'bio', 'city', 'uf', 'tags'];
        $updateData = array_filter($data, fn($key) => in_array($key, $allowedFields), ARRAY_FILTER_USE_KEY);

        if (empty($updateData)) {
            $response->getBody()->write(json_encode(['error' => 'Nenhum campo permitido para atualização']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $community = Community::where('id', $communityId)
                ->where('owner_id', $userId)
                ->first();

            if (!$community) {
                $response->getBody()->write(json_encode(['error' => 'Comunidade não encontrada']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            foreach ($updateData as $key => $value) {
                $community->$key = $value;
            }

            $community->save();

            $response->getBody()->write(json_encode([
                'message' => 'Dados da comunidade atualizadas com sucesso!'
            ]));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'Erro ao atualizar dados', 'details' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}