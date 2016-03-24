<?php

class ImageFactory {

	public function createImage($imgsrc)
	{	
		$info = getimagesize($imgsrc);
		switch ($info[2]) {
			case 1:
				$img = imagecreatefromgif($imgsrc);
				break;
			case 2:
				$img = imagecreatefromjpeg($imgsrc);
				break;
			case 3:
				$img = imagecreatefrompng($imgsrc);
				break;
			default:
				$img = FALSE;
				break;
		}
		return $img;
	}
	public function resizeImage($src, $dis_path, $rigionRatio, $w, $h)
	{
		$info = getimagesize($src);
		$height = $info[1];
		$width = $info[0];

		$w_scale = round($w/$width, 2);//宽缩放比例
		$h_scale = round($h/$height, 2);//高缩放比例
		//缩放比较小的作为图像的缩放比例
		$scale = $w_scale < $h_scale ? $w_scale : $h_scale;
		$disHeight = intval($height*$scale);
		$disWidth = intval($width*$scale);
		// echo "$disWidth $disHeight , $width, $height, $scale <br>";
		// echo "$dis_path<br>";
		// 1
		$img_cloth = imagecreatetruecolor($disWidth, $disHeight);
		// 2
		$img = $this->createImage($src);
		if ($img == FALSE) {
			return FALSE;
		}
		// 3
		if (imagecopyresampled($img_cloth, $img, 0, 0, 0, 0, $disWidth, $disHeight, $width, $height) == FALSE) {
			return FALSE;
		}
		// 4
		if (imagejpeg($img_cloth,$dis_path,100) == FALSE) {
			return FALSE;
		}
		// 5
		imagedestroy($img);
		return TRUE;
	}	
}

?>