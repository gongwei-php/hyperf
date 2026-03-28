<?php

declare(strict_types=1);

namespace App\Controller;

use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\RestController;
use Hyperf\Redis\RedisFactory;

#[RestController]
class PriceController
{
    protected $redis;

    public function __construct(RedisFactory $redisFactory)
    {
        $this->redis = $redisFactory->get('default');
    }

    #[GetMapping('/price')]
    public function getPrice()
    {
        $price = $this->redis->get('huobi_btc_price');
        return [
            'code' => 200,
            'price' => $price ?: '0',
            'symbol' => 'BTC/USDT'
        ];
    }
}
