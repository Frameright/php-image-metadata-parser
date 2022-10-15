<?php
namespace CSD\Image\Metadata\Xmp;

use CSD\Image\Metadata\Xmp\Point;
use CSD\Image\Metadata\Xmp\RoleFilter;
use CSD\Image\Metadata\Xmp\ShapeFilter;

/**
 * Represents an Image Region read from XMP metadata.
 * See https://iptc.org/std/photometadata/specification/IPTC-PhotoMetadata#image-region
 */
class ImageRegion
{
    /**
     * @var string|null
     */
    public $id;

    /**
     * @var array|null
     */
    public $names;

    /**
     * @var array|null
     */
    public $types;

    /**
     * @var array|null
     */
    public $roles;

    /**
     * 'rectangle', 'circle' or 'polygon'.
     *
     * @var string|null
     */
    public $rbShape;

    /**
     * E.g. 'relative'.
     *
     * @var string|null
     */
    public $rbUnit;

    /**
     * Rectangle or circle's coordinates.
     *
     * @var Point
     */
    public $rbXY;

    /**
     * Rectangle's height.
     *
     * @var string|null
     */
    public $rbH;

    /**
     * Rectangle's width.
     *
     * @var string|null
     */
    public $rbW;

    /**
     * Circle's radius.
     *
     * @var string|null
     */
    public $rbRx;

    /**
     * Polygon's vertices.
     *
     * @var array|null
     */
    public $rbVertices;

    /**
     * Initialize members if a node is provided.
     *
     * @param \DOMXPath|null $xpath
     * @param \DOMNode|null $node <rdf:li> node.
     */
    public function __construct($xpath = null, $node = null)
    {
        if (!$xpath || !$node) {
            // A test case probably wants to forge manually an instance of this
            // class.
            return;
        }

        $this->id = self::getNodeValue($xpath, 'Iptc4xmpExt:rId', $node);
        $this->names = self::getNodeValues(
            $xpath,
            'Iptc4xmpExt:Name/rdf:Alt/rdf:li',
            $node
        );
        $this->types = self::getEntityOrConceptValues(
            $xpath,
            'Iptc4xmpExt:rCtype',
            $node
        );
        $this->roles = self::getEntityOrConceptValues(
            $xpath,
            'Iptc4xmpExt:rRole',
            $node
        );

        $xpathToRb = 'Iptc4xmpExt:RegionBoundary';

        foreach ([
            'rbShape',
            'rbUnit',
            'rbH',
            'rbW',
            'rbRx',
        ] as $property) {
            $this->$property = self::getNodeValue(
                $xpath,
                "$xpathToRb/Iptc4xmpExt:$property",
                $node
            );
        }

        $this->rbXY = new Point(
            self::getNodeValue($xpath, "$xpathToRb/Iptc4xmpExt:rbX", $node),
            self::getNodeValue($xpath, "$xpathToRb/Iptc4xmpExt:rbY", $node)
        );

        $verticesNodes = $xpath->query(
            "$xpathToRb/Iptc4xmpExt:rbVertices/rdf:Seq/rdf:li",
            $node
        );
        if ($verticesNodes->length) {
            $this->rbVertices = [];
            foreach ($verticesNodes as $verticesNode) {
                $point = new Point(
                    self::getNodeValue($xpath, "Iptc4xmpExt:rbX", $verticesNode),
                    self::getNodeValue($xpath, "Iptc4xmpExt:rbY", $verticesNode)
                );
                array_push($this->rbVertices, $point);
            }
        }
    }

    /**
     * @param string $filter One of the ShapeFilter constants.
     *
     * @return bool
     */
    public function matchesShapeFilter($filter) {
        return (
            $filter === ShapeFilter::ANY ||
            ShapeFilter::getXmlRbShape($filter) === $this->rbShape
        );
    }

    /**
     * @param string $filter One of the RoleFilter constants.
     *
     * @return bool
     */
    public function matchesRoleFilter($filter) {
        return (
            $filter === RoleFilter::ANY ||
            count(array_intersect(
                RoleFilter::getMatchingXmlRoles($filter),
                $this->roles
            )) > 0
        );
    }

    /**
     * @param \DOMXPath $xpath
     * @param string $expression XPath expression leading to one single node.
     * @param \DOMNode $contextNode
     *
     * @return string|null
     */
    private static function getNodeValue($xpath, $expression, $contextNode) {
        $node = $xpath->query($expression, $contextNode)->item(0);
        return $node ? $node->nodeValue : null;
    }

    /**
     * @param \DOMXPath $xpath
     * @param string $expression XPath expression leading to several nodes.
     * @param \DOMNode $contextNode
     *
     * @return array|null
     */
    private static function getNodeValues($xpath, $expression, $contextNode) {
        $nodes = $xpath->query($expression, $contextNode);
        if (!$nodes->length) {
            return null;
        }

        $result = [];
        foreach ($nodes as $node) {
            array_push($result, $node->nodeValue);
        }
        return $result;
    }

    /**
     * See https://iptc.org/std/photometadata/specification/IPTC-PhotoMetadata#entity-or-concept-structure
     *
     * @param \DOMXPath $xpath
     * @param string $expression XPath expression leading to the parent of an
     *                           <rdf:Bag> of an Identity or Concept structure.
     * @param \DOMNode $contextNode
     *
     * @return array|null
     */
    private static function getEntityOrConceptValues(
        $xpath,
        $expression,
        $contextNode
    ) {
        $names = self::getNodeValues(
            $xpath,
            "$expression/rdf:Bag/rdf:li/Iptc4xmpExt:Name/rdf:Alt/rdf:li",
            $contextNode
        );
        $identifiers = self::getNodeValues(
            $xpath,
            "$expression/rdf:Bag/rdf:li/xmp:Identifier/rdf:Bag/rdf:li",
            $contextNode
        );

        if (!$names) {
            return $identifiers;
        }
        if (!$identifiers) {
            return $names;
        }
        return array_merge($names, $identifiers);
    }
}
