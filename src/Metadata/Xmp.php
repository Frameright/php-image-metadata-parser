<?php
namespace CSD\Image\Metadata;

use CSD\Image\Metadata\Xmp\ImageRegion;
use CSD\Image\Metadata\Xmp\RoleFilter;
use CSD\Image\Metadata\Xmp\ShapeFilter;

/**
 * Class to read XMP metadata from an image.
 *
 * @author Daniel Chesterton <daniel@chestertondevelopment.com>
 */
class Xmp
{
    /**
     *
     */
    const IPTC4_XMP_CORE_NS = 'http://iptc.org/std/Iptc4xmpCore/1.0/xmlns/';

    /**
     *
     */
    const IPTC4_XMP_EXT_NS = 'http://iptc.org/std/Iptc4xmpExt/2008-02-29/';

    /**
     *
     */
    const PHOTOSHOP_NS = 'http://ns.adobe.com/photoshop/1.0/';

    /**
     *
     */
    const DC_NS = 'http://purl.org/dc/elements/1.1/';

    /**
     *
     */
    const XMP_RIGHTS_NS = 'http://ns.adobe.com/xap/1.0/rights/';

    /**
     *
     */
    const RDF_NS = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';

    /**
     *
     */
    const XMP_NS = "http://ns.adobe.com/xap/1.0/";

    /**
     *
     */
    const PHOTO_MECHANIC_NS = "http://ns.camerabits.com/photomechanic/1.0/";

    /**
     * @var \DomDocument
     */
    private $dom;

    /**
     * @var \DOMXPath
     */
    private $xpath;

    /**
     * @var string
     */
    private $about = '';

    /**
     * The XMP namespaces used by this class.
     *
     * @var array
     */
    private $namespaces = [
        'rdf' => self::RDF_NS,
        'dc' => self::DC_NS,
        'photoshop' => self::PHOTOSHOP_NS,
        'xmp' => self::XMP_NS,
        'xmpRights' => self::XMP_RIGHTS_NS,
        'Iptc4xmpCore' => self::IPTC4_XMP_CORE_NS,
        'Iptc4xmpExt' => self::IPTC4_XMP_EXT_NS,
        'photomechanic' => self::PHOTO_MECHANIC_NS
    ];

    /**
     * @param string|null $data
     * @param bool        $formatOutput
     *
     * @throws \Exception
     */
    public function __construct($data = null, $formatOutput = false)
    {
        $this->dom = new \DomDocument('1.0', 'UTF-8');
        $this->dom->preserveWhiteSpace = false;
        $this->dom->formatOutput = $formatOutput;
        $this->dom->substituteEntities = false;

        if (!$data) {
            $data = '<x:xmpmeta xmlns:x="adobe:ns:meta/" />';
        }

        // load xml
        $this->dom->loadXML($data);
        $this->dom->encoding = 'UTF-8';

        if ('x:xmpmeta' !== $this->dom->documentElement->nodeName) {
            throw new \RuntimeException('Root node must be of type x:xmpmeta.');
        }

        // set up xpath
        $this->xpath = new \DOMXPath($this->dom);

        foreach ($this->namespaces as $prefix => $url) {
            $this->xpath->registerNamespace($prefix, $url);
        }

        // try and find an rdf:about attribute, and set it as the default if found
        $about = $this->xpath->query('//rdf:Description/@rdf:about')->item(0);

        if ($about) {
            $this->about = $about->nodeValue;
        }
    }

    /**
     * @param bool $formatOutput
     * @return $this
     */
    public function setFormatOutput($formatOutput)
    {
        $this->dom->formatOutput = $formatOutput;
        return $this;
    }

    /**
     * @return bool
     */
    public function getFormatOutput()
    {
        return $this->dom->formatOutput;
    }

    /**
     * @param string $fileName XMP file to load
     *
     * @return Xmp
     */
    public static function fromFile($fileName)
    {
        return new self(file_get_contents($fileName));
    }

    /**
     * @param      $field
     * @param      $ns
     * @param bool $checkAttributes
     *
     * @return \DOMNode|null
     */
    private function getNode($field, $ns, $checkAttributes = true)
    {
        $rdfDesc = $this->getRDFDescription($ns);

        // check for field as an element or an attribute
        $query = ($checkAttributes)? $field . '|@' . $field: $field;
        $result = $this->xpath->query($query, $rdfDesc);

        if ($result->length) {
            return $result->item(0);
        }

        return null;
    }

