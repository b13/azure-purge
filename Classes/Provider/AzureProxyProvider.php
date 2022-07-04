<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "azure_purge" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace B13\AzurePurge\Provider;

use B13\AzurePurge\AzureApi;
use B13\Proxycachemanager\Provider\ProxyProviderInterface;

/**
 * Uses AzurePurge's Guzzle Wrapper
 *
 * Ensure to set the appropriate configuration
 */
class AzureProxyProvider implements ProxyProviderInterface
{
    /**
     * @var AzureApi|bool|null
     */
    protected $api;

    public function setProxyEndpoints($endpoints)
    {
        // Not needed
    }

    public function flushCacheForUrl($url)
    {
        if (!$this->isActive()) {
            return;
        }
        if (empty($urls)) {
            return;
        }
        $this->api->invalidateUrl($url);
    }

    public function flushCacheForUrls(array $urls)
    {
        if (!$this->isActive()) {
            return;
        }
        if (empty($urls)) {
            return;
        }
        $this->api->invalidateUrls($urls);
    }

    public function flushAllUrls($urls = [])
    {
        if (!$this->isActive()) {
            return;
        }
        $this->api->invalidateAll();
    }

    protected function isActive(): bool
    {
        if ($this->api === null) {
            $this->api = $this->getAzureApi() ?? false;
        }
        return $this->api !== false;
    }

    protected function getAzureApi(): ?AzureApi
    {
        $api = new AzureApi();
        if ($api->isConfigured()) {
            return $api;
        }
        return null;
    }
}
