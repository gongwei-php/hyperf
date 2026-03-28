<?php

declare(strict_types=1);

namespace App\WebSocket;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\WebSocketClient\ClientFactory;
use Hyperf\WebSocketClient\WebSocketClient;
use Psr\Container\ContainerInterface;

class HuobiWebSocket
{
    // 火币官方 WebSocket 地址（公共行情）
    protected const HUOBI_WS_URL = 'wss://api.huobi.pro/ws';

    protected WebSocketClient $client;

    public function __construct(
        protected ContainerInterface $container,
        protected ClientFactory $clientFactory,
        protected StdoutLoggerInterface $logger
    ) {
        // 创建 WebSocket 客户端
        $this->client = $this->clientFactory->create(self::HUOBI_WS_URL);
    }

    /**
     * 启动连接并订阅
     */
    public function start()
    {
        $this->logger->info('🔥 开始连接 火币 WebSocket API');

        $this->client->on('open', function () {
            $this->logger->info('✅ 火币 WebSocket 连接成功');

            // ========== 订阅：BTC/USDT 实时价格 ==========
            $this->subscribe('market.btcusdt.ticker');

            // ========== 订阅：BTC/USDT 深度盘口 ==========
            // $this->subscribe('market.btcusdt.depth.step0');
        });

        // 接收消息
        $this->client->on('message', function ($data) {
            $this->handleMessage($data);
        });

        // 关闭连接
        $this->client->on('close', function () {
            $this->logger->info('❌ 火币 WebSocket 连接关闭');
        });

        // 连接错误
        $this->client->on('error', function ($e) {
            $this->logger->error('❌ 连接错误：' . $e);
        });

        $this->client->connect();
    }

    /**
     * 订阅频道
     */
    protected function subscribe(string $channel)
    {
        $this->client->push(json_encode([
            'sub' => $channel,
            'id' => uniqid()
        ]));

        $this->logger->info("📡 已订阅：{$channel}");
    }

    /**
     * 处理火币返回的数据（GZIP 解压 + 解析）
     */
    protected function handleMessage(string $data)
    {
        // 火币返回的数据是 GZIP 压缩的，必须解压
        $decode = gzdecode($data);
        $json = json_decode($decode, true);

        // 心跳包（必须回复）
        if (isset($json['ping'])) {
            $this->client->push(json_encode(['pong' => $json['ping']]));
            return;
        }

        // 处理 ticker 数据（实时价格）
        if (str_contains($json['ch'] ?? '', '.ticker')) {
            $ticker = $json['tick'];
            $symbol = str_replace('market.', '', str_replace('.ticker', '', $json['ch']));

            $this->logger->info("【{$symbol}】 价格：{$ticker['close']} USDT");
            return;
        }
    }
}
