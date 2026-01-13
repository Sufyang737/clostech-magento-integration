<?php
namespace Clostech\Integration\Model;

use Clostech\Integration\Api\StoreValidationInterface;
use Clostech\Integration\Helper\StoreValidator;
use Psr\Log\LoggerInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;

class StoreValidation implements StoreValidationInterface
{
    protected $storeValidator;
    protected $logger;
    protected $curl;
    protected $scopeConfig;
    protected $configWriter;
    
    public function __construct(
        StoreValidator $storeValidator,
        LoggerInterface $logger,
        Curl $curl,
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter
    ) {
        $this->storeValidator = $storeValidator;
        $this->logger = $logger;
        $this->curl = $curl;
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
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
            
            // Guardar storeId en configuración de Magento
            $this->configWriter->save(
                'clostech/integration/store_id',
                $storeId,
                \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                0
            );
            
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
                
                return [
                    'success' => true,
                    'message' => 'Store validated but sync failed',
                    'storeId' => $storeId,
                    'storeInfo' => $storeInfo,
                    'clostech_sync' => false
                ];
            }
            
            // Obtener API key y client_id de Clostech
            $credentials = $this->getApiCredentials($storeId);
            
            if ($credentials['success']) {
                // Guardar API key y client_id en configuración
                $this->configWriter->save(
                    'clostech/integration/api_key',
                    $credentials['api_key'],
                    \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                    0
                );
                
                $this->configWriter->save(
                    'clostech/integration/client_id',
                    $credentials['client_id'],
                    \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                    0
                );
                
                $this->logger->info('API credentials saved', [
                    'api_key' => substr($credentials['api_key'], 0, 10) . '...',
                    'client_id' => $credentials['client_id']
                ]);
            } else {
                $this->logger->warning('Could not retrieve API credentials from Clostech');
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
            $this->logger->info('📦 DATOS QUE SE ENVÍAN A CLOSTECH:', [
                'data' => $data,
                'json' => json_encode($data)
            ]);

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
     * Obtiene API key y client_id de Clostech
     */
    private function getApiCredentials(string $storeId): array
    {
        try {
            $clostechUrl = $this->getClostechUrl();
            $endpoint = $clostechUrl . '/api/apikeys/store';
            
            $this->logger->info('🔑 Requesting API credentials from Clostech', [
                'endpoint' => $endpoint,
                'store_id' => $storeId
            ]);
            
            // Configurar cURL
            $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
            $this->curl->setOption(CURLOPT_TIMEOUT, 30);
            $this->curl->addHeader('Content-Type', 'application/json');
            
            // Hacer POST
            $this->curl->post($endpoint, json_encode(['store_id' => $storeId]));
            
            $response = $this->curl->getBody();
            $statusCode = $this->curl->getStatus();
            
            $this->logger->info('API credentials response', [
                'status' => $statusCode,
                'response' => $response
            ]);
            
            if ($statusCode >= 200 && $statusCode < 300) {
                $data = json_decode($response, true);
                
                if (isset($data['data']['api_key']) && isset($data['data']['client']['id'])) {
                    return [
                        'success' => true,
                        'api_key' => $data['data']['api_key'],
                        'client_id' => $data['data']['client']['id']
                    ];
                }
            }
            
            return ['success' => false];
            
        } catch (\Exception $e) {
            $this->logger->error('Error getting API credentials: ' . $e->getMessage());
            return ['success' => false];
        }
    }
    
    /**
     * Obtiene URL de Clostech
     */
    private function getClostechUrl(): string
    {
        return 'https://identic-keenan-nonvalorous.ngrok-free.dev';
    }
}