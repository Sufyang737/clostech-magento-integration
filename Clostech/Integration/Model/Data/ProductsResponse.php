<?php
declare(strict_types=1);

namespace Clostech\Integration\Model\Data;

use JsonSerializable;

class ProductsResponse implements JsonSerializable
{
    private bool $success;
    private int $page;
    private int $pageSize;
    private int $totalCount;
    private array $products;
    private ?string $error;

    public function __construct(
        bool $success,
        int $page,
        int $pageSize,
        int $totalCount,
        array $products,
        ?string $error = null
    ) {
        $this->success = $success;
        $this->page = $page;
        $this->pageSize = $pageSize;
        $this->totalCount = $totalCount;
        $this->products = $products;
        $this->error = $error;
    }

    public function jsonSerialize(): array
    {
        $data = [
            'success' => $this->success,
            'page' => $this->page,
            'page_size' => $this->pageSize,
            'total_count' => $this->totalCount,
            'products' => $this->products
        ];

        if ($this->error !== null) {
            $data['error'] = $this->error;
        }

        return $data;
    }

    public function getSuccess(): bool
    {
        return $this->success;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    public function getProducts(): array
    {
        return $this->products;
    }

    public function getError(): ?string
    {
        return $this->error;
    }
}