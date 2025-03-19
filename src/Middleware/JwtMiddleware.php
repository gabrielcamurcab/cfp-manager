<?php

namespace App\Middleware;

use App\Helpers\JwtHelper;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class JwtMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');
        if (!$authHeader) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(['error' => 'Token não fornecido']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        // Verifica se o cabeçalho começa com "Bearer "
        if (strpos($authHeader, 'Bearer ') !== 0) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(['error' => 'Formato de token inválido']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $token = trim(str_replace('Bearer ', '', $authHeader));

        if (empty($token)) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(['error' => 'Token vazio']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        try {
            $decoded = JwtHelper::validateToken($token);
            $request = $request->withAttribute('userId', $decoded->sub);

            return $handler->handle($request);
        } catch (\Exception $e)
        {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(['error' => 'Token inválido', 'details' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }
    }
}