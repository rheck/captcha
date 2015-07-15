<?php

namespace Rheck\Captcha;

use Rheck\Captcha\Color\CaptchaColor;
use Rheck\Captcha\Exception\FontFileNotFoundException;
use Rheck\Captcha\Exception\InvalidImageTypeException;
use Rheck\Captcha\Generator\MathCode;
use Rheck\Captcha\Generator\StringCode;

/**
 * Class Captcha
 * @package Rheck\Captcha
 */
class Captcha
{
    /**
     * Constraint for the assets path.
     */
    const ASSETS_PATH = '/Resources/assets/';

    /**
     * Constraint for math type.
     */
    const TYPE_MATH   = 1;

    /**
     * Constraint for string type.
     */
    const TYPE_STRING = 2;

    /**
     * Constraint for image jpeg type.
     */
    const IMAGE_JPEG = 1;

    /**
     * Constraint for image png type.
     */
    const IMAGE_PNG = 2;

    /**
     * Constraint for image gif type.
     */
    const IMAGE_GIF = 3;

    /**
     * @var string
     */
    protected $imageUid;

    /**
     * @var int
     */
    protected $imageWidth;

    /**
     * @var int
     */
    protected $imageHeight;

    /**
     * @var int
     */
    protected $scale = 5;

    /**
     * @var int
     */
    protected $numLines = 2;

    /**
     * @var bool
     */
    protected $caseSensitive = false;

    /**
     * @var CaptchaColor
     */
    protected $imageBgColor;

    /**
     * @var CaptchaColor
     */
    protected $textColor;

    /**
     * @var CaptchaColor
     */
    protected $lineColor;

    /**
     * @var CaptchaColor
     */
    protected $noiseColor;

    /**
     * @var CaptchaColor
     */
    protected $signatureColor;

    /**
     * @var int
     */
    protected $noiseLevel;

    /**
     * @var string
     */
    protected $ttfFile;

    /**
     * @var string
     */
    protected $signatureFont;

    /**
     * @var string
     */
    protected $wordsListFile;

    /**
     * @var string
     */
    protected $audioPath;

    /**
     * @var int
     */
    protected $codeLength;

    /**
     * @var float
     */
    protected $perturbation;

    /**
     * @var
     */
    protected $gdBgColor;

    /**
     * @var
     */
    protected $gdTextColor;

    /**
     * @var
     */
    protected $gdLineColor;

    /**
     * @var
     */
    protected $gdNoiseColor;

    /**
     * @var
     */
    protected $gdSignatureColor;

    /**
     * @var string
     */
    protected $imageSignature;

    /**
     * @var
     */
    protected $im;

    /**
     * @var
     */
    protected $tmpImg;

    /**
     * @var string
     */
    protected $background;

    /**
     * @var
     */
    protected $imageMime;

    /**
     * @var int
     */
    protected $codeType;

    /**
     * @var string
     */
    protected $code;

    /**
     * @var string
     */
    protected $codeDisplay;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->normalizeColors();

        $this->ttfFile = $this->assetPath('AHGBold.ttf');
        $this->signatureFont = $this->ttfFile;

