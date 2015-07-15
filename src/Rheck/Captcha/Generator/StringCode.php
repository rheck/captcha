<?php

namespace Rheck\Captcha\Generator;

class StringCode
{

    /**
     * Constraint of Charset.
     */
    const CHARSET = 'ABCDEFGHKLMNPRSTUVWYZabcdefghklmnprstuvwyz23456789';

    /**
     * Static method to generate and return a new code.
     *
     * @param int $length
     * @return array
     */
    static public function generate($length = 6)
    {
        $code = '';

        $charset = self::CHARSET;

        $csLen = strlen($charset);

        for ($i = 1; $i <= $length; $i++) {
            $code .= $charset{rand(0, $csLen - 1)};
        }

        return array(
            'code'        => $code,
            'codeDisplay' => $code
        );
    }

}
