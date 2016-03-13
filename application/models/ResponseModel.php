<?php 

/**
* 
*/
class ResponseModel
{
	public $code;
	public $msg;
	public $data;

	public function __construct($data, $msg = "神奇的错误", $code = 0) 
	{
		$this->data = $data;
		$this->msg = $msg;
		$this->code = $code;
	}
}
?>