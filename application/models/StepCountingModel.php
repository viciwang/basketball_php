<?php 

require_once 'application/models/BB_Model.php';
/**
* 
*/
	function result_map_string_to_int($val)
	{
		$val['stepCount'] = intval($val['stepCount']);
		return $val;
	}

class StepCountingModel extends BB_Model
{
	
	function __construct()
	{
     	parent::__construct();
		$this->load->database();
	}

	public function getAllStepCounting($userId = '1')
	{
		$query = $this->db->query("SELECT * FROM StepCounting where uid = $uid");
		return $query->result_array();
	}

	public function getAverage()
	{
		$checkResult = $this->httpHeaderAuth();
		if (get_class($checkResult) === 'ResponseModel') {
			return $checkResult;
		}
		$uid = $checkResult->uid;
		$query = $this->db->query("SELECT date , stepCount FROM StepCountDailyList WHERE uid = $uid");
		$resultArray = $query->result_array();
		$total = 0;
		foreach ($resultArray as $record) {
			$total = $total + $record['stepCount'];
		}
		$count = count($resultArray) == 0 ? 1 : count($resultArray);
		$total = intval($total/$count);
		return new ResponseModel(array('totalCount'=>$total),'成功',0);
	}

	public function getHistory()
	{
		$checkResult = $this->httpHeaderAuth();
		if (get_class($checkResult) === 'ResponseModel') {
			return $checkResult;
		}
		$uid = $checkResult->uid;
		$date = $this->input->post("date");
		$query = $this->db->query("SELECT date , stepCount FROM StepCountDailyList WHERE uid = $uid AND date >= \"$date\" ORDER BY date DESC");

		$resultArray = $query->result_array();
		$mapArray = array_map('result_map_string_to_int', $resultArray);
		$retrurnArray = array();
		$temArray = array();
		$currentRecord;
		// 按月份分类
		for ($index=0; $index < count($mapArray); $index++) { 
			$currentRecord = $mapArray[$index];
			array_push($temArray, $currentRecord);
			if (strcmp(substr($currentRecord['date'], 8),'01') == 0) {
				array_push($retrurnArray, array('month'=>substr($currentRecord['date'], 0, 7),'dayRecords'=>$temArray));
				$temArray = array();
			}
		}
		if(!empty($temArray)) {
			array_push($retrurnArray, array('month'=>substr($currentRecord['date'], 0, 7),'dayRecords'=>$temArray));
		}
		return new ResponseModel($retrurnArray,'成功',0);
	}

	public function getRanking()
	{
		$checkResult = $this->httpHeaderAuth();
		if (get_class($checkResult) === 'ResponseModel') {
			return $checkResult;
		}
		$uid = $checkResult->uid;
		$query = $this->db->query("SELECT a.uid , a.stepCount, b.nickName, b.headImageUrl,b.personalDescription FROM StepCountDailyList a INNER JOIN User b ON a.uid = b.uid WHERE a.date = CURDATE() ORDER BY a.stepCount DESC");
		$resultArray = array_map('result_map_string_to_int',$query->result_array());
		$myRank;
		foreach ($resultArray as $key => $value) {
			$resultArray[$key]['rank'] = $key + 1;
			if(strcmp($resultArray[$key]['uid'],$uid) == 0) {
				$myRank = $resultArray[$key];
			}
		}
		return new ResponseModel(array('myRank'=>$myRank,'ranks'=>$resultArray),'成功',0);
	}

	public function uploadStepData()
	{
		$checkResult = $this->httpHeaderAuth();
		if (get_class($checkResult) === 'ResponseModel') {
			return $checkResult;
		}
		$uid = $checkResult->uid;
		$stepCount = $this->input->post('stepCount');
		$startTime = $this->input->post('startTime');

		$result = $this->db->query("SELECT * FROM StepCounting WHERE uid = $uid AND startTime = \"$startTime\"");
		$query;
		if ($result->row() == NULL) {
			$a = array('uid'=>$uid,'stepCount'=>$stepCount,'startTime'=>$startTime);
			$query = $this->db->insert('StepCounting',$a);
		}
		else {
			$a = array('stepCount'=>$this->input->post("stepCount"));
			$query = $this->db->update('StepCounting',$a,"uid = $uid AND startTime = \"$startTime\"");
		}
		if ($query === FALSE) {
			return new ResponseModel(NULL,'数据上传失败',1);
		}
		else {
			return new ResponseModel(NULL,'OK',0);
		}
	}
}
 ?>