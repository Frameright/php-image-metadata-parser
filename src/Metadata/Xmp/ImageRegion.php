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
     * Region's definition ID from the Frameright service.
     *
     * @var string|null
     */
    public $regionDefinitionId;

    /**
     * Region's definition name from the Frameright service.
     *
     * @var string|null
     */
    public $regionName;

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

        $this->id = self::getChildValueOrAttr($xpath, 'Iptc4xmpExt:rId', $node);

        $xpathNames = "Iptc4xmpExt:Name/rdf:Alt/rdf:li";
        $this->names = self::getNodeValues(
            $xpath,
            // Matches only children and grand-children, not deeper:
            "./$xpathNames|./*/$xpathNames",
            $node
        );

        $this->types = self::getEntityOrConceptValues(
            $xpath,
            './/Iptc4xmpExt:rCtype', // matches any descendant with this name
            $node
        );
        $this->roles = self::getEntityOrConceptValues(
            $xpath,
            './/Iptc4xmpExt:rRole', // matches any descendant with this name
            $node
        );

        $this->regionDefinitionId = self::getChildValueOrAttr(
            $xpath,
            'FramerightIdc:RegionDefinitionId',
            $node
        );

        $this->regionName = self::getChildValueOrAttr(
            $xpath,
            'FramerightIdc:RegionName',
            $node
        );

        $xpathToRb = './/Iptc4xmpExt:RegionBoundary'; // matches any descendant with this name
        $rbNode = $xpath->query($xpathToRb, $node)->item(0);
        if ($rbNode) {
            foreach ([
                'rbShape',
                'rbUnit',
                'rbH',
                'rbW',
                'rbRx',
            ] as $property) {
                $this->$property = self::getChildValueOrAttr(
                    $xpath,
                    "Iptc4xmpExt:$property",
                    $rbNode
                );
            }
        }

        $this->rbXY = new Point(
            self::getChildValueOrAttr($xpath, "Iptc4xmpExt:rbX", $rbNode),
            self::getChildValueOrAttr($xpath, "Iptc4xmpExt:rbY", $rbNode)
        );

        $verticesNodes = $xpath->query(
            "$xpathToRb/Iptc4xmpExt:rbVertices/rdf:Seq/rdf:li",
            $node
        );
        if ($verticesNodes->length) {
            $this->rbVertices = [];
            foreach ($verticesNodes as $verticesNode) {
                $point = new Point(
                    self::getChildValueOrAttr($xpath, "Iptc4xmpExt:rbX", $verticesNode),
                    self::getChildValueOrAttr($xpath, "Iptc4xmpExt:rbY", $verticesNode)
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
     * Get the value of the first node matching the given XPath expression.
     *
     * @param \DOMXPath $xpath
     * @param string $expression XPath expression.
     * @param \DOMNode $contextNode
     *
     * @return string|null
     */
    private static function getNodeValue($xpath, $expression, $contextNode) {
        $node = $xpath->query($expression, $contextNode)->item(0);
        return $node ? $node->nodeValue : null;
    }

    /**
     * Attempts to find a value in the following order:
     * 1. First direct child node having the given name.
     * 2. Context node's attribute with this name.
     * 3. First direct child node's attribute with this name.
     *
     * @param \DOMXPath $xpath
     * @param string $nodeOrAttrName
     * @param \DOMNode $contextNode
     *
     * @return string|null
     */
    private static function getChildValueOrAttr($xpath, $nodeOrAttrName, $contextNode) {
        $childValue = self::getNodeValue($xpath, $nodeOrAttrName, $contextNode);
        if ($childValue) {
            return $childValue;
        }

        $attrNameWithoutNamespace = preg_replace('/^.*:/', '', $nodeOrAttrName);
        $contextNodeAttrs = $contextNode->attributes;
        if ($contextNodeAttrs) {
            $contextNodeAttr = $contextNodeAttrs->getNamedItem($attrNameWithoutNamespace);
            if ($contextNodeAttr) {
                return $contextNodeAttr->nodeValue;
            }
        }

        $childNodeWithAttr = $xpath->query("*[@$nodeOrAttrName]", $contextNode)->item(0);
        if (!$childNodeWithAttr) {
            return null;
        }

        $childNodeAttrs = $childNodeWithAttr->attributes;
        if (!$childNodeAttrs) {
            return null;
        }

        $childNodeAttr = $childNodeAttrs->getNamedItem($attrNameWithoutNamespace);
        return $childNodeAttr->nodeValue;
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
