<?php
namespace Clostech\Integration\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Registry;

class TryOnButton extends Template
{
    protected $registry;
    
    public function __construct(
        Context $context,
        Registry $registry,
        array $data = []
    ) {
        $this->registry = $registry;
        parent::__construct($context, $data);
    }
    
    /**
     * Obtiene el producto actual
     */
    public function getCurrentProduct()
    {
        return $this->registry->registry('current_product');
    }
    
    /**
     * Obtiene el ID del producto actual
     */
    public function getCurrentProductId()
    {
        $product = $this->getCurrentProduct();
        return $product ? $product->getId() : null;
    }
    
    /**
     * Obtiene el SKU del producto actual
     */
    public function getCurrentProductSku()
    {
        $product = $this->getCurrentProduct();
        return $product ? $product->getSku() : null;
    }
    
    /**
     * Obtiene el nombre del producto actual
     */
    public function getCurrentProductName()
    {
        $product = $this->getCurrentProduct();
        return $product ? $product->getName() : null;
    }
}
