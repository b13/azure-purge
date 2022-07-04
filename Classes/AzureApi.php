<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "azure_purge" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace B13\AzurePurge;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;

class AzureApi
{
    protected string $authenticationUrl = 'https://login.microsoftonline.com/{tenantId}/oauth2/token';
    // see https://docs.microsoft.com/en-us/rest/api/cdn/endpoints/purge-content
    protected string $cdnPurgeUrl = 'https://management.azure.com/subscriptions/{subscriptionId}/resourceGroups/{resourceGroupName}/providers/Microsoft.Cdn/profiles/{cdnProfileName}/endpoints/{cdnEndpointName}/purge?api-version=2021-06-01';
    protected string $frontDoorPurgeUrl = 'https://management.azure.com/subscriptions/{subscriptionId}/resourceGroups/{resourceGroupName}/providers/Microsoft.Network/frontDoors/{frontDoorName}/purge?api-version=2019-05-01';

    public function authenticate(): \stdClass
    {
        $body = 'grant_type=client_credentials&client_id={appId}&client_secret={clientSecret}&resource=https%3A%2F%2Fmanagement.azure.com%2F';
        $body = $this->replaceVars($body);
        $response = $this->getClient()->request('POST', $this->replaceVars($this->authenticationUrl), ['body' => $body]);
        return json_decode($response->getBody()->getContents());
    }

    public function getToken(): ?string
    {
        $cacheIdentifier = 'azure-purge-access-token';
        $cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_hash');
        if ($token = $cache->get($cacheIdentifier)) {
            return $token;
        }
        try {
            $result = $this->authenticate();
            $token = $result->access_token;
            $cache->set($cacheIdentifier, $token, ['azure'], (int)$result->expires_in);
        } catch (ClientException $e) {
            return null;
        }
        return $token;
    }

    public function invalidateAll()
    {
        return $this->invalidateUrls(['/*']);
    }

    public function invalidateUrl(string $url)
    {
        return $this->invalidateUrls([$url]);
    }

    public function invalidateUrls(array $urls)
    {
        try {
            $response = $this->invalidate($urls);
            return $response->getStatusCode();
        } catch (ClientException $e) {
            return $e->getResponse()->getStatusCode();
        }
    }

    public function invalidate(array $urls)
    {
        $token = $this->getToken();
        $client = $this->getClient();
        $purgeUrl = $this->replaceVars($this->cdnPurgeUrl);
        $contentPaths = [];
        foreach ($urls as $url) {
            if (empty($url)) {
                continue;
            }
            $contentPaths[] = $this->getPathFromUrl($url);
        }
        $json = [
            'contentPaths' => $contentPaths,
        ];
        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ];
        $response = $client->request('POST', $purgeUrl, [
            'headers' => $headers,
            'json' => $json,
        ]);

        if ($this->isFrontDoorConfigured()) {
            $purgeUrl = $this->replaceVars($this->frontDoorPurgeUrl);
            try {
                $client->request('POST', $purgeUrl, [
                    'headers' => $headers,
                    'json' => $json,
                ]);
            } catch (ClientException $e) {
                return null;
            }
        }
        return $response;
    }

    protected function replaceVars(string $input): string
    {
        return str_replace(
            [
                '{appId}',
                '{tenantId}',
                '{clientSecret}',
                '{subscriptionId}',
                '{resourceGroupName}',
                '{cdnProfileName}',
                '{cdnEndpointName}',
                '{frontDoorName}',
            ],
            [
                getenv('AZURE_CLIENT_ID'),
                getenv('AZURE_TENANT_ID'),
                getenv('AZURE_CLIENT_SECRET'),
                getenv('AZURE_SUBSCRIPTION_ID'),
                getenv('AZURE_RESOURCE_GROUP'),
                getenv('AZURE_CDN_PROFILE'),
                getenv('AZURE_CDN_ENDPOINT'),
                getenv('AZURE_FRONTDOOR'),
            ],
            $input
        );
    }

    /**
     * Checks if an access token can be obtained
     */
    public function isConfigured(): bool
    {
        if (empty(getenv('AZURE_SUBSCRIPTION_ID'))) {
            return false;
        }
        if (empty(getenv('AZURE_RESOURCE_GROUP'))) {
            return false;
        }
        if (empty(getenv('AZURE_CDN_PROFILE'))) {
            return false;
        }
        if (empty(getenv('AZURE_CDN_ENDPOINT'))) {
            return false;
        }
        try {
            $this->authenticate();
        } catch (ClientException $e) {
            return false;
        }
        return true;
    }

    public function isFrontDoorConfigured()
    {
        return !empty(getenv('AZURE_FRONTDOOR'));
    }

    public function getClient(): ClientInterface
    {
        $httpOptions = $GLOBALS['TYPO3_CONF_VARS']['HTTP'];
        $httpOptions['verify'] = filter_var($httpOptions['verify'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $httpOptions['verify'];
        // @todo: use the PSR-17 factory instead
        unset($httpOptions['handler']);
        return new Client($httpOptions);
    }

    /**
     * Azure only accepts paths like '/my-url' and no domain etc. in front of it, so this is sanitized, only
     * path, query and fragment are kept.
     */
    private function getPathFromUrl(string $url): string
    {
        $urlParts = parse_url($url);
        unset($urlParts['scheme'], $urlParts['user'], $urlParts['pass'], $urlParts['host'], $urlParts['port']);
        return HttpUtility::buildUrl($urlParts);
    }
}
