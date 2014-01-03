<?php

/**
 * Description of ImageSize
 *
 * @author jonas emil <jonas@bluesteel.dk>
 */
class ImageSize {
	const 
		/**
		 * Constants for watermark positioning
		 */
		WM_CENTER = 'center',
		WM_STRETCH = 'stretch',
		WM_TOP_LEFT = 'topleft',
		WM_TOP_RIGHT = 'topright',
		WM_BOTTOM_RIGHT = 'bottomright',
		WM_BOTTOM_LEFT = 'bottomleft',
		/**
		 * Filetypes 
		 */
		TYPE_PNG = '.png',
		TYPE_JPG = '.jpg'
		;
	
	protected 
		$_width,
		$_height,
		$_file, 
		$_initWidth,
		$_initHeight,
		$_src,
		$_image,
		$config = array(),
		$headers = array()
		;

	/**
	 * Construct class
	 * @param array $config
	 * $config = array(
	 * 	'alpha' => BOOLEAN (default: false), 
	 *  'imageDir' => STRING Server path to images (default: __DIR__ . '/')
	 *  'cacheDir' => STRING Server path to cachefolder (default: ''), 
	 *  'stretch' => BOOLEAN stretch the image to width, height if both are set, or use w+h as max-width/height (default: false)
	 *  'crop' => BOOLEAN crop to width, height (centered) if both are set (default: true)
	 *  'outputType' => String a class constant starting with TYPE_ (default: TYPE_PNG)
	 * 	'outputQuality' => Integer 1->100, PNG has less range than JPG, so PNG quality will be divided by 10 (default: 100) 
	 *  'watermarkPath' => STRING Server path to watermark image, must be png (default: '')
	 *  'watermarkPosition' => STRING a class constant starting with WM_ (default: WM_CENTER)
	 * )
	 */
	public function __construct(Array $config = array(), $file = '', $width = 0, $height = 0 ) {
		$stdConfig = array(
			'alpha' => false,
			'imageDir' => __DIR__ . '/',
			'cacheDir' => '',
			'stretch' => false,
			'crop' => true,
			'outputType' => self::TYPE_PNG,
			'outputQuality' => 100,
			'watermarkPath' => '',
			'watermarkPosition' => self::WM_CENTER,
		);
		$this->_file = $file;
		$this->_width = $width;
		$this->_height = $height;
		$config = array_merge($stdConfig, $config);
		$this->setConfig($config);
	}

	/**
	 * This will create an instance of the class and use $_GET to generate image
	 * $_GET['img'], $_GET['w'], $_GET['h'] 
	 * @param array $config
	 */
	public static function createFromGlobals( Array $config = array() ) {
		$class = __CLASS__;
		$ImageSize = new $class( $config, $_GET['img'], $_GET['w'], $_GET['h'] );
		
		$ImageSize->generateImage();
		
		return $ImageSize;
	}
	
	/**
	 * Set configurations 
	 * @param array $config containing values from construct. Only values included will be set.
	 */
	public function setConfig( $config ) {
		foreach( $config as $k => $v ) {
			if( $k === 'outputQuality' ) {
				if( $v > 100 ) $v = 100;
				else if( $v < 1 ) $v = 1;
			} else if( $k === 'outputType' ) {
				if( $v === self::TYPE_PNG ) {
					$this->headers['Content-type'] = 'image/png'; 
				} else if( $v === self::TYPE_JPG ) {
					$this->headers['Content-type'] = 'image/jpeg'; 

				}
			}
			$this->config[$k] = $v;
		}
	}
	
	public function generateImage() {
		$file = $this->config['imageDir'] . $this->_file;
		list($width, $height, $type, $attr) = getimagesize( $file );
		$this->_initWidth = $width;
		$this->_initHeight = $height;
		
		if( $type === IMAGETYPE_PNG ) {
			$this->_src = imagecreatefrompng( $file );	
		} else if ( $type === IMAGETYPE_JPEG ) {
			$this->_src = imagecreatefromjpeg( $file );
		}
		$this->addWatermark();
		$size = $this->getImageSize();
		$this->_image = imagecreatetruecolor($size['width'], $size['height']);
		imagecopyresampled($this->_image, $this->_src, 0, 0, $size['x'], $size['y'], $size['dstWidth'], $size['dstHeight'], $size['srcWidth'], $size['srcHeight']);
	}


