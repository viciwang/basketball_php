<?php 

require_once 'application/models/ResponseModel.php';
require_once 'application/vendor/UUID.php';
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

	public function getUserByToken($token)
	{
		return $this->_getUserByType($token,1);
	}

	public function getUserByUid($uid)
	{
		return $this->_getUserByType($uid,2);
	}

	public function getUserByEmail($email)
	{
		return $this->_getUserByType($email,3);
	}

	// 
	private function _getUserByType($string , $type = 1)
	{
		$queryKey = 'token';
		switch ($type) {
			case 1: {
				$queryKey = 'token';
				break;
			}

			case 2: {
				$queryKey = 'uid';
				break;
			}

			case 3: {
				$queryKey = 'email';
				break;
			}
			default:
				break;
		}
		$queryString = "SELECT * FROM User where $queryKey = \"$string\" ";
		$query = $this->db->query($queryString);
		return  $query->row();
	}

	public function checkToken($uid,$token)
	{
		$user = $this->getUserByUid($uid);
		if ($user == NULL) {
			return new ResponseModel(null,"没有此用户",1);
		}
		elseif (strcmp($user->token, $token) != 0) {
			return new ResponseModel(null,"token已过期，请重新登录",99);
		}
		return $user;
	}

	public function addUser() 
	{
		$date = date_create();
		$token = UUID::v4();
		$queryResult = $this->getUserByEmail($this->input->post('email'));
		if ($queryResult != NULL)
		{
			return new ResponseModel(null,"该邮箱已被注册",1);
		}

		$email = $this->input->post('email');
		$codeRow = $this->db->query("SELECT * FROM VerifyCode WHERE email = \"$email\"")->row();
		if ($codeRow == NULL || ($codeRow != NULL && $codeRow->code != $this->input->post('verifyCode'))) {
			return new ResponseModel(null,"验证码错误",1);
		}

		// 获取编号
		$uidFile = fopen("application/models/CurrentUid.txt", "r") or die("Unable to open file!");
        $currentUid = fgets($uidFile);
        $currentUid = strval(++$currentUid);
        fclose($uidFile);

        $uidFile = fopen("application/models/CurrentUid.txt", "w") or die("Unable to open file!");
        fwrite($uidFile, $currentUid);
        fclose($uidFile);

		$user = array(
			'uid' => $currentUid,
			'email' => $this->input->post('email'),
			'nickName' => $email,
			'password' => $this->input->post('password'),
			'headImageUrl' => 'http://www.qqya.com/qqyaimg/allimg/100529/1_100529165618_29.jpg',
			'city' => '广州',
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
		$user = $this->getUserByEmail($this->input->post('email'));

		if ($user == NULL) {
			return new ResponseModel(null,"邮箱号码错误",1);
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
		unset($user->id);
		return new ResponseModel($user,"登录成功",0);
	}

	public function generateVerifyCode($email)
	{
		// if ($this->getUserByEmail($email) != NULL) {
		// 	return new ResponseModel(null, '该邮箱已被注册', 1);
		// }

		$codeRow = $this->db->query("SELECT * FROM VerifyCode WHERE email = \"$email\"")->row();
		$now = date_create();
		$code = rand(100000,999999);
		if ($codeRow == NULL) {
			$this->db->insert('VerifyCode', array(
				'email' => $email,
				'timestamp' => date_format($now, 'Y-m-d H:i:s'),
				'code' => $code 
				));
		}
		else {
			$update = array(
				'timestamp' => date_format($now, 'Y-m-d H:i:s'),
				'code' => $code 
				);
			$this->db->update('VerifyCode', $update ,"email = \"$email\" ");
		}
		return strval($code);

	}

	public function updateInfo()
	{
		$header = getallheaders();
		if ($header == NULL) {
			return ResponseModel(nil,"header中没有带token",1);
		}
		$token = $header['token'];
		$uid = $header['uid'];
		$checkResult = $this->checkToken($uid,$token);
		if (get_class($checkResult) === 'ResponseModel') {
			return $checkResult;
		}

		$user = $checkResult;
		$userInfo = array(
			'nickName' => $this->input->post('nickName'),
			'headImageUrl' => $this->input->post('headImageUrl'),
			'city' => $this->input->post('city'),
			);
		$this->db->update('User',$userInfo,"uid = \"$uid\"");
		$user->nickName = $this->input->post('nickName');
		$user->headImageUrl = $this->input->post('headImageUrl');
		$user->city = $this->input->post('city');
		unset($user->id);
		unset($user->password);
		return new ResponseModel($user,"更新成功",0);
	}
}
?>