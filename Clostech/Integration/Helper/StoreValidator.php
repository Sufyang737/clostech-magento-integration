<?php
namespace Clostech\Integration\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class StoreValidator extends AbstractHelper
{
    protected $storeManager;
    protected $scopeConfig;
    
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
    }
    
    
    // obtiene el dominio de la tienda del cliente y define si es valida
    
    public function validateStore(string $domain): bool //devuelve un bool
    {
        try {
            $baseUrl = $this->storeManager->getStore()->getBaseUrl();
            
            // Limpiar URLs para comparación
            $cleanBaseUrl = $this->cleanUrl($baseUrl);
            $cleanDomain = $this->cleanUrl($domain);
            
            return $cleanBaseUrl === $cleanDomain;
        } catch (\Exception $e) {
            $this->_logger->error('Error al validar la tienda: ' . $e->getMessage());
            return false;
        }
    }
    
    
    // importa los productos de la tienda validada
     
    public function getStoreInformation(): array
    {
        try {
            $store = $this->storeManager->getStore();
            
            return [
                'name' => $this->scopeConfig->getValue(
                    'general/store_information/name',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                ) ?: $store->getName(),
                'domain' => $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB, false),
                'secure_url' => $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB, true),
                'phone' => $this->scopeConfig->getValue(
                    'general/store_information/phone',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                ),
                'email' => $this->scopeConfig->getValue(
                    'trans_email/ident_general/email',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                ),
                'country' => $this->scopeConfig->getValue(
                    'general/country/default',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                ) ?: 'US',
                'currency' => $store->getCurrentCurrencyCode() ?: 'USD',
                'shop' => $store->getName(),
                'social' => $this->getSocialNetworks()
            ];
        } catch (\Exception $e) {
            $this->_logger->error('Error al extraer la información: ' . $e->getMessage());
            return [];
        }
    }
    
    
    // genera un storeId unico
    
    public function generateStoreId(): string
    {
        $randomNumber = random_int(100000000, 999999999);
        
        return (string)$randomNumber;
    }
    
    
    //limpia URL para comparación
    private function cleanUrl(string $url): string
    {
        // remueve protocolo
        $url = preg_replace('#^https?://#', '', $url);
        
        // remueve www.
        $url = preg_replace('#^www\.#', '', $url);
        
        // remueve slash
        $url = rtrim($url, '/');
        
        return strtolower($url);
    }
    
    
    // obtiene las redes sociales configuradas

    private function getSocialNetworks(): array
    {
        // Magento no tiene campos por defecto para redes sociales
        // Esto dependerá de si el cliente las configuró en algún módulo
        // Por ahora retornamos vacío, se puede extender después
        return [
            'facebook' => '',
            'instagram' => '',
            'twitter' => ''
        ];
    }


    // Transforma los datos importados al formato que espera Clostech
 
    public function formatDataForClostech(string $storeId, array $storeInfo): array
    {
        return [
            'storeid' => $storeId,
            'email' => $storeInfo['email'] ?? '',
            'domain' => $storeInfo['domain'] ?? '',
            'shop' => $storeInfo['shop'] ?? $storeInfo['name'] ?? '',
            'name' => $storeInfo['name'] ?? '',
            'country' => $storeInfo['country'] ?? 'US',
            'currency' => $storeInfo['currency'] ?? 'USD',
            'app_url' => $storeInfo['secure_url'] ?? $storeInfo['domain'] ?? ''
        ];
    }
}

// { "storeid", "email", "domain", "shop", "name", "country", "currency", "app_url" }