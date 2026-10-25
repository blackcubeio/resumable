# blackcube/resumable

Server side of [Resumable.js](https://github.com/23/resumable.js) uploads for Yii: three PSR-15 actions
(upload by chunks, preview, delete) working on the temporary store (`@bltmp`) of `blackcube/fileprovider`.

Its client side is **`@blackcube/aurelia-bridge-resumablejs`** (the `bleet-upload` attribute), which drives the
`Upload` widget of `blackcube/bleet`.

## Installation

```bash
composer require blackcube/resumable
npm install @blackcube/aurelia-bridge-resumablejs resumablejs
```

The configuration plugin registers `ResumableConfig` in the container.

## Configuration

`config/params.php` of the application:

```php
'blackcube/resumable' => [
    'chunkSize' => 524288,                    // bytes
    'uploadEndpoint' => '/upload',            // the routes below, read back with ResumableConfig
    'previewEndpoint' => '/upload/preview',
    'deleteEndpoint' => '/upload/delete',
    'filetypeIconAlias' => '@app/resources/filetypes/',   // <extension>.png icons, file.png as fallback
    'thumbnailWidth' => 200,
    'thumbnailHeight' => 200,
],
```

Without `filetypeIconAlias`, the preview of a file that is not an image uses the icons shipped by
`blackcube/fileprovider` (`CacheFile::FILETYPE_ICONS_PATH`).

## Routes

The application routes the three actions (one route each, any path):

```php
use Blackcube\Resumable\Handlers\ResumableDeleteHandler;
use Blackcube\Resumable\Handlers\ResumablePreviewHandler;
use Blackcube\Resumable\Handlers\ResumableUploadHandler;
use Yiisoft\Router\Route;

Route::methods(['GET', 'POST'], '/upload')->action(ResumableUploadHandler::class)->name('upload'),
Route::get('/upload/preview')->action(ResumablePreviewHandler::class)->name('upload.preview'),
Route::delete('/upload/delete')->action(ResumableDeleteHandler::class)->name('upload.delete'),
```

## Actions

| Action | Request | Answer |
|---|---|---|
| `ResumableUploadHandler` | `GET` with `resumableIdentifier`, `resumableFilename`, `resumableChunkNumber`: is this chunk already there? | `200` present, `204` absent, `400` missing parameter |
| | `POST` multipart: the `resumable*` fields of Resumable.js and the chunk as `file` | JSON `{"complete": bool, "finalFilename": string\|null}`; `finalFilename` is set once the last chunk is in and the file is assembled |
| `ResumablePreviewHandler` | `GET ?name=@bltmp/<file>` (`&original=1` for the full image) | the thumbnail (or the image), the SVG itself, or the file type icon; `404` unknown file, `400` without `name` |
| `ResumableDeleteHandler` | `DELETE ?name=@bltmp/<file>` | `204`; `403` for anything outside the temporary store or a path traversal; `400` without `name` |

An uploaded file is named `@bltmp/<finalFilename>`: that is the value the form posts. Moving it out of the
temporary store is the application's job (`FileProviderInterface` of `blackcube/fileprovider`).

## Client side

`Bleet::upload()` (`blackcube/bleet`) renders the drop zone; it gets the three routes and the chunk size from
the application:

```php
echo Bleet::upload($model, 'files')
    ->endpoint($urlGenerator->generate('upload'))
    ->previewEndpoint($urlGenerator->generate('upload.preview'))
    ->deleteEndpoint($urlGenerator->generate('upload.delete'))
    ->chunkSize($resumableConfig->getChunkSize())
    ->render();
```

The markup contract and the options of the attribute are documented in `@blackcube/aurelia-bridge-resumablejs`.

## Tests

```bash
# Unit tests (Provider, DocExamples suites)
make test-unit

# Functional tests (starts HTTP server)
make test-functional

# All tests
make test
```

## Documentation

- [API — Resumable](docs/api-resumable.md)

## License

BSD-3-Clause, see [LICENSE.md](LICENSE.md).
