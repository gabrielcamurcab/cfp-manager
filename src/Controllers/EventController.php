<?php

namespace App\Controllers;
use App\Models\Community;
use App\Models\Event;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class EventController
{
    public function create(Request $request, Response $response): Response
    {
        $userId = $request->getAttribute("userId");

        if (!$userId) {
            $response->getBody()->write(json_encode(['error' => 'Usuário não autenticado']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $data = json_decode($request->getBody()->getContents(), true) ?? [];

        $ownerValidation = Community::where('id', $data['community_id'])->where('user_id', $userId);

        if (!$ownerValidation) {
            $response->getBody()->write(json_encode(['error' => 'Comunidade não encontrada']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        if (!isset($data['community_id'], $data['name'], $data['local'], $data['date'], $data['start_time'], $data['end_time'], $data['cfp_start_date'], $data['cfp_end_date'])) {
            $response->getBody()->write(json_encode(['error' => 'Campos obrigatórios não preenchidos']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $event = Event::create([
            'community_id' => $data['community_id'],
            'name' => $data['name'],
            //'banner' => $data['banner']
            'local' => $data['local'],
            'date' => $data['date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'cfp_start_date' => $data['cfp_start_date'],
            'cfp_end_date' => $data['cfp_end_date'],
        ]);

        $response->getBody()->write(json_encode([
            'message' => 'Comunidade criada com sucesso!',
            'event' => $event
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }

    public function getByCommunityId(Request $request, Response $response, $args): Response
    {
        $id = $args['id'];
        $page = $request->getQueryParams()["page"] ?? 1;

        $limit = 10;
        $offset = $limit * ($page-1);

        if (!$id) {
            $response->getBody()->write(json_encode(['error' => 'Informe a ID da comunidade.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $query = Event::where('community_id', $id)
            ->select(
                'id',
                'name',
                'local',
                'date'
            )->orderBy('date');

        $data = $query->limit($limit)->offset($offset)->get()->toArray();

        $hasMore = Event::where('community_id', $id)
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

    public function getById(Request $request, Response $response, $args): Response
    {
        $id = $args['id'];

        if (!$id) {
            $response->getBody()->write(json_encode(['error' => 'Informe a ID da comunidade.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $data = Event::where('id', $id)
            ->select(
                'id',
                'name',
                'local',
                'date',
                'start_time',
                'end_time',
                'cfp_start_date',
                'cfp_end_date',
            )->first();

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    public function update(Request $request, Response $response, $args): Response
    {
        $id = $args['id'];
        $userId = $request->getAttribute('userId');
        $data = json_decode($request->getBody()->getContents(), true) ?? [];

        if (!$id) {
            $response->getBody()->write(json_encode(['error' => 'Informe a ID da comunidade.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        if (empty($data)) {
            $response->getBody()->write(json_encode(['error' => 'Nenhum dado para atualizar']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $allowedFields = ['name', 'local', 'date', 'start_time', 'end_time', 'cfp_start_date', 'cfp_end_date'];
        $updateData = array_filter($data, fn($key) => in_array($key, $allowedFields), ARRAY_FILTER_USE_KEY);

        if (empty($updateData)) {
            $response->getBody()->write(json_encode(['error' => 'Nenhum campo permitido para atualização']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try
        {
            $community = Community::where('owner_id', $userId)
                ->select('id')
                ->first();

            if (!$community) {
                $response->getBody()->write(json_encode(['error' => 'Não é possível atualizar esse evento.']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
            }

            $event = Event::where('id', $id)
                ->where('community_id', $community['id'])
                ->first();

            if (!$event) {
                $response->getBody()->write(json_encode(['error' => 'Evento não encontrado']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            foreach ($updateData as $key => $value) {
                $event->$key = $value;
            }

            $event->save();

            $response->getBody()->write(json_encode([
                'message' => 'Dados do evento atualizadas com sucesso!'
            ]));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e)
        {
            $response->getBody()->write(json_encode(['error' => 'Erro ao atualizar dados', 'details' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}