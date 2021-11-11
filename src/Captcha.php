<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace MiniCaptcha;

use Exception;
use Mini\Contracts\Container\BindingResolutionException;
use Mini\Facades\Config;
use Mini\Facades\File;
use Mini\Facades\Hash;
use Mini\Facades\Request;
use Mini\Facades\Response;
use Mini\Facades\Session;
use Mini\Service\HttpMessage\Stream\SwooleStream;
use Mini\Session\SessionServiceProvider;
use Mini\Support\Str;
use Mini\Support\HtmlString;
use Intervention\Image\Gd\Font;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Mini\Facades\Cache;
use Mini\Facades\Crypt;
use MiniCaptcha\Exceptions\CaptchaException;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Captcha
 * @package MiniCaptcha
 */
class Captcha
{
    /**
     * @var ImageManager
     */
    protected ImageManager $imageManager;

    /**
     * @var Image
     */
    protected Image $canvas;

    /**
     * @var Image
     */
    protected Image $image;

    /**
     * @var array
     */
    protected array $backgrounds = [];

    /**
     * @var array
     */
    protected array $fonts = [];

    /**
     * @var array
     */
    protected array $fontColors = [];

    /**
     * @var int
     */
    protected int $length = 5;

    /**
     * @var int
     */
    protected int $width = 120;

    /**
     * @var int
     */
    protected int $height = 36;

    /**
     * @var int
     */
    protected int $angle = 15;

    /**
     * @var int
     */
    protected int $lines = 3;

    /**
     * @var string
     */
    protected $characters;

    /**
     * @var array
     */
    protected array $text;

    /**
     * @var int
     */
    protected int $contrast = 0;

    /**
     * @var int
     */
    protected int $quality = 90;

    /**
     * @var int
     */
    protected int $sharpen = 0;

    /**
     * @var int
     */
    protected int $blur = 0;

    /**
     * @var bool
     */
    protected bool $bgImage = true;

    /**
     * @var string
     */
    protected string $bgColor = '#ffffff';

    /**
     * @var bool
     */
    protected bool $invert = false;

    /**
     * @var bool
     */
    protected bool $sensitive = false;

    /**
     * @var bool
     */
    protected bool $math = false;

    /**
     * @var int
     */
    protected int $textLeftPadding = 4;

    /**
     * @var string
     */
    protected $fontsDirectory;

    /**
     * @var int
     */
    protected int $expire = 60;

    /**
     * @var bool
     */
    protected bool $encrypt = true;

    /**
     * Constructor
     *
     * @throws BindingResolutionException
     * @internal param Validator $validator
     */
    public function __construct()
    {
        $this->imageManager = app('image');
        $this->characters = config('captcha.characters', ['1', '2', '3', '4', '6', '7', '8', '9']);
        $this->fontsDirectory = config('captcha.fontsDirectory', dirname(__DIR__) . '/assets/fonts');
        $backgrounds = File::files(__DIR__ . '/../assets/backgrounds');
        foreach ($backgrounds as $background) {
            $this->backgrounds[] = $background->getPathname();
        }
        $fonts = File::files($this->fontsDirectory);
        foreach ($fonts as $font) {
            $this->fonts[] = $font->getPathName();;
        }

    }

    /**
     * @param string $config
     * @return void
     */
    protected function configure(string $config): void
    {
        if ($data = Config::get('captcha.' . $config)) {
            foreach ($data as $key => $val) {
                $this->{$key} = $val;
            }
        }
    }

    /**
     * Create captcha image
     *
     * @param string $ck
     * @param string $config
     * @param bool $base64
     * @return ResponseInterface|string
     * @throws BindingResolutionException
     * @throws CaptchaException
     * @throws Exception
     */
    public function create(string $ck = '', string $config = 'default', bool $base64 = false)
    {
        $this->configure($config);

        $generator = $this->generate($ck);
        $this->text = $generator['value'];

        $this->canvas = $this->imageManager->canvas(
            $this->width,
            $this->height,
            $this->bgColor
        );
        if ($this->bgImage) {
            $this->image = $this->imageManager->make($this->background())->resize(
                $this->width,
                $this->height
            );
            $this->canvas->insert($this->image);
        } else {
            $this->image = $this->canvas;
        }

        if ($this->contrast !== 0) {
            $this->image->contrast($this->contrast);
        }

        $this->text();

        $this->lines();

        if ($this->sharpen) {
            $this->image->sharpen($this->sharpen);
        }
        if ($this->invert) {
            $this->image->invert();
        }
        if ($this->blur) {
            $this->image->blur($this->blur);
        }

        return $base64
            ? $this->image->encode('data-url')->encoded
            : $this->response();
    }

    /**
     * @param string $captcha_key
     * @return string
     * @throws CaptchaException|BindingResolutionException
     */
    public function getCk(string $captcha_key = ''): string
    {
        if ($captcha_key) {
            return $captcha_key;
        }
        if (app('providers')->serviceProviderWasBooted(SessionServiceProvider::class)) {
            $captcha_key = Session::getId();
            if (!$captcha_key) {
                throw new CaptchaException('create captcha: no session id');
            }
        } else {
            $captcha_key = (string)Request::header('ck', Request::input('ck'));
            if (!$captcha_key) {
                $captcha_key = Request::ip();
            }
        }
        return $captcha_key;
    }

    /**
     * @param string $format
     * @return ResponseInterface
     */
    protected function response(string $format = 'png'): ResponseInterface
    {
        $this->image->encode($format, $this->quality);
        $data = $this->image->getEncoded();
        $mime = finfo_buffer(finfo_open(FILEINFO_MIME_TYPE), $data);
        $length = strlen($data);
        return Response::withAddedHeader('Content-Type', $mime)->withAddedHeader('Content-Length', $length)->withBody(new SwooleStream($data));
    }

