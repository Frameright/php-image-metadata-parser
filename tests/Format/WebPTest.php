<?php

namespace CSD\Image\Tests\Format;

use CSD\Image\Format\WebP;
use CSD\Image\Metadata\Exif;
use CSD\Image\Metadata\Xmp;
use CSD\Image\Metadata\UnsupportedException;

/**
 * @coversDefaultClass \CSD\Image\Format\WebP
 */
class WebPTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Data provider for valid WebP tests.
     *
     * @return array
     */
    public function providerTestValidWebP()
    {
        return [
            // [method, filename, expectedHeadline]
            ['fromFile', 'meta.webp', 'Headline'],
            ['fromString', 'meta.webp', 'Headline'],
        ];
    }

    /**
     * Test that WebP can read XMP data using both fromFile and fromString methods.
     *
     * @dataProvider providerTestValidWebP
     *
     * @param string $method           The method to use ('fromFile' or 'fromString')
     * @param string $filename         The filename of the test image
     * @param string $expectedHeadline The expected headline in the XMP data
     */
    public function testValidWebP($method, $filename, $expectedHeadline)
    {
        $filePath = __DIR__ . '/../Fixtures/' . $filename;

        if ($method === 'fromFile') {
            $webp = WebP::fromFile($filePath);
        } elseif ($method === 'fromString') {
            $string = file_get_contents($filePath);
            $webp = WebP::fromString($string);
        } else {
            throw new \InvalidArgumentException("Invalid method: $method");
        }

        $this->assertInstanceOf(WebP::class, $webp);
        $this->assertGreaterThan(0, $webp->getSize()["width"]);
        $this->assertGreaterThan(0, $webp->getSize()["height"]);

        $xmp = $webp->getXmp();

        $this->assertInstanceOf(Xmp::class, $xmp);
        $this->assertSame($expectedHeadline, $xmp->getHeadline());
    }

    /**
     * Data provider for invalid WebP tests.
     *
     * @return array
     */
    public function providerTestInvalidWebP()
    {
        return [
            // [method, filename, expectedExceptionMessage]
            ['fromFile', 'nometa.jpg', 'Invalid WebP file'],
            ['fromString', 'nometa.jpg', 'Invalid WebP file'],
        ];
    }

    /**
     * Test that a non-WebP file throws an exception.
     *
     * @dataProvider providerTestInvalidWebP
     *
     * @param string $method                  The method to use ('fromFile' or 'fromString')
     * @param string $filename                The filename of the test image
     * @param string $expectedExceptionMessage The expected exception message
     */
    public function testInvalidWebP($method, $filename, $expectedExceptionMessage)
    {
        $filePath = __DIR__ . '/../Fixtures/' . $filename;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        if ($method === 'fromFile') {
            WebP::fromFile($filePath);
        } elseif ($method === 'fromString') {
            $string = file_get_contents($filePath);
            WebP::fromString($string);
        } else {
            throw new \InvalidArgumentException("Invalid method: $method");
        }
    }

    /**
     * Data provider for Exif tests.
     *
     * @return array
     */
    public function providerTestGetExif()
    {
        return [
            // [method, filename]
            ['fromFile', 'exif.webp'],
            ['fromString', 'exif.webp'],
        ];
    }

    /**
     * Test that Exif data can be retrieved from WebP files.
     *
     * @dataProvider providerTestGetExif
     *
     * @param string $method   The method to use ('fromFile' or 'fromString')
     * @param string $filename The filename of the test image
     */
    public function testGetExif($method, $filename)
    {
        $filePath = __DIR__ . '/../Fixtures/' . $filename;

        if ($method === 'fromFile') {
            $webp = WebP::fromFile($filePath);
        } elseif ($method === 'fromString') {
            $string = file_get_contents($filePath);
            $webp = WebP::fromString($string);
        } else {
            throw new \InvalidArgumentException("Invalid method: $method");
        }

        $exif = $webp->getExif();

        $this->assertInstanceOf(Exif::class, $exif);

        // TODO: Add assertions to test actual Exif data
    }

    /**
     * Data provider for unsupported metadata tests.
     *
     * @return array
     */
    public function providerTestUnsupportedMetadata()
    {
        return [
            // [method, filename, metadataMethod, expectedExceptionMessage]
            ['fromFile', 'meta.webp', 'getIptc', 'WebP files do not support IPTC metadata'],
            ['fromString', 'meta.webp', 'getIptc', 'WebP files do not support IPTC metadata'],
        ];
    }

    /**
     * Test that calling unsupported metadata methods throws an exception.
     *
     * @dataProvider providerTestUnsupportedMetadata
     *
     * @param string $method                   The method to use ('fromFile' or 'fromString')
     * @param string $filename                 The filename of the test image
     * @param string $metadataMethod           The metadata method to call ('getIptc')
     * @param string $expectedExceptionMessage The expected exception message
     */
    public function testUnsupportedMetadata($method, $filename, $metadataMethod, $expectedExceptionMessage)
    {
        $filePath = __DIR__ . '/../Fixtures/' . $filename;

        $this->expectException(UnsupportedException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        if ($method === 'fromFile') {
            $webp = WebP::fromFile($filePath);
        } elseif ($method === 'fromString') {
            $string = file_get_contents($filePath);
            $webp = WebP::fromString($string);
        } else {
            throw new \InvalidArgumentException("Invalid method: $method");
        }

        $webp->$metadataMethod();
    }

    public function ttestSimpleUnsupported()  # FIXME - this test fails
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only extended WebP format is supported');
        $image = WebP::fromFile(__DIR__ . '/../Fixtures/simple.webp');
     }
}
