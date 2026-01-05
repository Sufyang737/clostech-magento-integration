<?php
declare(strict_types=1);

namespace Clostech\Integration\Api;

interface ProductListInterface
{
    /**
     *
     *
     * @param int|null $page Page number (default: 1)
     * @param int|null $pageSize Items per page (default: 50)
     * @return array
     */
    public function getList(?int $page = 1, ?int $pageSize = 50): array;
}