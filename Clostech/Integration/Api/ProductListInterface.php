<?php
declare(strict_types=1);

namespace Clostech\Integration\Api;

use Clostech\Integration\Api\Data\ProductInterface;

interface ProductListInterface
{
    /**
     * Get all products with parent-variant relationships
     *
     * @return \Clostech\Integration\Api\Data\ProductInterface[]
     */
    public function getList(): array;
}