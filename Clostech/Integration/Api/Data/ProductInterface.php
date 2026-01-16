<?php

// define los getters y setters del objeto product
declare(strict_types=1);

namespace Clostech\Integration\Api\Data;

interface ProductInterface
{
    /**
     * @return string
     */
    public function getClientId(): string;

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return string
     */
    public function getSku(): string;

    /**
     * @return string
     */
    public function getTypeClothes(): string;

    /**
     * @return bool
     */
    public function getUseClostech(): bool;

    /**
     * @return string
     */
    public function getCategory(): string;

    /**
     * @return string
     */
    public function getTags(): string;

    /**
     * @return bool
     */
    public function getUseSize(): bool;

    /**
     * @return bool
     */
    public function getUseRecomendationSize(): bool;

    /**
     * @return \Clostech\Integration\Api\Data\VariantInterface[]|null
     */
    public function getVariants(): ?array;

    /**
     * @param string $clientId
     * @return $this
     */
    public function setClientId(string $clientId): self;

    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name): self;

    /**
     * @param string $sku
     * @return $this
     */
    public function setSku(string $sku): self;

    /**
     * @param string $typeClothes
     * @return $this
     */
    public function setTypeClothes(string $typeClothes): self;

    /**
     * @param bool $useClostech
     * @return $this
     */
    public function setUseClostech(bool $useClostech): self;

    /**
     * @param string $category
     * @return $this
     */
    public function setCategory(string $category): self;

    /**
     * @param string $tags
     * @return $this
     */
    public function setTags(string $tags): self;

    /**
     * @param bool $useSize
     * @return $this
     */
    public function setUseSize(bool $useSize): self;

    /**
     * @param bool $useRecomendationSize
     * @return $this
     */
    public function setUseRecomendationSize(bool $useRecomendationSize): self;

    /**
     * @param \Clostech\Integration\Api\Data\VariantInterface[]|null $variants
     * @return $this
     */
    public function setVariants(?array $variants): self;
}