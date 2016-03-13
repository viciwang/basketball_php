<?php 

require_once 'application/models/ResponseModel.php';
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

	public function updateInfo()
	{
		$response = $this->userModel->updateUserInfo();
		echo json_encode($response);
	}

	public function getVerifyCode()
	{
		$result = $this->userModel->generateVerifyCode($this->input->post('email'));
		if (!is_string($result)) {
			echo json_encode($result);
			return;
		}

		$to = $this->input->post('email');
		$code = $result;
		$subject = "verify code from basketball.com";
		$message = "验证码为$code,请在10分钟内完成验证。";
		$from = "auto_send@basketball.com";
		$headers = "From: $from";
		mail($to,$subject,$message,$headers);
		$response = new ResponseModel(array('verifyCode' => $code) , "验证码已发送", 0);
		echo  json_encode($response);
	}
}
?>