    /**
     * Returns data for the given XMP field. Returns null if the field does not exist.
     *
     * @param string $field The field to return.
     * @param string $namespace
     *
     * @return string|null
     */
    private function getAttr($field, $namespace)
    {
        $node = $this->getNode($field, $namespace);

        if ($node) {
            return $node->nodeValue;
        }
        return null;
    }

    /**
     * @param $field
     * @param $namespace
     *
     * @return array|null
     */
    private function getBag($field, $namespace)
    {
        $node = $this->getNode($field, $namespace, false);

        if ($node) {
            $bag = $this->xpath->query('rdf:Bag', $node)->item(0);

            if ($bag) {
                for ($items = [], $i = 0; $i < $bag->childNodes->length; $i++) {
                    $items[] = $bag->childNodes->item($i)->nodeValue;
                }

                return $items;
            }
        }

        return null;
    }

    /**
     * @param $field
     * @param $namespace
     *
     * @return null|string
     */
    private function getAlt($field, $namespace)
    {
        $node = $this->getNode($field, $namespace, false);

        if ($node) {
            $bag = $this->xpath->query('rdf:Alt', $node)->item(0);

            if ($bag) {
                return $bag->childNodes->item(0)->nodeValue;
            }
        }

        return null;
    }

    /**
     * @param $field
     * @param $namespace
     *
     * @return array|null
     */
    private function getSeq($field, $namespace)
    {
        $node = $this->getNode($field, $namespace, false);

        if ($node) {
            $bag = $this->xpath->query('rdf:Seq', $node)->item(0);

            if ($bag) {
                for ($items = [], $i = 0; $i < $bag->childNodes->length; $i++) {
                    $items[] = $bag->childNodes->item($i)->nodeValue;
                }

                return $items;
            }
        }

        return null;
    }

    /**
     * @param $namespace
     *
     * @return \DOMNode|null
     */
    private function getRDFDescription($namespace)
    {
        // element
        $description = $this->xpath->query("//rdf:Description[*[namespace-uri()='$namespace']]");

        if ($description->length > 0) {
            return $description->item(0);
        }

        // attribute
        $description = $this->xpath->query("//rdf:Description[@*[namespace-uri()='$namespace']]");

        if ($description->length > 0) {
            return $description->item(0);
        }

        return null;
    }

    /**
     * @param $namespace
     *
     * @return \DOMElement|\DOMNode|null
     */
    private function getOrCreateRDFDescription($namespace)
    {
        $desc = $this->getRDFDescription($namespace);

        if ($desc) {
            return $desc;
        }

        // try and find any rdf:Description, and add to that
        $desc = $this->xpath->query('//rdf:Description')->item(0);

        if ($desc) {
            return $desc;
        }

        // no rdf:Description's, create new
        $prefix = array_search($namespace, $this->namespaces);

        $desc = $this->dom->createElementNS(self::RDF_NS, 'rdf:Description');
        $desc->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:' . $prefix, $namespace);

        $rdf = $this->xpath->query('rdf:RDF', $this->dom->documentElement)->item(0);

        // check if rdf:RDF element exists, and create it if not
        if (!$rdf) {
            $rdf = $this->dom->createElementNS(self::RDF_NS, 'rdf:RDF');
            $this->dom->documentElement->appendChild($rdf);
        }

        $rdf->appendChild($desc);

        return $desc;
    }

    /**
     * @return string
     */
    public function getHeadline()
    {
        return $this->getAttr('photoshop:Headline', self::PHOTOSHOP_NS);
    }

    /**
     * @return string
     */
    public function getCaption()
    {
        return $this->getAlt('dc:description', self::DC_NS);
    }

    /**
     * @return string
     */
    public function getEvent()
    {
        return $this->getAttr('Iptc4xmpExt:Event', self::IPTC4_XMP_EXT_NS);
    }

    /**
     * @return string
     */
    public function getLocation()
    {
        return $this->getAttr('Iptc4xmpCore:Location', self::IPTC4_XMP_CORE_NS);
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->getAttr('photoshop:City', self::PHOTOSHOP_NS);
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->getAttr('photoshop:State', self::PHOTOSHOP_NS);
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->getAttr('photoshop:Country', self::PHOTOSHOP_NS);
    }

    /**
     * @return string
     */
    public function getCountryCode()
    {
        return $this->getAttr('Iptc4xmpCore:CountryCode', self::IPTC4_XMP_CORE_NS);
    }

    /**
     * @return array
     */
    public function getIPTCSubjectCodes()
    {
        return $this->getBag('Iptc4xmpCore:SubjectCode', self::IPTC4_XMP_CORE_NS);
    }

