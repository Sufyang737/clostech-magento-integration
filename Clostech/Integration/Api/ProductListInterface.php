<?php
declare(strict_types=1);

namespace Clostech\Integration\Api;

use Clostech\Integration\Model\Data\ProductsResponse;

interface ProductListInterface
{
    /**
     *
     *
     * @param int|null $page Page number (default: 1)
     * @param int|null $pageSize Items per page (default: 50)
     * @return \Clostech\Integration\Model\Data\ProductsResponse
     */
    public function getList(?int $page = 1, ?int $pageSize = 50): ProductsResponse;
}