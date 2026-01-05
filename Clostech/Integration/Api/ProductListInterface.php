<?php
declare(strict_types=1);

namespace Clostech\Integration\Api;

interface ProductListInterface
{
    /**
     * Get paginated list of products with parent-variant relationships
     *
     * @param int|null $page
     * @param int|null $pageSize
     * @return string JSON response
     */
    public function getList(?int $page = 1, ?int $pageSize = 50): string;
}