<?php

namespace CSD\Image\Tests;

use CSD\Image\Image;
use CSD\Image\Format\PNG;
use CSD\Image\Format\JPEG;
use CSD\Image\Format\WebP;
use CSD\Image\Format\PSD;

/**
 * @author Daniel Chesterton <daniel@chestertondevelopment.com>
 *
 * @coversDefaultClass \CSD\Image
 */
class ImageTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @covers ::fromFile
     * @covers ::getSize
     */
    public function testPNG()
    {
        $image = Image::fromFile(__DIR__ . '/Fixtures/nometa.png');
        $this->assertInstanceOf(PNG::class, $image);
        $this->assertSame([
            'width' => 10,
            'height' => 10,
        ], $image->getSize());
    }

    /**
     * @covers ::fromString
     * @covers ::getSize
     */
    public function testPNGFromString()
    {
        $string = \file_get_contents(__DIR__ . '/Fixtures/nometa.png');
        $image = Image::fromString($string);

        $this->assertInstanceOf(PNG::class, $image);
        $this->assertSame([
            'width' => 10,
            'height' => 10,
        ], $image->getSize());
    }

    /**
     * @covers ::fromFile
     * @covers ::getSize
     */
    public function testJPG()
    {
        $image = Image::fromFile(__DIR__ . '/Fixtures/nometa.jpg');
        $this->assertInstanceOf(JPEG::class, $image);
        $this->assertSame([
            'width' => 10,
            'height' => 10,
        ], $image->getSize());
    }

    /**
     * @covers ::fromString
     * @covers ::getSize
     */
    public function testJPGFromString()
    {
        $string = \file_get_contents(__DIR__ . '/Fixtures/nometa.jpg');

        $image = Image::fromString($string);
        $this->assertInstanceOf(JPEG::class, $image);
        $this->assertSame([
            'width' => 10,
            'height' => 10,
        ], $image->getSize());
    }

    /**
     * @covers ::fromFile
     * @covers ::getSize
     */
    public function testWebP()
    {
        $image = Image::fromFile(__DIR__ . '/Fixtures/simple.webp');
        $this->assertInstanceOf(WebP::class, $image);
        $this->assertSame([
            'width' => 550,
            'height' => 368,
        ], $image->getSize());
    }

    /**
     * @covers ::fromString
     * @covers ::getSize
     */
    public function testWebpFromString()
    {
        $string = \file_get_contents(__DIR__ . '/Fixtures/simple.webp');
        $image = Image::fromString($string);

        $this->assertInstanceOf(WebP::class, $image);
        $this->assertSame([
            'width' => 550,
            'height' => 368,
        ], $image->getSize());
    }


    /**
     * @covers ::fromFile
     * @covers ::getSize
     */
    public function testPSD()
    {
        $image = Image::fromFile(__DIR__ . '/Fixtures/nometa.psd');
        $this->assertInstanceOf(PSD::class, $image);
        $this->assertSame([
            'width' => 800,
            'height' => 600,
        ], $image->getSize());
    }

    /**
     * @covers ::fromFile
     */
    public function testUppercase()
    {
        $image = Image::fromFile(__DIR__ . '/Fixtures/UPPERCASE.JPG');
        $this->assertInstanceOf(JPEG::class, $image);
    }

    /**
     * @covers ::fromFile
     */
    public function testJPEG()
    {
        $image = Image::fromFile(__DIR__ . '/Fixtures/nometa.jpeg');
        $this->assertInstanceOf(JPEG::class, $image);
    }

    /**
     * @covers ::fromFile
     */
    public function testInvalidFile()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unrecognised file name');
        Image::fromFile(__FILE__);
    }
}
