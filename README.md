# Image metadata parsing library (PHP 5.5+)

> **NOTE**: this is based on
> [dchesterton/image](https://github.com/dchesterton/image). Many thanks to
  [dchesterton](https://github.com/dchesterton)!

Supported image types:
   - JPEG
   - PNG
   - ~~WEBP~~

Supported image meta types:
   - XMP
   - IPTC
   - ~~EXIF~~

## Installation

Pull the library in your project  via [Composer](https://getcomposer.org/)
with the following `composer.json`:

```json
{
  "minimum-stability": "dev",
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/Frameright/php-image-metadata-parser.git"
    }
  ],
  "require": {
    "frameright/image-metadata-parser": "dev-master"
  }
}
```

**Dependencies**: [`php-xml`](https://www.php.net/manual/en/book.dom.php)

## Usage

See also [Getting Started](docs/01_Getting_Started.md).

### Get metadata

```php
$image = Image::fromFile($filename);

$headline = $image->getXmp()->getHeadline();
$camera = $image->getExif()->getCamera();
...
```

### Loading specific image type

When file type is known, you can load the file type directly using the file types' `fromFile` method.

```php
$jpeg = JPEG::fromFile('image.jpg');
$png = PNG::fromFile('image.png');
```

### Instantiate from bytes

If you don't have a file to work with but you do have the image stored in a string (from database, ImageMagick etc.) you can easily instantiate an object from the string.

```php
$data = ...

$jpeg = JPEG::fromString($data);
```

### Instantiate from GD or a stream

You can also create an object from a GD resource or a stream.

```php
$gd = imagecreate(100, 100);
$jpeg = JPEG::fromResource($gd);
```

```php
$stream = fopen('...', 'r+');
$jpeg = JPEG::fromStream($stream);
```

### Aggregate metadata

When just want a piece of metadata and don't care whether it's from XMP, IPTC or EXIF, you can use the aggregate meta object.

```php
$image = Image::fromFile($filename);
$headline = $image->getAggregate()->getHeadline();
```

By default it checks XMP first, then IPTC, then EXIF but you can change the priority:

```php
$aggregate = $image->getAggregate();
$aggregate->setPriority(['exif', 'iptc', 'xmp']);

$aggregate->getHeadline(); // will now check EXIF first, then IPTC, then XMP
```

You can also exclude a metadata type if you do not want to use it:

```php
$aggregate->setPriority(['iptc', 'xmp']);
$aggregate->getHeadline(); // will only check IPTC and XMP
```

#### Get GPS data

```php
$image = ...
$gps = $image->getAggregateMeta()->getGPS(); // checks EXIF and XMP
// or $gps = $image->getExif()->getGPS();

$lat = $gps->getLatitude();
```

## Contributing

Run the linter and the unit tests with:

```bash
composer install
composer lint
composer test
```

See also [Contributing](docs/02_Contributing.md).

## Changelog

**1.0.0** (2023-04-10):
  * Initial version.
