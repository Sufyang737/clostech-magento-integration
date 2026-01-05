<?php
declare(strict_types=1);

namespace Clostech\Integration\Model\Data;

use Clostech\Integration\Api\Data\ProductInterface;
use Magento\Framework\Model\AbstractModel;

class Product extends AbstractModel implements ProductInterface
{
    private string $storeId = '';
    private string $name = '';
    private string $sku = '';
    private string $typeClothes = 'none';
    private bool $useClostech = true;
    private string $category = '';
    private string $tags = '';
    private bool $useSize = false;
    private bool $useRecomendationSize = false;
    private ?array $variants = null;

    public function getStoreId(): string
    {
        return $this->storeId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function getTypeClothes(): string
    {
        return $this->typeClothes;
    }

    public function getUseClostech(): bool
    {
        return $this->useClostech;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getTags(): string
    {
        return $this->tags;
    }

    public function getUseSize(): bool
    {
        return $this->useSize;
    }

    public function getUseRecomendationSize(): bool
    {
        return $this->useRecomendationSize;
    }

    public function getVariants(): ?array
    {
        return $this->variants;
    }

    public function setStoreId(string $storeId): ProductInterface
    {
        $this->storeId = $storeId;
        return $this;
    }

    public function setName(string $name): ProductInterface
    {
        $this->name = $name;
        return $this;
    }

    public function setSku(string $sku): ProductInterface
    {
        $this->sku = $sku;
        return $this;
    }

    public function setTypeClothes(string $typeClothes): ProductInterface
    {
        $this->typeClothes = $typeClothes;
        return $this;
    }

    public function setUseClostech(bool $useClostech): ProductInterface
    {
        $this->useClostech = $useClostech;
        return $this;
    }

    public function setCategory(string $category): ProductInterface
    {
        $this->category = $category;
        return $this;
    }

    public function setTags(string $tags): ProductInterface
    {
        $this->tags = $tags;
        return $this;
    }

    public function setUseSize(bool $useSize): ProductInterface
    {
        $this->useSize = $useSize;
        return $this;
    }

    public function setUseRecomendationSize(bool $useRecomendationSize): ProductInterface
    {
        $this->useRecomendationSize = $useRecomendationSize;
        return $this;
    }

    public function setVariants(?array $variants): ProductInterface
    {
        $this->variants = $variants;
        return $this;
    }
}