#!/usr/bin/env php
<?php

// load the pulled library:
require __DIR__ . '/vendor/autoload.php';

use CSD\Image\Image;

$image = Image::fromFile('reproducer.jpg');
$xmp_metadata = $image->getXmp();

// nicely format output with indentation and extra space:
$xmp_metadata->setFormatOutput(true);

// print all the XML metadata:
print_r($xmp_metadata->getString());

// extract the image regions specifically:
$regions = $xmp_metadata->getImageRegions();
print_r($regions);
