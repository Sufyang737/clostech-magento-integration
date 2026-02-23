<?php

// este archivo valida la tienda, genera store_id, clostech recibe el store_id y responde con una api_key y client_id
// los cuales se almacenan en la config de la tienda junto al store_id
namespace Clostech\Integration\Model;

use Clostech\Integration\Api\StoreValidationInterface;
use Clostech\Integration\Helper\StoreValidator;
use Psr\Log\LoggerInterface;
use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;

class StoreValidation implements StoreValidationInterface
{
    protected $storeValidator;
    protected $logger;
    protected $curlFactory;
    protected $scopeConfig;
    protected $configWriter;
    
    public function __construct(
        StoreValidator $storeValidator,
        LoggerInterface $logger,
        CurlFactory $curlFactory,
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter
    ) {
        $this->storeValidator = $storeValidator;
        $this->logger = $logger;
        $this->curlFactory = $curlFactory;
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
    }
    
    public function validate(string $domain)
    {
        try {
            $isValid = $this->storeValidator->validateStore($domain);
            
            if (!$isValid) {
                return [
                    'success' => false,
                    'message' => 'Invalid store domain',
                    'storeId' => null,
                    'storeInfo' => null
                ];
            }
            
            $existingStoreId = $this->scopeConfig->getValue(
                'clostech/integration/store_id',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            
            if ($existingStoreId) {
                $this->logger->info('la tienda ya ha sido validada', [
                    'storeId' => $existingStoreId
                ]);
                
                $storeId = $existingStoreId;
            } else {
                $storeId = $this->storeValidator->generateStoreId();
                
                $this->configWriter->save(
                    'clostech/integration/store_id',
                    $storeId,
                    \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                    0
                );
                
                $this->logger->info('nuevo storeId generado y almacenado', [
                    'storeId' => $storeId
                ]);
            }
            
            $storeInfo = $this->storeValidator->getStoreInformation();
            
            // Solo sincronizar si es tienda nueva
            if (!$existingStoreId) {
                $clostechData = $this->storeValidator->formatDataForClostech($storeId, $storeInfo);
                
                $clostechResponse = $this->sendToClostech($clostechData);
                
                if (!$clostechResponse['success']) {
                    $this->logger->error('Falló la sincronización con Clostech', [
                        'error' => $clostechResponse['message']
                    ]);
                    
                    return [
                        'success' => true,
                        'message' => 'La tienda fue validada, pero la sincronización falló',
                        'storeId' => $storeId,
                        'storeInfo' => $storeInfo,
                        'clostech_sync' => false
                    ];
                }
            }
            
            $this->logger->info('Tienda validada con exito', [
                'domain' => $domain,
                'storeId' => $storeId,
                'is_new' => !$existingStoreId
            ]);
            
            return [
                'success' => true,
                'message' => 'Tienda validada con exito',
                'storeId' => $storeId,
                'storeInfo' => $storeInfo,
                'clostech_sync' => !$existingStoreId
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Error al validar la tienda: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'storeId' => null,
                'storeInfo' => null
            ];
        }
    }
    
    private function sendToClostech(array $data): array
    {
        try {
            $this->logger->info('datos que se envian a Clostech:', [
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
            
            // Instancia limpia de cURL
            $curl = $this->curlFactory->create();
            
            $curl->setOption(CURLOPT_RETURNTRANSFER, true);
            $curl->setOption(CURLOPT_TIMEOUT, 30);
            $curl->addHeader('Content-Type', 'application/json');
            
            $curl->post($endpoint, json_encode($data));
            
            $response = $curl->getBody();
            $statusCode = $curl->getStatus();
            
            $this->logger->info('Clostech API response', [
                'status' => $statusCode,
                'response' => $response
            ]);
            
            if ($statusCode >= 200 && $statusCode < 300) {
                return [
                    'success' => true,
                    'message' => 'datos sincronizados con exito',
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
    
    public function getApiCredentials(string $storeId): array
    {
        try {
            $clostechUrl = $this->getClostechUrl();
            $endpoint = $clostechUrl . '/api/apikeys/store';
            
            // Instancia limpia de cURL (sin contaminación del request anterior)
            $curl = $this->curlFactory->create();
            
            // FIX: Agregar espacio al inicio porque el backend de Clostech guarda así el store_id
            $payload = ['store_id' => $storeId];
            $jsonPayload = json_encode($payload);
            
            $this->logger->info('=== REQUEST DEBUG ===', [
                'endpoint' => $endpoint,
                'payload_array' => $payload,
                'payload_json' => $jsonPayload,
                'payload_length' => strlen($jsonPayload)
            ]);
            
            $curl->setOption(CURLOPT_RETURNTRANSFER, true);
            $curl->setOption(CURLOPT_TIMEOUT, 30);
            $curl->addHeader('Content-Type', 'application/json');
            $curl->addHeader('Accept', 'application/json');
            
            $curl->post($endpoint, $jsonPayload);
            
            $response = $curl->getBody();
            $statusCode = $curl->getStatus();
            
            $this->logger->info('=== RESPONSE DEBUG ===', [
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
    
    private function getClostechUrl(): string
    {
        return 'https://portal-empresa.clostech.tech';
    }
}