<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

use Intervention\Image\ImageManager;
use Mini\Contracts\Container\BindingResolutionException;
use Mini\Support\HtmlString;

if (!function_exists('captcha')) {
    /**
     * @param string $ck
     * @param string $config
     * @param bool $base64
     * @return mixed
     * @throws BindingResolutionException
     */
    function captcha(string $ck = '', string $config = 'default', bool $base64 = false)
    {
        return app('captcha')->create($ck, $config, $base64);
    }
}

if (!function_exists('captcha_src')) {
    /**
     * @param string $ck
     * @param string $config
     * @return string
     * @throws BindingResolutionException
     */
    function captcha_src(string $ck = '', string $config = 'default'): string
    {
        return app('captcha')->src($ck, $config);
    }
}

if (!function_exists('captcha_img')) {

    /**
     * @param string $ck
     * @param string $config
     * @param array $attrs
     * @return HtmlString
     * @throws BindingResolutionException
     */
    function captcha_img(string $ck = '', string $config = 'default', array $attrs = []): HtmlString
    {
        return app('captcha')->img($ck, $config, $attrs);
    }
}

if (!function_exists('captcha_check')) {
    /**
     * @param string $value
     * @param string $ck
     * @param bool $removeSession
     * @return bool
     * @throws BindingResolutionException
     */
    function captcha_check(string $value, string $ck = '', bool $removeSession = true): bool
    {
        return app('captcha')->check($value, $ck, $removeSession);
    }
}
