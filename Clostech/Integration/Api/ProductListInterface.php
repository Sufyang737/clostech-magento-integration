<?php
declare(strict_types=1);

namespace Clostech\Integration\Api;

interface ProductListInterface
{
    /**
     *
     *
     * @param int|null
     * @param int|null
     * @return array
     */
    public function getList(?int $page = 1, ?int $pageSize = 50): array;
}