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
		echo json_encode($response);
	}

	public function login() 
	{
		$response = $this->userModel->login();
		echo json_encode($response);
	}
}
?>