<?php
namespace CSD\Image\Metadata\Xmp;

use CSD\Image\Metadata\UnsupportedException;

/**
 * PHP5 enum.
 */
abstract class ShapeFilter
{
    const ANY = '';
    const RECTANGLE = 'rectangle';
    const CIRCLE = 'circle';
    const POLYGON = 'polygon';

    /**
     * @param string $value RECTANGLE, CIRCLE or POLYGON.
     *
     * @return string The corresponding value of the <Iptc4xmpExt:rbShape>
     *                element.
     * @throws UnsupportedException
     */
    public static function getXmlRbShape($value)
    {
        switch ($value) {
            case self::RECTANGLE:
            case self::CIRCLE:
            case self::POLYGON:
                return $value;
        }
        throw new UnsupportedException('Unsupported region shape');
    }
}