    /**
     * Image backgrounds
     *
     * @return string
     * @throws Exception
     */
    protected function background(): string
    {
        return $this->backgrounds[random_int(0, count($this->backgrounds) - 1)];
    }

    /**
     * Generate captcha text
     * @param string $ck
     * @return array
     * @throws CaptchaException|BindingResolutionException
     * @throws Exception
     */
    protected function generate(string $ck = ''): array
    {
        $characters = is_string($this->characters) ? str_split($this->characters) : $this->characters;

        $bag = [];

        if ($this->math) {
            $x = random_int(10, 30);
            $y = random_int(1, 9);
            $bag = "$x + $y = ";
            $key = $x + $y;
            $key .= '';
        } else {
            for ($i = 0; $i < $this->length; $i++) {
                $char = $characters[random_int(0, count($characters) - 1)];
                $bag[] = $this->sensitive ? $char : Str::lower($char);
            }
            $key = implode('', $bag);
        }

        $hash = Hash::make($key);
        if ($this->encrypt) {
            $hash = Crypt::encrypt($hash);
        }

        Cache::put($this->get_cache_key($ck), [
            'sensitive' => $this->sensitive,
            'key' => $hash,
            'encrypt' => $this->encrypt
        ], $this->expire);

        return [
            'value' => $bag,
            'sensitive' => $this->sensitive,
            'key' => $hash
        ];
    }

    /**
     * Writing captcha text
     *
     * @return void
     */
    protected function text(): void
    {
        $marginTop = $this->image->height() / $this->length;

        $text = $this->text;
        if (is_string($text)) {
            $text = str_split($text);
        }

        foreach ($text as $key => $char) {
            $marginLeft = $this->textLeftPadding + ($key * ($this->image->width() - $this->textLeftPadding) / $this->length);

            $this->image->text($char, $marginLeft, $marginTop, function ($font) {
                /* @var Font $font */
                $font->file($this->font());
                $font->size($this->fontSize());
                $font->color($this->fontColor());
                $font->align('left');
                $font->valign('top');
                $font->angle($this->angle());
            });
        }
    }

    /**
     * Image fonts
     *
     * @return string
     * @throws Exception
     */
    protected function font(): string
    {
        return $this->fonts[random_int(0, count($this->fonts) - 1)];
    }

    /**
     * Random font size
     *
     * @return int
     * @throws Exception
     */
    protected function fontSize(): int
    {
        return random_int($this->image->height() - 10, $this->image->height());
    }

    /**
     * Random font color
     *
     * @return string
     * @throws Exception
     */
    protected function fontColor(): string
    {
        if (!empty($this->fontColors)) {
            $color = $this->fontColors[random_int(0, count($this->fontColors) - 1)];
        } else {
            $color = '#' . str_pad(dechex(random_int(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
        }

        return $color;
    }

    /**
     * Angle
     *
     * @return int
     * @throws Exception
     */
    protected function angle(): int
    {
        return random_int((-1 * $this->angle), $this->angle);
    }

    /**
     * Random image lines
     *
     * @return Image|ImageManager
     * @throws Exception
     */
    protected function lines()
    {
        for ($i = 0; $i <= $this->lines; $i++) {
            $this->image->line(
                random_int(0, $this->image->width()) + $i * random_int(0, $this->image->height()),
                random_int(0, $this->image->height()),
                random_int(0, $this->image->width()),
                random_int(0, $this->image->height()),
                function ($draw) {
                    /* @var Font $draw */
                    $draw->color($this->fontColor());
                }
            );
        }

        return $this->image;
    }

    /**
     * Captcha check
     *
     * @param string $value
     * @param string $ck
     * @param bool $removeSession
     * @return bool
     * @throws BindingResolutionException
     * @throws CaptchaException
     */
    public function check(string $value, string $ck = '', bool $removeSession = true): bool
    {
        if (!$cache = Cache::get($this->get_cache_key($ck))) {
            return false;
        }

        $key = $cache['key'] ?? null;
        $sensitive = $cache['sensitive'] ?? null;
        $encrypt = $cache['encrypt'] ?? null;

        if (!$sensitive) {
            $value = Str::lower($value);
        }

        if ($encrypt) {
            $key = Crypt::decrypt($key);
        }
        $check = Hash::check($value, $key);
        // if verify pass,remove session
        if ($removeSession && $check) {
            Cache::delete($this->get_cache_key($ck));
        }

        return $check;
    }

    /**
     * Returns the md5 short version of the key for cache
     * @param string $ck
     * @return string
     * @throws BindingResolutionException
     * @throws CaptchaException
     */
    protected function get_cache_key(string $ck = ''): string
    {
        return 'captcha.' . $this->getCk($ck);
    }

    /**
     * Generate captcha image source
     * @param string $ck
     * @param string $config
     * @return string
     * @throws Exception
     */
    public function src(string $ck = '', string $config = 'default'): string
    {
        return url('captcha/' . $config) . '?ck=' . $this->getCk($ck) . '&rd=' . Str::random(8);
    }

    /**
     * Generate captcha image html tag
     *
     * @param string $ck
     * @param string $config
     * @param array $attrs
     * $attrs -> HTML attributes supplied to the image tag where key is the attribute and the value is the attribute value
     * @return HtmlString
     * @throws Exception
     */
    public function img(string $ck = '', string $config = 'default', array $attrs = []): HtmlString
    {
        $attrs_str = '';
        foreach ($attrs as $attr => $value) {
            if ($attr === 'src') {
                //Neglect src attribute
                continue;
            }

            $attrs_str .= $attr . '="' . $value . '" ';
        }
        return new HtmlString('<img src="' . $this->src($ck, $config) . '" ' . trim($attrs_str) . '>');
    }
}
