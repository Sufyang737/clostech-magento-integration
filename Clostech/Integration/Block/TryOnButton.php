<?php
namespace Clostech\Integration\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Catalog\Helper\Image;

class TryOnButton extends Template
{
    protected $registry;
    protected $scopeConfig;
    protected $imageHelper;
    protected $productRepository;
    private $logger;
    
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $scopeConfig,
        Image $imageHelper,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Psr\Log\LoggerInterface $logger,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->scopeConfig = $scopeConfig;
        $this->imageHelper = $imageHelper;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
        parent::__construct($context, $data);
    }
    
    // obtiene el producto que el usuario está viendo
    public function getCurrentProduct()
    {
        return $this->registry->registry('current_product');
    }
    
    public function getCurrentProductId()
    {
        $product = $this->getCurrentProduct();
        return $product ? $product->getId() : null;
    }
    
    public function getCurrentProductSku()
    {
        $product = $this->getCurrentProduct();
        return $product ? $product->getSku() : null;
    }
    
    public function getCurrentProductName()
    {
        $product = $this->getCurrentProduct();
        return $product ? $product->getName() : null;
    }
    
     // obtiene el storeId de la config de la tienda

    public function getStoreId(): ?string
    {
        return $this->scopeConfig->getValue(
            'clostech/integration/store_id',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getApiKey(): ?string
    {
        return $this->scopeConfig->getValue(
            'clostech/integration/api_key',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getClientId(): ?string
    {
        return $this->scopeConfig->getValue(
            'clostech/integration/client_id',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    
    
    // obtiene la URL de la imagen del producto
    public function getProductImageUrl(): ?string
    {
        $product = $this->getCurrentProduct();
        if (!$product) {
            return null;
        }
        
        try {
            return $this->imageHelper->init($product, 'product_page_image_large')
                ->getUrl();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get product configurations for configurable products
     * Returns array mapping "size-color" => child_product_id
     */
    public function getProductConfigurations(): array
    {
        $product = $this->getCurrentProduct();
        
        // Si no es configurable, retornar vacío
        if (!$product || $product->getTypeId() !== 'configurable') {
            return [];
        }
        
        $configurations = [];
        
        try {
            // Obtener el objeto ConfigurableProduct
            $configurableProduct = $this->productRepository->get($product->getSku());
            
            // Obtener productos hijos
            $children = $configurableProduct->getTypeInstance()->getUsedProducts($configurableProduct);
            
            foreach ($children as $child) {
                // Obtener valores de los atributos
                $size = $child->getAttributeText('size');
                $color = $child->getAttributeText('color');
                
                if ($size && $color) {
                    // Crear key "L-Black"
                    $key = $size . '-' . $color;
                    $configurations[$key] = $child->getId();
                }
            }
        } catch (\Exception $e) {
            // Log error pero no romper
            $this->logger->error('Error getting product configurations: ' . $e->getMessage());
        }
        
        return $configurations;
    }

    /**
     * Get product configurations as JSON
     */
    public function getProductConfigurationsJson(): string
    {
        return json_encode($this->getProductConfigurations());
    }

    /**
     * Get available sizes and colors for the product
     * Returns array with 'sizes' and 'colors' arrays
     */
    public function getProductOptions(): array
    {
        $product = $this->getCurrentProduct();
        
        if (!$product || $product->getTypeId() !== 'configurable') {
            return ['sizes' => [], 'colors' => []];
        }
        
        $options = ['sizes' => [], 'colors' => []];
        
        try {
            $configurableProduct = $this->productRepository->get($product->getSku());
            $children = $configurableProduct->getTypeInstance()->getUsedProducts($configurableProduct);
            
            $sizes = [];
            $colors = [];
            
            foreach ($children as $child) {
                $size = $child->getAttributeText('size');
                $color = $child->getAttributeText('color');
                
                if ($size && !in_array($size, $sizes)) {
                    $sizes[] = $size;
                }
                if ($color && !in_array($color, $colors)) {
                    $colors[] = $color;
                }
            }
            
            $options['sizes'] = $sizes;
            $options['colors'] = $colors;
            
        } catch (\Exception $e) {
            $this->logger->error('Error getting product options: ' . $e->getMessage());
        }
        
        return $options;
    }

    /**
     * Get product options as JSON
     */
    public function getProductOptionsJson(): string
    {
        return json_encode($this->getProductOptions());
    }
}