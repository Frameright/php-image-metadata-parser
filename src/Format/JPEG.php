<?php
namespace CSD\Image\Format;

use CSD\Image\Metadata\Exif;
use CSD\Image\Metadata\Iptc;
use CSD\Image\Metadata\Xmp;
use CSD\Image\Image;

/**
 * @author Daniel Chesterton <daniel@chestertondevelopment.com>
 */
class JPEG extends Image
{
    const SOI = "\xFF\xD8";

    /**
     * @var JPEG\Segment[]
     */
    private $segments;

    /**
     * @var Xmp
     */
    private $xmp;

    /**
     * @var string
     */
    private $imageData;

    /**
     * @param $imageData string
     * @param $segments JPEG\Segment[]
     */
    private function __construct($imageData, $segments)
    {
        $this->imageData = $imageData;
        $this->segments = $segments;
    }

    /**
     * @param $name
     *
     * @return JPEG\Segment[]
     */
    private function getSegmentsByName($name)
    {
        $segments = [];

        foreach ($this->segments as $segment) {
            if ($segment->getName() == $name) {
                $segments[] = $segment;
            }
        }

        return $segments;
    }

    /**
     * Load a JPEG from a GD image resource.
     *
     * @param $gd
     * @return self
     */
    public static function fromResource($gd)
    {
        ob_start();
        imagejpeg($gd);

        $contents = ob_get_contents();
        ob_end_clean();

        return self::fromString($contents);
    }

    /**
     * Load a JPEG from a string.
     *
     * @param $string
     * @return self
     */
    public static function fromString($string)
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $string);
        rewind($stream);

        return self::fromStream($stream);
    }

    /**
     * Load a JPEG from an Imagick instance.
     *
     * @param \Imagick $imagick
     *
     * @return JPEG
     */
    public static function fromImagick(\Imagick $imagick)
    {
        $imagick->setImageFormat('jpg');
        return self::fromString($imagick->getImageBlob());
    }

    /**
     * Load a JPEG from a stream.
     *
     * @param resource $fileHandle
     *
     * @return self
     * @throws \Exception
     */
    public static function fromStream($fileHandle)
    {
        try {
            // Read the first two characters
            $data = fread($fileHandle, 2);

            // Check that the first two characters are 0xFF 0xDA (SOI - Start of image)
            if ($data !== self::SOI) {
                throw new \Exception('Could not find SOI, invalid JPEG file.');
            }

            // Read the next two characters
            $data = fread($fileHandle, 2);

            // Check that the third character is 0xFF (Start of first segment header)
            if ($data[0] != "\xFF") {
                throw new \Exception('No start of segment header character, JPEG probably corrupted.');
            }

            $segments = [];
            $imageData = null;

            // Cycle through the file until, either an EOI (End of image) marker is hit or end of file is hit
            while (($data[1] != "\xD9") && (!feof($fileHandle))) {
                // Found a segment to look at.
                // Check that the segment marker is not a restart marker, restart markers don't have size or data
                if ((ord($data[1]) < 0xD0) || (ord($data[1]) > 0xD7)) {
                    $decodedSize = unpack('nsize', fread($fileHandle, 2)); // find segment size

                    $segmentStart = ftell($fileHandle); // segment start position
                    $segmentData = fread($fileHandle, $decodedSize['size'] - 2); // read segment data
                    $segmentType = ord($data[1]);

                    $segments[] = new JPEG\Segment($segmentType, $segmentStart, $segmentData);
                }

                // If this is a SOS (Start Of Scan) segment, then there is no more header data, the image data follows
                if ($data[1] == "\xDA") {
                    // read the rest of the file, reading 1mb at a time until EOF
                    $compressedData = '';
                    do {
                        $compressedData .= fread($fileHandle, 1048576);
                    } while (!feof($fileHandle));

                    // Strip off EOI and anything after
                    $eoiPos = strpos($compressedData, "\xFF\xD9");
                    $imageData = substr($compressedData, 0, $eoiPos);

                    break; // exit loop as no more headers available.
                } else {
                    // Not an SOS - Read the next two bytes - should be the segment marker for the next segment
                    $data = fread($fileHandle, 2);

                    // Check that the first byte of the two is 0xFF as it should be for a marker
                    if ($data[0] != "\xFF") {
                        throw new \Exception('No FF found, JPEG probably corrupted.');
                    }
                }
            }

            return new self($imageData, $segments);

        } finally {
            fclose($fileHandle);
        }

        return false;
    }

    /**
     * Load a JPEG from a file.
     *
     * @param $filename
     *
     * @return self
     * @throws \Exception
     */
    public static function fromFile($filename)
    {
        $fileHandle = @fopen($filename, 'rb');

        if (!$fileHandle) {
            throw new \Exception(sprintf('Could not open file %s', $filename));
        }

        return self::fromStream($fileHandle);
    }

    /**
     * @return Xmp
     */
    public function getXmp()
    {
        if (!$this->xmp) {
            $possible = $this->getSegmentsByName('APP1');
            $xmpData = null;

            foreach ($possible as $segment) {
                $data = $segment->getData();

                if (0 === strncmp($data, "http://ns.adobe.com/xap/1.0/\x00", 29)) {
                    $xmpData = substr($data, 29);
                    break;
                }
            }
            $this->xmp = new Xmp($xmpData);
        }

        return $this->xmp;
    }

    /**
     * @return Exif
     */
    public function getExif()
    {
        // TODO: Implement getExif() method.
    }

    /**
     * @return Iptc
     */
    public function getIptc()
    {
        // TODO: Implement getIptc() method.
    }
}
