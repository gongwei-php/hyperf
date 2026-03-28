<?php

declare(strict_types=1);

namespace App\WebSocket;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\WebSocketClient\ClientFactory;
use Hyperf\WebSocketClient\Frame;

class HuobiWebSocket
{
    protected const HUOBI_WS_URL = 'wss://api.huobi.pro/ws';

    protected $client;

    public function __construct(
        protected ClientFactory $clientFactory,
        protected StdoutLoggerInterface $logger
    ) {
        $this->client = $this->clientFactory->create(self::HUOBI_WS_URL);
    }

    public function start()
    {
        $this->logger->info('🔥 开始连接 火币 WebSocket API');

        $this->subscribe('market.btcusdt.ticker');

        while (true) {
            /** @var Frame $frame */
            $frame = $this->client->recv();

            if (! $frame) {
                $this->logger->error('WebSocket 断开连接');
                break;
            }

            // 🔥 关键修复：直接把对象转字符串，或直接用 (string)$frame
            $this->handleMessage((string) $frame);
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

    // 直接接收字符串
    protected function handleMessage(string $data)
    {
        $decode = gzdecode($data);
        $json = json_decode($decode, true);

        // 心跳
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