    /**
     * {@inheritdoc}
     *
     * todo: rename to getAuthor/getCreator
     */
    public function getPhotographerName()
    {
        $seq = $this->getSeq('dc:creator', self::DC_NS);

        if (is_array($seq)) {
            return $seq[0];
        }
        return $seq;
    }

    /**
     * @return string
     */
    public function getCredit()
    {
        return $this->getAttr('photoshop:Credit', self::PHOTOSHOP_NS);
    }

    /**
     * @return string
     */
    public function getPhotographerTitle()
    {
        return $this->getAttr('photoshop:AuthorsPosition', self::PHOTOSHOP_NS);
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->getAttr('photoshop:Source', self::PHOTOSHOP_NS);
    }

    /**
     * @return string
     */
    public function getCopyright()
    {
        return $this->getAlt('dc:rights', self::DC_NS);
    }

    /**
     * @return string
     */
    public function getCopyrightUrl()
    {
        return $this->getAttr('xmpRights:WebStatement', self::XMP_RIGHTS_NS);
    }

    /**
     * @return string
     */
    public function getRightsUsageTerms()
    {
        return $this->getAlt('xmpRights:UsageTerms', self::XMP_RIGHTS_NS);
    }

    /**
     * @return string
     */
    public function getObjectName()
    {
        return $this->getAttr('dc:title', self::DC_NS);
    }

    /**
     * @return string
     */
    public function getCaptionWriters()
    {
        return $this->getAttr('photoshop:CaptionWriter', self::PHOTOSHOP_NS);
    }

    /**
     * @return string
     */
    public function getInstructions()
    {
        return $this->getAttr('photoshop:Instructions', self::PHOTOSHOP_NS);
    }

    /**
     * @return string
     */
    public function getCategory()
    {
        return $this->getAttr('photoshop:Category', self::PHOTOSHOP_NS);
    }

    /**
     * @return array
     */
    public function getSupplementalCategories()
    {
        return $this->getBag('photoshop:SupplementalCategories', self::PHOTOSHOP_NS);
    }

    /**
     * @return string
     */
    public function getContactAddress()
    {
        return $this->getContactInfo('Iptc4xmpCore:CiAdrExtadr');
    }

    /**
     * @return string
     */
    public function getContactCity()
    {
        return $this->getContactInfo('Iptc4xmpCore:CiAdrCity');
    }

    /**
     * @return string
     */
    public function getContactState()
    {
        return $this->getContactInfo('Iptc4xmpCore:CiAdrRegion');
    }

    /**
     * @return string
     */
    public function getContactZip()
    {
        return $this->getContactInfo('Iptc4xmpCore:CiAdrPcode');
    }

    /**
     * @return string
     */
    public function getContactCountry()
    {
        return $this->getContactInfo('Iptc4xmpCore:CiAdrCtry');
    }

    /**
     * @return string
     */
    public function getContactEmail()
    {
        return $this->getContactInfo('Iptc4xmpCore:CiEmailWork');
    }

    /**
     * @param $field
     *
     * @return null|string
     */
    private function getContactInfo($field)
    {
        $contactInfo = $this->getNode('Iptc4xmpCore:CreatorContactInfo', self::IPTC4_XMP_CORE_NS);

        if (!$contactInfo) {
            return null;
        }

        $node = $this->xpath->query($field . '|@' . $field, $contactInfo);

        if ($node->length) {
            return $node->item(0)->nodeValue;
        }

        return null;
    }

    /**
     * @return string
     */
    public function getContactPhone()
    {
        return $this->getContactInfo('Iptc4xmpCore:CiTelWork');
    }

    /**
     * @return string
     */
    public function getContactUrl()
    {
        return $this->getContactInfo('Iptc4xmpCore:CiUrlWork');
    }

    /**
     * @return array
     */
    public function getKeywords()
    {
        return $this->getBag('dc:subject', self::DC_NS);
    }

    /**
     * @return string
     */
    public function getTransmissionReference()
    {
        return $this->getAttr('photoshop:TransmissionReference', self::PHOTOSHOP_NS);
    }

    /**
     * @return string
     */
    public function getUrgency()
    {
        return $this->getAttr('photoshop:Urgency', self::PHOTOSHOP_NS);
    }

    /**
     * @return string
     */
    public function getRating()
    {
        return $this->getAttr('xmp:Rating', self::XMP_NS);
    }

    /**
     * @return string
     */
    public function getCreatorTool()
    {
        return $this->getAttr('xmp:CreatorTool', self::XMP_NS);
    }

    /**
     * @return array
     */
    public function getPersonsShown()
    {
        return $this->getBag('Iptc4xmpExt:PersonInImage', self::IPTC4_XMP_EXT_NS);
    }

