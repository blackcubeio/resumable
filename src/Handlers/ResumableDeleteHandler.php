<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Resumable\Handlers;

use Blackcube\Resumable\ResumableService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Yiisoft\Http\Status;

/**
 * Handler for temporary file deletion.
 *
 * Deletes ONLY files in @bltmp (temporary).
 * Files in @blfs (Flysystem) are NOT deleted here — this is the responsibility
 * of business logic (Blams).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class ResumableDeleteHandler implements RequestHandlerInterface
{
    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly ResumableService $resumableService,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        $name = $params['name'] ?? null;

        if ($name === null || $name === '') {
            $status = Status::BAD_REQUEST;
        } else {
            try {
                $this->resumableService->deleteTmpFile($name);
                $status = Status::NO_CONTENT;
            } catch (\InvalidArgumentException|\League\Flysystem\PathTraversalDetected $e) {
                $this->logger->warning('Temporary file deletion refused', [
                    'name' => $name,
                    'exception' => $e,
                ]);
                $status = Status::FORBIDDEN;
            }
        }

        return $this->responseFactory->createResponse($status);
    }
}
