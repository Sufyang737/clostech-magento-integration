<?php
namespace Clostech\Integration\Controller\Tryon;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\View\LayoutFactory;

class Index extends Action
{
    private $resultRawFactory;
    private $layoutFactory;

    public function __construct(
        Context $context,
        RawFactory $resultRawFactory,
        LayoutFactory $layoutFactory
    ) {
        parent::__construct($context);
        $this->resultRawFactory = $resultRawFactory;
        $this->layoutFactory = $layoutFactory;
    }

    public function execute()
    {
        $layout = $this->layoutFactory->create();
        $block = $layout->createBlock('Magento\Framework\View\Element\Template');
        $block->setTemplate('Clostech_Integration::tryon-full.phtml');
        
        $resultRaw = $this->resultRawFactory->create();
        $resultRaw->setContents($block->toHtml());
        return $resultRaw;
    }
}