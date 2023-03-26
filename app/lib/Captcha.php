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

    /**
     *Initializes a new instance of the Captcha class with the specified width and height.
     * @param string $width The width of the captcha image in pixels.
     * @param string $height The height of the captcha image in pixels.
     * @throws \Exception If an error occurs while setting the captcha code, fonts folder or font.
     */
    public function __construct(string $width = '90', string $height = '30')
    {
        // Set the width and height properties
        $this->width = $width;
        $this->height = $height;

        try {
            // Set a random code for the captcha
            $this->setCode(mt_rand(1000, 9999));
            // Set the fonts folder
            $this->setFontsFolder(__DIR__ . '/fonts');
            // Set the font to be used for the captcha
            $this->setFont('monofont');
        } catch (\Exception $e) {
        }
    }

    /**
     * Generate a captcha image with random background dots and lines, and a random string of characters.
     * @return string a base64-encoded jpeg image string.
     * @throws \Exception if no font file is set.
     */
    public function load(): string
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
     *  Sets the captcha code.
     * @param ?string $code The captcha code to set.
     * @throws \Exception If no captcha code is provided.
     */
    public function setCode(?string $code = null): void
    {
        if (empty($code)) {
            throw new \Exception('No captcha code provided', 500);
        } else {
            $this->code = $code;
        }
    }

    /**
     * Gets the captcha code.
     * @return string|null The captcha code, or null if it hasn't been set.
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * Sets the path to the fonts folder used for generating the captcha image.
     *
     * @param ?string $folder The path to the fonts folder.
     * @throws \Exception If the fonts folder is not provided or is not readable.
     */
    public function setFontsFolder(?string $folder = null): void
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

    /**
     * Retrieves the path to the fonts folder used for generating the captcha image.
     * @return ?string The path to the fonts folder.
     */
    public function getFontsFolder(): ?string
    {
        return $this->fontsFolder;
    }

    /**
     *
     * Set the font file to be used for generating the captcha image
     * @param ?string $font The name of the font file, without the .ttf extension
     * @return void
     * @throws \Exception If the fonts folder is not set or the specified font file cannot be loaded
     */
    public function setFont(?string $font = null): void
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

    /**
     * Get the font file currently set for generating the captcha image
     * @return ?string The path to the font file, or null if not set
     */
    public function getFont(): ?string
    {
        return $this->font;
    }
}