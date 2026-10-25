# resumable — Config and service

## `ResumableConfig` (`src/ResumableConfig.php`)

`__construct(int $chunkSize = 524288, ?string $uploadEndpoint = null, ?string $previewEndpoint = null, ?string $deleteEndpoint = null, ?string $filetypeIconAlias = null, int $thumbnailWidth = 200, int $thumbnailHeight = 200)` (`:31-40`).

| Getter | Returns |
|---|---|
| `getChunkSize(): int` | the chunk size in bytes, consumed by the widget, not by the server |
| `getUploadEndpoint()`, `getPreviewEndpoint()`, `getDeleteEndpoint(): string` | the configured URL, or `RuntimeException('<name> is not configured. Set it in params.php under blackcube/resumable.')` (`:47-69`) |
| `getFiletypeIconAlias(): string` | the configured alias, else `CacheFile::FILETYPE_ICONS_PATH` of `blackcube/fileprovider` (absolute path of its 31 `<ext>.png` icons) (`:71-74`) |
| `getThumbnailWidth()`, `getThumbnailHeight(): int` | preview thumbnail box |

`static cleanFilename(string $filename): string` (`:89-97`): removes `../`, `..\`, `..`, then replaces every run of characters outside `[a-z0-9_\-.]` (case-insensitive) by one `_`. `'../../../etc/passwd'` → `'etc_passwd'`, `'my photo (1).jpg'` → `'my_photo_1_.jpg'` (`docs/api-resumable.md:52-56`).

## `ResumableService` (`src/ResumableService.php`)

`__construct(FileProviderInterface $fileProvider, ResumableConfig $config)` (`:37-41`). As in `blackcube/fileprovider`, the provider must be a `FileProvider`: `fileExists()`, `directoryExists()`, `createDirectory()`, `writeStream()`, `readStream()`, `listContents()`, `deleteDirectory()`, `delete()`, `mimeType()` and `cover()` are not in `FileProviderInterface`.

### Paths

| Method | Path |
|---|---|
| `getTmpPrefix(): string` | `$fileProvider->getTempAlias()` (`:211-214`) |
| `getChunkDir($identifier)` (private) | `<tmp>/<cleanFilename(identifier)>` (`:216-220`) |
| `getChunkPath($identifier, $filename, $chunkNumber)` (private) | `<chunkDir>/<filename>.part<n>` (`:222-225`), `$filename` taken as given |

### Chunks

| Method | Behaviour |
|---|---|
| `chunkExists(string $identifier, string $filename, int $chunkNumber): bool` | `fileExists()` on the chunk path (`:46-50`) |
| `saveChunk(string $identifier, string $filename, int $chunkNumber, mixed $stream): void` | creates the chunk directory when absent, then `writeStream()` (`:57-71`); the caller owns the stream |
| `isComplete(string $identifier, string $filename, int $totalChunks): bool` | `false` when `$totalChunks <= 0`; otherwise every chunk `1..n` must exist, one `fileExists()` each (`:76-88`) |
| `assemble(string $identifier, string $filename): string` | lists the chunk directory, keeps the files whose basename starts with `<filename>.part`, `natsort()`s them, concatenates their streams into a `php://temp` stream, writes it to `<tmp>/<cleanFilename(filename)>`, deletes the chunk directory, returns the clean filename; `''` only when `php://temp` cannot be opened (`:95-133`). Chunks of another filename sharing the identifier stay in place. |

### Files

| Method | Behaviour |
|---|---|
| `deleteTmpFile(string $name): void` | `InvalidArgumentException('Can only delete temporary files')` when `isTempPath($name)` is false; otherwise `delete()` if the file exists, nothing if it does not (`:140-149`) |
| `fileExists(string $name): bool` | `canHandle($name) && fileExists($name)`: `false` for an unknown alias instead of an exception (`:154-158`) |
| `getPreview(string $name, bool $original = false): ?array` | `null` when `fileExists()` is false; else `['stream' => resource, 'mimeType' => provider mimeType, 'filename' => basename]`; the stream is `cover(thumbnailWidth, thumbnailHeight)->readStream()` when `$original` is false and `isImage()` is true, the raw `readStream()` otherwise (`:165-188`). The mime type is the source's even when a thumbnail is produced. |
| `isImage(string $name): bool` | extension in `png, jpg, jpeg, gif, webp, avif` (`:27-34`, `:193-197`) |
| `isSvg(string $name): bool` | extension `svg` (`:35`, `:202-206`) |

Measured: `assemble()` runs on the clean filename for the final path but on the raw `$filename` for the chunk prefix; the handlers pass the clean name to both, so both agree.
