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
    protected ?string $name = 'huobi:start';
    protected string $description = '启动火币 WebSocket 订阅';

    public function __construct(private ContainerInterface $container)
    {
        parent::__construct();
    }

    public function handle()
    {
        $huobi = $this->container->get(HuobiWebSocket::class);
        $huobi->start();
    }
}
