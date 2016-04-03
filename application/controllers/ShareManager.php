<?php
error_reporting(0);
require 'application/vendor/ImageFactory.php';
class ShareManager extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->model('shareModel');
	}
	private function imageSave($simages) 
	{	
		if (empty($simages)) {
			return '';
		}
		$idx = 1;
		$imgFac = new ImageFactory();
		$imgNames = array();
		foreach ($simages as $img) {
			if(!(($img['type'] == 'image/jpeg') 
			||($img['type'] == 'image/jpg')
			||($img['type'] == 'image/png')
			)) {
				return new ResponseModel(null, '不支持的图片类型', 0);
			}
			// if ($img['size'] > 1024*1024) {
			// 	return new ResponseModel(null, '图片必须小于1M', 0);
			// }
			if ($img['error']) {
				return new ResponseModel(null, $img['error'], 0);
			}

			$imgSeq = time()."_".$idx;
			// rigion+time+idx+extendName
			$rigionName = "rigion_"."$imgSeq.".substr($img['type'], 6);
			$thumbnailName = "thumbnail_".$imgSeq.".jpg";
			// path
			$rigionPath = './shareImages/rigion/'.$rigionName;
			$thumbnailPath = './shareImages/thumbnail/'.$thumbnailName;
			//save rigion image
			move_uploaded_file($img['tmp_name'], $rigionPath);
			// build thumbnail
			if ($imgFac->resizeImage($rigionPath, $thumbnailPath, 1, 200, 200) == FALSE) {
				return new ResponseModel(null, '图片缩放出错', 0);
			}
			array_push($imgNames, "$imgSeq.".substr($img['type'], 6));
			$idx++;
		}
		if (!empty($imgNames)) {
			$imgNames = implode(',', $imgNames);
			// echo "$imgNames";
		} else {
			$imgNames = '';
		}
		return $imgNames;
	}
	public function addShare() {

		// 1.check user status
		$check_result = $this->shareModel->httpHeaderAuth();
		if (get_class($check_result) == 'ResponseModel') {
			
			echo json_encode($check_result,JSON_UNESCAPED_UNICODE);
			return;
		}

		// 2. save image
		$imgNo = intval($this->input->post('imgCount'));
		$imgs = array();
		for ($i=1; $i<=$imgNo ; $i++) { 
			array_push($imgs, $_FILES['img_'.$i]);
		}

		$img_result = $this->imageSave($imgs);
		if (get_class($img_result) == 'ResponseModel') {
			echo json_encode($img_result,JSON_UNESCAPED_UNICODE);
			return;
		}

		// 3.create model
		$shareInfo = new ShareModel();
		$shareInfo->userId = $check_result->uid;
		$shareInfo->imageName = $img_result;
		// 3.1 convert datetime
		//echo $this->input->post('publicDate');
		date_default_timezone_set('PRC');
		$timestamp = strtotime($this->input->post('publicDate'));
		$date = date('Y-m-d H:i:s',$timestamp);
		$shareInfo->publicDate = $date;

		$shareInfo->content = $this->input->post('content');
		if (empty($shareInfo->content)) {
			echo json_encode(new ResponseModel(null,"内容为空",0),JSON_UNESCAPED_UNICODE);
		}
		$shareInfo->sourceIP = $_SERVER['REMOTE_ADDR'];
		$shareInfo->locationName = $this->input->post('locationName');
		$shareInfo->approveCount = 0;
		$shareInfo->commentCount = 0;
		// 4.insert
		$insert_result = $this->shareModel->insertShareEntity($shareInfo);
		if (get_class($insert_result) == 'ResponseModel') {
			echo json_encode($check_result,JSON_UNESCAPED_UNICODE);
			return;
		}
		echo json_encode(new ResponseModel(null,"分享成功",200),JSON_UNESCAPED_UNICODE);
	}
	// 所有分享
	public function getShareInfo()
	{
		// $this->shareModel->deleteAllRows();
		// return;
		$pageIdx = intval($this->input->get('pageIdx'));
		$pageSize = intval($this->input->get('pageSize')?:20);
		// 1.check user status
		$check_result = $this->shareModel->httpHeaderAuth();
		if (get_class($check_result) == 'ResponseModel') {
			echo json_encode($check_result);
			return;
		}
		// 2.get entity
		$query_result = $this->shareModel->getAllShare($pageIdx, $pageSize, $check_result);
		echo json_encode(new ResponseModel($query_result,"获取分享成功",200),JSON_UNESCAPED_UNICODE);
	}
	// 用户分享
	public function getUserShare() {
		$pageIdx = intval($this->input->get('pageIdx'));
		$pageSize = intval($this->input->get('pageSize')?:20);
		// 1.check user status
		$check_result = $this->shareModel->httpHeaderAuth();
		if (get_class($check_result) == 'ResponseModel') {
			echo json_encode($check_result);
			return;
		}
		// 2.get entity
		$query_result = $this->shareModel->getShareByUser($pageIdx, $pageSize, $check_result);
		echo json_encode(new ResponseModel($query_result,"获取分享成功",200),JSON_UNESCAPED_UNICODE);
	}
	// 删除分享
	public function deShare() {
		// 1.check user status
		$check_result = $this->shareModel->httpHeaderAuth();
		if (get_class($check_result) == 'ResponseModel') {
			echo json_encode($check_result, JSON_UNESCAPED_UNICODE);
			return;
		}
		$deleteResult = $this->shareModel->deleteShare($this->input->get('shareId'), $check_result);
		if (get_class($deleteResult) == 'ResponseModel') {
			echo json_encode($deleteResult, JSON_UNESCAPED_UNICODE);
			return;
		}
		echo json_encode(new ResponseModel($deleteResult,"删除分享成功",200),JSON_UNESCAPED_UNICODE);
	}
	// comment
	public function comment() {
		// 1.check user status
		$check_result = $this->shareModel->httpHeaderAuth();
		if (get_class($check_result) == 'ResponseModel') {
			echo json_encode($check_result);
			return;
		}
		// 2.setup comment model
		$comment = new ShareComment();
		$comment->userId = $check_result->uid;
		$comment->shareId = $this->input->post('shareId');
		// 2.1 convert date time
		date_default_timezone_set('PRC');
		$timestamp = strtotime($this->input->post('publicDate'));
		$date = date('Y-m-d H:i:s',$timestamp);
		$comment->publicDate = $date;

		$comment->content = $this->input->post('content');
		$comment->isReply = intval($this->input->post('isReply'));
		$comment->replyUserId = $this->input->post('replyUserId');
		$comment->replyUserName = $this->input->post('replyUserName');
		if ($comment->isReply == 0) {
			$comment->replyUserId = "无";
			$comment->replyUserName = "无";
		}
		$comment->sourceIP = $_SERVER['REMOTE_ADDR'];

		// 3. insert
		$insertResult = $this->shareModel->insertComment($comment);
		// 如果出错
		if (get_class($insertResult) == 'ResponseModel') {
			echo json_encode($insertResult,JSON_UNESCAPED_UNICODE);
			return;
		}
		// 正常返回
		echo json_encode(new ResponseModel($insertResult,"评论成功",200),JSON_UNESCAPED_UNICODE);
	}
	public function getComment()
	{
		// $this->shareModel->deleteAllRows();
		// return;
		$pageIdx = intval($this->input->get('pageIdx'));
		$pageSize = intval($this->input->get('pageSize')?:20);
		$shareId = $this->input->get('shareId');
		$comments = $this->shareModel->getComment($pageIdx, $pageSize, $shareId);
		// 如果出错
		if (get_class($comments) == 'ResponseModel') {
			echo json_encode($comments,JSON_UNESCAPED_UNICODE);
			return;
		}
		echo json_encode(new ResponseModel($comments,"获取成功",200),JSON_UNESCAPED_UNICODE);
	}
	public function approve()
	{
		// 1.check user status
		$check_result = $this->shareModel->httpHeaderAuth();
		if (get_class($check_result) == 'ResponseModel') {
			echo json_encode($check_result);
			return;
		}

		$approve = new ShareApprove();
		$approve->shareId = $this->input->post('shareId');
		$approve->publicDate = $this->input->post('publicDate');
		$approve->userId = $check_result->uid;
		$approve->sourceIP = $_SERVER['REMOTE_ADDR'];

		$result = $this->shareModel->insertApprove($approve);
		// 如果出错
		if (get_class($result) == 'ResponseModel') {
			echo json_encode($result,JSON_UNESCAPED_UNICODE);
			return;
		}
		echo json_encode(new ResponseModel($result,"点赞成功",200),JSON_UNESCAPED_UNICODE);
	}
	public function deapprove()
	{
		// 1.check user status
		$check_result = $this->shareModel->httpHeaderAuth();
		if (get_class($check_result) == 'ResponseModel') {
			echo json_encode($check_result);
			return;
		}

		$approve = new ShareApprove();
		$approve->shareId = $this->input->post('shareId');
		$approve->userId = $check_result->uid;

		$result = $this->shareModel->deleteApprove($approve);
		// 如果出错
		if (get_class($result) == 'ResponseModel') {
			echo json_encode($result,JSON_UNESCAPED_UNICODE);
			return;
		}
		echo json_encode(new ResponseModel($result,"取消赞成功",200),JSON_UNESCAPED_UNICODE);
	}
}
?>