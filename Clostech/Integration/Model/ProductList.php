<?php
declare(strict_types=1);

namespace Clostech\Integration\Model;

use Clostech\Integration\Api\ProductListInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\DataObject;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Psr\Log\LoggerInterface;

class ProductList implements ProductListInterface
{
    private ProductRepositoryInterface $productRepository;
    private CategoryRepositoryInterface $categoryRepository;
    private SearchCriteriaBuilder $searchCriteriaBuilder;
    private Configurable $configurableType;
    private LoggerInterface $logger;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        CategoryRepositoryInterface $categoryRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Configurable $configurableType,
        LoggerInterface $logger
    ) {
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->configurableType = $configurableType;
        $this->logger = $logger;
    }

    public function getList(?int $page = 1, ?int $pageSize = 50): array
    {
        try {
            $page = max(1, $page ?? 1);
            $pageSize = min(100, max(1, $pageSize ?? 50));

            $searchCriteria = $this->searchCriteriaBuilder
                ->setPageSize($pageSize)
                ->setCurrentPage($page)
                ->create();

            $searchResults = $this->productRepository->getList($searchCriteria);
            $products = [];

            foreach ($searchResults->getItems() as $product) {
                if ($this->isChildProduct($product)) {
                    continue;
                }

                $productData = $this->buildProductData($product);
                $products[] = $productData;
            }

            $response = [
                'success' => true,
                'page' => $page,
                'page_size' => $pageSize,
                'total_count' => $searchResults->getTotalCount(),
                'products' => $products
            ];

            $dataObject = new DataObject($response);
            return $dataObject->__toArray();

        } catch (\Exception $e) {
            $this->logger->error('Clostech Products Endpoint Error: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            
            $errorResponse = [
                'success' => false,
                'error' => 'An error occurred while fetching products',
                'page' => $page ?? 1,
                'page_size' => $pageSize ?? 50,
                'total_count' => 0,
                'products' => []
            ];

            $dataObject = new DataObject($errorResponse);
            return $dataObject->__toArray();
        }
    }

    private function buildProductData($product): array
    {
        $productData = [
            'store_id' => '',
            'name' => $product->getName(),
            'sku' => $product->getSku(),
            'type_clothes' => $this->determineClothesType($product),
            'use_clostech' => true,
            'category' => $this->getPrimaryCategory($product),
            'tags' => $this->getProductTags($product),
            'use_size' => false,
            'use_recomendation_size' => false
        ];

        if ($product->getTypeId() === 'configurable') {
            $productData['variants'] = $this->getVariants($product);
        }

        return $productData;
    }

    private function getVariants($product): array
    {
        $variants = [];
        $childrenIds = $this->configurableType->getChildrenIds($product->getId());
        
        if (!empty($childrenIds[0])) {
            foreach ($childrenIds[0] as $childId) {
                try {
                    $child = $this->productRepository->getById($childId);
                    $variants[] = [
                        'name' => $child->getName(),
                        'sku' => $child->getSku(),
                        'type_clothes' => $this->determineClothesType($child),
                        'category' => $this->getPrimaryCategory($child),
                        'tags' => $this->getProductTags($child)
                    ];
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