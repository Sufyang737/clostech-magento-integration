<?php
declare(strict_types=1);

namespace Clostech\Integration\Api;

/**
 * Interface for Clostech products endpoint
 * Returns product data in Clostech-compatible JSON format
 */
interface ProductListInterface
{
    /**
     * Get paginated list of products with parent-variant relationships
     *
     * @param int|null $page Page number (default: 1)
     * @param int|null $pageSize Items per page (default: 50)
     * @return \Magento\Framework\DataObject
     */
    public function getList(?int $page = 1, ?int $pageSize = 50): \Magento\Framework\DataObject;
}