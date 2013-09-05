<?php    

    include "phpqrcode/qrlib.php";
	include "prettyqr512.php";

    if (!isset($_REQUEST['data']))
	{
		echo "ERROR: missing data\n";
		die();
    }

    $errorCorrectionLevel = 'L';
    if (isset($_REQUEST['level']) && in_array($_REQUEST['level'], array('L','M','Q','H')))
        $errorCorrectionLevel = $_REQUEST['level'];

    if($_REQUEST['look'] == "pretty")
	{
        $qr = QRcode::text($_REQUEST['data'], false, $errorCorrectionLevel, 0, 2);
        $pretty = new PrettyQrCode512($qr,"tiles/tileset_emboss.png",'backgrounds',20,60);
		$image = $pretty->asPng();

		$s = $_REQUEST['size'];
		if($s != 0)
		{
			$image->resizeImage($s,$s,imagick::FILTER_LANCZOS,1.0);
		}
        
        if($_REQUEST['test'] != 0)
        {
            $geo = $image->getImageGeometry();
            
            $stamp = new Imagick("backgrounds/example.png");
            $stamp->resizeImage($geo['width'],$geo['height'],imagick::FILTER_LANCZOS,1.0);
            
            $image->compositeImage($stamp,imagick::COMPOSITE_OVER,0,0);
        }
        
		Header("Content-Type: image/png");
		echo $image;
	} else
	{
		Header("Content-Type: image/png");
		echo   QRcode::png($_REQUEST['data'], false,$errorCorrectionLevel, 20, 2);
	}
?>
