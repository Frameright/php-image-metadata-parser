<?php
namespace CSD\Image\Metadata;

/**
 * @author Daniel Chesterton <daniel@chestertondevelopment.com>
 */
class Aggregate
{
    private $fields = [
        'headline' => ['xmp', 'iptc'],
        'caption' => ['xmp', 'iptc'],
        'location' => ['xmp', 'iptc'],
        'city' => ['xmp', 'iptc'],
        'state' => ['xmp', 'iptc'],
        'country' => ['xmp', 'iptc'],
        'countryCode' => ['xmp', 'iptc'],
        'photographerName' => ['xmp', 'iptc'],
        'credit' => ['xmp', 'iptc'],
        'photographerTitle' => ['xmp', 'iptc'],
        'source' => ['xmp', 'iptc'],
        'copyright' => ['xmp', 'iptc'],
        'objectName' => ['xmp', 'iptc'],
        'captionWriters' => ['xmp', 'iptc'],
        'instructions' => ['xmp', 'iptc'],
        'category' => ['xmp', 'iptc'],
        'supplementalCategories' => ['xmp', 'iptc'],
        'transmissionReference' => ['xmp', 'iptc'],
        'urgency' => ['xmp', 'iptc'],
        'keywords' => ['xmp', 'iptc'],
        'dateCreated' => ['xmp', 'iptc']
    ];

    /**
     * @var Xmp
     */
    private $xmp;

    /**
     * @var Iptc
     */
    private $iptc;

    /**
     * @var Exif
     */
    private $exif;

    /**
     * @var array
     */
    private $priority;

    /**
     * Constructor
     *
     * @param Xmp  $xmp
     * @param Iptc $iptc
     * @param Exif $exif
     */
    public function __construct(Xmp $xmp = null, Iptc $iptc = null, Exif $exif = null)
    {
        $this->xmp = $xmp;
        $this->iptc = $iptc;
        $this->exif = $exif;
        $this->priority = ['xmp', 'iptc', 'exif'];
    }

    /**
     * @param array $priority
     *
     * @return $this
     * @throws \Exception
     */
    public function setPriority(array $priority)
    {
        foreach ($priority as $metaType) {
            if (!in_array($metaType, ['xmp', 'iptc', 'exif'], true)) {
                throw new \Exception('Priority can only contain xmp, iptc or exif');
            }
        }

        $this->priority = $priority;
        return $this;
    }

    /**
     * @param string $field
     *
     * @return string|null
     */
    private function get($field)
    {
        foreach ($this->priority as $metaType) {
            // check if this meta type is supported for this field
            if (!in_array($metaType, $this->fields[$field], true)) {
                continue;
            }

            $metaObject = $this->$metaType;

            if (!$metaObject) {
                continue;
            }

            $getter = 'get' . ucfirst($field);
            $value = $metaObject->$getter();

            if ($value) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function getHeadline()
    {
        return $this->get('headline');
    }

    /**
     * @return string|null
     */
    public function getCaption()
    {
        return $this->get('caption');
    }

    /**
     * @return string|null
     */
    public function getLocation()
    {
        return $this->get('location');
    }

    /**
     * @return string|null
     */
    public function getCity()
    {
        return $this->get('city');
    }

    /**
     * @return string|null
     */
    public function getState()
    {
        return $this->get('state');
    }

    /**
     * @return string|null
     */
    public function getCountry()
    {
        return $this->get('country');
    }

    /**
     * @return string|null
     */
    public function getCountryCode()
    {
        return $this->get('countryCode');
    }

    /**
     * @return string|null
     */
    public function getPhotographerName()
    {
        return $this->get('photographerName');
    }

    /**
     * @return string|null
     */
    public function getCredit()
    {
        return $this->get('credit');
    }

    /**
     * @return string|null
     */
    public function getPhotographerTitle()
    {
        return $this->get('photographerTitle');
    }

    /**
     * @return string|null
     */
    public function getSource()
    {
        return $this->get('source');
    }

    /**
     * @return string|null
     */
    public function getCopyright()
    {
        return $this->get('copyright');
    }

    /**
     * @return string|null
     */
    public function getObjectName()
    {
        return $this->get('objectName');
    }

    /**
     * @return string|null
     */
    public function getCaptionWriters()
    {
        return $this->get('captionWriters');
    }

    /**
     * @return string|null
     */
    public function getInstructions()
    {
        return $this->get('instructions');
    }

    /**
     * @return string|null
     */
    public function getCategory()
    {
        return $this->get('category');
    }

    /**
     * @return string|null
     */
    public function getSupplementalCategories()
    {
        return $this->get('supplementalCategories');
    }

    /**
     * @return string|null
     */
    public function getTransmissionReference()
    {
        return $this->get('transmissionReference');
    }

    /**
     * @return string|null
     */
    public function getUrgency()
    {
        return $this->get('urgency');
    }

    /**
     * @return array|null
     */
    public function getKeywords()
    {
        return $this->get('keywords');
    }

    /**
     * @return \DateTime|null
     */
    public function getDateCreated()
    {
        return $this->get('dateCreated');
    }
}
