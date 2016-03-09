<?php
/**
 * 数据库迁移model
 */
 class Migrate_model extends CI_Model
 {
 	
 	public function __construct()
 	{
 		$this->load->database();
 		$this->load->helper('date');
 	}

 	public function resetStepCountingData() 
 	{
 		$this->db->empty_table('StepCounting');
 		for ($userCount=10; $userCount < 20; $userCount++) { 
 			$userId = '10000000'.$userCount;
 			$startDate = date_create('2015-10-01 00:00:00');
 			for ($timeStamp=1; $timeStamp <= 2400; $timeStamp++) { 
 				$data = array(
 					'startDate' => date_timestamp_get($startDate),
 					'stepCount' => rand(0, 50000),
 					'userId' => $userId
 					);
                $this->db->insert('StepCounting', $data);
 				date_add($startDate,date_interval_create_from_date_string("1 hours"));
 			}
 			echo "insert user:".$userId.' success'."\n";
 		}
 		// echo date_format($date,'Y-m-d H:i:s');
 	}
 } 
 ?>