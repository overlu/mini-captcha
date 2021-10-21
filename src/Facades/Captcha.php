<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace MiniCaptcha\Facades;

use Mini\Facades\Facade;

/**
 * Class Captcha
 * @method static mixed|array create(string $ck = '', string $config = 'default', bool $api = false)
 * @method static bool check(string $value, string $ck = '')
 * @method static bool check_api(string $value, string $key, string $ck = '', string $config = 'default')
 * @method static string src(string $ck = '', string $config = 'default')
 * @method static string getCk(string $ck = '')
 * @method static \Mini\Support\HtmlString img(string $ck = '', string $config = 'default', array $attrs = [])
 *
 * @see \MiniCaptcha\Captcha
 */
class Captcha extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'captcha';
    }
}
