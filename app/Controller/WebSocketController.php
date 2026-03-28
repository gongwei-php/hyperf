<?php

declare(strict_types=1);

namespace App\Controller;

use Hyperf\WebSocketServer\Annotation\WebSocket;
use Hyperf\WebSocketServer\Context;
use Hyperf\WebSocketServer\Sender;
use Psr\Container\ContainerInterface;

#[WebSocket(path: '/ws')]
class WebSocketController
{
    public $sender;
    public $container;

    public function __construct(Sender $sender, ContainerInterface $container)
    {
        $this->sender = $sender;
        $this->container = $container;
    }

    public function onOpen($fd, $request)
    {
        Context::set('client_fd', $fd);
        echo "客户端 {$fd} 已连接\n";
    }

    public function onClose($fd)
    {
        echo "客户端 {$fd} 已断开\n";
    }
}
