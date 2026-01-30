<?php
namespace Clostech\Integration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class OrderPlaceAfter implements ObserverInterface
{
    protected $curl;
    protected $logger;
    protected $scopeConfig;
    
    public function __construct(
        Curl $curl,
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->curl = $curl;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
    }
    
    public function execute(Observer $observer)
    {
        try {
            /** @var \Magento\Sales\Model\Order $order */
            $order = $observer->getEvent()->getOrder();
            
            // Obtener datos de la orden
            $orderData = $this->prepareOrderData($order);
            
            // Enviar a Clostech
            $this->sendToClostech($orderData);
            
        } catch (\Exception $e) {
            $this->logger->error('Error en OrderPlaceAfter: ' . $e->getMessage());
        }
    }
    
    private function prepareOrderData($order): array
    {
        // Obtener storeId de Clostech
        $storeId = $this->scopeConfig->getValue(
            'clostech/integration/store_id',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        
        // Preparar productos
        $products = [];
        foreach ($order->getAllVisibleItems() as $item) {
            $products[] = [
                'id' => $item->getProductId(),
                'name' => $item->getName(),
                'price' => (float)$item->getPrice(),
                'quantity' => (int)$item->getQtyOrdered()
            ];
        }
        
        // TODO: Obtener usedClostech de localStorage
        // Por ahora lo ponemos en false
        $usedClostech = false;
        
        return [
            'storeId' => $storeId,
            'total' => (float)$order->getGrandTotal(),
            'usedClostech' => $usedClostech,
            'customerEmail' => $order->getCustomerEmail(),
            'products' => $products,
            'timestamp' => date('c'), // ISO 8601
            'session_id' => $order->getIncrementId()
        ];
    }
    
    private function sendToClostech(array $data): void
    {
        try {
            $clostechUrl = $this->getClostechUrl();
            $endpoint = $clostechUrl . '/api/orders';
            
            $this->logger->info('Enviando orden a Clostech:', [
                'data' => $data,
                'endpoint' => $endpoint
            ]);
            
            // Configurar cURL
            $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
            $this->curl->setOption(CURLOPT_TIMEOUT, 30);
            $this->curl->addHeader('Content-Type', 'application/json');
            
            // Hacer POST
            $this->curl->post($endpoint, json_encode($data));
            
            $response = $this->curl->getBody();
            $statusCode = $this->curl->getStatus();
            
            $this->logger->info('Clostech orders API response', [
                'status' => $statusCode,
                'response' => $response
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Error enviando orden a Clostech: ' . $e->getMessage());
        }
    }

    private function getClostechUrl(): string
    {
        return 'https://portal-empresa.clostech.tech';
    }
}