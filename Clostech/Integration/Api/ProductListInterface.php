<?php
declare(strict_types=1);

namespace Clostech\Integration\Api;

use Clostech\Integration\Api\Data\ProductsResponseInterface;

interface ProductListInterface
{
    /**
     * Get paginated list of products with parent-variant relationships
     *
     * @param int|null $page
     * @param int|null $pageSize
     * @return \Clostech\Integration\Api\Data\ProductsResponseInterface
     */
    public function getList(?int $page = 1, ?int $pageSize = 50): ProductsResponseInterface;
}