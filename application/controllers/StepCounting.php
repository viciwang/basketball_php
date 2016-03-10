<?php 
/**
* 
*/
class StepCounting extends CI_Controller
{
	
	function __construct()
	{
		parent::__construct();
		$this->load->model('stepCountingModel');
	}

	public function loadStatistics() 
	{
		$data = $this->stepCountingModel->getAllStepCounting('1000000010');
		echo json_encode($data);
	}
}
 ?>