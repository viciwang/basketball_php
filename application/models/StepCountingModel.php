<?php 

require_once 'application/models/BB_Model.php';
/**
* 
*/
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
		return new ResponseModel(array('steps'=>$resultArray),'成功',0);
	}
}
 ?>