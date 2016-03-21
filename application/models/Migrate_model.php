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
 		$queryString = "SELECT * FROM User";
		$query = $this->db->query($queryString);
		$users =  $query->result_array();
		$this->db->empty_table('StepCounting');
		$this->db->empty_table('StepCountDailyList');
		// echo json_encode($users);
		foreach ($users as $user) {
			echo json_encode($user);
			$uid = $user['uid'];
 			$startTime = date_create('2016-02-01 00:00:00');

		     // 插入一些运动数据
 			for ($timeStamp=0; $timeStamp < 100; $timeStamp++) { 
 				$total = 0;
 				for ($index=0; $index < 24; $index++) {
 				$randNum = rand(0, 800);
 				$total += $randNum;
 				$data = array(
 					'startTime' => date_format($startTime,'Y-m-d H:i:s'),
 					'stepCount' => $randNum,
 					'uid' => $uid
 					);
                $this->db->insert('StepCounting', $data);
 				date_add($startTime,date_interval_create_from_date_string("1 hours"));
 				}

 				$d = array(
 					'uid'=>$uid,
 					'date'=>date_format(date_sub($startTime,date_interval_create_from_date_string("1 hours")),'Y-m-d'),
 					'stepCount'=>$total
 					);
 				$this->db->insert('StepCountDailyList',$d);
 			}
 			echo "insert user:".$uid.' success'."\n";
		}

 		// echo date_format($date,'Y-m-d H:i:s');

 	}
 } 
 ?>