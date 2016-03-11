<?php 

include 'ResponseModel.php';
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
		$token = uniqid('', true);
		$query = $this->db->query("SELECT * FROM User where uid = ".$this->input->post('uid'));
		$queryResult = $query->result_array();
		if (empty($queryResult) == false)
		{
			return new ResponseModel(null,"该号码已被注册",1);
		}

		$user = array(
			'uid' => $this->input->post('uid'),
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
}
?>