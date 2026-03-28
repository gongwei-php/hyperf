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
            /** @var Frame $data */
            $data = $this->client->recv();

            if (! $data instanceof Frame || $data->getPayload() === '') {
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

    // 接收 Frame 类型
    protected function handleMessage(Frame $frame)
    {
        $data = $frame->getPayload(); // 从 Frame 中获取真实数据
        $decode = gzdecode($data);
        $json = json_decode($decode, true);

        if (isset($json['ping'])) {
            $this->client->push(json_encode(['pong' => $json['ping']]));
            return;
        }

        if (isset($json['tick'])) {
            $symbol = str_replace(['market.', '.ticker'], '', $json['ch'] ?? '');
            $price = $json['tick']['close'];
            $this->logger->info("✅ {$symbol} 实时价格：{$price} USDT");
        }
    }
}
