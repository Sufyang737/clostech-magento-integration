<?php
declare(strict_types=1);

namespace Clostech\Integration\Model\Data;

use Clostech\Integration\Api\Data\VariantInterface;
use Magento\Framework\Model\AbstractModel;

class Variant extends AbstractModel implements VariantInterface
{
    private string $name = '';
    private string $sku = '';
    private string $typeClothes = 'none';
    private string $category = '';
    private string $tags = '';

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

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getTags(): string
    {
        return $this->tags;
    }

    public function setName(string $name): VariantInterface
    {
        $this->name = $name;
        return $this;
    }

    public function setSku(string $sku): VariantInterface
    {
        $this->sku = $sku;
        return $this;
    }

    public function setTypeClothes(string $typeClothes): VariantInterface
    {
        $this->typeClothes = $typeClothes;
        return $this;
    }

    public function setCategory(string $category): VariantInterface
    {
        $this->category = $category;
        return $this;
    }

    public function setTags(string $tags): VariantInterface
    {
        $this->tags = $tags;
        return $this;
    }
}