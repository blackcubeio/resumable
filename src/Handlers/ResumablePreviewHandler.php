<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Resumable\Handlers;

use Blackcube\Resumable\ResumableConfig;
use Blackcube\Resumable\ResumableService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Http\Status;

/**
 * Handler for uploaded file previews.
 *
 * Handles:
 * - @bltmp/...: temporary files
 * - @blfs/..., @blcdn/..., etc.: final files (any FileProvider alias)
 *
 * For images: generates a thumbnail or direct stream
 * For others: returns an icon based on extension
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class ResumablePreviewHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly ResumableService $resumableService,
        private readonly ResumableConfig $config,
        private readonly Aliases $aliases,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        $name = $params['name'] ?? null;
        $original = ($params['original'] ?? '0') === '1';

        $response = null;
        if ($name === null || $name === '') {
            $response = $this->responseFactory->createResponse(Status::BAD_REQUEST);
        } elseif ($this->resumableService->isSvg($name) === true) {
            $preview = $this->resumableService->getPreview($name, true);
            if ($preview !== null) {
                $response = $this->streamResponse($preview, 'image/svg+xml');
            }
        } elseif ($this->resumableService->isImage($name) === true) {
            $preview = $this->resumableService->getPreview($name, $original);
            if ($preview !== null) {
                $response = $this->streamResponse($preview);
            }
        } elseif ($this->resumableService->fileExists($name) === true) {
            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $response = $this->streamIcon($extension);
        }

        if ($response === null) {
            $response = $this->responseFactory->createResponse(Status::NOT_FOUND);
        }

        return $response;
    }

    /**
     * Stream a response with the preview.
     *
     * @param array{stream: resource, mimeType: string, filename: string} $preview
     */
    private function streamResponse(array $preview, ?string $forceMimeType = null): ResponseInterface
    {
        $stream = $this->streamFactory->createStreamFromResource($preview['stream']);

        return $this->responseFactory->createResponse(Status::OK)
            ->withHeader('Content-Type', $forceMimeType ?? $preview['mimeType'])
            ->withHeader('Content-Disposition', 'inline; filename="' . $preview['filename'] . '"')
            ->withBody($stream);
    }

    private function streamIcon(string $extension): ResponseInterface
    {
        $iconAlias = $this->config->getFiletypeIconAlias() . $extension . '.png';
        $iconPath = $this->aliases->get($iconAlias);

        if (file_exists($iconPath) === false) {
            $iconPath = $this->aliases->get($this->config->getFiletypeIconAlias() . 'file.png');
        }

        if (file_exists($iconPath) === false) {
            $response = $this->responseFactory->createResponse(Status::NOT_FOUND);
        } else {
            $stream = $this->streamFactory->createStreamFromFile($iconPath, 'r');

            $response = $this->responseFactory->createResponse(Status::OK)
                ->withHeader('Content-Type', 'image/png')
                ->withHeader('Content-Disposition', 'inline; filename="icon.png"')
                ->withHeader('Content-Length', (string) filesize($iconPath))
                ->withBody($stream);
        }

        return $response;
    }
}
