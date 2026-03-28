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

        // 连接
        $this->client->connect();

        // 订阅
        $this->subscribe('market.btcusdt.ticker');

        // 循环接收消息（Hyperf 官方正确写法）
        while (true) {
            // 读取消息
            $data = $this->client->recv();
            if ($data === '' || $data === null) {
                $this->logger->error('WebSocket 断开连接');
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
        // 火币数据 gzip 解压
        $decode = gzdecode($data);
        $json = json_decode($decode, true);

        // 心跳回复
        if (isset($json['ping'])) {
            $this->client->push(json_encode(['pong' => $json['ping']]));
            return;
        }

        // 输出实时价格
        if (isset($json['tick'])) {
            $symbol = str_replace(['market.', '.ticker'], '', $json['ch'] ?? '');
            $price = $json['tick']['close'];
            $this->logger->info("✅ {$symbol} 价格：{$price} USDT");
        }
    }
}
