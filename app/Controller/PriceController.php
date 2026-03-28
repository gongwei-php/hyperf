<?php

declare(strict_types=1);

namespace App\Controller;

use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Controller;

#[Controller]
class PriceController
{
    #[GetMapping(path: '/price', options: ['method' => ['GET', 'OPTIONS']])]
    public function index()
    {
        // 强制发送跨域头
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        header('Access-Control-Allow-Headers: *');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            exit;
        }

        $file = BASE_PATH . '/public/price.json';
        $data = file_exists($file) ? json_decode(file_get_contents($file), true) : ['price' => 0];

        echo json_encode($data);
        exit;
    }
}
