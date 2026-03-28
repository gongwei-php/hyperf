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
        // 跨域头（核心）
        $response = $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type');

        // 读取价格
        $file = BASE_PATH . '/public/price.json';
        $data = file_exists($file) ? json_decode(file_get_contents($file), true) : ['price' => '0'];

        return $response->json($data);
    }

    // 处理 OPTIONS 预检请求，彻底解决跨域
    #[GetMapping('/price')]
    public function options(ResponseInterface $response)
    {
        return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type')
            ->withStatus(204);
    }
}
