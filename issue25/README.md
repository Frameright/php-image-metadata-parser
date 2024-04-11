Attempt at reproducing https://github.com/Frameright/php-image-metadata-parser/issues/25

### Quickly run my attempt

Make sure you have PHP interpreter and Composer installed on your system:

```bash
php --version
composer --version
```

Then run the following commands:

```bash
# Clone my attempt at reproducing the issue
git clone https://github.com/Frameright/php-image-metadata-parser
cd php-image-metadata-parser/issue25

# Install the latest version of the PHP library
composer require frameright/image-metadata-parser

# Run this simple PHP script (see details below about what it does)
./image-metadata-parser.php
```

This PHP script doesn't do much. It just loads the image file and prints out XMP metadata:

```
#!/usr/bin/env php
<?php

// load the pulled library:
require __DIR__ . '/vendor/autoload.php';

use CSD\Image\Image;

$image = Image::fromFile('reproducer.jpg');
$xmp_metadata = $image->getXmp();

// nicely format output with indentation and extra space:
$xmp_metadata->setFormatOutput(true);

print_r($xmp_metadata->getString());
```