	public function getImageSize() {
		$size = array('x' => 0, 'y' => 0, 'srcWidth' => $this->_initWidth, 'srcHeight' => $this->_initHeight);
		if( !$this->_width && !$this->_height ) {
			// use original size
			$size['width'] = $this->_initWidth;
			$size['height'] = $this->_initHeight;
		} else if( $this->_width && !$this->_height ) {
			// restrict width
			$size['width'] = $this->_width;
			$size['height'] = intval(($this->_width / $this->_initWidth) * $this->_initHeight);
		} else if ( $this->_height && !$this->_width ) {
			// restrict height
			$size['height'] = $this->_height;
			$size['width'] = intval(($this->_height / $this->_initHeight) * $this->_initWidth);
		} else {
			// restrict both
			if( $this->config['crop'] ) {
				$cropScale = $this->_width / $this->_height;
				$srcScale = $this->_initWidth / $this->_initHeight;
				if( $srcScale > $cropScale ) {
					$width = $this->_initHeight * $cropScale;
					$height = $this->_initHeight;
					$x = intval(($this->_initWidth - $width)/2);
					$y = 0;
				} else {
					$width = $this->_initWidth;
					$height = $this->_initWidth / $cropScale;
					$x = 0;
					$y = intval(($this->_initHeight - $height)/2);
				}
				$size['x'] = $x;
				$size['y'] = $y;
				$size['width'] = $this->_width;
				$size['height'] = $this->_height;
				$size['dstWidth'] = $this->_width;
				$size['dstHeight'] = $this->_height;
				$size['srcWidth'] = $width;
				$size['srcHeight'] = $height;
			} else if( $this->config['stretch'] ) {
				$size['width'] = $this->_width;
				$size['height'] = $this->_height;
			} else {
				$deltaW = $this->_width / $this->_initWidth;
				$deltaH = $this->_height / $this->_initHeight;
				if( $deltaW < $deltaH ){
					// Restrict width
					$size['width'] = $this->_width;
					$size['height'] = intval($deltaW * $this->_initHeight);
				} else { 
					// Restrict height (or if equal, both)
					$size['width'] = intval($deltaH * $this->_initWidth);
					$size['height'] = $this->_height;
				}
			}
		} 
		if( !$size['dstWidth'] ) {
			$size['dstWidth'] = $size['width'];
			$size['dstHeight'] = $size['height'];
		}
		return $size;
	}
	
	public function printImage() {
		$this->addHeaders();
		$filename = $this->getFilename();
		switch( $this->config['outputType'] ) {
			case self::TYPE_PNG:
				$quality = min(floor($this->config['outputQuality'] / 10), 9);
				imagepng( $this->_image, null, $quality );
				break;
			case self::TYPE_JPG: 
				$quality = $this->config['outputQuality'];
				imagejpeg( $this->_image, null, $quality );
				break;
		}
		$this->clearImage();
	}

	public function addHeaders() {
		//return;
		header('Content-Disposition: inline; filename="' . $this->getFilename() . '"');
		foreach( $this->headers as $h => $v) {
			header($h . ': ' . $v);
		}
	}

	public function getWatermark() {
		if( !($path = $this->config['watermarkPath']) ) {
			return false;
		}
		list( $width, $height, $type, $attr ) = getimagesize( $path );
		if( $type !== IMAGETYPE_PNG ) {
			return false;
		}
		$image = imagecreatefrompng($path);
		
		imagealphablending($image,false);
		imagesavealpha($image, false);
		
		return array(
			'image' => $image,
			'width' => $width,
			'height' => $height,
		);
	}
	
	public function addWatermark( ) {
		return false;
		$srcImage = $this->_src;
		$wm = $this->getWatermark();
		if( !$wm ) {
			return false;
		}
		$size = $this->getWatermarkSize($wm);
		$image = imagecreatetruecolor($size['width'], $size['height']);
		imagealphablending($image,false);
		imagesavealpha($image, true);
//		$black = imagecolorallocate($image, 0, 0, 0);
//		imagecolortransparent($image, $black);

		//imagecopyresampled($image, $wm['image'], 0, 0, $size['x'], $size['y'], $size['width'], $size['height'], $wm['width'], $wm['height']);
		imagecopyresampled($image, $wm['image'], 0, 0, 0,0, $size['width'], $size['height'], $size['width'], $size['height']);
		imagealphablending($srcImage, true);
		imagesavealpha($srcImage, false);
		imagecopyresampled($srcImage, $image, 0, 0, 0, 0, $size['width'], $size['height'], $size['width'], $size['height']);
		$this->destroyImage($image);
		$this->destroyImage($wm['image']);
		return $srcImage;
	}
	public function getWatermarkSize( $wm ) {
		$size = array('x' => 0, 'y' => 0, 'width' => $this->_initWidth, 'height' => $this->_initHeight);
		switch ( $this->config['watermarkPosition'] ) {
			case self::WM_TOP_LEFT:
				// x = y = 0;
				break;
			case self::WM_TOP_RIGHT:
				$size['x'] = intval( $this->_initWidth - $wm['width'] );
				break;
			case self::WM_BOTTOM_RIGHT:
				$size['x'] = intval( $this->_initWidth - $wm['width'] );
				$size['y'] = intval( $this->_initHeight - $wm['height'] );
				break;
			case self::WM_BOTTOM_LEFT:
				$size['y'] = intval( $this->_initHeight - $wm['height'] );
				break;
			case self::WM_STRETCH:
				break;
			default: // WM_CENTER
				$size['x'] = intval( abs($this->_initWidth - $wm['width']) / 2 );
				$size['y'] = intval( abs($this->_initHeight - $wm['height']) / 2);
				break;
		}
		return $size;
	}
	
	public function getFilename() {
		$file = explode('/',$this->_file);
		$file = array_reverse($file);
		$name = explode('.', $file[0]);
		array_splice($name,-1);
		return implode('',$name);
	}
	
	public function clearImages() {
		$this->destroyImage($this->_image);
		$this->destroyImage($this->_src);
	}
	public function destroyImage( $image ) {
		if( $image ) {
			imagedestroy($image);
		}
	}
}
