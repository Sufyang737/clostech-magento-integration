<?php
namespace Clostech\Integration\Model;

use Clostech\Integration\Api\StoreValidationInterface;
use Clostech\Integration\Helper\StoreValidator;
use Psr\Log\LoggerInterface;

class StoreValidation implements StoreValidationInterface
{
    protected $storeValidator;
    protected $logger;
    
    public function __construct(
        StoreValidator $storeValidator,
        LoggerInterface $logger
    ) {
        $this->storeValidator = $storeValidator;
        $this->logger = $logger;
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
            // validacion de la tienda
            $isValid = $this->storeValidator->validateStore($domain);
            
            if (!$isValid) {
                return [
                    'success' => false,
                    'message' => 'Invalid store domain',
                    'storeId' => null,
                    'storeInfo' => null
                ];
            }
            
            // generar storeId
            $storeId = $this->storeValidator->generateStoreId();
            
            // obtener info de la tienda
            $storeInfo = $this->storeValidator->getStoreInformation();
            
            // mañana: guardar en la DB de Clostech
            // $this->saveToClostechDB($storeId, $storeInfo);
            
            $this->logger->info('Store validated successfully', [
                'domain' => $domain,
                'storeId' => $storeId
            ]);
            
            return [
                'success' => true,
                'message' => 'Store validated successfully',
                'storeId' => $storeId,
                'storeInfo' => $storeInfo
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
}