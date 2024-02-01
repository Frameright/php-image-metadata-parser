<?php
namespace CSD\Image\Tests\Metadata;

use CSD\Image\Format\JPEG;
use CSD\Image\Metadata\Xmp;
use CSD\Image\Metadata\Xmp\ImageRegion;
use CSD\Image\Metadata\Xmp\Point;
use CSD\Image\Metadata\Xmp\RoleFilter;
use CSD\Image\Metadata\Xmp\ShapeFilter;

/**
 * @coversDefaultClass \CSD\Image\Metadata\Xmp
 */
class XmpTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return array
     */
    public function getDataForAllFile()
    {
        return [
            ['headline', 'Headline'],
            ['caption', 'José Mourinho'],
            ['keywords', ['A keyword', 'Another keyword']],
            ['category', 'SPO'],
            ['contactZip', 'NW1 1AA'],
            ['contactEmail', 'sales@example.com'],
            ['contactCountry', 'England'],
            ['contactAddress', '123 Street Road'],
            ['contactCity', 'London'],
            ['contactUrl', 'http://www.example.com'],
            ['contactPhone', '+44 7901 123456'],
            ['contactState', 'Greater London'],
            ['transmissionReference', 'JOB001'],
            ['objectName', 'OBJECT_NAME'],
            ['instructions', 'All rights reserved.'],
            ['captionWriters', 'Description Writers'],
            ['rightsUsageTerms', 'All rights reserved.'],
            ['event', 'Event Name'],
            ['city', 'London'],
            ['state', 'Greater London'],
            ['location', 'Buckingham Palace'],
            ['country', 'England'],
            ['countryCode', 'GBR'],
            ['IPTCSubjectCodes', ['subj:15054000']],
            ['photographerName', 'Photographer'],
            ['photographerTitle', 'Staff'],
            ['copyrightUrl', 'www.example.com'],
            ['source', 'example.com'],
            ['copyright', 'example.com'],
            ['credit', 'Photographer/Agency'],
            ['urgency', '2'],
            ['rating', '4'],
            ['creatorTool', 'Creator Tool'],
            ['intellectualGenre', 'Intellectual genre'],
            ['supplementalCategories', ['Football', 'Soccer', 'Sport']],
            ['personsShown', ['A person', 'Another person']],
            ['featuredOrganisationName', ['Featured Organisation']],
            ['featuredOrganisationCode', ['Featured Organisation Code']],
            ['IPTCScene', ['IPTC Scene']]
        ];
    }

    /**
     * @return array
     */
    public function getAltFields()
    {
        return [
            ['caption', 'dc:description'],
            ['objectName', 'dc:title'],
            ['copyright', 'dc:rights'],
            ['rightsUsageTerms', 'xmpRights:UsageTerms'],
        ];
    }

    /**
     * @return array
     */
    public function getAttrFields()
    {
        return [
            ['location', 'Iptc4xmpCore:Location'],
            ['contactPhone', 'Iptc4xmpCore:CiTelWork'],
            ['contactAddress', 'Iptc4xmpCore:CiAdrExtadr'],
            ['contactCity', 'Iptc4xmpCore:CiAdrCity'],
            ['contactState', 'Iptc4xmpCore:CiAdrRegion'],
            ['contactZip', 'Iptc4xmpCore:CiAdrPcode'],
            ['contactCountry', 'Iptc4xmpCore:CiAdrCtry'],
            ['contactEmail', 'Iptc4xmpCore:CiEmailWork'],
            ['contactUrl', 'Iptc4xmpCore:CiUrlWork'],
            ['city', 'photoshop:City'],
            ['state', 'photoshop:State'],
            ['country', 'photoshop:Country'],
            ['countryCode', 'Iptc4xmpCore:CountryCode'],
            ['credit', 'photoshop:Credit'],
            ['source', 'photoshop:Source'],
            ['copyrightUrl', 'xmpRights:WebStatement'],
            ['captionWriters', 'photoshop:CaptionWriter'],
            ['instructions', 'photoshop:Instructions'],
            ['category', 'photoshop:Category'],
            ['urgency', 'photoshop:Urgency'],
            ['rating', 'xmp:Rating'],
            ['creatorTool', 'xmp:CreatorTool'],
            ['photographerTitle', 'photoshop:AuthorsPosition'],
            ['transmissionReference', 'photoshop:TransmissionReference'],
            ['headline', 'photoshop:Headline'],
            ['event', 'Iptc4xmpExt:Event'],
            ['intellectualGenre', 'Iptc4xmpCore:IntellectualGenre'],
        ];
    }

    /**
     * @return array
     */
    public function getBagFields()
    {
        return [
            ['keywords', 'dc:subject'],
            ['personsShown', 'Iptc4xmpExt:PersonInImage'],
            ['iptcSubjectCodes', 'Iptc4xmpCore:SubjectCode'],
            ['supplementalCategories', 'photoshop:SupplementalCategories']
        ];
    }

    /**
     * @dataProvider getDataForAllFile
     */
    public function testGetDataFromAllFile($field, $value)
    {
        $getter = 'get' . ucfirst($field);

        $xmp = $this->getXmpFromFile();
        $this->assertEquals($value, $xmp->$getter());

        $xmp = $this->getXmpFromFile2();
        $this->assertEquals($value, $xmp->$getter());
    }

    /**
     * @covers ::getToolkit
     */
    public function testGetToolkit()
    {
        $xmp = $this->getXmpFromFile();

        $this->assertEquals('XMP Core 5.1.2', $xmp->getToolkit());
    }

    /**
     * @covers ::getToolkit
     */
    public function testEmptyToolkit()
    {
        $xmp = new Xmp;
        $this->assertNull($xmp->getToolkit());
    }

    /**
     * @covers ::getXml
     */
    public function testXmpContainsProcessingInstructions()
    {
        $this->assertXmpContainsProcessingInstructions(new Xmp);
        $this->assertXmpContainsProcessingInstructions(new Xmp('<x:xmpmeta xmlns:x="adobe:ns:meta/" />'));
        $this->assertXmpContainsProcessingInstructions($this->getXmpFromFile());
    }

    /**
     * @dataProvider getDataForAllFile
     */
    public function testGetNonExistentValue($field)
    {
        $getter = 'get' . ucfirst($field);

        $xmp = new Xmp;
        $this->assertNull($xmp->$getter());
    }

    /**
     * Test that a rdf:Bag item returns null when the tag is set but there are no items.
     *
     * @covers ::getBag
     */
    public function testGetEmptyBagValue()
    {
        $xmp = new Xmp('<x:xmpmeta xmlns:x="adobe:ns:meta/" x:xmptk="XMP Core 5.1.2">
             <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">
              <rdf:Description rdf:about=""
                xmlns:photoshop="http://ns.adobe.com/photoshop/1.0/">
               <photoshop:SupplementalCategories />
              </rdf:Description>
             </rdf:RDF>
            </x:xmpmeta>
        ');

        $this->assertNull($xmp->getSupplementalCategories());
    }

    /**
     * Test that a rdf:Bag item returns null when the tag is set but there are no items.
     *
     * @covers ::getSeq
     */
    public function testGetEmptySeqValue()
    {
        $xmp = new Xmp('<x:xmpmeta xmlns:x="adobe:ns:meta/" x:xmptk="XMP Core 5.1.2">
             <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">
              <rdf:Description rdf:about=""
                xmlns:dc="http://purl.org/dc/elements/1.1/">
               <dc:creator />
              </rdf:Description>
             </rdf:RDF>
            </x:xmpmeta>
        ');

        $this->assertNull($xmp->getPhotographerName());
    }

    /**
     * Test that a rdf:Alt item returns null when the tag is set but there are no items.
     *
     * @covers ::getAlt
     */
    public function testGetEmptyAltValue()
    {
        $xmp = new Xmp('<x:xmpmeta xmlns:x="adobe:ns:meta/" x:xmptk="XMP Core 5.1.2">
             <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">
              <rdf:Description rdf:about=""
                xmlns:xmpRights="http://ns.adobe.com/xap/1.0/rights/">
               <xmpRights:UsageTerms />
              </rdf:Description>
             </rdf:RDF>
            </x:xmpmeta>
        ');

        $this->assertNull($xmp->getRightsUsageTerms());
    }

    /**
     * @covers ::getContactInfo
     */
    public function testEmptyContactValue()
    {
        $xmp = new Xmp('<x:xmpmeta xmlns:x="adobe:ns:meta/" x:xmptk="XMP Core 5.1.2">
             <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">
              <rdf:Description rdf:about=""
                xmlns:Iptc4xmpCore="http://iptc.org/std/Iptc4xmpCore/1.0/xmlns/">
               <Iptc4xmpCore:CreatorContactInfo />
              </rdf:Description>
             </rdf:RDF>
            </x:xmpmeta>
        ');

        $this->assertNull($xmp->getContactCity());
    }

    /**
     * @covers ::getFormatOutput
     * @covers ::setFormatOutput
     */
    public function testFormatOutput()
    {
        $xmp = new Xmp;

        $this->assertFalse($xmp->getFormatOutput());

        $return = $xmp->setFormatOutput(true);

        $this->assertSame($xmp, $return);
        $this->assertTrue($xmp->getFormatOutput());
    }

    /**
     * Test that the reader only accepts valid XMP root tag.
     */
    public function testInvalidXmlException()
    {
        $this->expectException(\RuntimeException::class);
        new Xmp('<myelement />');
    }

    /**
     * @covers ::fromFile
     */
    public function testFromFile()
    {
        $this->assertInstanceOf(Xmp::class, Xmp::fromFile(__DIR__ . '/../Fixtures/all.XMP'));
    }

    /**
     * @covers ::getImageRegions
     */
    public function testGetImageRegions()
    {
        $jpeg = JPEG::fromFile(
            __DIR__ . '/../Fixtures/IPTC-PhotometadataRef-Std2021.1.jpg');
        $xmp = $jpeg->getXmp();

        $expectedRectangleRegion = new ImageRegion();
        $expectedRectangleRegion->id = 'persltr2';
        $expectedRectangleRegion->names = ['Listener 1'];
        $expectedRectangleRegion->types = [
            'Region Boundary Content Type Name (ref2021.1)',
            'https://example.org/rctype/type2021.1a',
            'https://example.org/rctype/type2021.1b',
        ];
        $expectedRectangleRegion->roles = [
            'Region Boundary Content Role Name (ref2021.1)',
            'https://example.org/rrole/role2021.1a',
            'https://example.org/rrole/role2021.1b',
        ];
        $expectedRectangleRegion->rbShape = 'rectangle';
        $expectedRectangleRegion->rbUnit = 'relative';
        $expectedRectangleRegion->rbXY = new Point(0.31, 0.18);
        $expectedRectangleRegion->rbH = 0.385;
        $expectedRectangleRegion->rbW = 0.127;

        $expectedCircleRegion = new ImageRegion();
        $expectedCircleRegion->id = 'persltr3';
        $expectedCircleRegion->names = ['Listener 2'];
        $expectedCircleRegion->types = [
            'Region Boundary Content Type Name (ref2021.1)',
            'https://example.org/rctype/type2021.1a',
            'https://example.org/rctype/type2021.1b',
        ];
        $expectedCircleRegion->roles = [
            'Region Boundary Content Role Name (ref2021.1)',
            'https://example.org/rrole/role2021.1a',
            'https://example.org/rrole/role2021.1b',
        ];
        $expectedCircleRegion->rbShape = 'circle';
        $expectedCircleRegion->rbUnit = 'relative';
        $expectedCircleRegion->rbXY = new Point(0.59, 0.426);
        $expectedCircleRegion->rbRx = 0.068;

        $expectedPolygonRegion = new ImageRegion();
        $expectedPolygonRegion->id = 'persltr1';
        $expectedPolygonRegion->names = ['Speaker 1'];
        $expectedPolygonRegion->types = [
            'Region Boundary Content Type Name (ref2021.1)',
            'https://example.org/rctype/type2021.1a',
            'https://example.org/rctype/type2021.1b',
        ];
        $expectedPolygonRegion->roles = [
            'Region Boundary Content Role Name (ref2021.1)',
            'https://example.org/rrole/role2021.1a',
            'https://example.org/rrole/role2021.1b',
        ];
        $expectedPolygonRegion->rbShape = 'polygon';
        $expectedPolygonRegion->rbUnit = 'relative';
        $expectedPolygonRegion->rbXY = new Point(null, null);
        $expectedPolygonRegion->rbVertices = [
            new Point(0.05, 0.713),
            new Point(0.148, 0.041),
            new Point(0.375, 0.863),
        ];

        $this->assertEquals([
            $expectedRectangleRegion,
            $expectedCircleRegion,
            $expectedPolygonRegion,
        ], $xmp->getImageRegions());

        $this->assertEquals([
            $expectedRectangleRegion,
        ], $xmp->getImageRegions(ShapeFilter::RECTANGLE));

        $this->assertEquals([
        ], $xmp->getImageRegions(ShapeFilter::RECTANGLE, RoleFilter::CROP));
    }

    /**
     * @covers ::getImageRegions
     */
    public function testGetImageRegionFromFramerightImage()
    {
        $jpeg = JPEG::fromFile(
            __DIR__ . '/../Fixtures/frameright.jpg');

        $xmp = $jpeg->getXmp();

        $expectedRegion = new ImageRegion();
        $expectedRegion->regionDefinitionId = 'definition-7a54f275-6872-435e-befc-b52d97653a28';
        $expectedRegion->regionName = '4:3 Horizontal';
        $expectedRegion->id = 'crop-e789b6b8-ee15-45c0-a0c5-3ad38858db14';
        $expectedRegion->names = null;
        $expectedRegion->types = null;
        $expectedRegion->roles = [
            'http://cv.iptc.org/newscodes/imageregionrole/cropping',
        ];
        $expectedRegion->rbShape = 'rectangle';
        $expectedRegion->rbUnit = 'relative';
        $expectedRegion->rbXY = new Point(0.0375, 0);
        $expectedRegion->rbRx = null;
        $expectedRegion->rbH = '1';
        $expectedRegion->rbW = '0.8890625';

        $this->assertEquals([
            $expectedRegion,
        ], $xmp->getImageRegions());
    }

    /**
     * @covers ::getImageRegions
     *
     * When opening an image with regions in Photoshop and saving it again, the
     * RDF/XML structure might change e.g. from
     *
     * <Iptc4xmpExt:RegionBoundary rdf:parseType="Resource">
     *   <Iptc4xmpExt:rbShape>rectangle</Iptc4xmpExt:rbShape>
     *   <Iptc4xmpExt:rbUnit>relative</Iptc4xmpExt:rbUnit>
     *   <Iptc4xmpExt:rbW>0.6663040522164808</Iptc4xmpExt:rbW>
     *   <Iptc4xmpExt:rbH>1</Iptc4xmpExt:rbH>
     *   <Iptc4xmpExt:rbX>0.25564318738101716</Iptc4xmpExt:rbX>
     *   <Iptc4xmpExt:rbY>0</Iptc4xmpExt:rbY>
     * </Iptc4xmpExt:RegionBoundary>
     *
     * to
     *
     * <Iptc4xmpExt:RegionBoundary Iptc4xmpExt:rbShape="rectangle" Iptc4xmpExt:rbUnit="relative"
     *     Iptc4xmpExt:rbW="0.6663040522164808" Iptc4xmpExt:rbH="1"
     *     Iptc4xmpExt:rbX="0.25564318738101716"
     *     Iptc4xmpExt:rbY="0" />
     *
     * This test validates that we can parse what's generated by Photoshop.
     */
    public function testGetImageRegionFromImageResavedWithPhotoshop()
    {
        $jpeg = JPEG::fromFile(
            __DIR__ . '/../Fixtures/skaterphotoshop.jpg');

        $xmp = $jpeg->getXmp();

        $expectedFirstRegion = new ImageRegion();
        $expectedFirstRegion->regionDefinitionId = '6a3d4ec1-7c0c-58db-9645-6185a80efbcd';
        $expectedFirstRegion->regionName = '1:1 Square';
        $expectedFirstRegion->id = 'crop-cb6bbb93-539d-401b-bb57-341f461ab2ab';
        $expectedFirstRegion->names = null;
        $expectedFirstRegion->types = null;
        $expectedFirstRegion->roles = [
            'http://cv.iptc.org/newscodes/imageregionrole/cropping',
        ];
        $expectedFirstRegion->rbShape = 'rectangle';
        $expectedFirstRegion->rbUnit = 'relative';
        $expectedFirstRegion->rbXY = new Point(0.25564318738101716, 0);
        $expectedFirstRegion->rbRx = null;
        $expectedFirstRegion->rbH = '1';
        $expectedFirstRegion->rbW = '0.6663040522164808';

        $this->assertEquals(
            $expectedFirstRegion,
            $xmp->getImageRegions()[0]
        );
    }

    /**
     * @param Xmp $xmp
     */
    private function assertXmpContainsProcessingInstructions(Xmp $xmp)
    {
        $this->assertStringContainsString("<?xpacket begin=\"\xef\xbb\xbf\" id=\"W5M0MpCehiHzreSzNTczkc9d\"?>", $xmp->getString());
        $this->assertStringContainsString('<?xpacket end="w"?>', $xmp->getString());
    }

    /**
     * Gets XMP file where the data is written as attributes.
     *
     * @return Xmp
     */
    private function getXmpFromFile()
    {
        return new Xmp(file_get_contents(__DIR__ . '/../Fixtures/all.XMP'));
    }

    /**
     * Gets XMP file where the data is written as elements.
     *
     * @return Xmp
     */
    private function getXmpFromFile2()
    {
        return new Xmp(file_get_contents(__DIR__ . '/../Fixtures/all2.XMP'));
    }
}
