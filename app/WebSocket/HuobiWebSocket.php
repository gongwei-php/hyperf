<?php

declare(strict_types=1);

namespace App\WebSocket;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\WebSocketClient\ClientFactory;
use Hyperf\WebSocketServer\Sender;
use Hyperf\WebSocketServer\Context;

class HuobiWebSocket
{
    protected const HUOBI_WS_URL = 'wss://api.huobi.pro/ws';
    protected $client;

    public function __construct(
        protected ClientFactory $clientFactory,
        protected StdoutLoggerInterface $logger,
        protected Sender $sender
    ) {
        $this->client = $this->clientFactory->create(self::HUOBI_WS_URL);
    }

    public function start()
    {
        $this->logger->info('🔥 火币 WebSocket 已启动');
        $this->subscribe('market.btcusdt.ticker');

        while (true) {
            $frame = $this->client->recv();
            if (! $frame) {
                $this->logger->error('连接断开');
                break;
            }

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

        // 推送价格给前端
        if (isset($json['tick'])) {
            $price = $json['tick']['close'];
            $this->logger->info("✅ BTC价格：{$price}");

            // 推送给所有前端
            $fd = Context::get('client_fd');
            if ($fd) {
                $this->sender->push($fd, json_encode([
                    'symbol' => 'BTC/USDT',
                    'price' => $price
                ]));
            }
        }
    }
}
