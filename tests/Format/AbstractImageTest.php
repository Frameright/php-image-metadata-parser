<?php

namespace CSD\Image\Tests\Format;

use CSD\Image\Image;
use CSD\Image\Metadata\Aggregate;
use CSD\Image\Metadata\Exif;
use CSD\Image\Metadata\Iptc;
use CSD\Image\Metadata\UnsupportedException;
use CSD\Image\Metadata\Xmp;
use Mockery as M;

/**
 * @author Daniel Chesterton <daniel@chestertondevelopment.com>
 *
 * @coversDefaultClass \CSD\Image\AbstractImage
 */
class AbstractImageTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @covers ::getAggregate
     */
    public function testGetAggregate()
    {
        $image = $this->getMockForAbstractImage();
        $image->expects($this->once())->method('getXmp')->will($this->returnValue(m::mock(Xmp::class)));
        $image->expects($this->once())->method('getIptc')->will($this->returnValue(m::mock(Iptc::class)));
        $image->expects($this->once())->method('getExif')->will($this->returnValue(m::mock(Exif::class)));

        $aggregate = $image->getAggregate();

        $this->assertInstanceOf(Aggregate::class, $aggregate);
    }

    /**
     * @covers ::getAggregate
     */
    public function testGetAggregateWithUnsupportedTypes()
    {
        $image = $this->getMockForAbstractImage();
        $image->expects($this->once())->method('getXmp')->will($this->throwException(new UnsupportedException));
        $image->expects($this->once())->method('getIptc')->will($this->throwException(new UnsupportedException));
        $image->expects($this->once())->method('getExif')->will($this->throwException(new UnsupportedException));

        $aggregate = $image->getAggregate();

        $this->assertInstanceOf(Aggregate::class, $aggregate);
    }

    /**
     * @return Image|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getMockForAbstractImage()
    {
        return $this->getMockForAbstractClass(Image::class);
    }
}
