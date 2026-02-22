<?php

namespace Clostech\Integration\Model;

use Clostech\Integration\Api\CredentialsSyncInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;

class CredentialsSync implements CredentialsSyncInterface
{
    protected $logger;
    protected $scopeConfig;
    protected $configWriter;
    protected $storeValidation;
    
    public function __construct(
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter,
        StoreValidation $storeValidation
    ) {
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
        $this->storeValidation = $storeValidation;
    }
    
    public function sync()
    {
        try {
            // Leer store_id de la config
            $storeId = $this->scopeConfig->getValue(
                'clostech/integration/store_id',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            
            if (!$storeId) {
                return [
                    'success' => false,
                    'message' => 'Store ID no encontrado. Primero debe validar la tienda.'
                ];
            }
            
            $this->logger->info('Iniciando sincronización de credenciales', [
                'storeId' => $storeId
            ]);
            
            // Retry logic
            $credentials = ['success' => false];
            $maxAttempts = 5;
            $delaySeconds = 3;
            
            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                $this->logger->info("Intento {$attempt} de {$maxAttempts} para obtener credenciales");
                
                if ($attempt > 1) {
                    sleep($delaySeconds);
                }
                
                $credentials = $this->storeValidation->getApiCredentials($storeId);
                
                if ($credentials['success']) {
                    $this->logger->info("Credenciales obtenidas exitosamente en intento {$attempt}");
                    break;
                }
                
                $this->logger->warning("Intento {$attempt} fallido, reintentando...");
            }
            
            if (!$credentials['success']) {
                return [
                    'success' => false,
                    'message' => 'No se pudieron obtener las credenciales después de ' . $maxAttempts . ' intentos'
                ];
            }
            
            // Guardar credenciales
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
            
            $this->logger->info('Credenciales guardadas exitosamente', [
                'api_key' => substr($credentials['api_key'], 0, 10) . '...',
                'client_id' => $credentials['client_id']
            ]);
            
            return [
                'success' => true,
                'message' => 'Credenciales sincronizadas y guardadas correctamente',
                'client_id' => $credentials['client_id']
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Error al sincronizar credenciales: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
}