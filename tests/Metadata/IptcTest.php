<?php
namespace CSD\Image\Tests\Metadata\Reader;

use CSD\Image\Metadata\Iptc;

/**
 * @coversDefaultClass \CSD\Image\Metadata\Iptc
 */
class IptcTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Iptc
     */
    private $meta;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        $this->meta = Iptc::fromFile(__DIR__ . '/../Fixtures/metapm.jpg');
    }

    /**
     * @return array
     */
    public function getMetaFields()
    {
        return [
            ['headline'],
            ['caption'],
            ['location'],
            ['city'],
            ['state'],
            ['country'],
            ['countryCode'],
            ['photographerName'],
            ['credit'],
            ['source'],
            ['photographerTitle'],
            ['copyright'],
            ['objectName'],
            ['captionWriters'],
            ['instructions'],
            ['category'],
            ['supplementalCategories'],
            ['transmissionReference'],
            ['urgency'],
            ['keywords']
        ];
    }

    /**
     * @covers ::getHeadline
     */
    public function testHeadline()
    {
        $this->assertEquals('Headline', $this->meta->getHeadline());
    }

    public function tsestCaption()
    {
        $this->assertEquals(
            'José Mourinho',
            $this->meta->getCaption()
        );
    }

    /**
     * @covers ::getKeywords
     */
    public function tesstKeywords()
    {
        $this->assertEquals(
            'Canvey Island, Carshalton Athletic, England, Essex, Football, Ryman Isthmian Premier League, Soccer, ' .
            'Sport, Sports, The Prospects Stadium',
            $this->meta->getKeywords()
        );
    }

    public function tesstCategory()
    {
        $this->assertEquals('SPO', $this->meta->getCategory());
    }

    /**
     * @dataProvider getMetaFields
     */
    public function testNull($field)
    {
        $getter = 'get' . ucfirst($field);

        $iptc = new Iptc;

        $this->assertNull($iptc->$getter());
    }
}
