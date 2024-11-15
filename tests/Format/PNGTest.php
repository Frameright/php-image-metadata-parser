<?php
namespace CSD\Image\Tests\Format;

use CSD\Image\Format\PNG;
use CSD\Image\Metadata\Xmp;
use CSD\Image\Metadata\UnsupportedException;

/**
 * @coversDefaultClass \CSD\Image\Format\PNG
 */
class PNGTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Data provider for testGetXmp method.
     *
     * @return array
     */
    public function providerTestGetXmp()
    {
        return [
            // [method, filename, expectedPhotographerName, expectedHeadline]
            ['fromFile', 'metaphotoshop.png', 'Author', null],
            ['fromString', 'metaphotoshop.png', 'Author', null],
            ['fromFile', 'metapm.png', null, 'Headline'],
            ['fromString', 'metapm.png', null, 'Headline'],
            ['fromFile', 'nometa.png', null, null],
            ['fromString', 'nometa.png', null, null],
        ];
    }

    /**
     * Test that PNG can read XMP data using both fromFile and fromString methods.
     *
     * @dataProvider providerTestGetXmp
     *
     * @param string      $method                 The method to use ('fromFile' or 'fromString')
     * @param string      $filename               The filename of the test image
     * @param string|null $expectedPhotographerName The expected photographer name in the XMP data
     * @param string|null $expectedHeadline       The expected headline in the XMP data
     */
    public function testGetXmp($method, $filename, $expectedPhotographerName, $expectedHeadline)
    {
        $filePath = __DIR__ . '/../Fixtures/' . $filename;

        if ($method === 'fromFile') {
            $png = PNG::fromFile($filePath);
        } elseif ($method === 'fromString') {
            $string = file_get_contents($filePath);
            $png = PNG::fromString($string);
        } else {
            throw new \InvalidArgumentException("Invalid method: $method");
        }

        $this->assertInstanceOf(PNG::class, $png);
        $this->assertGreaterThan(0, $png->getSize()["width"]);
        $this->assertGreaterThan(0, $png->getSize()["height"]);

        $xmp = $png->getXmp();

        $this->assertInstanceOf(Xmp::class, $xmp);
        $this->assertSame($expectedPhotographerName, $xmp->getPhotographerName());
        $this->assertSame($expectedHeadline, $xmp->getHeadline());
    }

    /**
     * Data provider for testInvalidPNG method.
     *
     * @return array
     */
    public function providerTestInvalidPNG()
    {
        return [
            // [method, filename, expectedExceptionMessage]
            ['fromFile', 'nometa.jpg', 'Invalid PNG file signature'],
            ['fromString', 'nometa.jpg', 'Invalid PNG file signature'],
        ];
    }

    /**
     * Test that a non-PNG file throws an exception.
     *
     * @dataProvider providerTestInvalidPNG
     *
     * @param string $method                 The method to use ('fromFile' or 'fromString')
     * @param string $filename               The filename of the test image
     * @param string $expectedExceptionMessage The expected exception message
     */
    public function testInvalidPNG($method, $filename, $expectedExceptionMessage)
    {
        $filePath = __DIR__ . '/../Fixtures/' . $filename;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        if ($method === 'fromFile') {
            $png = PNG::fromFile($filePath);
        } elseif ($method === 'fromString') {
            $string = file_get_contents($filePath);
            $png = PNG::fromString($string);
        } else {
            throw new \InvalidArgumentException("Invalid method: $method");
        }
    }

    /**
     * Data provider for testUnsupportedMetadata method.
     *
     * @return array
     */
    public function providerTestUnsupportedMetadata()
    {
        return [
            // [method, filename, metadataMethod, expectedExceptionMessage]
            ['fromFile', 'nometa.png', 'getExif', 'PNG files do not support EXIF metadata'],
            ['fromString', 'nometa.png', 'getExif', 'PNG files do not support EXIF metadata'],
            ['fromFile', 'nometa.png', 'getIptc', 'PNG files do not support IPTC metadata'],
            ['fromString', 'nometa.png', 'getIptc', 'PNG files do not support IPTC metadata'],
        ];
    }

    /**
     * Test that calling unsupported metadata methods throws an exception.
     *
     * @dataProvider providerTestUnsupportedMetadata
     *
     * @param string $method                 The method to use ('fromFile' or 'fromString')
     * @param string $filename               The filename of the test image
     * @param string $metadataMethod         The metadata method to call ('getExif' or 'getIptc')
     * @param string $expectedExceptionMessage The expected exception message
     */
    public function testUnsupportedMetadata($method, $filename, $metadataMethod, $expectedExceptionMessage)
    {
        $filePath = __DIR__ . '/../Fixtures/' . $filename;

        $this->expectException(UnsupportedException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        if ($method === 'fromFile') {
            $png = PNG::fromFile($filePath);
        } elseif ($method === 'fromString') {
            $string = file_get_contents($filePath);
            $png = PNG::fromString($string);
        } else {
            throw new \InvalidArgumentException("Invalid method: $method");
        }

        $png->$metadataMethod();
    }

    /**
     * Test that a valid PNG file can be loaded using both fromFile and fromString.
     *
     * @return void
     */
    public function testValidPNG()
    {
        $methods = ['fromFile', 'fromString'];
        $filename = 'nometa.png';

        foreach ($methods as $method) {
            $filePath = __DIR__ . '/../Fixtures/' . $filename;

            if ($method === 'fromFile') {
                $png = PNG::fromFile($filePath);
            } elseif ($method === 'fromString') {
                $string = file_get_contents($filePath);
                $png = PNG::fromString($string);
            }

            $this->assertInstanceOf(PNG::class, $png);
        }
    }

    /**
     * Test that a PNG file with malformed chunks throws an exception.
     *
     * @return void
     */
    public function testMalformedChunks()
    {
        $methods = ['fromFile', 'fromString'];
        $filename = 'malformedchunks.png';
        $expectedExceptionMessage = 'Invalid CRC for chunk with type: IHDR';

        foreach ($methods as $method) {
            $this->expectException(\Exception::class);
            $this->expectExceptionMessage($expectedExceptionMessage);

            $filePath = __DIR__ . '/../Fixtures/' . $filename;

            if ($method === 'fromFile') {
                $png = PNG::fromFile($filePath);
            } elseif ($method === 'fromString') {
                $string = file_get_contents($filePath);
                $png = PNG::fromString($string);
            }
        }
    }
}

