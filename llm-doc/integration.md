# resumable — Integration

## Split

- Package: the service, the config object, the three handlers, the config-plugin files.
- Application: a configured `blackcube/fileprovider` (its temp alias is where everything lands), the three routes, the `blackcube/resumable` params block echoing those routes, PSR-17 factories and `JsonResponseFactory` in the container, `intervention/image` for thumbnails, the move of the assembled file out of the temporary store, the `bleet` widget on the client.

## Yii (config-plugin)

`config/configuration.php`: `params` → `common/params.php`, `di` → `common/di.php`, `source-directory` `config`.

Params (`config/common/params.php:11-21`), block `blackcube/resumable`; the package ships every key as `null`, the effective defaults are the `??` fallbacks of `config/common/di.php:19-25`:

| Key | Effective default | Use |
|---|---|---|
| `chunkSize` | `524288` | handed to the widget through `ResumableConfig::getChunkSize()` |
| `uploadEndpoint`, `previewEndpoint`, `deleteEndpoint` | `null` → `RuntimeException` on read | the three route URLs, read back by the application when rendering the widget |
| `filetypeIconAlias` | `null` → `CacheFile::FILETYPE_ICONS_PATH` | directory alias of `<ext>.png` icons, with `file.png` as fallback; resolved through `Aliases::get()` |
| `thumbnailWidth`, `thumbnailHeight` | `200`, `200` | preview thumbnail box |

DI (`config/common/di.php:15-28`): only `ResumableConfig::class`, built from the params. `ResumableService` (`FileProviderInterface`, `ResumableConfig`) and the handlers (`ResponseFactoryInterface`, `StreamFactoryInterface`, `JsonResponseFactory`, `Aliases`, `LoggerInterface`) are autowired: the application container must provide the PSR-17 factories and `Yiisoft\DataResponse\ResponseFactory\JsonResponseFactory`.

## Routes

One route per handler, any path; the paths are then written in the params (`README.md:41-50`):

```php
Route::methods(['GET', 'POST'], '/upload')->action(ResumableUploadHandler::class)->name('upload'),
Route::get('/upload/preview')->action(ResumablePreviewHandler::class)->name('upload.preview'),
Route::delete('/upload/delete')->action(ResumableDeleteHandler::class)->name('upload.delete'),
```

The package declares no route and no middleware: authentication of the endpoints is the application's.

## Widget

`Bleet::upload($model, 'files')` (`blackcube/bleet`) renders the drop zone and receives the three URLs and the chunk size from the application (`README.md:69-75`):

```php
echo Bleet::upload($model, 'files')
    ->endpoint($urlGenerator->generate('upload'))
    ->previewEndpoint($urlGenerator->generate('upload.preview'))
    ->deleteEndpoint($urlGenerator->generate('upload.delete'))
    ->chunkSize($resumableConfig->getChunkSize())
    ->render();
```

Client package: `@blackcube/aurelia-bridge-resumablejs` (`bleet-upload` attribute) with `resumablejs`.

## After the upload

The form receives `@bltmp/<finalFilename>`; the application validates it (`FileProviderInterface::isTempPath()`) and moves it to a permanent alias with `FileProvider::move()`. Nothing in this package deletes a finished upload left in the temporary store.

## Tests

`make test-unit` runs the Codeception suites `Provider` and `DocExamples`; `make test-functional` starts `php -S localhost:8888 -t tests/_app/public` with `FILESYSTEM_TYPE=local`, runs the `Functional` suite and stops the server; `make test` runs both (`Makefile:7-27`).
