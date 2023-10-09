<?php

namespace CSD\Image\Tests\Format;

use CSD\Image\Image;
use CSD\Image\Metadata\Aggregate;
use CSD\Image\Metadata\Exif;
use CSD\Image\Metadata\Iptc;
use CSD\Image\Metadata\UnsupportedException;
use CSD\Image\Metadata\Xmp;
use CSD\Image\Metadata\Xmp\ImageRegion;
use CSD\Image\Metadata\Xmp\Point;
use CSD\Image\Metadata\Xmp\RoleFilter;
use CSD\Image\Metadata\Xmp\ShapeFilter;
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
     * @covers ::getIDCMetadata
     */
    public function testGetIDCMetadata()
    {
        $image = $this->getMockForAbstractImage();

        $xmp = $this->createMock(Xmp::class);
        $image->expects($this->atLeastOnce())->method('getXmp')->will($this->returnValue($xmp));

        $inputRectangleRegion = new ImageRegion();
        $inputRectangleRegion->id = 'persltr2';
        $inputRectangleRegion->names = ['Listener 1'];
        $inputRectangleRegion->types = [
            'Region Boundary Content Type Name (ref2021.1)',
            'https://example.org/rctype/type2021.1a',
            'https://example.org/rctype/type2021.1b',
        ];
        $inputRectangleRegion->roles = [
            'Region Boundary Content Role Name (ref2021.1)',
            'https://example.org/rrole/role2021.1a',
            'https://example.org/rrole/role2021.1b',
        ];
        $inputRectangleRegion->rbShape = 'rectangle';
        $inputRectangleRegion->rbUnit = 'relative';
        $inputRectangleRegion->rbXY = new Point(0.31, 0.18);
        $inputRectangleRegion->rbH = 0.385;
        $inputRectangleRegion->rbW = 0.127;
        $inputRectangleRegion->regionDefinitionId = 'rectangleregiondefid';
        $inputRectangleRegion->regionName = 'rectangleregionname';

        $inputCircleRegion = new ImageRegion();
        $inputCircleRegion->id = 'persltr3';
        $inputCircleRegion->names = ['Listener 2'];
        $inputCircleRegion->types = [
            'Region Boundary Content Type Name (ref2021.1)',
            'https://example.org/rctype/type2021.1a',
            'https://example.org/rctype/type2021.1b',
        ];
        $inputCircleRegion->roles = [
            'Region Boundary Content Role Name (ref2021.1)',
            'https://example.org/rrole/role2021.1a',
            'https://example.org/rrole/role2021.1b',
        ];
        $inputCircleRegion->rbShape = 'circle';
        $inputCircleRegion->rbUnit = 'relative';
        $inputCircleRegion->rbXY = new Point(0.59, 0.426);
        $inputCircleRegion->rbRx = 0.068;
        $inputCircleRegion->regionDefinitionId = 'circleregiondefid';
        $inputCircleRegion->regionName = 'circleregionname';

        $inputPolygonRegion = new ImageRegion();
        $inputPolygonRegion->id = 'persltr1';
        $inputPolygonRegion->names = ['Speaker 1'];
        $inputPolygonRegion->types = [
            'Region Boundary Content Type Name (ref2021.1)',
            'https://example.org/rctype/type2021.1a',
            'https://example.org/rctype/type2021.1b',
        ];
        $inputPolygonRegion->roles = [
            'Region Boundary Content Role Name (ref2021.1)',
            'https://example.org/rrole/role2021.1a',
            'https://example.org/rrole/role2021.1b',
        ];
        $inputPolygonRegion->rbShape = 'polygon';
        $inputPolygonRegion->rbUnit = 'relative';
        $inputPolygonRegion->rbXY = new Point(null, null);
        $inputPolygonRegion->rbVertices = [
            new Point(0.05, 0.713),
            new Point(0.148, 0.041),
            new Point(0.375, 0.863),
        ];
        $inputPolygonRegion->regionDefinitionId = 'polygonregiondefid';
        $inputPolygonRegion->regionName = 'polygonregionname';

        $xmp->expects($this->atLeastOnce())->method('getImageRegions')->with(
            ShapeFilter::ANY,
            RoleFilter::ANY
        )->will($this->returnValue([
            $inputRectangleRegion,
            $inputCircleRegion,
            $inputPolygonRegion,
        ]));

        $expectedRectangleRegion = [
            'id' => 'persltr2',
            'names' => ['Listener 1'],
            'shape' => 'rectangle',
            'types' => [
                'Region Boundary Content Type Name (ref2021.1)',
                'https://example.org/rctype/type2021.1a',
                'https://example.org/rctype/type2021.1b',
            ],
            'roles' => [
                'Region Boundary Content Role Name (ref2021.1)',
                'https://example.org/rrole/role2021.1a',
                'https://example.org/rrole/role2021.1b',
            ],
            'unit' => 'relative',
            'imageWidth' => null,
            'imageHeight' => null,
            'x' => 0.31,
            'y' => 0.18,
            'width' => 0.127,
            'height' => 0.385,
            'radius' => null,
            'vertices' => [],
            'regionDefinitionId' => 'rectangleregiondefid',
            'regionName' => 'rectangleregionname',
        ];

        $expectedCircleRegion = [
            'id' => 'persltr3',
            'names' => ['Listener 2'],
            'shape' => 'circle',
            'types' => [
                'Region Boundary Content Type Name (ref2021.1)',
                'https://example.org/rctype/type2021.1a',
                'https://example.org/rctype/type2021.1b',
            ],
            'roles' => [
                'Region Boundary Content Role Name (ref2021.1)',
                'https://example.org/rrole/role2021.1a',
                'https://example.org/rrole/role2021.1b',
            ],
            'unit' => 'relative',
            'imageWidth' => null,
            'imageHeight' => null,
            'x' => 0.59,
            'y' => 0.426,
            'width' => null,
            'height' => null,
            'radius' => 0.068,
            'vertices' => [],
            'regionDefinitionId' => 'circleregiondefid',
            'regionName' => 'circleregionname',
        ];

        $expectedPolygonRegion = [
            'id' => 'persltr1',
            'names' => ['Speaker 1'],
            'shape' => 'polygon',
            'types' => [
                'Region Boundary Content Type Name (ref2021.1)',
                'https://example.org/rctype/type2021.1a',
                'https://example.org/rctype/type2021.1b',
            ],
            'roles' => [
                'Region Boundary Content Role Name (ref2021.1)',
                'https://example.org/rrole/role2021.1a',
                'https://example.org/rrole/role2021.1b',
            ],
            'unit' => 'relative',
            'imageWidth' => null,
            'imageHeight' => null,
            'x' => null,
            'y' => null,
            'width' => null,
            'height' => null,
            'radius' => null,
            'vertices' => [
                [
                    'x' => 0.05,
                    'y' => 0.713,
                ],
                [
                    'x' => 0.148,
                    'y' => 0.041,
                ],
                [
                    'x' => 0.375,
                    'y' => 0.863,
                ],
            ],
            'regionDefinitionId' => 'polygonregiondefid',
            'regionName' => 'polygonregionname',
        ];

        $this->assertEquals([
            $expectedRectangleRegion,
            $expectedCircleRegion,
            $expectedPolygonRegion,
        ], $image->getIDCMetadata(
            ShapeFilter::ANY,
            RoleFilter::ANY,
            /* essentialsOnly: */ false,
        ));

        unset($expectedRectangleRegion['types']);
        unset($expectedRectangleRegion['roles']);
        unset($expectedRectangleRegion['imageWidth']);
        unset($expectedRectangleRegion['imageHeight']);
        unset($expectedRectangleRegion['radius']);
        unset($expectedRectangleRegion['vertices']);

        unset($expectedCircleRegion['types']);
        unset($expectedCircleRegion['roles']);
        unset($expectedCircleRegion['imageWidth']);
        unset($expectedCircleRegion['imageHeight']);
        unset($expectedCircleRegion['width']);
        unset($expectedCircleRegion['height']);
        unset($expectedCircleRegion['vertices']);

        unset($expectedPolygonRegion['types']);
        unset($expectedPolygonRegion['roles']);
        unset($expectedPolygonRegion['imageWidth']);
        unset($expectedPolygonRegion['imageHeight']);
        unset($expectedPolygonRegion['x']);
        unset($expectedPolygonRegion['y']);
        unset($expectedPolygonRegion['width']);
        unset($expectedPolygonRegion['height']);
        unset($expectedPolygonRegion['radius']);

        $this->assertEquals([
            $expectedRectangleRegion,
            $expectedCircleRegion,
            $expectedPolygonRegion,
        ], $image->getIDCMetadata(
            ShapeFilter::ANY,
            RoleFilter::ANY,
            /* essentialsOnly: */ true,
        ));
    }

    /**
     * @return Image|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getMockForAbstractImage()
    {
        return $this->getMockForAbstractClass(Image::class);
    }
}
