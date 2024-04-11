#!/usr/bin/env bash

# exiftool -XMP -b reproducer.jpg | xmlstarlet fo > exiftool_output.xml
exiftool -XMP -b frameright.jpg | xmlstarlet fo > exiftool_output_good.xml
