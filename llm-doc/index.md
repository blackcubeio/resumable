# blackcube/resumable — LLM index

**Role:** server side of Resumable.js uploads for Yii3: three PSR-15 handlers (chunk test and upload, preview, delete) and one service working on the temporary store (`tempAlias`, `@bltmp` by default) of `blackcube/fileprovider`. A completed upload is a file `@bltmp/<finalFilename>` that the application moves out itself. The client side is `@blackcube/aurelia-bridge-resumablejs` driving the `Upload` widget of `blackcube/bleet`.

## Package card

| | |
|---|---|
| Namespace | `Blackcube\Resumable` |
| Service | `ResumableService` (chunks, assembly, deletion, preview streams), `ResumableConfig` (endpoints, chunk size, thumbnail size, icon alias, `cleanFilename()`) |
| Handlers | `Handlers\ResumableUploadHandler` (GET test / POST chunk), `Handlers\ResumablePreviewHandler` (GET), `Handlers\ResumableDeleteHandler` (DELETE) — all implement `Psr\Http\Server\RequestHandlerInterface` directly, no `blackcube/handler` |
| Requires | PHP ^8.2, `blackcube/fileprovider` ^1.0, `psr/http-factory` ^1.1, `psr/http-message` ^2.0, `psr/http-server-handler` ^1.0, `psr/log` ^3.0, `yiisoft/aliases` ^3.1, `yiisoft/data-response` ^2.2, `yiisoft/http` ^1.3. Suggests `blackcube/bleet` (the widget), `intervention/image` (thumbnails in the preview). |
| Yii config | config-plugin: `config/common/params.php` (block `blackcube/resumable`), `config/common/di.php` (`ResumableConfig` only; the service and the handlers are autowired). |
| License | BSD-3-Clause |

## Rules

- Everything lives under the temp alias of the provider: chunks in `<tmp>/<identifier>/<filename>.part<n>`, the assembled file in `<tmp>/<cleanFilename>` (`src/ResumableService.php:95-133`, `:216-225`). The alias comes from `FileProviderInterface::getTempAlias()`, never from this package's config.
- The upload answer carries the bare `finalFilename`, not a path: the form posts `@bltmp/<finalFilename>`; moving it to a permanent alias is the application's job (`README.md:61-62`).
- Filenames are cleaned before reaching the service (`ResumableConfig::cleanFilename()`: `..` sequences removed, any character outside `[a-z0-9_\-.]` replaced by `_`, `src/ResumableConfig.php:89-97`); the handlers clean `resumableFilename`, the service cleans the identifier and the assembled name, not the chunk filename it receives.
- Deletion is refused outside the temp alias (`isTempPath()` false → `InvalidArgumentException` → `403`, `src/ResumableService.php:140-149`, `src/Handlers/ResumableDeleteHandler.php:53-62`); preview accepts any alias the provider handles (`canHandle()`), so `@blfs/…` previews work.
- `getUploadEndpoint()`, `getPreviewEndpoint()`, `getDeleteEndpoint()` throw `RuntimeException` when the param is missing (`src/ResumableConfig.php:47-69`): the routes are declared by the application and echoed back through the params.
- Thumbnails need `intervention/image` in the application: without it the provider returns the full image bytes and the preview is the original, silently.

## Parts

| File | Content | Read when |
|---|---|---|
| `service.md` | `ResumableConfig` (constructor, getters, `cleanFilename()`), `ResumableService` (paths, chunk methods, assembly, deletion, preview, type detection). | before calling the service or changing the upload rules |
| `handlers.md` | The HTTP contract of the three handlers: parameters, status codes, bodies, headers, constructor dependencies. | before routing or consuming the endpoints |
| `integration.md` | config-plugin files, params and defaults, DI, routes, what the application provides, the `bleet` widget wiring, tests. | before plugging the package in |
| `extension.md` | What extends (service subclass, icons, DI overrides) and what does not. | before extending |
