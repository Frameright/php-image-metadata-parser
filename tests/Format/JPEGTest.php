<?php
namespace CSD\Image\Tests\Format;

use CSD\Image\Format\JPEG;
use CSD\Image\Metadata\Xmp;

/**
 * @coversDefaultClass \CSD\Image\Format\JPEG
 */
class JPEGTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Data provider for testGetXmp method.
     *
     * @return array
     */
    public function providerTestGetXmp()
    {
        return [
            // [method, filename, expectedHeadline]
            ['fromFile', 'metapm.jpg', 'Headline'],
            ['fromString', 'metapm.jpg', 'Headline'],
            ['fromFile', 'metaphotoshop.jpg', 'Headline'],
            ['fromString', 'metaphotoshop.jpg', 'Headline'],
            ['fromFile', 'nometa.jpg', null],
            ['fromString', 'nometa.jpg', null],
        ];
    }

    /**
     * Test that JPEG can read XMP data using both fromFile and fromString methods.
     *
     * @dataProvider providerTestGetXmp
     *
     * @param string      $method          The method to use ('fromFile' or 'fromString')
     * @param string      $filename        The filename of the test image
     * @param string|null $expectedHeadline The expected headline in the XMP data
     */
    public function testGetXmp($method, $filename, $expectedHeadline)
    {
        $filePath = __DIR__ . '/../Fixtures/' . $filename;

        if ($method === 'fromFile') {
            $jpeg = JPEG::fromFile($filePath);
        } elseif ($method === 'fromString') {
            $string = file_get_contents($filePath);
            $jpeg = JPEG::fromString($string);
        } else {
            throw new \InvalidArgumentException("Invalid method: $method");
        }

        $this->assertInstanceOf(JPEG::class, $jpeg);
        $this->assertGreaterThan(0, $jpeg->getSize()["width"]);
        $this->assertGreaterThan(0, $jpeg->getSize()["height"]);

        $xmp = $jpeg->getXmp();

        $this->assertInstanceOf(Xmp::class, $xmp);
        $this->assertSame($expectedHeadline, $xmp->getHeadline());
    }
}

