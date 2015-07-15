<?php

namespace Rheck\Captcha\Generator;

class MathCode
{

    /**
     * Constraint for plus operation.
     */
    const SIGN_PLUS = 0;

    /**
     * Constraint for minus operation.
     */
    const SIGN_MINUS = 1;

    /**
     * Constraint for multiple operation.
     */
    const SIGN_MULTIPLE = 2;

    /**
     * Static method to generate and return a new code.
     *
     * @return array
     */
    static public function generate()
    {
        $first  = rand(1, 10);
        $second = rand(1, 5);

        $sign   = rand(0, 2);

        $code = false;
        $codeDisplay = '';

        switch ($sign) {
            case self::SIGN_PLUS:
                $code = $first + $second;
                $codeDisplay = sprintf('%d + %d', $first, $second);
                break;
            case self::SIGN_MINUS:
                $code = $first - $second;
                $codeDisplay = sprintf('%d - %d', $first, $second);
                break;
            case self::SIGN_MULTIPLE:
                $code = $first * $second;
                $codeDisplay = sprintf('%d * %d', $first, $second);
                break;
        }

        return array(
            'code'        => $code,
            'codeDisplay' => $codeDisplay
        );
    }

}
