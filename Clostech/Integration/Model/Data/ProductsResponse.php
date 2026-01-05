<?php
declare(strict_types=1);

namespace Clostech\Integration\Model\Data;

use Clostech\Integration\Api\Data\ProductsResponseInterface;
use Magento\Framework\Model\AbstractModel;

class ProductsResponse extends AbstractModel implements ProductsResponseInterface
{
    private bool $success;
    private int $page;
    private int $pageSize;
    private int $totalCount;
    private array $products;
    private ?string $error = null;

    public function getSuccess(): bool
    {
        return $this->success ?? false;
    }

    public function getPage(): int
    {
        return $this->page ?? 1;
    }

    public function getPageSize(): int
    {
        return $this->pageSize ?? 50;
    }

    public function getTotalCount(): int
    {
        return $this->totalCount ?? 0;
    }

    public function getProducts(): array
    {
        return $this->products ?? [];
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function setSuccess(bool $success): ProductsResponseInterface
    {
        $this->success = $success;
        return $this;
    }

    public function setPage(int $page): ProductsResponseInterface
    {
        $this->page = $page;
        return $this;
    }

    public function setPageSize(int $pageSize): ProductsResponseInterface
    {
        $this->pageSize = $pageSize;
        return $this;
    }

    public function setTotalCount(int $totalCount): ProductsResponseInterface
    {
        $this->totalCount = $totalCount;
        return $this;
    }

    public function setProducts(array $products): ProductsResponseInterface
    {
        $this->products = $products;
        return $this;
    }

    public function setError(?string $error): ProductsResponseInterface
    {
        $this->error = $error;
        return $this;
    }
}