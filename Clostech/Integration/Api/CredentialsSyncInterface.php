<?php

namespace Clostech\Integration\Api;

interface CredentialsSyncInterface
{
    /**
     * Sincronizar credenciales desde Clostech
     * 
     * @return mixed[]
     */
    public function sync();
}