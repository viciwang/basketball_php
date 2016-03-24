<?php 
/**
* 
*/
class Image extends CI_Controller
{
	public function commonImages($imageName = 'defaultImage')
	{
		 $attachment_location = 'headImages/'. $imageName . '.png';
		 if (file_exists($attachment_location)) {

            header($_SERVER["SERVER_PROTOCOL"] . " 200 OK");
            header("Cache-Control: public"); // needed for i.e.
            header("Content-Type: image/png");
            header("Content-Transfer-Encoding: Binary");
            header("Content-Length:".filesize($attachment_location));
            header("Content-Disposition: attachment; filename=". $imageName);
            readfile($attachment_location);
            die();        
        } else {
            die("Error: File not found.");
        } 
	}

	public function headImages($imageName = 'defaultHeadImage')
	{
		$this->commonImages($imageName);
	}
}
 ?>