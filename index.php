<?php

require 'bootstrap.php';

use App\Middleware\CorsMiddleware;
use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;
use App\Controllers\UserController;
use App\Controllers\AuthController;
use App\Controllers\CommunityController;
use App\Middleware\JwtMiddleware;

$app = AppFactory::create();

$app->add(new CorsMiddleware());

$app->group('/user', function (RouteCollectorProxy $group) {
    $group->post('', [UserController::class, 'create']);
    $group->post('/login', [UserController::class, 'login']);
    $group->put('', [UserController::class, 'update'])->add(new JwtMiddleware());
    $group->patch('/password', [UserController::class, 'updatePassword'])->add(new JwtMiddleware());
    $group->get('', [UserController::class, 'getData'])->add(new JwtMiddleware());
});

$app->group('/auth', function (RouteCollectorProxy $group) {
    $group->post('/login', [AuthController::class, 'login']);
});

$app->group('/community', function (RouteCollectorProxy $group){
    $group->post('', [CommunityController::class, 'create'])->add(new JwtMiddleware());
});

$app->get('/', function ($request, $response, $args) {
    $response->getBody()->write(json_encode(['message' => 'API Funcionando']));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function (Request $request, Response $response) {
    $response->getBody()->write(json_encode(['message' => 'Rota não encontrada', 'code' => 404]));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
});

$app->run();