<?php
declare(strict_types=1);

namespace Clostech\Integration\Api;

use Clostech\Integration\Api\Data\ProductInterface;

interface ProductListInterface
{
    /**
     * obtiene todos los productos con variante clave-valor
     *
     * @return \Clostech\Integration\Api\Data\ProductInterface[]
     */
    public function getList(): array;
}