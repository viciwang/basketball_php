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

	public function uploadHeadImage() 
	{
		$file = $_FILES['image'];
		if (!(($file['type'] == 'image/jpeg') 
			||($file['type'] == 'image/jpg')
			||($file['type'] == 'image/png')
			)) {
			echo json_encode(new ResponseModel(null, '文件必须是png、jpeg或jpg格式', 1));
		    return;
		}
		elseif ($file['size'] > 1048576) {
			echo json_encode(new ResponseModel(null, '图片必须小于1Mb',1));
			return;
		}
		elseif ($file['error']) {
			echo json_encode(new ResponseModel(null, $file['error'],1));
			return;
		}
		$fileName = time().'.'.substr($file['type'], 6);
		move_uploaded_file($file['tmp_name'], './headImages/'.$fileName);
		$response = new ResponseModel(array('headImageUrl' => 'http://'.$_SERVER['SERVER_NAME'].':'.$_SERVER["SERVER_PORT"].'/headImages/'.$fileName), '成功',0);
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