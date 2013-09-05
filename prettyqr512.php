<?php

function array_insert($src,$ins,$pos,$rep=0)
{
  array_splice($src,$pos,$rep,$ins);
  return($src);
}
/**
  @brief Class PrettyQrCode512 for create QR code
*/
class PrettyQrCode512
{
    // the final image is a square of size (qrdim + 2) * tileSize + marginSize

	private $tileSize;
	private $marginSize;
	private $mQr;
	private $mTraced;
	private $tileMap;
	private $tileset;
	private $backgroundsDir;
	/**
	* @brief Construktor
	* @param qr, qr code for apply pretty
	* @param tileset tile image for generate qr code
	* @param backgroundsDir	the directory for backgrounds
	* @param tileSize tile size
	* @param marginSize margi size
	*/
	function __construct($qr, $tileset, $backgroundsDir, $tileSize, $marginSize)
	{
	$this->tileMap = array();
	$this->tileset = new Imagick($tileset);
        $this->backgroundsDir = $backgroundsDir;
        $this->tileSize = $tileSize;
        $this->marginSize = $marginSize;

        // make sure the dir is terminated by /
        if($this->backgroundsDir[strlen($this->backgroundsDir)-1] != '/')
            $this->backgroundsDir = $this->backgroundsDir.'/';
        
		// frame the QR code with 1-tile empty space
		for($i=0;$i<count($qr);++$i)
			$qr[$i] = '0'.$qr[$i].'0';

		$empty=str_pad("",count($qr)+2,"0");
		$qr = array_insert($qr,$empty,0);
		$qr = array_insert($qr,$empty,count($qr));

		$this->mQr = $qr;
        
		$this->trace();
	}
	/**
	* @brief Save QR code as PNG format
	* @return Return image in PNG format
	*/
	function asPng()
	{	
		$im = $this->getImage();
		$im->setImageFormat('png');
		return $im;
	}
	/**
	* @brief Save QR code as JPEG format
	* @return Rerturn image in JEPG format
	*/
	function asJpeg()
	{	
		$im = $this->getImage();
		$bg = new Imagick();
		$bg->newImage($im->getImageWidth(),$im->getImageHeight(), "white");
		$bg->compositeImage($im,imagick::COMPOSITE_DEFAULT,0,0);
		$bg->setImageFormat('jpeg');
		
		return $bg;
	}
	/**
	* @brief Create the empty QR image, the size is without margin: the top-left
	* @return PretyQR image
	*/
	function getImage()
	{
        // create the empty QR image, the size is without margin: the top-left
        // tile is at 0,0
		$im = $this->getEmptyQrImage();

        // put the tiles in place
		for($y=0;$y<count($this->mQr);++$y)
		{
			for($x=0;$x<strlen($this->mQr[$y]);++$x)
			{
				$box = $this->getTileForCode($this->mTraced[$y][$x]);

				$im->compositeImage($box,imagick::COMPOSITE_OVER,
							$x*$this->tileSize,
							$y*$this->tileSize);
			}
		}
        
        // apply the background - this extends the image by $this->marginSize
		$this->applyBackground($im);

        // put the logo in place
//		$this->applyLogo($im);

		return $im;
	}

	/**
	* @brief Get rectangle where the logo should be
	* @return The rectangle, in both tile coordinates and final image pixel coordinates
	*/ 
	private function getLogoRect()
	{
		$clearx = (int)(count($this->mQr)/4);
		$cleary = (int)(count($this->mQr)/4);
		$py=(count($this->mQr)-$cleary)/2;
		$px=(count($this->mQr)-$clearx)/2;
		$ret = array();

		$ret['tw'] = $clearx;
		$ret['th'] = $cleary;
		$ret['tx'] = $px;
		$ret['ty'] = $py;

		$ret['x'] = $ret['tx']*$this->tileSize + $this->marginSize;
		$ret['y'] = $ret['ty']*$this->tileSize + $this->marginSize;
		$ret['w'] = $ret['tw']*$this->tileSize;
		$ret['h'] = $ret['th']*$this->tileSize;

		return $ret;
	}

