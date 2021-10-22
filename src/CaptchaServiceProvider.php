<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace MiniCaptcha;

use Mini\Contracts\Container\BindingResolutionException;
use Mini\Facades\Validator;
use Mini\Service\HttpServer\RouteService;
use Mini\Support\ServiceProvider;
use MiniCaptcha\Rules\CaptchaApi;

/**
 * Class CaptchaServiceProvider
 * @package MiniCaptcha
 */
class CaptchaServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot(): void
    {
        // Publish configuration files
        $this->publishes([
            __DIR__ . '/../config/captcha.php' => config_path('captcha.php')
        ], 'config');

        RouteService::registerHttpRoute([
            'GET', 'captcha/base64[/{config}]', '\MiniCaptcha\CaptchaController@getCaptchaBase64'
        ]);
        RouteService::registerHttpRoute([
            'GET', 'captcha[/{config}]', '\MiniCaptcha\CaptchaController@getCaptcha'
        ]);

        Validator::addValidator('captcha', new \MiniCaptcha\Rules\Captcha());
    }

    /**
     * Register the service provider.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function register(): void
    {
        // Merge configs
        $this->mergeConfigFrom(
            __DIR__ . '/../config/captcha.php',
            'captcha'
        );

        // Bind captcha
        $this->app->singleton('captcha', function () {
            return new Captcha();
        });
    }
}
