<?php 
/**
* 
*/
class User extends CI_Controller
{
	
	function __construct()
	{
		parent::__construct();
		$this->load->model('userModel');
	}

	public function register() 
	{
		$response = $this->userModel->addUser();
		if($response->code === 0)
		{
			echo "插入成功";
			echo json_encode($response);
		}
		else 
		{
			echo "插入失败";
			echo json_encode($response);
		}
	}
}
?>