    /**
     * @return string
     */
    public function getIntellectualGenre()
    {
        return $this->getAttr('Iptc4xmpCore:IntellectualGenre', self::IPTC4_XMP_CORE_NS);
    }

    /**
     * @return \DateTime|null|false Returns null when attribute is not present, false when it's invalid or a \DateTime
     *                              object when valid/
     */
    public function getDateCreated()
    {
        $date = $this->getAttr('photoshop:DateCreated', self::PHOTOSHOP_NS);

        if (!$date) {
            return null;
        }

        switch (strlen($date)) {
            case 4: // YYYY
                return \DateTime::createFromFormat('Y', $date);
            case 7: // YYYY-MM
                return \DateTime::createFromFormat('Y-m', $date);
            case 10: // YYYY-MM-DD
                return \DateTime::createFromFormat('Y-m-d', $date);
        }

        return new \DateTime($date);
    }

    /**
     * Get about.
     *
     * @return string
     */
    public function getAbout()
    {
        return $this->about;
    }

    /**
     * @return null|string
     */
    public function getToolkit()
    {
        $toolkit = $this->xpath->query('@x:xmptk', $this->dom->documentElement)->item(0);

        if ($toolkit) {
            return $toolkit->nodeValue;
        }

        return null;
    }

    /**
     * @return string
     */
    public function getString()
    {
        // ensure the xml has the required xpacket processing instructions
        $result = $this->xpath->query('/processing-instruction(\'xpacket\')');
        $hasBegin = $hasEnd = false;

        /** @var $item \DOMProcessingInstruction */
        foreach ($result as $item) {
            // do a quick check if the processing instruction contains 'begin' or 'end'
            if (false !== stripos($item->nodeValue, 'begin')) {
                $hasBegin = true;
            } elseif (false !== stripos($item->nodeValue, 'end')) {
                $hasEnd = true;
            }
        }

        if (!$hasBegin) {
            $this->dom->insertBefore(
                $this->dom->createProcessingInstruction(
                    'xpacket',
                    "begin=\"\xef\xbb\xbf\" id=\"W5M0MpCehiHzreSzNTczkc9d\""
                ),
                $this->dom->documentElement // insert before root
            );
        }

        if (!$hasEnd) {
            $this->dom->appendChild($this->dom->createProcessingInstruction('xpacket', 'end="w"')); // append to end
        }

        // ensure all rdf:Description elements have an rdf:about attribute
        $descriptions = $this->xpath->query('//rdf:Description');

        for ($i = 0; $i < $descriptions->length; $i++) {
            /** @var \DOMElement $desc */
            $desc = $descriptions->item($i);
            $desc->setAttributeNS(self::RDF_NS, 'rdf:about', $this->about);
        }

        return $this->dom->saveXML();
    }

    /**
     * @return array
     */
    public function getIPTCScene()
    {
        return $this->getBag('Iptc4xmpCore:Scene', self::IPTC4_XMP_CORE_NS);
    }

    /**
     * @return array
     */
    public function getFeaturedOrganisationName()
    {
        return $this->getBag('Iptc4xmpExt:OrganisationInImageName', self::IPTC4_XMP_EXT_NS);
    }

    /**
     * @return array
     */
    public function getFeaturedOrganisationCode()
    {
        return $this->getBag('Iptc4xmpExt:OrganisationInImageCode', self::IPTC4_XMP_EXT_NS);
    }

    /**
     * @param string $shapeFilter
     * @param string $roleFilter
     *
     * @return array
     */
    public function getImageRegions(
        $shapeFilter = ShapeFilter::ANY,
        $roleFilter = RoleFilter::ANY)
    {
        $imageRegionNode = $this->getNode(
            'Iptc4xmpExt:ImageRegion',
            self::IPTC4_XMP_EXT_NS,
            false
        );
        if (!$imageRegionNode) {
            return [];
        }

        $imageRegionBag = $this->xpath->query('rdf:Bag', $imageRegionNode)
                                      ->item(0);
        if (!$imageRegionBag) {
            return [];
        }

        $results = [];
        foreach ($imageRegionBag->childNodes as $imageRegionItem) {
            $imageRegion = new ImageRegion($this->xpath, $imageRegionItem);
            array_push($results, $imageRegion);
        }

        $results = array_filter(
            $results,
            function($element) use($shapeFilter, $roleFilter) {
                return (
                    $element->matchesShapeFilter($shapeFilter) &&
                    $element->matchesRoleFilter($roleFilter)
                );
            }
        );

        return $results;
    }
}
