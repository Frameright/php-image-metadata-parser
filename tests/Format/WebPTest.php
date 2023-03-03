<?php

namespace CSD\Image\Tests\Format;

use CSD\Image\Format\WebP;
use CSD\Image\Metadata\Exif;
use CSD\Image\Metadata\Xmp;

/**
 * @author Daniel Chesterton <daniel@chestertondevelopment.com>
 *
 * @coversDefaultClass \CSD\Image\Format\WebP
 */
class WebPTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test that a non-WebP file throws an exception.
     *
     * @covers ::fromFile
     * @covers ::__construct
     */
    public function testFromFileInvalidWebP()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid WebP file');
        WebP::fromFile(__DIR__ . '/../Fixtures/nometa.jpg');
    }

    public function testFromFile()
    {
        $webp = WebP::fromFile(__DIR__ . '/../Fixtures/meta.webp');
        $this->assertInstanceOf(WebP::class, $webp);

        $xmp = $webp->getXmp();

        $this->assertInstanceOf(XMP::class, $xmp);
        $this->assertSame('Headline', $xmp->getHeadline());
    }

    public function testGetExif()
    {
        $webp = WebP::fromFile(__DIR__ . '/../Fixtures/exif.webp');
        $exif = $webp->getExif();

        $this->assertInstanceOf(Exif::class, $exif);

        // todo: test actual value of exif
    }

    /**
     * @covers ::getIptc
     */
    public function testGetIptc()
    {
        $this->expectException(\CSD\Image\Metadata\UnsupportedException::class);
        $this->expectExceptionMessage('WebP files do not support IPTC metadata');
        $webp = WebP::fromFile(__DIR__ . '/../Fixtures/meta.webp');
        $webp->getIptc();
    }

    public function ttestSimpleUnsupported()  # FIXME - this test fails
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only extended WebP format is supported');
        WebP::fromFile(__DIR__ . '/../Fixtures/simple.webp');
    }
}
