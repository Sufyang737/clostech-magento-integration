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
    
    /**
     * Valida que la tienda Magento sea válida
     */
    public function validateStore(string $domain): bool
    {
        try {
            $baseUrl = $this->storeManager->getStore()->getBaseUrl();
            
            // Limpiar URLs para comparación
            $cleanBaseUrl = $this->cleanUrl($baseUrl);
            $cleanDomain = $this->cleanUrl($domain);
            
            return $cleanBaseUrl === $cleanDomain;
        } catch (\Exception $e) {
            $this->_logger->error('Error validating store: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Extrae información del cliente desde Magento
     */
    public function getStoreInformation(): array
    {
        try {
            return [
                'name' => $this->scopeConfig->getValue(
                    'general/store_information/name',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                ),
                'domain' => $this->storeManager->getStore()->getBaseUrl(),
                'secure_url' => $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB, true),
                'phone' => $this->scopeConfig->getValue(
                    'general/store_information/phone',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                ),
                'email' => $this->scopeConfig->getValue(
                    'trans_email/ident_general/email',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                ),
                // Redes sociales (si están configuradas)
                'social' => $this->getSocialNetworks()
            ];
        } catch (\Exception $e) {
            $this->_logger->error('Error getting store information: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Genera un storeId único
     */
    public function generateStoreId(): string
    {
        // Generar número aleatorio grande
        $randomNumber = random_int(100000000, 999999999);
        
        // Convertir a string
        return (string)$randomNumber;
    }
    
    /**
     * Limpia URL para comparación
     */
    private function cleanUrl(string $url): string
    {
        // Remover protocolo
        $url = preg_replace('#^https?://#', '', $url);
        
        // Remover www.
        $url = preg_replace('#^www\.#', '', $url);
        
        // Remover trailing slash
        $url = rtrim($url, '/');
        
        return strtolower($url);
    }
    
    /**
     * Obtiene redes sociales configuradas
     */
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
}