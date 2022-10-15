<?php
namespace CSD\Image\Metadata\Xmp;

/**
 * (X, Y) pair of coordinates.
 */
class Point
{
    /**
     * @var string|null
     */
    public $rbX;

    /**
     * @var string|null
     */
    public $rbY;

    public function __construct($x, $y)
    {
        $this->rbX = $x;
        $this->rbY = $y;
    }
}
