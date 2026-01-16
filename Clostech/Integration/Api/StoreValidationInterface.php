<?php
namespace Clostech\Integration\Api;

interface StoreValidationInterface
{
    /**
     * Valida la tienda y genera storeId
     * 
     * @param string
     * @return mixed
     */
    public function validate(string $domain);
}