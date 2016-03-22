<?php 

require_once 'application/models/BB_Model.php';
/**
* 
*/
	function get_history_result_map($val)
	{
		return array('stepCount'=> intval($val['stepCount']),'date'=> $val['date']);
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
		$query = $this->db->query("SELECT date , stepCount FROM StepCountDailyList WHERE uid = $uid ORDER BY date DESC");
		$resultArray = $query->result_array();
		$mapArray = array_map('get_history_result_map', $resultArray);
		$retrunArray = array();
		$temArray = array();

		// 按月份分类
		for ($index=0; $index < count($mapArray); $index++) { 
			$currentRecord = $mapArray[$index];
			array_push($temArray, $currentRecord);
			if (strcmp(substr($currentRecord['date'], 8),'01') == 0) {
				array_push($retrunArray, array('month'=>substr($currentRecord['date'], 0, 7),'dayRecords'=>$temArray));
				$temArray = array();
			}
		}
		return new ResponseModel($retrunArray,'成功',0);
	}

	public function getRanking()
	{
		$checkResult = $this->httpHeaderAuth();
		if (get_class($checkResult) === 'ResponseModel') {
			return $checkResult;
		}
		$uid = $checkResult->uid;
		$query = $this->db->query("SELECT a.uid , a.stepCount, b.nickName FROM StepCountDailyList a INNER JOIN User b ON a.uid = b.uid WHERE a.date = CURDATE() ORDER BY a.stepCount DESC");
		$resultArray = $query->result_array();
		return new ResponseModel(array('steps'=>$resultArray),'成功',0);
	}


}
 ?>