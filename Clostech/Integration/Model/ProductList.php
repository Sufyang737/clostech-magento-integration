<?php
// ESTE ARCHIVO MANEJA TODA LA LOGICA DE EXTRACCIÓN DE LOS PRODUCTOS DESDE MAGENTO Y LOS PREPARA
// EN EL FORMATO QUE CLOSTECH NECESITA

declare(strict_types=1); // peddimos que sea estricto con el tipado

namespace Clostech\Integration\Model;

// imports
use Clostech\Integration\Api\ProductListInterface; // contratp
use Clostech\Integration\Api\Data\ProductInterfaceFactory; // factory para crear products vacios
use Clostech\Integration\Api\Data\VariantInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface; // para extraer los productos desde la bd de magento
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface; //para buscar data en la config de la tienda (ej: storeId)
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Psr\Log\LoggerInterface; // para logs

class ProductList implements ProductListInterface
{
    // definicion de lo que la clase va a usar
    private ProductRepositoryInterface $productRepository;
    private CategoryRepositoryInterface $categoryRepository;
    private SearchCriteriaBuilder $searchCriteriaBuilder;
    private Configurable $configurableType;
    private LoggerInterface $logger;
    private ProductInterfaceFactory $productFactory;
    private VariantInterfaceFactory $variantFactory;
    private ScopeConfigInterface $scopeConfig;

    // recibe dependencias como params
    public function __construct(
        ProductRepositoryInterface $productRepository,
        CategoryRepositoryInterface $categoryRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Configurable $configurableType,
        LoggerInterface $logger,
        ProductInterfaceFactory $productFactory,
        VariantInterfaceFactory $variantFactory,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->configurableType = $configurableType;
        $this->logger = $logger;
        $this->productFactory = $productFactory;
        $this->variantFactory = $variantFactory;
        $this->scopeConfig = $scopeConfig;
    }

    public function getList(): array
    // ejecuta la busqueda en la bd de magento y devuelve un array indexado
    // con los productos
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
        // crea el objeto producto con todos los campos necesarios
        $clientId = $this->scopeConfig->getValue( // Obtener storeId de la configuración
            'clostech/integration/client_id',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $productDto = $this->productFactory->create();
        $productDto->setClientId($clientId ?? '');
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
        // tiene como param un producto padre y obtiene todas las variantes
        // ej: Entrada:
        // Producto padre (ej: "Remera Nike")
        //Salida: Array de variantes (ej: [XS, S, M, L, XL])
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
    { // determina tipo de prenda, pero por ahora no devuelve nada
        return 'none';
    }

    private function getPrimaryCategory($product): string
    { // obtiene el nombre de la categeroia principal del producto
        $categoryIds = $product->getCategoryIds();
        
        if (empty($categoryIds)) {
            return '';
        }

        try {
            $category = $this->categoryRepository->get($categoryIds[0]);
            return $category->getName();
        } catch (\Exception $e) {
            $this->logger->warning('Error al cargar la categeria', [
                'product_sku' => $product->getSku(),
                'category_id' => $categoryIds[0]
            ]);
            return '';
        }
    }

    private function getProductTags($product): string
    { // obtiene los tags del producto. ej: verano,casual,algodon
        $metaKeywords = $product->getMetaKeyword();
        
        if (empty($metaKeywords)) {
            return '';
        }

        return $metaKeywords;
    }

    private function isChildProduct($product): bool
    { // determina si un producto es hijo/variante de otro
        if ($product->getTypeId() === 'simple') {
            $parentIds = $this->configurableType->getParentIdsByChild($product->getId());
            return !empty($parentIds);
        }
        
        return false;
    }
}