	/**
	* @brief Get tile for qr code from cache and put in the cache if the tile is not in cache.
	* @param code tile code
	* @return Return tile
	*/
	private function getTileForCode($code)
	{
        // check if the tile is in cache
		if(isset($this->tileMap[$code]))
			return $this->tileMap[$code];

        // crop the single tile from tileset
		$x = $code%32;
		$y = (int)($code/32);

		$x = $x * 3*$this->tileSize + $this->tileSize;
		$y = $y * 3*$this->tileSize + $this->tileSize;

		$im = clone $this->tileset;
		$im->cropImage($this->tileSize,$this->tileSize,$x,$y);

        // put it into cache
		$this->tileMap[$code] = $im;

		return $im;
	}
	/**
	 * @brief Create an empty image for QR Code
	 * @return Empty Image 
	*/
	private function getEmptyQrImage()
	{
		$im = new Imagick();
		$dim = count($this->mTraced) * $this->tileSize;

		$im->newImage($dim,$dim,new ImagickPixel('#00000000'));

		return $im;
	}
    /**
     * @brief Create the background for QR code
     * @return QR code background image
     */
    private function getBackgroundImage()
    {
        if(count($this->mQr)-2 == 21)
			$bg = new Imagick($this->backgroundsDir.'bg_21.png');
		else if(count($this->mQr)-2 == 25)
			$bg = new Imagick($this->backgroundsDir.'bg_25.png');
		else if(count($this->mQr)-2 == 29)
			$bg = new Imagick($this->backgroundsDir.'bg_29.png');
		else if(count($this->mQr)-2 == 33)
			$bg = new Imagick($this->backgroundsDir.'bg_33.png');
		else
		{
            // try generic background, without bevel effect
			$bg = new Imagick($this->backgroundsDir.'bg.png');
			$x = ($bg->getImageWidth() - $im->getImageWidth())/2 - $this->marginSize;
			$y = ($bg->getImageHeight() - $im->getImageHeight())/2 - $this->marginSize;
            
			$bg->cropImage($im->getImageWidth() + 2*$this->marginSize,
                           $im->getImageHeight() +2*$this->marginSize,$x,$y);
		}
        
        return $bg;
    }
    


/* 
        if(count($this->mQr)-2 == 21)
			$bg = new Imagick($this->backgroundsDir.'bg_21.png');
		else if(count($this->mQr)-2 == 25)
			$bg = new Imagick($this->backgroundsDir.'bg_25.png');
		else if(count($this->mQr)-2 == 29)
			$bg = new Imagick($this->backgroundsDir.'bg_29.png');
		else if(count($this->mQr)-2 == 33)
			$bg = new Imagick($this->backgroundsDir.'bg_33.png');
		else
		{
            // try generic background, without bevel effect
			$bg = new Imagick($this->backgroundsDir.'bg.png');
			$x = ($bg->getImageWidth() - $im->getImageWidth())/2 - $this->marginSize;
			$y = ($bg->getImageHeight() - $im->getImageHeight())/2 - $this->marginSize;
            
			$bg->cropImage($im->getImageWidth() + 2*$this->marginSize,
                           $im->getImageHeight() +2*$this->marginSize,$x,$y);
		}
        
        return $bg;
*/
	/**
	 * @brief Apply background to QR code 
	 * @param im The image for apply background
	 */
	private function applyBackground(&$im)
	{
        $bg = $this->getBackgroundImage();

		$bg->compositeImage($im,imagick::COMPOSITE_DEFAULT,$this->marginSize,$this->marginSize);
		$im = $bg;
	}

	
	/**
	 * @brief Apply logo to QR code 
	 * @param im The image for apply logo
	 */
	private function applyLogo(&$im)
	{
		$l = $this->getLogoRect();

		// red box
/*
		$frame = new Imagick();
		$frame->newImage($l['w'],$l['h'],new ImagickPixel('#ff000020'));
*/
		// square logo
		$frame = new Imagick($this->backgroundsDir.'logo.png');
		$frame->resizeImage($l['w'],$l['h'],imagick::FILTER_LANCZOS,1.0);

		// txt logo
/*
		$frame = new Imagick('tiles/logo+txt.png');
		$frame->scaleImage($l['w'],$l['h'],1);
*/
		$im->compositeImage($frame,imagick::COMPOSITE_DEFAULT,$l['x'],$l['y']);

	}
	/**
	 * @brief Create the "traced" matrix with tile codes
	 */
	private function trace()
	{
		$out = array();
		$qr = $this->mQr;

        // wipe-out the area where the logo is going to be placed
		$clearSpace = $this->getLogoRect();
		for($y=$clearSpace['ty'];$y<$clearSpace['ty']+$clearSpace['th'];++$y)
		{
			for($x=$clearSpace['tx'];$x<$clearSpace['tx']+$clearSpace['tw'];++$x)
			{
				if($x == 0 && $y == 0) continue;
				if($x == 0 && $y == $clearSpace['ty']+$clearSpace['th']-1) continue;
				if($x == $clearSpace['tx']+$clearSpace['tw']-1 && $y == $clearSpace['ty']+$clearSpace['th']-1) continue;
				if($x == $clearSpace['tx']+$clearSpace['tw']-1 && $y == 0) continue;

				$qr[(int)$y][(int)$x] = '0';
			}
		}

        // create the "traced" matrix with tile codes
		for($y=0;$y<count($qr);++$y)
		{
			for($x=0;$x<strlen($qr[$y]);++$x)
			{
				$idx = 0;

				if(!$this->isEmpty($qr,$x+1,$y+1)) $idx += 1;
				if(!$this->isEmpty($qr,$x+0,$y+1)) $idx += 2;
				if(!$this->isEmpty($qr,$x-1,$y+1)) $idx += 4;
				if(!$this->isEmpty($qr,$x+1,$y+0)) $idx += 8;
				if(!$this->isEmpty($qr,$x+0,$y+0)) $idx += 16;
				if(!$this->isEmpty($qr,$x-1,$y+0)) $idx += 32;
				if(!$this->isEmpty($qr,$x+1,$y-1)) $idx += 64;
				if(!$this->isEmpty($qr,$x+0,$y-1)) $idx += 128;
				if(!$this->isEmpty($qr,$x-1,$y-1)) $idx += 256;

				$out[$y][$x] = $idx;
			}
		}

		$this->mTraced = $out;
	}

	/**
	 * @brief test for empty
	 */
	private function isEmpty($qr,$x,$y)
	{
		if($y<0 || $y >= count($qr))
			return 1;

		if($x<0 || $x >= strlen($qr[$y]))
			return 1;

		$ret = ($qr[$y][$x] == '0');

		return $ret;
	}
}


?>
