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
    
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $scopeConfig,
        Image $imageHelper,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->scopeConfig = $scopeConfig;
        $this->imageHelper = $imageHelper;
        parent::__construct($context, $data);
    }
    
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
    
    /**
     * Obtiene el storeId de Clostech desde la configuración
     */
    public function getStoreId(): ?string
    {
        return $this->scopeConfig->getValue(
            'clostech/integration/store_id',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    
    /**
     * Obtiene la URL de la imagen principal del producto
     */
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
}