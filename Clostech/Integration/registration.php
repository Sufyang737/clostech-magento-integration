<?php
# Registra la ubicación fisica del modulo (en nuestro caso en app/code/clostech/integration)
declare(strict_types=1);

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Clostech_Integration', # nombre del modulo registrado
    __DIR__
);