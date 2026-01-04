<?php
declare(strict_types=1);

namespace Clostech\Integration\Model;

use Clostech\Integration\Api\ProductListInterface;

class ProductList implements ProductListInterface
{
    public function getList(int $page = 1, int $pageSize = 50): array
    {
        return [
            'page' => $page,
            'pageSize' => $pageSize,
            'products' => []
        ];
    }
}
