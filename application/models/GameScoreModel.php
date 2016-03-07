<?php
error_reporting(E_ALL ^ E_DEPRECATED);
/**
* Game Score Model
*/

class GameScoreModel extends CI_Model
{
	private $dblink;
	private $db_select;
	function __construct(){

		parent::__construct();
		// $this->dblink = mysql_connect('localhost','root','');
		
		// $this->db_select = mysql_select_db("sport",$this->dblink);
		// if (!$this->db_select) {
		// 	echo "call game<br>";
		// 	return nil;
		// } 
	}

	public function getGameInformation() {


	}

	public function deleteAllRows() {
		$this->db->empty_table('gameScore');
	}

	public function getLastRow() {
		$this->db->select_max('date');
		$query = $this->db->get('gameScore');
		$result = $query->result();
		echo $result[0]->date.'<br>'.count($result);
		date_default_timezone_set('PRC');
		echo date('Y-m-d H:i:s',time());
	}

	public function getDateGames($date) {

		date_default_timezone_set('PRC');
		$today = date('Y-m-d',$date);
		$tomorrow = date("Y-m-d",($date+3600*24));
		$where = "gamesDate>=\"$today\" AND gamesDate < \"$tomorrow\"";

		$this->db->where($where);
		$query = $this->db->get('gameScore');
		return $query->result();
	}

	public function updateGames($games, $date) {

		$oldData = $this->getDateGames($date);

		if (!$oldData || count($oldData)==0) {
			$this->insertGames($games);
			return true;
		}
		if(count($oldData)!=count($games)) {
			//echo "not equal! new count = ".count($games)." and old count = ".count($oldData);
			return false;
		}
		$isUpdata = false;
		foreach ($games as $updateGame) {
			$isUpdata = false;
			foreach ($oldData as $oldGame) {
				if (!strcmp($updateGame->hostTeam, $oldGame->hostTeam)) {
					$updateGame->gameId = $oldGame->gameId;
					$isUpdata = true;
				}
			}
			if (!$isUpdata) {
				return false;
			}
		}
		$this->db->update_batch('gameScore',$games,'gameId');
		return true;
	}

	public function insertGames($games) {
		$this->db->insert_batch('gameScore',$games);
	}

}


?>