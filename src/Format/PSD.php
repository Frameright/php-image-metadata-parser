<?php

namespace CSD\Image\Format;

use CSD\Image\Metadata\Exif;
use CSD\Image\Metadata\Iptc;
use CSD\Image\Metadata\Xmp;
use CSD\Image\Image;

/**
 * @author Daniel Chesterton <daniel@chestertondevelopment.com>
 * @author Joel Bernerman <joel.bernerman@aller.se>
 */
class PSD extends Image {

    /**
     * File signature.
     */
    const FILE_SIGNATURE = "\x38\x42\x50\x53";

    /**
     * Image resource block signature.
     */
    const IRB_SIGNATURE = "\x38\x42\x49\x4d";

    /**
     * Image resource IDs.
     */
    const IRID_EXIF1 = "\x04\x22";
    const IRID_EXIF2 = "\x04\x23";
    const IRID_XMP = "\x04\x24";
    const IRID_IPTC = "\x04\x04";

    private $fileheader;
    private $IRBs;
    private $eoIRS;
    private $soIRS;
    private $xmp;
    private $exif;
    private $iptc;
    private $tmpHandle;

    /**
     * 
     * @param type $fileHeader
     * @param type $IRBs
     * @param type $soIRS
     * @param type $eoIRS
     */
    private function __construct($fileHeader, $IRBs, $soIRS, $eoIRS, $tmpHandle)
    {

        $this->IRBs = $IRBs;
        $this->fileheader = $fileHeader;
        $this->soIRS = $soIRS;
        $this->eoIRS = $eoIRS;
        $this->tmpHandle = $tmpHandle;
    }

    /**
     * Load a PSD from a GD image resource.
     *
     * @param $gd
     * @return self
     */
    public static function fromResource($gd)
    {
        throw new UnsupportedException('GD does not support PSD files.');
    }

    /**
     * Load PSD from string.
     */
    public static function fromString($string)
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $string);
        rewind($stream);

        return self::fromStream($stream);
    }

    /**
     * Load PSD from Imagick.
     */
    public static function fromImagick(\Imagick $imagick)
    {
        $imagick->setImageFormat('psd');
        return self::fromString($imagick->getImageBlob());
    }

    /**
     * Load a PSD from a stream.
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
            $fileHeader = fread($fileHandle, 4);

            // Check PSD file signature.
            if ($fileHeader !== self::FILE_SIGNATURE) {
                throw new \Exception('Could not find SIGNATURE, invalid PSD file.');
            }

            // Keep reading file header, 22 bytes we only need when writing changes.
            $fileHeader .= fread($fileHandle, 22);
            // Read Color mode Section.
            $bsize = fread($fileHandle, 4);
            $CMLength = unpack('Nsize', $bsize);
            $fileHeader .= $bsize;
            if ($CMLength['size'] > 0) {
                $fileHeader .= fread($fileHandle, $CMLength['size']);
            }

            // Save position of Image Resource Section start.
            // From this location we will modify data.
            $soIRS = ftell($fileHandle);
            // Get Image resource section length.
            $bIRSLength = fread($fileHandle, 4);
            $IRSLength = unpack('Nsize', $bIRSLength);
            // Save position of Image Resource Section end.
            // This is the position to read from tempfile when saving modified.
            $eoIRS = $soIRS + 4 + $IRSLength['size'];

            // Read Resource blocks then stop.
            $IRBs = array();
            while (ftell($fileHandle) < $eoIRS) {
                $IRBSignature = fread($fileHandle, 4);
                if ($IRBSignature === self::IRB_SIGNATURE) {
                    // We have a Image resource block, Read Resource Block.
                    $IRBs[] = self::readResourceBlock($fileHandle);
                }
            }

            $tmpHandle = tmpfile();
            rewind($fileHandle);
            stream_copy_to_stream($fileHandle, $tmpHandle);
            return new self($fileHeader, $IRBs, $soIRS, $eoIRS, $tmpHandle);
        } finally {
            fclose($fileHandle);
        }
        return false;
    }

    private static function readResourceBlock($fileHandle)
    {
        $resourceId = fread($fileHandle, 2);
        $pascalString = fread($fileHandle, 1);
        $pascalStringLength = unpack('Hsize', $pascalString);
        $pascalStringLength = hexdec($pascalStringLength['size']);
        if ($pascalStringLength == 0) {
            // Padding to even bytes.
            $pascalString .= fread($fileHandle, 1);
        }
        else {
            $pascalString .= fread($fileHandle, $pascalStringLength);
            // Padding to even bytes.
            if ($pascalStringLength & 1) {
                $pascalString .= fread($fileHandle, 1);
            }
        }

        $bRBsize = fread($fileHandle, 4);
        $RBsize = unpack('Nsize', $bRBsize);
        $resourceBlockData = fread($fileHandle, $RBsize['size']);

        // Content lenght is uneven, skip padding byte in file handle.
        if ($RBsize['size'] & 1) {
            fseek($fileHandle, 1, SEEK_CUR);
        }

        return new PSD\IRB($resourceId, $pascalString, $resourceBlockData);
    }

    /**
     * Load a PSD from a file.
     *
     * @param $filename
     *
     * @return self
     * @throws \Exception
     */
    public static function fromFile($filename)
    {
        $fileHandle = @fopen($filename, 'rb+');

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
            $present = false;
            foreach ($this->IRBs as $IRB) {
                if ($IRB->getResourceId() === self::IRID_XMP) {
                    $this->xmp = new Xmp($IRB->getData());
                    $present = true;
                    break;
                }
            }
            if (!$present) {
                $this->xmp = new Xmp();
            }
        }

        return $this->xmp;
    }

    /**
     * @return Exif
     */
    public function getExif()
    {
        if (!$this->exif) {
            foreach ($this->IRBs as $IRB) {
                if ($IRB->getResourceId() == self::IRID_EXIF1) {
                    $this->exif = new Exif($IRB->getData());
                    break;
                }
            }
        }

        return $this->exif;
    }

    /**
     * @return Iptc
     */
    public function getIptc()
    {
        if (!$this->iptc) {
            foreach ($this->IRBs as $IRB) {
                if ($IRB->getResourceId() == self::IRID_IPTC) {
                    $this->iptc = new Iptc($IRB->getData());
                    break;
                }
            }
        }

        return $this->iptc;
    }

}
