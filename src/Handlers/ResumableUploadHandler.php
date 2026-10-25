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
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\DataResponse\ResponseFactory\JsonResponseFactory;
use Yiisoft\Http\Method;
use Yiisoft\Http\Status;

/**
 * Handler for file upload via Resumable.js.
 *
 * Handles:
 * - GET: test if a chunk exists (for resume)
 * - POST: upload a chunk
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class ResumableUploadHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly JsonResponseFactory $jsonResponseFactory,
        private readonly ResumableService $resumableService,
        private readonly ResumableConfig $config,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() === Method::GET) {
            $response = $this->handleTestChunk($request);
        } else {
            $response = $this->handleUploadChunk($request);
        }

        return $response;
    }

    private function handleTestChunk(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();

        $identifier = $this->getResumableParam($params, 'identifier');
        $filename = $this->getResumableParam($params, 'filename');
        $chunkNumber = $this->getResumableParam($params, 'chunkNumber');

        if ($identifier === null || $filename === null || $chunkNumber === null) {
            $status = Status::BAD_REQUEST;
        } else {
            $filename = $this->config->cleanFilename($filename);
            $isPresent = $this->resumableService->chunkExists($identifier, $filename, (int) $chunkNumber) === true;
            $status = $isPresent === true ? Status::OK : Status::NO_CONTENT;
        }

        return $this->responseFactory->createResponse($status);
    }

    private function handleUploadChunk(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->responseFactory->createResponse(Status::BAD_REQUEST);
        $params = $request->getParsedBody();
        if (is_array($params) === true) {
            $uploadedFiles = $request->getUploadedFiles();

            $identifier = $this->getResumableParam($params, 'identifier');
            $filename = $this->getResumableParam($params, 'filename');
            $chunkNumber = $this->getResumableParam($params, 'chunkNumber');
            $totalChunks = $this->getResumableParam($params, 'totalChunks');
            $file = $uploadedFiles['file'] ?? null;

            if ($identifier !== null && $filename !== null && $chunkNumber !== null && $totalChunks !== null && $file !== null) {
                $filename = $this->config->cleanFilename($filename);

                if ($this->resumableService->chunkExists($identifier, $filename, (int) $chunkNumber) === false) {
                    $stream = $file->getStream()->detach();
                    if ($stream !== null) {
                        $this->resumableService->saveChunk(
                            $identifier,
                            $filename,
                            (int) $chunkNumber,
                            $stream
                        );
                    }
                }

                $finalFilename = null;
                if ($this->resumableService->isComplete($identifier, $filename, (int) $totalChunks) === true) {
                    $finalFilename = $this->resumableService->assemble($identifier, $filename);
                }

                $response = $this->jsonResponseFactory->createResponse([
                    'complete' => $finalFilename !== null && $finalFilename !== '',
                    'finalFilename' => $finalFilename,
                ]);
            }
        }

        return $response;
    }

    private function getResumableParam(array $params, string $name): ?string
    {
        $value = $params['resumable' . ucfirst($name)] ?? null;
        return is_string($value) === true ? $value : null;
    }
}
