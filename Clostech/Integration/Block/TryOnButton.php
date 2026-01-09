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
}