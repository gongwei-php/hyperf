<?php

declare(strict_types=1);

namespace App\Command;

use App\WebSocket\HuobiWebSocket;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Psr\Container\ContainerInterface;

#[Command]
class HuobiCommand extends HyperfCommand
{
    protected $name = 'huobi:start';

    protected string $description = '启动火币 WebSocket 行情订阅';

    // 注入容器
    public function __construct(ContainerInterface $container)
    {
        parent::__construct();
        $this->container = $container;
    }

    public function handle()
    {
        $huobi = $this->container->get(HuobiWebSocket::class);
        $huobi->start();
    }
}
