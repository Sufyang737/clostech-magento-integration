<?php
declare(strict_types=1);

namespace Clostech\Integration\Api\Data;

interface ProductsResponseInterface
{
    /**
     * @return bool
     */
    public function getSuccess(): bool;

    /**
     * @return int
     */
    public function getPage(): int;

    /**
     * @return int
     */
    public function getPageSize(): int;

    /**
     * @return int
     */
    public function getTotalCount(): int;

    /**
     * @return array
     */
    public function getProducts(): array;

    /**
     * @return string|null
     */
    public function getError(): ?string;

    /**
     * @param bool $success
     * @return $this
     */
    public function setSuccess(bool $success): self;

    /**
     * @param int $page
     * @return $this
     */
    public function setPage(int $page): self;

    /**
     * @param int $pageSize
     * @return $this
     */
    public function setPageSize(int $pageSize): self;

    /**
     * @param int $totalCount
     * @return $this
     */
    public function setTotalCount(int $totalCount): self;

    /**
     * @param array $products
     * @return $this
     */
    public function setProducts(array $products): self;

    /**
     * @param string|null $error
     * @return $this
     */
    public function setError(?string $error): self;
}