<?php
class ArticleModel extends CI_Model
{
	
	function __construct()
	{
		parent::__construct();
		$this->load->database();
	}
	
	function getArticleSource() {
	
		$query = $this->db->get('Article');
		$result = $query->result();
		return $result;
	}
}
?>