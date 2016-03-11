<?php 

include 'ResponseModel.php';
include 'application/vendor/UUID.php';
/**
* 
*/
class UserModel extends CI_Model
{
	
	function __construct()
	{
		parent::__construct();
		$this->load->database();
	}

	public function addUser() 
	{
		$date = date_create();
		$token = UUID::v4();
		$query = $this->db->query("SELECT * FROM User where uid = ".$this->input->post('phone'));
		$queryResult = $query->result_array();
		if (empty($queryResult) == false)
		{
			return new ResponseModel(null,"该号码已被注册",1);
		}

		$user = array(
			'uid' => $this->input->post('phone'),
			'nickName' => $this->input->post('nickName'),
			'password' => $this->input->post('password'),
			'headImageUrl' => $this->input->post('headImageUrl'),
			'city' => $this->input->post('city'),
			'token' => $token,
			'lastLoginTime' => date_format($date,'Y-m-d H:i:s')
			);
		if ($this->db->insert('User',$user) === false) 
		{
			return new ResponseModel(null,"神奇的错误",1);
		}

		unset($user['password']);
		return new ResponseModel($user,"注册成功",0);
	}

	public function login()
	{
		$query = $this->db->query("SELECT * FROM User where uid = ".$this->input->post('phone'));
		$user = $query->row();

		if (empty($user)) {
			return new ResponseModel(null,"手机号码错误",1);
		}
		else if (strcmp($user->password, $this->input->post('password')) != 0) {
			return new ResponseModel(null,"密码错误",1);
		}

		$date = date_create();
		$token = substr(UUID::v4(),0,23);
		$update = array(
			'lastLoginTime' => date_format($date, 'Y-m-d H:i:s'),
			'token' => $token
			);
		$this->db->update('User',$update, "uid = $user->uid");

		$user->lastLoginTime = $update['lastLoginTime'];
		$user->token = $token;
		unset($user->password);
		return new ResponseModel($user,"登录成功",0);
	}
}
?>