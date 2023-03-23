<?php

namespace App\Lib;

class Captcha
{
    private ?string $code = null;
    private \GdImage $image;
    private ?float $width;
    private ?float $height;
    private ?string $font = null;
    private ?string $fontsFolder = null;

    public function __construct($width = '90', $height = '30')
    {
        $this->width = $width;
        $this->height = $height;

        try {
            $this->setCode(mt_rand(1000, 9999));
            $this->setFontsFolder(__DIR__ . '/fonts');
            $this->setFont('monofont');
        } catch (\Exception $e) {
        }
    }

    /**
     * @throws \Exception
     */
    public function load()
    {
        if (empty($this->font)) {
            throw new \Exception('No font file set', 500);
        }

        $this->image = imagecreate($this->width, $this->height) or die('Cannot initialize new GD image stream');
        $bgColor = imagecolorallocate($this->image, 175, 175, 175);
        $textColor = imagecolorallocate($this->image, 210, 210, 210);
        $noiseColor = imagecolorallocate($this->image, 60, 60, 60);
        $fontSize = (int)($this->height * 0.75);

        /* generate random dots in background */
        for ($i = 0; $i < ($this->width * $this->height) / 3; $i++) {
            imagefilledellipse($this->image, mt_rand(0, $this->width), mt_rand(0, $this->height), 1, 1, $noiseColor);
        }

        /* generate random lines in background */
        for ($i = 0; $i < ($this->width * $this->height) / 150; $i++) {
            imageline($this->image, mt_rand(0, $this->width), mt_rand(0, $this->height), mt_rand(0, $this->width), mt_rand(0, $this->height), $noiseColor);
        }

        /* create text box and add text */
        $textBox = imagettfbbox($fontSize, 0, $this->font, $this->code) or die('Error in imagettfbbox function');
        $x = (int)(($this->width - $textBox[4]) / 2);
        $y = (int)(($this->height - $textBox[5]) / 2);
        imagettftext($this->image, $fontSize, 0, $x, $y, $textColor, $this->font, $this->code) or die('Error in imagettftext function');

        ob_start();
        imagejpeg($this->image);
        $imageString = base64_encode(ob_get_clean());
        imagedestroy($this->image);
        return $imageString;
    }

    /**
     * @throws \Exception
     */
    public function setCode($code = null): void
    {
        if (empty($code)) {
            throw new \Exception('No captcha code provided', 500);
        } else {
            $this->code = $code;
        }
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * @throws \Exception
     */
    public function setFontsFolder($folder = null): void
    {
        if (empty($folder)) {
            throw new \Exception('Fonts folder not provided', 500);
        }

        $_folder = realpath($folder);
        if ($_folder && is_dir($_folder) && is_readable($_folder)) {
            $this->fontsFolder = $_folder . "/";
        } else {
            throw new \Exception("Config folder read error: {$folder}", 500);
        }
    }

    public function getFontsFolder(): ?string
    {
        return $this->fontsFolder;
    }

    /**
     * @throws \Exception
     */
    public function setFont($font = null): void
    {
        if (empty($this->fontsFolder)) {
            throw new \Exception('Fonts folder not set', 500);
        }

        $_font = $this->fontsFolder . $font . '.ttf';
        if (is_file($_font) && is_readable($_font)) {
            $this->font = $_font;
        } else {
            throw new \Exception("Unable to load font file: {$font}", 500);
        }
    }

    public function getFont(): ?string
    {
        return $this->font;
    }
}