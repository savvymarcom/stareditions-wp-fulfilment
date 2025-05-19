<?php

namespace SavvyWebFulfilment\Admin;

class SavvyPluginConfig
{

    private array $fulfilmentProviderOptions;
    private string $pluginName;
    private string $bandName;
    private string $brandLogo;
    private string $savvyBaseApiUrl;

    public function __construct()
    {
        $this->init();
    }

    private function init(): void
    {
        $this->setFulfilmentProviderOptions();
        $this->setBrandName();
        $this->setPluginName();
        $this->setBrandLogo();
        $this->setSavvyApiUrl();
    }

    private function setFulfilmentProviderOptions(): void
    {
        $this->fulfilmentProviderOptions = [
            'manual' => 'Manual',
            'stareditions' => 'Star Editions Fulfilment',
        ];
    }

    private function setBrandName(): void
    {
        $this->bandName = 'Star Editions';
    }

    private function setPluginName(): void
    {
        $this->pluginName = $this->bandName . ' Fulfilment';
    }

    private function setBrandLogo(): void
    {
        $this->brandLogo = 'https://savvywebsystem.s3.us-east-2.amazonaws.com/savvy-web-files/star-editions-logo_1200x720.png';
    }

    private function setSavvyApiUrl(): void
    {
        $this->savvyBaseApiUrl = 'https://admin.savvyweb.uk/api/v1/';
    }


    
    public function getSavvyFulfilmentProviderOptions(): array
    {
        return $this->fulfilmentProviderOptions;
    }

    public function getSavvyBrandName(): string
    {
        return $this->bandName;
    }

    public function getSavvyBrandLogo(): string
    {
        return $this->brandLogo;
    }

    public function getSavvyPluginName(): string
    {
        return $this->pluginName;
    }

    public function getSavvyApiUrl(): string
    {
        return $this->savvyBaseApiUrl;
    }

}