php-image-size
==============

Resizes, crops, stretches and adds watermarks to your images on the fly.

Usage:

    <?php
    require_once '../src/smokiespartakus/Images/ImageSize.php';
    $config = array(
        'alpha' => false,
        'crop' => true,
        'stretch' => false,
        'imageDir' => __DIR__ . '/',
        'cacheDir' => __DIR__ . '/imgcache',
        'imageQuality' => 100,
        'imageType' => ImageSize::TYPE_PNG,
        'watermarkPath' => __DIR__ . '/wm2.png',
        'watermarkPosition' => ImageSize::WM_CENTER,
    );
    $Image = ImageSize::createFromGlobals( $config );
    $Image->printImage();

Features to come:
- Save photos in cache folder
- Make watermark work
- Add watermark text
