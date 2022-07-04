<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "azure_purge" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace B13\AzurePurge\Command;

use B13\AzurePurge\AzureApi;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Generic Purge CLI Command to invalidate a set of URLs or a full domain / CDN endpoint.
 * The latter might be useful for deployments.
 *
 * Use
 *   vendor/bin/typo3 azure:purgecdn --endpoint my-endpoint
 * for invalidating by CP Code.
 *
 * Use
 *  vendor/bin/typo3 azure:purgecdn --url https://example.com/my-page/
 * for invalidating by URL
 */
class PurgeCommand extends Command
{
    protected function configure()
    {
        $this
            ->setDescription('Purge Azure CDN Endpoint caches')
            ->addOption(
                'url',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'A list of absolute URLs to purge.',
                []
            );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $urls = $input->getOption('url');

        $api = new AzureApi();
        $authenticationResponse = $api->authenticate();

        if (!$io->isQuiet()) {
            $io->title('Invalidating cache via POST request');
        }
        try {
            $response = $api->invalidate($urls);
        } catch (ClientException $e) {
            $io->error(
                [
                    'An error occurred while purging caches',
                    (string)$e->getResponse()->getBody()->getContents(),
                ]
            );
            return 1;
        }
        if ($response && $response->getStatusCode() < 300) {
            if (!$io->isQuiet()) {
                $io->success('Done - status code ' . $response->getStatusCode());
            }
        } else {
            $io->error(
                [
                    'An error occurred while purging caches',
                    $response ? (string)$response->getBody()->getContents() : '',
                ]
            );
            return 1;
        }
        return 0;
    }
}
