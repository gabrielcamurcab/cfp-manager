<?php

require 'bootstrap.php';

use App\Middleware\CorsMiddleware;
use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$app = AppFactory::create();

$app->add(new CorsMiddleware());

$app->get('/', function ($request, $response, $args) {
    $response->getBody()->write(json_encode(['message' => 'API Funcionando']));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function (Request $request, Response $response) {
    $response->getBody()->write(json_encode(['message' => 'Rota não encontrada', 'code' => 404]));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
});

$app->run();