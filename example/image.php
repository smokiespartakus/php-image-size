<?php
error_reporting(E_ALL ^E_NOTICE);
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