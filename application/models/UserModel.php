<?php 

require_once 'application/models/ResponseModel.php';
require_once 'application/vendor/UUID.php';
require_once 'application/models/BB_Model.php';
/**
* 
*/
class UserModel extends BB_Model
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
		//删除验证码
		$this->db->delete('VerifyCode',"email = \"$email\"");
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

	public function resetPassword()
	{
		$queryResult = $this->getUserByEmail($this->input->post('email'));
		if ($queryResult == NULL)
		{
			return new ResponseModel(null,"该邮箱未注册",1);
		}

		$email = $this->input->post('email');
		$codeRow = $this->db->query("SELECT * FROM VerifyCode WHERE email = \"$email\"")->row();
		if ($codeRow == NULL || ($codeRow != NULL && $codeRow->code != $this->input->post('verifyCode'))) {
			return new ResponseModel(null,"验证码错误",1);
		}
		$this->db->delete('VerifyCode',"email = \"$email\"");
		$info = array('password'=>$this->input->post('password'));
		$this->db->update('User',$info,"uid = $queryResult->uid");
		return new ResponseModel(NULL,"成功",0);
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
		$checkResult = $this->httpHeaderAuth();
		if (get_class($checkResult) === 'ResponseModel') {
			return $checkResult;
		}

		$user = $checkResult;
		$uid = $user->uid;
		$userInfo = array(
			'nickName' => $this->input->post('nickName'),
			'personalDescription' => $this->input->post('personalDescription'),
			'city' => $this->input->post('city'),
			);
		$this->db->update('User',$userInfo,"uid = \"$uid\"");
		$user->nickName = $this->input->post('nickName');
		$user->personalDescription = $this->input->post('personalDescription');
		$user->city = $this->input->post('city');
		unset($user->id);
		unset($user->password);
		return new ResponseModel($user,"更新成功",0);
	}

	public function updateHeadImageUrl() 
	{
		$checkResult = $this->httpHeaderAuth();
		if (get_class($checkResult) === 'ResponseModel') {
			return $checkResult;
		}

		$uid = $checkResult->uid;

		$file = $_FILES['image'];
		if (!(($file['type'] == 'image/jpeg') 
			||($file['type'] == 'image/jpg')
			||($file['type'] == 'image/png')
			)) {
			return new ResponseModel(null, '文件必须是png、jpeg或jpg格式', 1);
		}
		elseif ($file['size'] > 1048576) {
			return new ResponseModel(null, '图片必须小于1Mb',1);
		}
		elseif ($file['error']) {
			return new ResponseModel(null, $file['error'],1);
		}
		$fileName = time().'.'.substr($file['type'], 6);
		move_uploaded_file($file['tmp_name'], './headImages/'.$fileName);

		//更新数据库
		$info = array('headImageUrl' => 'http://'.$_SERVER['SERVER_NAME'].':'.$_SERVER["SERVER_PORT"].'/headImages/'.$fileName);
		$this->db->update('User',$info,"uid = \"$uid\"");

		$response = new ResponseModel($info, '成功',0);
		return $response;
	}

	public function logout() 
	{
		$checkResult = $this->httpHeaderAuth();
		if (get_class($checkResult) === 'ResponseModel') {
			return $checkResult;
		}

		$uid = $checkResult->uid;
		$userInfo = array(
			'token' => substr(UUID::v4(),0,23)
			);
		$this->db->update('User',$userInfo,"uid = \"$uid\"");
		return new ResponseModel(NULL,"更新成功",0);
	}
}
?>