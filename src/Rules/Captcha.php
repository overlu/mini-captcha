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

    /** @var array */
    protected array $fillableParams = ['ck', 'removeSession'];

    /**
     * Check the $value is valid
     *
     * @param mixed $value
     * @return bool
     * @throws BindingResolutionException
     */
    public function check($value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        $acceptable = ['yes', 'on', '1', 1, true, 'true'];
        $removeSession = $this->parameter('removeSession', true);
        return captcha_check($value, $this->parameter('ck', ''), in_array($removeSession, $acceptable, true));
    }
}
