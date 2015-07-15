<?php

namespace Rheck\Captcha\Color;

use Rheck\Captcha\Exception\InvalidHexadecimalException;

/**
 * Class CaptchaColor
 * @package Conrad\CaptchaBundle\Classes
 */
class CaptchaColor
{

    /**
     * Constraint for the default color in hexadecimal.
     */
    const DEFAULT_HEX_COLOR = '#FFFFFF';

    /**
     * @var integer
     */
    protected $red;

    /**
     * @var integer
     */
    protected $green;

    /**
     * @var integer
     */
    protected $blue;

    /**
     * Method to set the color from hexadecimal (#FFFFFF).
     *
     * @param string $color
     * @return CaptchaColor
     */
    public function setHexadecimal($color = self::DEFAULT_HEX_COLOR)
    {
        $color = $this->checkHexadecimal($color);

        list($red, $green, $blue) = $this->buildHexToRgb($color);

        $this->red   = $red;
        $this->green = $green;
        $this->blue  = $blue;

        return $this;
    }

    /**
     * Method to set the color from RGB format (R=0,G=128,B=255).
     *
     * @param integer $red
     * @param integer $green
     * @param integer $blue
     * @return CaptchaColor
     */
    public function setRGB($red, $green, $blue)
    {
        $red   = $this->normalizeRange($red);
        $green = $this->normalizeRange($green);
        $blue  = $this->normalizeRange($blue);

        $this->red   = $red;
        $this->green = $green;
        $this->blue  = $blue;

        return $this;
    }

    /**
     * Method to normalize the range of decimal color.
     *
     * @param integer $decColor
     * @return integer
     */
    protected function normalizeRange($decColor)
    {
        if ($decColor < 0) {
            return 0;
        }

        if ($decColor > 255) {
            return 255;
        }

        return $decColor;
    }

    /**
     * Method to build hexadecimal to rgb array.
     *
     * @param string $hexColor
     * @return array
     */
    public function buildHexToRgb($hexColor)
    {
        if (strlen($hexColor) == 3) {
            return array(
                hexdec(str_repeat(substr($hexColor, 0, 1), 2)),
                hexdec(str_repeat(substr($hexColor, 1, 1), 2)),
                hexdec(str_repeat(substr($hexColor, 2, 1), 2))
            );
        }

        return array(
            hexdec(substr($hexColor, 0, 2)),
            hexdec(substr($hexColor, 2, 2)),
            hexdec(substr($hexColor, 4, 2))
        );
    }

    /**
     * Method to check the sent hexadecimal color format.
     *
     * @param string $hexColor
     * @return string
     * @throws InvalidHexadecimalException
     */
    protected function checkHexadecimal($hexColor)
    {
        preg_match('/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/', $hexColor, $matches);

        if (!count($matches)) {
            throw new InvalidHexadecimalException(sprintf('Sent hexadecimal color is not valid: [%s]', $hexColor));
        }

        return array_pop($matches);
    }

    /**
     * @return int
     */
    public function getRed()
    {
        return $this->red;
    }

    /**
     * @return int
     */
    public function getGreen()
    {
        return $this->green;
    }

    /**
     * @return int
     */
    public function getBlue()
    {
        return $this->blue;
    }

}
