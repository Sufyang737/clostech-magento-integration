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
    
    // valida, genera el storeId único (sólo si la tienda no tiene uno) 
    // y lo almacena en la config de la tienda
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
            
            // verifica si ya existe un storeId en la config de la tienda
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
                // genera nuevo storeId solo si no existe
                $storeId = $this->storeValidator->generateStoreId();
                
                // guarda el nuevo storeId en configuración
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
            
            // obtiene la info de la tienda
            $storeInfo = $this->storeValidator->getStoreInformation();
            
            // si el storeId es nuevo, sincronizamos con Clostech
            // y se le pasa la info de la tienda
            if (!$existingStoreId || $existingStoreId) {
                // transforma los datos a como Clostech espera recibirlos (JSON object en lugar de ArrayIndexado)
                $clostechData = $this->storeValidator->formatDataForClostech($storeId, $storeInfo);
                
                // se envia la info a Clostech
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
                
                // recibimos una api_key y un client_id de Clostech en base al storeId
                $credentials = $this->getApiCredentials($storeId);
                
                if ($credentials['success']) {
                    // si las credenciales se generaron y se recibieron con exito, se guardan en la config de la store
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
                    
                    $this->logger->info('Credenciales guardadas', [
                        'api_key' => substr($credentials['api_key'], 0, 10) . '...',
                        'client_id' => $credentials['client_id']
                    ]);
                } else {
                    $this->logger->warning('No se pudieron recuperar las credenciales de la API de Clostech');
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
    
    /**
     * Envía datos a Clostech
     */
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
    
    /**
     * Obtiene API key y client_id de Clostech
     */
    private function getApiCredentials(string $storeId): array
    {
        try {
            $clostechUrl = $this->getClostechUrl();
            $endpoint = $clostechUrl . '/api/apikeys/store';
            
            $this->logger->info('Requesting API credentials from Clostech', [
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