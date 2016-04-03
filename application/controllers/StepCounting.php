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

	public function average() 
	{
		$data = $this->stepCountingModel->getAverage();
		echo json_encode($data);
	}

	public function history()
	{
		$data = $this->stepCountingModel->getHistory();
		echo json_encode($data);
	}

	public function ranking()
	{
		$data = $this->stepCountingModel->getRanking();
		echo json_encode($data);
	}

	public function uploadStepData()
	{
		$data = $this->stepCountingModel->uploadStepData();
		echo json_encode($data);
	}
}
 ?>