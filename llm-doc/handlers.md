# resumable — Handlers

All three implement `Psr\Http\Server\RequestHandlerInterface` with a public `handle(ServerRequestInterface): ResponseInterface`; the application binds each to a route (any path, see `integration.md`). Resumable.js parameters are read as `resumable<Name>` (`getResumableParam()`, `src/Handlers/ResumableUploadHandler.php:117-121`): only string values count, anything else is treated as missing.

## `ResumableUploadHandler`

Constructor: `(ResponseFactoryInterface $responseFactory, JsonResponseFactory $jsonResponseFactory, ResumableService $resumableService, ResumableConfig $config)` (`:36-42`). `GET` goes to the chunk test, any other method to the chunk upload (`:44-53`).

### GET — chunk test (`:55-72`)

| Query | Answer |
|---|---|
| `resumableIdentifier`, `resumableFilename`, `resumableChunkNumber` present | `200` when `chunkExists(identifier, cleanFilename(filename), chunkNumber)`, `204` when not |
| one of them missing | `400` |

Empty body in every case.

### POST — chunk upload (`:74-115`)

Multipart body: `resumableIdentifier`, `resumableFilename`, `resumableChunkNumber`, `resumableTotalChunks`, and the chunk as the uploaded file `file`.

| Case | Answer |
|---|---|
| parsed body not an array, or any of the five inputs missing | `400`, empty |
| chunk already present | not rewritten (`:90`) |
| chunk absent | `saveChunk()` with the detached upload stream (`:91-99`) |
| then `isComplete()` true | `assemble()` and JSON `{"complete": true, "finalFilename": "<cleanFilename>"}` |
| then `isComplete()` false | JSON `{"complete": false, "finalFilename": null}` |

The JSON response comes from `Yiisoft\DataResponse\ResponseFactory\JsonResponseFactory`; `complete` is `true` only when `finalFilename` is a non-empty string (`:107-110`).

## `ResumablePreviewHandler`

Constructor: `(ResponseFactoryInterface $responseFactory, StreamFactoryInterface $streamFactory, ResumableService $resumableService, ResumableConfig $config, Aliases $aliases)` (`src/Handlers/ResumablePreviewHandler.php:39-46`). `GET ?name=<alias path>[&original=1]` (`:48-77`):

| `name` | Answer |
|---|---|
| missing or `''` | `400` |
| `isSvg()` and the file exists | `200`, the file itself, `Content-Type: image/svg+xml`, `Content-Disposition: inline; filename="<basename>"` (`original` ignored) |
| `isImage()` and the file exists | `200`, thumbnail `cover(thumbnailWidth, thumbnailHeight)` or the original when `original=1`, `Content-Type` = provider mime type of the source, `Content-Disposition: inline; filename="<basename>"` |
| any other extension, file exists | `200`, `<filetypeIconAlias><ext>.png`, else `file.png`, `Content-Type: image/png`, `Content-Length`, `Content-Disposition: inline; filename="icon.png"` (`streamIcon()`, `:94-116`); `404` when neither icon file exists |
| file missing or unknown alias | `404` |

`name` may point to any alias the provider handles (`@bltmp/…`, `@blfs/…`): the preview is not restricted to the temporary store.

## `ResumableDeleteHandler`

Constructor: `(ResponseFactoryInterface $responseFactory, ResumableService $resumableService, ?LoggerInterface $logger = null)`, `NullLogger` by default (`src/Handlers/ResumableDeleteHandler.php:37-43`). `DELETE ?name=<path>` (`:45-66`):

| `name` | Answer |
|---|---|
| missing or `''` | `400` |
| under the temp alias | `204`, whether the file existed or not |
| outside the temp alias (`InvalidArgumentException`, including `UnknownFilesystemException`) or Flysystem `PathTraversalDetected` | `403`, after `$logger->warning('Temporary file deletion refused', ['name' => …, 'exception' => …])` |

Empty body in every case.
