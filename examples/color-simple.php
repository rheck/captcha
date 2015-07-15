<?php

require_once('../vendor/autoload.php');

use Rheck\Captcha\Color\CaptchaColor;

$color = new CaptchaColor();

$color->setHexadecimal('#FFF');

$color2 = new CaptchaColor();

$color2->setRGB(255, 255, 255);

var_dump($color);
var_dump($color2);
