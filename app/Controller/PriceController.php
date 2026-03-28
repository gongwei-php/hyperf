<?php

declare(strict_types=1);

namespace App\Controller;

use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[Controller]
class PriceController
{
    #[GetMapping('/price')]
    public function index(ServerRequestInterface $request, ResponseInterface $response)
    {
        $response = $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type');

        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            return $response->withStatus(204);
        }

        $file = BASE_PATH . '/public/prices.json';
        $data = file_exists($file) ? json_decode(file_get_contents($file), true) : [];

        return $response->json($data);
    }
}
