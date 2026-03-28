<?php

declare(strict_types=1);

namespace App\WebSocket;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\WebSocketClient\ClientFactory;

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

        if (isset($json['ping'])) {
            $this->client->push(json_encode(['pong' => $json['ping']]));
            return;
        }

        if (isset($json['tick'])) {
            $price = $json['tick']['close'];
            $this->logger->info("✅ BTC 实时价格：" . $price);

            // 👇 就加这一行（把价格写入文件）
            file_put_contents(BASE_PATH . '/public/price.json', json_encode([
                'price' => $price,
                'time' => date('Y-m-d H:i:s')
            ]));
        }
    }
}
