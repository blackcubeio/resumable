<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

use Blackcube\Resumable\ResumableConfig;

/** @var array $params */

return [
    ResumableConfig::class => [
        'class' => ResumableConfig::class,
        '__construct()' => [
            'chunkSize' => $params['blackcube/resumable']['chunkSize'] ?? 524288,
            'uploadEndpoint' => $params['blackcube/resumable']['uploadEndpoint'] ?? null,
            'previewEndpoint' => $params['blackcube/resumable']['previewEndpoint'] ?? null,
            'deleteEndpoint' => $params['blackcube/resumable']['deleteEndpoint'] ?? null,
            'filetypeIconAlias' => $params['blackcube/resumable']['filetypeIconAlias'] ?? null,
            'thumbnailWidth' => $params['blackcube/resumable']['thumbnailWidth'] ?? 200,
            'thumbnailHeight' => $params['blackcube/resumable']['thumbnailHeight'] ?? 200,
        ],
    ],
];
