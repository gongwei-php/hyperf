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
    protected string $description = '火币行情订阅';

    public function __construct(private ContainerInterface $container)
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->container->get(HuobiWebSocket::class)->start();
    }
}
