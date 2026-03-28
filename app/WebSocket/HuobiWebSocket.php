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
        // 创建时自动连接，不需要手动 connect()
        $this->client = $this->clientFactory->create(self::HUOBI_WS_URL);
    }

    public function start()
    {
        $this->logger->info('🔥 开始连接 火币 WebSocket API');

        // 发送订阅
        $this->subscribe('market.btcusdt.ticker');

        // 循环接收消息
        while (true) {
            $data = $this->client->recv();

            if ($data === null || $data === '') {
                $this->logger->error('WebSocket 连接断开');
                break;
            }

            $this->handleMessage($data);
        }
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

        // 心跳回复
        if (isset($json['ping'])) {
            $this->client->push(json_encode(['pong' => $json['ping']]));
            return;
        }

        // 输出价格
        if (isset($json['tick'])) {
            $symbol = str_replace(['market.', '.ticker'], '', $json['ch'] ?? '');
            $price = $json['tick']['close'];
            $this->logger->info("✅ {$symbol} 实时价格：{$price} USDT");
        }
    }
}
