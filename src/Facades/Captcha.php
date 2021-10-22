<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace MiniCaptcha\Facades;

use Mini\Facades\Facade;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Captcha
 * @method static string|ResponseInterface create(string $ck = '', string $config = 'default', bool $base64 = false)
 * @method static bool check(string $value, string $ck = '', bool $removeSession = true)
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
