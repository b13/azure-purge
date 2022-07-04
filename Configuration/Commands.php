<?php

/*
 * This file is part of TYPO3 CMS-based extension "azure_purge" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

return [
    'azure:purgecdn' => [
        'class' => \B13\AzurePurge\Command\PurgeCommand::class,
        'schedulable' => true,
    ],
];
