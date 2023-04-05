<?php

namespace CSD\Image;

use CSD\Image\Format\JPEG;
use CSD\Image\Format\PNG;
use CSD\Image\Format\WebP;
use CSD\Image\Metadata\Aggregate;
use CSD\Image\Metadata\Xmp\RoleFilter;
use CSD\Image\Metadata\Xmp\ShapeFilter;
use CSD\Image\Metadata\UnsupportedException;

/**
 * @author Daniel Chesterton <daniel@chestertondevelopment.com>
 */
abstract class Image implements ImageInterface
{
    /**
     * Image width in pixels.
     *
     * @var integer
     */
    protected $width;

    /**
     * Image height in pixels.
     *
     * @var integer
     */
    protected $height;

    /**
     * @return Aggregate
     */
    public function getAggregate()
    {
        try {
            $xmp = $this->getXmp();
        } catch (UnsupportedException $e) {
            $xmp = null;
        }

        try {
            $exif = $this->getExif();
        } catch (UnsupportedException $e) {
            $exif = null;
        }

        try {
            $iptc = $this->getIptc();
        } catch (UnsupportedException $e) {
            $iptc = null;
        }

        return new Aggregate($xmp, $iptc, $exif);
    }

    /**
     * @return array Array of integer values with the following keys: `width`
     *               and `height`.
     */
    public function getSize()
    {
        return [
            'width' => $this->width,
            'height' => $this->height,
        ];
    }

    /**
     * Helper method returning the result of Xmp::getImagesRegions() together
     * with additional information about the image, in a format close to what is
     * expected by the Image Display Control web component:
     * https://github.com/Frameright/image-display-control-web-component/blob/main/image-display-control/docs/reference/attributes.md#data-image-regions
     *
     * @param string $shapeFilter Can be used to retrieve only regions of a
     *                            specific shape, e.g. ShapeFilter::RECTANGLE
     * @param string $roleFilter Can be used to retrieve only regions with a
     *                           specific role, e.g. RoleFilter::CROP
     * @param string $essentialOnly If true, only essential region properties
     *                              will be returned, e.g. properties like
     *                             `types` and `roles` will be skipped.
     *
     * @return array
     */
    public function getIDCMetadata(
        $shapeFilter = ShapeFilter::ANY,
        $roleFilter = RoleFilter::ANY,
        $essentialOnly = true)
    {
        $idc_metadata = [];
        $regions = $this->getXmp()->getImageRegions($shapeFilter, $roleFilter);
        foreach ($regions as $region) {
            $idc_metadata_region = [
                'id' => $region->id,
                'names' => $region->names,
                'shape' => $region->rbShape,

                'types' => $region->types,
                'roles' => $region->roles,

                // Can be 'relative' or 'pixel', see
                // https://iptc.org/std/photometadata/specification/IPTC-PhotoMetadata#boundary-measuring-unit
                'unit' => $region->rbUnit,

                // Useful when unit is 'pixel', see
                // https://github.com/Frameright/image-display-control-web-component/blob/main/image-display-control/docs/reference/attributes.md
                'imageWidth' => $this->width,
                'imageHeight' => $this->height,

                'x' => $region->rbXY->rbX,
                'y' => $region->rbXY->rbY,

                'width' => $region->rbW,
                'height' => $region->rbH,

                'radius' => $region->rbRx,

                'vertices' => [],
            ];

            if ($region->rbVertices) {
                foreach ($region->rbVertices as $vertice) {
                    array_push($idc_metadata_region['vertices'], [
                        'x' => $vertice->rbX,
                        'y' => $vertice->rbY,
                    ]);
                }
            }

            if ($essentialOnly) {
                unset($idc_metadata_region['types']);
                unset($idc_metadata_region['roles']);

                if ($idc_metadata_region['unit'] === 'relative') {
                    unset($idc_metadata_region['imageWidth']);
                    unset($idc_metadata_region['imageHeight']);
                }

                switch($idc_metadata_region['shape']) {
                    case ShapeFilter::RECTANGLE:
                        unset($idc_metadata_region['radius']);
                        unset($idc_metadata_region['vertices']);
                        break;
                    case ShapeFilter::CIRCLE:
                        unset($idc_metadata_region['width']);
                        unset($idc_metadata_region['height']);
                        unset($idc_metadata_region['vertices']);
                        break;
                    case ShapeFilter::POLYGON:
                        unset($idc_metadata_region['x']);
                        unset($idc_metadata_region['y']);
                        unset($idc_metadata_region['width']);
                        unset($idc_metadata_region['height']);
                        unset($idc_metadata_region['radius']);
                        break;
                }
            }

            array_push($idc_metadata, $idc_metadata_region);
        }
        return $idc_metadata;
    }

    /**
     * @param string $fileName
     *
     * @throws \Exception
     * @return ImageInterface
     *
     * @todo add more sophisticated checks by inspecting file
     */
    public static function fromFile($fileName)
    {
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $result = null;
        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                $result = Format\JPEG::fromFile($fileName);
                break;
            case 'png':
                $result = Format\PNG::fromFile($fileName);
                break;
            case 'webp':
                $result = Format\WebP::fromFile($fileName);
                break;
            case 'psd':
                $result = Format\PSD::fromFile($fileName);
                break;
        }
        if (!$result) {
            throw new \Exception('Unrecognised file name');
        }

        $size = getimagesize($fileName);
        $result->width = $size[0];
        $result->height = $size[1];
        return $result;
    }

    /**
     * @param $string
     *
     * @return JPEG|WebP|PNG|false
     */
    public static function fromString($string)
    {
        $len = strlen($string);

        // try JPEG
        if ($len >= 2) {
            if (JPEG::SOI === substr($string, 0, 2)) {
                return JPEG::fromString($string);
            }
        }

        // try WebP
        if ($len >= 4) {
            if ('RIFF' === substr($string, 0, 4) && 'WEBP' === substr($string, 8, 4)) {
                return WebP::fromString($string);
            }
        }

        // try PNG
        if ($len >= 8) {
            if (PNG::SIGNATURE === substr($string, 0, 8)) {
                return PNG::fromString($string);
            }
        }

        return false;
    }
}
