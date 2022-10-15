<?php
namespace CSD\Image\Metadata\Xmp;

use CSD\Image\Metadata\UnsupportedException;

/**
 * PHP5 enum.
 */
abstract class RoleFilter
{
    const ANY = '';
    const CROP = 'crop';

    /**
     * @param string $value CROP.
     *
     * @return array The list of <Iptc4xmpExt:rRole><Iptc4xmpExt:Name> or
     *               <Iptc4xmpExt:rRole><Iptc4xmpExt:Identifier> values
     *               that would match the filter.
     * @throws UnsupportedException
     */
    public static function getMatchingXmlRoles($value)
    {
        switch ($value) {
            case self::CROP:
                // See https://cv.iptc.org/newscodes/imageregionrole/
                return [
                    'cropping',
                    'recommended cropping',
                    'landscape format cropping',
                    'portrait format cropping',
                    'square format cropping',
                    'http://cv.iptc.org/newscodes/imageregionrole/cropping',
                    'http://cv.iptc.org/newscodes/imageregionrole/recomCropping',
                    'http://cv.iptc.org/newscodes/imageregionrole/landscapeCropping',
                    'http://cv.iptc.org/newscodes/imageregionrole/portraitCropping',
                    'http://cv.iptc.org/newscodes/imageregionrole/squareCropping',
                ];
        }
        throw new UnsupportedException('Unsupported region role filter');
    }
}
