<?php

declare(strict_types=1);

namespace App\WebSocket;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\WebSocketClient\ClientFactory;

class HuobiWebSocket
{
    protected const HUOBI_WS_URL = 'wss://api.huobi.pro/ws';
    protected $client;

    // 要订阅的币种
    protected $symbols = [
        'btcusdt',
        'ethusdt',
        'solusdt',
        'dogeusdt'
    ];

    public function __construct(
        protected ClientFactory $clientFactory,
        protected StdoutLoggerInterface $logger
    ) {
        $this->client = $this->clientFactory->create(self::HUOBI_WS_URL);
    }

    public function start()
    {
        $this->logger->info('🔥 多币种行情服务已启动');

        // 订阅所有币种
        foreach ($this->symbols as $s) {
            $this->subscribe("market.{$s}.ticker");
            $this->logger->info("📡 已订阅：{$s}");
        }

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

        // 处理行情数据
        if (isset($json['tick']) && isset($json['ch'])) {
            $symbol = str_replace('market.', '', str_replace('.ticker', '', $json['ch']));
            $price = $json['tick']['close'];

            $this->logger->info("✅ {$symbol} = {$price}");

            // 读取所有价格
            $file = BASE_PATH . '/public/prices.json';
            $prices = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
            $prices[$symbol] = $price;
            $prices['time'] = date('Y-m-d H:i:s');

            // 自动创建目录
            if (!is_dir(BASE_PATH . '/public')) {
                mkdir(BASE_PATH . '/public', 0777, true);
            }

            file_put_contents($file, json_encode($prices));
        }
    }
}
