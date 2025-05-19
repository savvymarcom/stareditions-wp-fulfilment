<?php

namespace SavvyWebFulfilment\Service;

class FulfilmentProviderService
{

    private $fulfilmentProviders = [];

    public function __construct()
    {
        $this->setFulfilmentProviders();
    }

    private function setFulfilmentProviders()
    {
        $this->fulfilmentProviders = [
            'savvyweb',
            'star-editions',
            'savvy-web'
        ];
    }

    public function checkValidFulfilmentProvider($fulfilmentMethod): bool
    {
        $fulfilmentMethod = strtolower(trim($fulfilmentMethod));
        return in_array($fulfilmentMethod, $this->fulfilmentProviders, true);
    }

}