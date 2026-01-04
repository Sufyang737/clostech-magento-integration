<?php
declare(strict_types=1);

namespace Clostech\Integration\Api;

interface ProductListInterface
{
    /**
     * Get paginated list of products for Clostech integration
     *
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getList(int $page = 1, int $pageSize = 50): array;
}