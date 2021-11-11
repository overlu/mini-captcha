<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace MiniCaptcha;

use Exception;
use Psr\Http\Message\ResponseInterface;

/**
 * Class CaptchaController
 * @package MiniCaptcha
 */
class CaptchaController
{
    /**
     * get CAPTCHA
     *
     * @param Captcha $captcha
     * @param string $config
     * @return ResponseInterface
     * @throws Exception
     */
    public function getCaptcha(Captcha $captcha, $config = 'default'): ResponseInterface
    {
        return $captcha->create(\MiniCaptcha\Facades\Captcha::getCk(), $config);
    }

    /**
     * get CAPTCHA base64 data
     *
     * @param Captcha $captcha
     * @param string $config
     * @return string
     * @throws Exception
     */
    public function getCaptchaBase64(Captcha $captcha, $config = 'default'): string
    {
        return $captcha->create(\MiniCaptcha\Facades\Captcha::getCk(), $config, true);
    }
}
