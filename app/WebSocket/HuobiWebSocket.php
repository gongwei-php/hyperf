<?php

declare(strict_types=1);

namespace App\WebSocket;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\WebSocketClient\ClientFactory;
use Hyperf\Redis\RedisFactory;

class HuobiWebSocket
{
    protected const HUOBI_WS_URL = 'wss://api.huobi.pro/ws';

    protected $client;
    protected $redis;

    public function __construct(
        protected ClientFactory $clientFactory,
        protected StdoutLoggerInterface $logger,
        RedisFactory $redisFactory
    ) {
        $this->client = $this->clientFactory->create(self::HUOBI_WS_URL);
        $this->redis = $redisFactory->get('default');
    }

    public function start()
    {
        $this->logger->info('🔥 火币行情服务已启动');
        $this->subscribe('market.btcusdt.ticker');

        while (true) {
            $frame = $this->client->recv();
            if (! $frame) break;

            $this->handleMessage((string) $frame);
        }
    }

    protected function subscribe($channel)
    {
        $this->client->push(json_encode([
            'sub' => $channel,
            'id' => uniqid()
        ]));
        $this->logger->info("📡 已订阅：{$channel}");
    }

    protected function handleMessage(string $data)
    {
        $decode = gzdecode($data);
        $json = json_decode($decode, true);

        // 心跳
        if (isset($json['ping'])) {
            $this->client->push(json_encode(['pong' => $json['ping']]));
            return;
        }

        // 把价格存入 Redis
        if (isset($json['tick'])) {
            $price = $json['tick']['close'];
            $this->redis->set('huobi_btc_price', $price);
            $this->logger->info("✅ BTC实时价格：" . $price);
        }
    }
}
