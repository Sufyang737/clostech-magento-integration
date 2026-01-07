<?php
declare(strict_types=1);

namespace Clostech\Integration\Api\Data;

interface ProductListResponseInterface
{
    /**
     * @return int
     */
    public function getPage();

    /**
     * @return int
     */
    public function getPageSize();

    /**
     * @return array
     */
    public function getProducts();
}
