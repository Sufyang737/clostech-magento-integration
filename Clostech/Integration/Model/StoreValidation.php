<?php
namespace Clostech\Integration\Model;

use Clostech\Integration\Api\StoreValidationInterface;
use Clostech\Integration\Helper\StoreValidator;
use Psr\Log\LoggerInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\App\Config\ScopeConfigInterface;

class StoreValidation implements StoreValidationInterface
{
    protected $storeValidator;
    protected $logger;
    protected $curl;
    protected $scopeConfig;
    
    public function __construct(
        StoreValidator $storeValidator,
        LoggerInterface $logger,
        Curl $curl,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->storeValidator = $storeValidator;
        $this->logger = $logger;
        $this->curl = $curl;
        $this->scopeConfig = $scopeConfig;
    }
    
    /**
     * Valida la tienda y genera storeId
     * 
     * @param string $domain
     * @return array
     */
    public function validate(string $domain)
    {
        try {
            // Validar tienda
            $isValid = $this->storeValidator->validateStore($domain);
            
            if (!$isValid) {
                return [
                    'success' => false,
                    'message' => 'Invalid store domain',
                    'storeId' => null,
                    'storeInfo' => null
                ];
            }
            
            // Generar storeId
            $storeId = $this->storeValidator->generateStoreId();
            
            // Obtener información de la tienda
            $storeInfo = $this->storeValidator->getStoreInformation();
            
            // Transformar datos al formato de Clostech
            $clostechData = $this->storeValidator->formatDataForClostech($storeId, $storeInfo);
            
            // Enviar datos a Clostech
            $clostechResponse = $this->sendToClostech($clostechData);
            
            if (!$clostechResponse['success']) {
                $this->logger->error('Failed to sync with Clostech', [
                    'error' => $clostechResponse['message']
                ]);
            }
            
            $this->logger->info('Store validated successfully', [
                'domain' => $domain,
                'storeId' => $storeId,
                'clostech_sync' => $clostechResponse['success']
            ]);
            
            return [
                'success' => true,
                'message' => 'Store validated successfully',
                'storeId' => $storeId,
                'storeInfo' => $storeInfo,
                'clostech_sync' => $clostechResponse['success']
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Error validating store: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'storeId' => null,
                'storeInfo' => null
            ];
        }
    }
    
    /**
     * Envía datos a Clostech
     */
    private function sendToClostech(array $data): array
    {
        try {
            // Obtener URL de Clostech desde configuración
            // Por ahora usamos placeholder, después lo configuraremos
            $clostechUrl = $this->getClostechUrl();
            
            if (empty($clostechUrl)) {
                return [
                    'success' => false,
                    'message' => 'Clostech URL not configured'
                ];
            }
            
            $endpoint = $clostechUrl . '/api/shopify/client_information';
            
            // Configurar cURL
            $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
            $this->curl->setOption(CURLOPT_TIMEOUT, 30);
            $this->curl->addHeader('Content-Type', 'application/json');
            
            // Hacer POST
            $this->curl->post($endpoint, json_encode($data));
            
            $response = $this->curl->getBody();
            $statusCode = $this->curl->getStatus();
            
            $this->logger->info('Clostech API response', [
                'status' => $statusCode,
                'response' => $response
            ]);
            
            if ($statusCode >= 200 && $statusCode < 300) {
                return [
                    'success' => true,
                    'message' => 'Data synced successfully',
                    'response' => $response
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Clostech API returned status ' . $statusCode,
                'response' => $response
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Error sending to Clostech: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtiene URL de Clostech desde configuración
     *
     */
    private function getClostechUrl(): string
    {
        // URL de Clostech
        // En desarrollo: usar ngrok URL
        // En producción: cambiar a URL real de Clostech
        return 'http://placeholder-clostech-url.com';
    }
}