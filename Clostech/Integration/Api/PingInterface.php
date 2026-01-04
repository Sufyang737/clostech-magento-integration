<?php
declare(strict_types=1);

namespace Clostech\Integration\Api;

interface PingInterface
{
    /**
     * @return string
     */
    public function ping();
}