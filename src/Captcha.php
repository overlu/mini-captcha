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
use Mini\Facades\Hash;
use Mini\Facades\Session;
use Mini\Filesystem\File;
use Mini\Support\Str;
use Mini\Support\HtmlString;
use Intervention\Image\Gd\Font;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Mini\Facades\Cache;
use Mini\Facades\Crypt;

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
    protected iterable $backgrounds = [];

    /**
     * @var array
     */
    protected iterable $fonts = [];

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
     * @param string $config
     * @param bool $api
     * @return array|mixed
     * @throws Exception
     */
    public function create(string $config = 'default', bool $api = false): array
    {
        $this->backgrounds = \Mini\Facades\File::files(__DIR__ . '/../assets/backgrounds');
        $this->fonts = \Mini\Facades\File::files($this->fontsDirectory);

        if (version_compare(app()->version(), '5.5.0', '>=')) {
            $this->fonts = array_map(static function ($file) {
                /* @var File $file */
                return $file->getPathName();
            }, $this->fonts);
        }

        $this->fonts = array_values($this->fonts); //reset fonts array index

        $this->configure($config);

        $generator = $this->generate();
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

        if ($api) {
            Cache::put($this->get_cache_key($generator['key']), $generator['value'], $this->expire);
        }

        return $api ? [
            'sensitive' => $generator['sensitive'],
            'key' => $generator['key'],
            'img' => $this->image->encode('data-url')->encoded
        ] : $this->image->response('png', $this->quality);
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
     *
     * @return array
     * @throws Exception
     */
    protected function generate(): array
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

        Session::put('captcha', [
            'sensitive' => $this->sensitive,
            'key' => $hash,
            'encrypt' => $this->encrypt
        ]);

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
     * @return bool
     */
    public function check(string $value): bool
    {
        if (!Session::has('captcha')) {
            return false;
        }

        $key = Session::get('captcha.key');
        $sensitive = Session::get('captcha.sensitive');
        $encrypt = Session::get('captcha.encrypt');

        if (!$sensitive) {
            $value = Str::lower($value);
        }

        if ($encrypt) {
            $key = Crypt::decrypt($key);
        }
        $check = Hash::check($value, $key);
        // if verify pass,remove session
        if ($check) {
            Session::remove('captcha');
        }

        return $check;
    }

    /**
     * Returns the md5 short version of the key for cache
     *
     * @param string $key
     * @return string
     */
    protected function get_cache_key(string $key): string
    {
        return 'captcha_' . md5($key);
    }

    /**
     * Captcha check
     *
     * @param string $value
     * @param string $key
     * @param string $config
     * @return bool
     */
    public function check_api(string $value, string $key, string $config = 'default'): bool
    {
        if (!Cache::pull($this->get_cache_key($key))) {
            return false;
        }

        $this->configure($config);

        if (!$this->sensitive) {
            $value = Str::lower($value);
        }
        if ($this->encrypt) {
            $key = Crypt::decrypt($key);
        }
        return Hash::check($value, $key);
    }

    /**
     * Generate captcha image source
     *
     * @param string $config
     * @return string
     * @throws Exception
     * @throws Exception
     */
    public function src(string $config = 'default'): string
    {
        return url('captcha/' . $config) . '?' . Str::random(8);
    }

    /**
     * Generate captcha image html tag
     *
     * @param string $config
     * @param array $attrs
     * $attrs -> HTML attributes supplied to the image tag where key is the attribute and the value is the attribute value
     * @return HtmlString
     * @throws Exception
     */
    public function img(string $config = 'default', array $attrs = []): HtmlString
    {
        $attrs_str = '';
        foreach ($attrs as $attr => $value) {
            if ($attr === 'src') {
                //Neglect src attribute
                continue;
            }

            $attrs_str .= $attr . '="' . $value . '" ';
        }
        return new HtmlString('<img src="' . $this->src($config) . '" ' . trim($attrs_str) . '>');
    }
}
