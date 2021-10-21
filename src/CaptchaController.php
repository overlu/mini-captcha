<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace MiniCaptcha;

use Exception;
use Mini\Facades\Response;

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
     * @return array|mixed
     * @throws Exception
     */
    public function getCaptcha(Captcha $captcha, $config = 'default')
    {
        return $captcha->create(\MiniCaptcha\Facades\Captcha::getCk(), $config);
    }

    /**
     * get CAPTCHA api
     *
     * @param Captcha $captcha
     * @param string $config
     * @return array|mixed
     * @throws Exception
     */
    public function getCaptchaApi(Captcha $captcha, $config = 'default')
    {
        return $captcha->create(\MiniCaptcha\Facades\Captcha::getCk(), $config, true);
    }
}
