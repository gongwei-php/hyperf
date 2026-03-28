<?php

declare(strict_types=1);

namespace App\Controller;

use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Controller;

#[Controller]
class PriceController
{
    #[GetMapping('/price')]
    public function index()
    {
        // 读取我们写入的价格
        $file = BASE_PATH . '/public/price.json';
        $data = file_exists($file) ? json_decode(file_get_contents($file), true) : ['price' => 0];

        return $data;
    }
}
