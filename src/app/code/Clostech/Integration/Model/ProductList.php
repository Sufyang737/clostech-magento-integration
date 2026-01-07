<?php
declare(strict_types=1);

namespace Clostech\Integration\Model;

use Clostech\Integration\Api\ProductListInterface;
use Clostech\Integration\Api\Data\ProductInterfaceFactory;
use Clostech\Integration\Api\Data\VariantInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Psr\Log\LoggerInterface;

class ProductList implements ProductListInterface
{
    private ProductRepositoryInterface $productRepository;
    private CategoryRepositoryInterface $categoryRepository;
    private SearchCriteriaBuilder $searchCriteriaBuilder;
    private Configurable $configurableType;
    private LoggerInterface $logger;
    private ProductInterfaceFactory $productFactory;
    private VariantInterfaceFactory $variantFactory;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        CategoryRepositoryInterface $categoryRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Configurable $configurableType,
        LoggerInterface $logger,
        ProductInterfaceFactory $productFactory,
        VariantInterfaceFactory $variantFactory
    ) {
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->configurableType = $configurableType;
        $this->logger = $logger;
        $this->productFactory = $productFactory;
        $this->variantFactory = $variantFactory;
    }

    public function getList(): array
    {
        try {
            $searchCriteria = $this->searchCriteriaBuilder->create();
            $searchResults = $this->productRepository->getList($searchCriteria);
            $products = [];

            foreach ($searchResults->getItems() as $product) {
                if ($this->isChildProduct($product)) {
                    continue;
                }

                $productData = $this->buildProductData($product);
                $products[] = $productData;
            }

            return $products;

        } catch (\Exception $e) {
            $this->logger->error('Clostech Products Endpoint Error: ' . $e->getMessage(), [
                'exception' => $e
            ]);

            return [];
        }
    }

    private function buildProductData($product)
    {
        $productDto = $this->productFactory->create();
        $productDto->setStoreId('');
        $productDto->setName($product->getName());
        $productDto->setSku($product->getSku());
        $productDto->setTypeClothes($this->determineClothesType($product));
        $productDto->setUseClostech(true);
        $productDto->setCategory($this->getPrimaryCategory($product));
        $productDto->setTags($this->getProductTags($product));
        $productDto->setUseSize(false);
        $productDto->setUseRecomendationSize(false);

        if ($product->getTypeId() === 'configurable') {
            $variants = $this->getVariants($product);
            $productDto->setVariants($variants);
        }

        return $productDto;
    }

    private function getVariants($product): array
    {
        $variants = [];
        $childrenIds = $this->configurableType->getChildrenIds($product->getId());
        
        if (!empty($childrenIds[0])) {
            foreach ($childrenIds[0] as $childId) {
                try {
                    $child = $this->productRepository->getById($childId);
                    
                    $variantDto = $this->variantFactory->create();
                    $variantDto->setName($child->getName());
                    $variantDto->setSku($child->getSku());
                    $variantDto->setTypeClothes($this->determineClothesType($child));
                    $variantDto->setCategory($this->getPrimaryCategory($child));
                    $variantDto->setTags($this->getProductTags($child));
                    
                    $variants[] = $variantDto;
                } catch (\Exception $e) {
                    $this->logger->warning('Error loading variant: ' . $childId, [
                        'parent_sku' => $product->getSku(),
                        'error' => $e->getMessage()
                    ]);
                    continue;
                }
            }
        }

        return $variants;
    }

    private function determineClothesType($product): string
    {
        return 'none';
    }

    private function getPrimaryCategory($product): string
    {
        $categoryIds = $product->getCategoryIds();
        
        if (empty($categoryIds)) {
            return '';
        }

        try {
            $category = $this->categoryRepository->get($categoryIds[0]);
            return $category->getName();
        } catch (\Exception $e) {
            $this->logger->warning('Error loading category', [
                'product_sku' => $product->getSku(),
                'category_id' => $categoryIds[0]
            ]);
            return '';
        }
    }

    private function getProductTags($product): string
    {
        $metaKeywords = $product->getMetaKeyword();
        
        if (empty($metaKeywords)) {
            return '';
        }

        return $metaKeywords;
    }

    private function isChildProduct($product): bool
    {
        if ($product->getTypeId() === 'simple') {
            $parentIds = $this->configurableType->getParentIdsByChild($product->getId());
            return !empty($parentIds);
        }
        
        return false;
    }
}