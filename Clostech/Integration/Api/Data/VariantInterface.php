<?php

// define los getters y setters del objeto hijo/variante
declare(strict_types=1);

namespace Clostech\Integration\Api\Data;

interface VariantInterface
{
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
     * @return string
     */
    public function getCategory(): string;

    /**
     * @return string
     */
    public function getTags(): string;

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
     * @param string $category
     * @return $this
     */
    public function setCategory(string $category): self;

    /**
     * @param string $tags
     * @return $this
     */
    public function setTags(string $tags): self;
}