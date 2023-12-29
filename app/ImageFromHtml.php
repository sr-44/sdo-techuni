<?php

namespace App;

use Knp\Snappy\Image;

class ImageFromHtml
{
    public static function generate(string $html, string $outputPath): void
    {
        $snappy = new Image('/usr/bin/wkhtmltoimage', ['encoding' => 'utf-8']);
        $snappy->generateFromHtml($html, $outputPath);
    }
}