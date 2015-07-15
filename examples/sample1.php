<?php

require_once('../vendor/autoload.php');

use Rheck\Captcha\Color\CaptchaColor;
use Rheck\Captcha\Captcha;

$color = new CaptchaColor();
$color->setHexadecimal('#FFF');

$captcha = new Captcha();

$captcha->setImageUid('12345');
$captcha->setImageWidth(150);
$captcha->setImageHeight(60);


$textColor = new CaptchaColor();
$textColor->setHexadecimal('#53C7F2');
$captcha->setTextColor($textColor);

$captcha->setNumLines(2);

$captcha->generate();

$captcha->output();