        $this->wordsListFile = $this->assetPath('words/words.txt');
        $this->audioPath = $this->assetPath('audio/');
        $this->codeLength = 6;
        $this->perturbation = 0.75;
        $this->noiseLevel = 2;
        $this->imageMime = self::IMAGE_PNG;
        $this->codeType = self::TYPE_STRING;
    }

    /**
     * Method to generate the captcha.
     *
     * @throws FontFileNotFoundException
     */
    public function generate()
    {
        $this->im = imagecreatetruecolor($this->imageWidth, $this->imageHeight);
        $this->tmpImg = imagecreatetruecolor($this->imageWidth * $this->scale, $this->imageHeight * $this->scale);

        $this->allocateColors();

        imagepalettecopy($this->tmpImg, $this->im);

        $this->setBackground();
        $this->createCode();

        if ($this->noiseLevel) {
            $this->drawNoise();
        }

        $this->drawWord();

        if ($this->perturbation) {
            $this->distortedCopy();
        }

        if ($this->numLines) {
            $this->drawLines();
        }

        if (strlen($this->imageSignature)) {
            $this->drawSignature();
        }
    }

    /**
     * Method to output the captcha image.
     */
    public function output()
    {
        header(sprintf('Last-Modified: %sGMT', gmdate('D, d M Y H:i:s')));
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-chech=0, pre-check=0', false);
        header('Pragma: no-cache');

        switch ($this->imageMime) {
            case self::IMAGE_JPEG:
                header('Content-Type: image/jpeg');
                imagejpeg($this->im, null, 90);
                break;

            case self::IMAGE_PNG:
                header('Content-Type: image/png');
                imagepng($this->im);
                break;

            case self::IMAGE_GIF:
                header('Content-Type: image/gif');
                imagegif($this->im);
                break;

            default:
                throw new InvalidImageTypeException(sprintf('Mime type is invalid: [%s]', $this->imageMime));
                break;
        }

        imagedestroy($this->im);
        exit;
    }

    /**
     * Method to check the stored code with the entered one.
     *
     * @param string $stored
     * @param string $entered
     * @return bool
     */
    public function checkCode($stored, $entered)
    {
        if (!$this->caseSensitive) {
            $stored  = strtolower($stored);
            $entered = strtolower($entered);
        }

        return $stored === $entered;
    }

    /**
     * Method to draw the word on the captcha image.
     */
    protected function drawWord()
    {
        if (!is_readable($this->ttfFile)) {
            throw new FontFileNotFoundException('Failed to load TTF font file!');
        }

        if ($this->perturbation > 0) {
            $width  = $this->imageWidth * $this->scale;
            $height = $this->imageHeight * $this->scale;

            $fontSize = $height * 0.4;

            $bBox = imageftbbox($fontSize, 0, $this->ttfFile, $this->codeDisplay);

            $tx = $bBox[4] - $bBox[0];
            $ty = $bBox[5] - $bBox[1];

            $x = floor($width / 2 - $tx / 2 - $bBox[0]);
            $y = round($height / 2 - $ty / 2 - $bBox[1]);

            imagettftext($this->tmpImg, $fontSize, 0, $x, $y, $this->gdTextColor, $this->ttfFile, $this->codeDisplay);

            return;
        }

        $fontSize = $this->imageHeight * 0.4;

        $bBox = imageftbbox($fontSize, 0, $this->ttfFile, $this->codeDisplay);

        $tx = $bBox[4] - $bBox[0];
        $ty = $bBox[5] - $bBox[1];

        $x = floor($this->imageWidth / 2 - $tx / 2 - $bBox[0]);
        $y = round($this->imageHeight / 2 - $ty / 2 - $bBox[1]);

        imagettftext($this->im, $fontSize, 0, $x, $y, $this->gdTextColor, $this->ttfFile, $this->codeDisplay);
    }

    /**
     * Method to draw lines to the image.
     */
    protected function drawLines()
    {
        for ($line = 0; $line < $this->numLines; ++$line) {
            $x  = $this->imageWidth * (1 + $line) / ($this->numLines + 1);
            $x += (0.5 - $this->frand()) * $this->imageWidth / $this->numLines;
            $y  = rand($this->imageHeight * 0.1, $this->imageHeight * 0.9);

            $theta = ($this->frand() - 0.5) * M_PI * 0.7;
            $w     = $this->imageWidth;
            $len   = rand($w * 0.4, $w * 0.7);
            $lwid  = rand(0, 2);

            $k = $this->frand() * 0.6 * 0.2;
            $k = $k * $k * 0.5;

            $phi  = $this->frand() * 6.28;
            $step = 0.5;

            $dx = $step * cos($theta);
            $dy = $step * sin($theta);

            $n = $len / $step;

            $amp = 1.5 * $this->frand() / ($k + 5.0 / $len);

            $x0 = $x - 0.5 * $len * cos($theta);
            $y0 = $y - 0.5 * $len * sin($theta);

            for ($i = 0; $i < $n; ++$i) {
                $x = $x0 + $i * $dx + $amp * $dy * sin($k * $i * $step + $phi);
                $y = $y0 + $i * $dy - $amp * $dx * sin($k * $i * $step + $phi);

                imagefilledrectangle($this->im, $x, $y, $x + $lwid, $y + $lwid, $this->gdLineColor);
            }
        }
    }

    /**
     * Method to create the code of captcha.
     */
    protected function createCode()
    {
        $this->code = false;

        $generated = StringCode::generate($this->codeLength);
        if ($this->codeType == self::TYPE_MATH) {
            $generated = MathCode::generate();
        }

        $this->code = $generated['code'];
        $this->codeDisplay = $generated['codeDisplay'];
    }

    /**
     * Method to draw the noise on the image.
     */
    protected function drawNoise()
    {
        $this->noiseLevel = $this->noiseLevel < 10 ? $this->noiseLevel : 10;

        $noiseLevel = $this->noiseLevel * 125;

        $width  = $this->imageWidth * $this->scale;
        $height = $this->imageHeight * $this->scale;

        for ($i = 0; $i < $noiseLevel; ++$i) {
            $x = rand(10, $width);
            $y = rand(10, $height);
            $s = rand(7, 10);

            if ($x - $s <= 0 && $y - $s <= 0) {
                continue;
            }

            imagefilledarc($this->tmpImg, $x, $y, $s, $s, 0, 360, $this->gdNoiseColor, IMG_ARC_PIE);
        }
    }

    /**
     * Method to draw the signature on the captcha image.
     */
    protected function drawSignature()
    {
        $bBox = imagettfbbox(10, 0, $this->signatureFont, $this->imageSignature);
        $textLen = $bBox[2] - $bBox[0];

        $x = $this->imageWidth - $textLen - 5;
        $y = $this->imageHeight - 3;

        imagettftext($this->im, 10, 0, $x, $y, $this->gdSignatureColor, $this->signatureFont, $this->imageSignature);
    }

    /**
     * Method to set the background on the captcha image.
     */
    protected function setBackground()
    {
        imagefilledrectangle($this->im, 0, 0, $this->imageWidth, $this->imageHeight, $this->gdBgColor);
        imagefilledrectangle($this->tmpImg, 0, 0, $this->imageWidth * $this->scale, $this->imageHeight * $this->scale, $this->gdBgColor);

        if (is_null($this->background)) {
            return;
        }


        if (false == ($dat = getimagesize($this->background))) {
            return;
        }

        switch ($dat[2]) {
            case 1:
                $newImage = imagecreatefromgif($this->background);
                break;
            case 2:
                $newImage = imagecreatefromjpeg($this->background);
                break;
            case 3:
                $newImage = imagecreatefrompng($this->background);
                break;
            default:
                return;
        }

        if (!$newImage) {
            return;
        }

        imagecopyresized($this->im, $newImage, 0, 0, 0, 0, $this->imageWidth, $this->imageHeight, imagesx($newImage), imagesy($newImage));
    }

    /**
     * Method to allocate the colors to be used on the image.
     */
    protected function allocateColors()
    {
        $this->gdBgColor = imagecolorallocate(
            $this->im,
            $this->imageBgColor->getRed(),
            $this->imageBgColor->getGreen(),
            $this->imageBgColor->getBlue());

        $alpha = intval(50 / 100 * 127);

        $this->gdTextColor = imagecolorallocatealpha(
            $this->im,
            $this->textColor->getRed(),
            $this->textColor->getGreen(),
            $this->textColor->getBlue(),
            $alpha);

        $this->gdLineColor = imagecolorallocatealpha(
            $this->im,
            $this->lineColor->getRed(),
            $this->lineColor->getGreen(),
            $this->lineColor->getBlue(),
            $alpha);

        $this->gdNoiseColor = imagecolorallocatealpha(
            $this->im,
            $this->noiseColor->getRed(),
            $this->noiseColor->getGreen(),
            $this->noiseColor->getBlue(),
            $alpha);

        $this->gdSignatureColor = imagecolorallocate(
            $this->im,
            $this->signatureColor->getRed(),
            $this->signatureColor->getGreen(),
            $this->signatureColor->getBlue());
    }

    /**
     * Method to return the right path for asset.
     *
     * @param string $fileName
     * @return string
     */
    protected function assetPath($fileName)
    {
        return __DIR__ . self::ASSETS_PATH . $fileName;
    }

    /**
     * Method to set the default colors for not configured colors.
     */
    protected function normalizeColors()
    {
        if (is_null($this->imageBgColor)) {
            $this->imageBgColor = new CaptchaColor();

            $this->imageBgColor->setHexadecimal('#FFFFFF');
        }

        if (is_null($this->textColor)) {
            $this->textColor = new CaptchaColor();

            $this->textColor->setHexadecimal('#616161');
        }

        if (is_null($this->lineColor)) {
            $this->lineColor = new CaptchaColor();

            $this->lineColor->setHexadecimal('#616161');
        }

        if (is_null($this->noiseColor)) {
            $this->noiseColor = new CaptchaColor();

            $this->noiseColor->setHexadecimal('#616161');
        }

        if (is_null($this->signatureColor)) {
            $this->signatureColor = new CaptchaColor();

            $this->signatureColor->setHexadecimal('#616161');
        }
    }

    /**
     * Method to copy the distortion for image.
     */
    protected function distortedCopy()
    {
        $numPoles = 3;

        $px  = array();
        $py  = array();
        $rad = array();
        $amp = array();

        for ($i = 0; $i < $numPoles; ++$i) {
            $px[$i]  = rand($this->imageWidth * 0.2, $this->imageWidth * 0.8);
            $py[$i]  = rand($this->imageHeight * 0.2, $this->imageHeight * 0.8);
            $rad[$i] = rand($this->imageHeight * 0.2, $this->imageHeight * 0.8);
            $amp[$i] = $this->perturbation * (((- $this->frand()) * 0.15) - 0.15);
        }

        $bgCol  = imagecolorat($this->tmpImg, 0, 0);
        $width  = $this->scale * $this->imageWidth;
        $height = $this->scale * $this->imageHeight;
        imagepalettecopy($this->im, $this->tmpImg);

        for ($ix = 0; $ix < $this->imageWidth; ++$ix) {
            for ($iy = 0; $iy < $this->imageHeight; ++$iy) {

                $x = $ix;
                $y = $iy;

                for ($i = 0; $i < $numPoles; ++$i) {
                    $dx = $ix - $px[$i];
                    $dy = $iy - $py[$i];

                    if ($dx == 0 && $dy ==0) {
                        continue;
                    }

                    $r = sqrt($dx * $dx * $dy * $dy);
                    if ($r > $rad[$i]) {
                        continue;
                    }

                    $rScale = $amp[$i] * sin(3.14 * $r / $rad[$i]);

                    $x += $dx * $rScale;
                    $y += $dy * $rScale;
                }

                $c  = $bgCol;
                $x *= $this->scale;
                $y *= $this->scale;

                if ($x >= 0 && $x < $width && $y >= 0 && $y < $height) {
                    $c = imagecolorat($this->tmpImg, $x, $y);
                }

                if ($c != $bgCol) {
                    imagesetpixel($this->im, $ix, $iy, $c);
                }
            }
        }
    }

    /**
     * Method to return a random float number.
     *
     * @return float
     */
    protected function frand()
    {
        return 0.0001 * rand(0,9999);
    }

    /**
     * Method to change the variables values.
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        preg_match('/^(set)(.*)|(get)(.*)$/', $name, $matches);

        $name = lcfirst(array_pop($matches));
        $method = array_pop($matches);

        if (property_exists($this, $name)) {
            if ($method == 'get') {
                return $this->{$name};
            }

            $this->{$name} = array_pop($arguments);
        }

    }


}
