<?php 
require_once 'application/models/ResponseModel.php';
require_once 'application/vendor/UUID.php';
/**
* 
*/
class BB_Model extends CI_Model
{
	
	function __construct()
	{
		parent::__construct();
		$this->load->database();
	}

	public function getUserByToken($token)
	{
		return $this->getUserByType($token,1);
	}

	public function getUserByUid($uid)
	{
		return $this->getUserByType($uid,2);
	}

	public function getUserByEmail($email)
	{
		return $this->getUserByType($email,3);
	}

	// 
	public function getUserByType($string , $type = 1)
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

	public function httpHeaderAuth ()
	{
		$header = getallheaders();
		if ($header == NULL) {
			return ResponseModel(nil,"header中没有带token",1);
		}
		$token = $header['token'];
		$uid = $header['uid'];
		$checkResult = $this->checkToken($uid,$token);
		return $checkResult;
	}

	private function checkToken($uid,$token)
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
}

?>
