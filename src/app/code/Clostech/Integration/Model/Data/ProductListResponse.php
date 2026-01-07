<?php
declare(strict_types=1);

namespace Clostech\Integration\Model\Data;

use Clostech\Integration\Api\Data\ProductListResponseInterface;

class ProductListResponse implements ProductListResponseInterface
{
    private int $page;
    private int $pageSize;
    private array $products;

    public function __construct(int $page, int $pageSize, array $products)
    {
        $this->page = $page;
        $this->pageSize = $pageSize;
        $this->products = $products;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    public function getProducts(): array
    {
        return $this->products;
    }
}
