<?php
require_once 'application/models/ResponseModel.php';
require 'application/vendor/Readability.inc.php';
require 'application/vendor/html2text-master/src/Html2Text.php';
class Article
{
	public $title = null;
	public $images = null;
	public $content = null;
	function __construct($title,$content,$images)
	{
		$this->title = $title;
		$this->content = $content;
		$this->images = $images;
	}
}
class ArticleManager extends CI_Controller
{
	
	function __construct()
	{
		parent::__construct();
		$this->load->model('ArticleModel');
	}
	function articleSource() {
		echo $_SERVER["REMOTE_ADDR"].'<br>';
		$this->load->model('ArticleModel','privateModel');
		$data = $this->privateModel->getArticleSource();
		echo json_encode(new ResponseModel($data,'',200),JSON_UNESCAPED_UNICODE);
	}

	function articleParse() {
		$url = $this->input->get('link');//'http://www.dgtle.com/article-13702-1.html';//'http://36kr.com/p/5043930.html';//
		$html = file_get_contents($url);
		$responseInfo = $http_response_header;
		$html_input_charset = 'gbk';
		foreach ($responseInfo as $loop) {
			//echo "$loop<br>";
			$range = strpos($loop, "charset");
		if($range !== false){
		$html_input_charset = trim(substr($loop, $range+8));
		//echo "$html_input_charset";
		}
		}

		$Readability     = new Readability($html, $html_input_charset); // default charset is utf-8
		$ReadabilityData = $Readability->getContent(); // throws an exception when no suitable content is found
		$html = new \Html2Text\Html2Text($ReadabilityData['content']);
		$ReadabilityData['content'] = $html->getText();
		$articleObject = new Article($ReadabilityData['title'],$ReadabilityData['content'],$ReadabilityData['images']);
		$results = array('code' => 101,
						 'msg' => '',
						 'data' => $articleObject );

		echo urldecode(json_encode($results,JSON_UNESCAPED_UNICODE));
	}
}
?>