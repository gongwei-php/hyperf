<?php

declare(strict_types=1);

namespace App\WebSocket;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\WebSocketClient\ClientFactory;
use Hyperf\WebSocketClient\Client;

class HuobiWebSocket
{
    protected const HUOBI_WS_URL = 'wss://api.huobi.pro/ws';

    protected Client $client;

    public function __construct(
        protected ClientFactory $clientFactory,
        protected StdoutLoggerInterface $logger
    ) {
        $this->client = $this->clientFactory->create(self::HUOBI_WS_URL);
    }

    public function start()
    {
        $this->logger->info('🔥 开始连接 火币 WebSocket API');

        $this->client->on('open', function () {
            $this->logger->info('✅ 火币 WebSocket 连接成功');
            $this->subscribe('market.btcusdt.ticker');
        });

        $this->client->on('message', function ($data) {
            $this->handleMessage($data);
        });

        $this->client->on('close', function () {
            $this->logger->warning('❌ 连接关闭');
        });

        $this->client->on('error', function ($e) {
            $this->logger->error('❌ 连接错误：' . $e);
        });

        $this->client->connect();
    }

    protected function subscribe(string $channel)
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

        if (isset($json['ping'])) {
            $this->client->push(json_encode(['pong' => $json['ping']]));
            return;
        }

        if (str_contains($json['ch'] ?? '', '.ticker')) {
            $symbol = str_replace(['market.', '.ticker'], '', $json['ch']);
            $price = $json['tick']['close'];
            $this->logger->info("【{$symbol}】实时价格：{$price} USDT");
        }
    }
}
