# Microsoft Azure CDN Adapter for TYPO3

When TYPO3 is behind an Azure's CDN Profile, this extension is a perfect companion
for you.

This extension hides the complexity of Azure's CDN API to purge caches.

## Installation

You can install this extension by using composer:

    composer req b13/azure-purge

## Usage

By default, EXT:azure-purge ships with a `azure:cdnpurge` CLI command to purge a full
CDN profile or a specific URL within the CDN profile.

It is possible to purge a single or multiple URLs

    ./vendor/bin/typo3 azure:cdnpurge --url=/page1 --url=/page2

or purge a whole CDN Profile

    ./vendor/bin/typo3 azure:cdnpurge --profile=abcdef

## Integration into TYPO3 Backend

EXT:azure-purge can be used in conjunction with TYPO3's Proxy Cache Manager Extension.

Using the Azure CDN Adapter for EXT:proxycachemanager flushes page caches directly
when modifying a page. This is perfect if you're dealing with Azure CDN
that not just caches your static assets but also your pages.

For this, ensure to set the class `\B13\AzurePurge\Provider\AzureProxyProvider` in
the settings of EXT:proxycachemanager.

## Configuration

This extension purges CDN caches via cURL requests wrapped in the Guzzle
API (bundled in TYPO3) instead of using the CLI binary `az` as requested
by most examples.

For this reason, a OAuth Client with a `Client ID`, `Tenant ID` and a `Secret`
must be created within Azure Portal first.

TYPO3 then creates an Access Token (oauth2/authorize), which is then
used to purge content on the Azure CDN Profile.

Azure thus requires:

* Azure App ID / Client ID
* Azure Tenant ID
* Azure Client Secret

Then:
* Azure Subscription
* Azure Resource Group
* Azure CDN Profile
* Azure CDN Endpoint
* Optional: FrontDoor ID

All need to be provided via Environment variables:

* AZURE_CLIENT_ID
* AZURE_TENANT_ID
* AZURE_CLIENT_SECRET
* AZURE_SUBSCRIPTION_ID
* AZURE_RESOURCE_GROUP
* AZURE_CDN_PROFILE
* AZURE_CDN_ENDPOINT
* AZURE_FRONTDOOR


## Support for Azure FrontDoors

See https://docs.microsoft.com/de-de/rest/api/frontdoorservice/frontdoor/endpoints/purge-content

Ensure to set the environment variable `AZURE_FRONTDOOR` to the Front Door Name.

### Using multiple sections / CDN endpoints in Site Configuration

This isn't implemented yet.

## License

The extension is licensed under GPL v2+, same as the TYPO3 Core. For details see the LICENSE file in this repository.

Icon courtesy of Microsoft Azure Icon Pack. https://docs.microsoft.com/de-de/azure/architecture/icons/

## Open Issues

If you find an issue, feel free to create an issue on GitHub or a pull request.

## Credits

This extension was created by [Benni Mack](https://github.com/bmack) in 2022 for [b13 GmbH](https://b13.com).

[Find more TYPO3 extensions we have developed](https://b13.com/useful-typo3-extensions-from-b13-to-you) that help us deliver value in client projects. As part of the way we work, we focus on testing and best practices to ensure long-term performance, reliability, and results in all our code.
