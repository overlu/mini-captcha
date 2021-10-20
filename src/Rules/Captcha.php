<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace MiniCaptcha\Rules;

use Mini\Contracts\Container\BindingResolutionException;
use Mini\Validator\Rule;

class Captcha extends Rule
{

    /** @var string */
    protected string $message = "The :attribute is wrong";

    /**
     * Check the $value is valid
     *
     * @param mixed $value
     * @return bool
     * @throws BindingResolutionException
     */
    public function check($value): bool
    {
        return captcha_check($value);
    }
}
