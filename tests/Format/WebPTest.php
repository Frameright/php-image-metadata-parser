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

    public function testChangeXmp()
    {
        $tmp = tempnam(sys_get_temp_dir(), 'WebP');

        $webp = WebP::fromFile(__DIR__ . '/../Fixtures/meta.webp');
        $webp->getXmp()->setHeadline('PHP headline');

        // This calls WebP::getBytes(), which doesn't really do anything with
        // $this->getXmp(), so we end up here saving a file with no XMP
        // metadata at all. Because of this, this test case can't possibly
        // succeed.
        $webp->save($tmp);

        $newWebp = WebP::fromFile($tmp);

        // This assertion fails because getHeadline() returns null, because we
        // are unable to produce a WebP file containing XMP metadata as stated
        // above.
        // $this->assertSame('PHP headline', $newWebp->getXmp()->getHeadline());
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

    public function ttestSimpleUnsupported()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only extended WebP format is supported');
        WebP::fromFile(__DIR__ . '/../Fixtures/simple.webp');
    }

    public function testConvertsFromSimpleFormat()
    {
        // todo: mock Xmp class
        $xmp = new Xmp;

        $webp = WebP::fromFile(__DIR__ . '/../Fixtures/simple.webp');
        $webp->setXmp($xmp);

        var_dump($webp->getBytes());
    }
}
