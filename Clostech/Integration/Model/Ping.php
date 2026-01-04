<?php
declare(strict_types=1);

namespace Clostech\Integration\Model;

use Clostech\Integration\Api\PingInterface;

class Ping implements PingInterface
{
    public function ping()
    {
        return 'pong';
    }
}