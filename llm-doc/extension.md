# resumable — Extension

## Extension points

| Point | Where | Use |
|---|---|---|
| Upload rules | subclass `ResumableService` (every method is `public`, the class is not final) and bind it in the application `di.php` | other chunk layout, extra validation before `saveChunk()`, post-assembly hook; the handlers type-hint `ResumableService`, so the subclass is injected |
| Endpoints, sizes, icons | the `blackcube/resumable` params | no code |
| Filetype icons | `filetypeIconAlias` pointing to an application directory of `<ext>.png` + `file.png` | icons other than the 31 of `blackcube/fileprovider` |
| Handlers | own `RequestHandlerInterface` using `ResumableService`, or a subclass of a handler (public `handle()`, private helpers) | other parameter names, authentication inside the handler, other response bodies |
| Logger | `LoggerInterface` autowired into `ResumableDeleteHandler` | audit refused deletions |

## Not extensible

| Element | Reason |
|---|---|
| Resumable.js parameter names (`resumable<Name>`) | `private getResumableParam()` in `ResumableUploadHandler` (`src/Handlers/ResumableUploadHandler.php:117-121`) |
| Status codes and the JSON shape `{complete, finalFilename}` | `private handleTestChunk()` / `handleUploadChunk()` |
| Chunk and directory naming (`<identifier>/<filename>.part<n>`) | `private getChunkDir()` / `getChunkPath()` in `ResumableService` (`src/ResumableService.php:216-225`) |
| Image and SVG extension lists | `private const IMAGE_EXTENSIONS`, `SVG_EXTENSIONS` (`:27-35`) |
| `cleanFilename()` character set | static method with a hardcoded pattern (`src/ResumableConfig.php:89-97`) |
| Temp alias | always `FileProviderInterface::getTempAlias()`: configure it in `blackcube/fileprovider`, not here |
| `ResumableConfig` values after construction | private properties, getters only |
