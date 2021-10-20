<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace MiniCaptcha\Rules;

use Mini\Contracts\Container\BindingResolutionException;
use Mini\Validator\Rule;

class CaptchaApi extends Rule
{

    /** @var string */
    protected string $message = "The :attribute is wrong";

    /** @var array */
    protected array $fillableParams = ['key', 'config'];

    /**
     * Check the $value is valid
     *
     * @param mixed $value
     * @return bool
     * @throws BindingResolutionException
     */
    public function check($value): bool
    {
        if (!$config = $this->parameter('config')) {
            $config = 'default';
        }
        return captcha_api_check($value, $this->parameter('key'), $config);
    }
}
