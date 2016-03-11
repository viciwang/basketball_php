<?php 
/**
* 
*/
class StepCountingModel extends CI_Model
{
	
	function __construct()
	{
     	parent::__construct();
		$this->load->database();
	}

	public function getAllStepCounting($userId = '1')
	{
		$query = $this->db->query("SELECT * FROM StepCounting where userId = $uid");
		return $query->result_array();
	}
}
 ?>