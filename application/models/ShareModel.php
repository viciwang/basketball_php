<?php
require_once 'application/models/BB_Model.php';
class ShareComment {
	public $commentId;
	public $userId;
	public $shareId;
	public $publicDate;
	public $content;
	public $isReply;
	public $replyUserName;
	public $replyUserId;
	public $sourceIP;
}
class ShareApprove {
	public $approveId;
	public $shareId;
	public $publicDate;
	public $userId;
	public $sourceIP;
}
class ShareModel extends BB_Model
{
	protected $default_page_size = 20;
	protected $default_img_rigion_prepath = 'http://localhost:8081/shareImages/rigion/rigion_';
	protected $default_img_thu_prepath = 'http://localhost:8081/shareImages/thumbnail/thumbnail_';

	// attribute
	public $shareId;
	public $userId;
	public $publicDate;
	public $approveCount;
	public $commentCount;
	public $imageName;
	public $content;
	public $sourceIP;
	public $locationName;
	public $locationPoint;

	// func
	public function deleteAllRows() {
		$this->db->empty_table('Share');
	}
	public function insertShareEntity($entity) {
		if ($this->db->insert('Share',$entity) == false) {
			return new ResponseModel(null,"数据库插入失败",403);
		} else {
			return true;
		}
	}
	private function getShareEntity($pageIdx = 0, $pageSize = 20, $user, $isUserShare = false) {
		if ($pageIdx < 0) {
			return new ResponseModel(null,"无效的页数",0);
		}

		if ($isUserShare == true) {
			$this->db->where("userId = \"$user->uid\"");
		}
		$this->db->order_by('publicDate','DESC');
		$this->db->limit($pageSize,$pageIdx*$pageSize);
		$query = $this->db->get('Share');
		$result = $query->result();
		$resultArray = array();
		foreach ($result as $info) {
			$infoArray = json_decode(json_encode($info),true);;
			$infoArray['nickName'] = $user->nickName;
			$infoArray['headImageUrl'] = $user->headImageUrl;
			$infoArray['images'] = $this->convertImageName($info->imageName);
			$infoArray['isApprove'] = ($this->getApprove($infoArray['shareId'], $user->uid)==NULL)?0:1;
			// array_push($info, 'nickName'=>$user->nickName);
			// array_push($info, 'headImageUrl'=>$user->headImageUrl);
			// array_push($info, 'images'=>$this->convertImageName($info['imageName']));
			// 去掉字段，不用返回客户端
			
			// 判断是否为自己的分享
			if ($info->userId == $user->uid) {
				$infoArray['isUserShare'] = '1';
			} else {
				$infoArray['isUserShare'] = '0';
			}
			unset($infoArray['imageName']);
			unset($infoArray['sourceIP']);
			array_push($resultArray, $infoArray);
		}
		return $resultArray;
	}
	public function getShareByUser($pageIdx = 0, $pageSize = 20, $user) {
		return $this->getShareEntity($pageIdx, $pageSize, $user, true);
	}
	public function getAllShare($pageIdx = 0, $pageSize = 20, $user) {
		return $this->getShareEntity($pageIdx, $pageSize, $user, false);
	}
	public function deleteShare($shareId, $user) {
		$this->db->where("shareId = \"$shareId\" AND userId = \"$user->uid\"");
		$query = $this->db->get('Share');
		if($query->row() == NULL)
		{
			return new ResponseModel(null,"删除分享失败",0);
		}
		$this->db->where("shareId = \"$shareId\" AND userId = \"$user->uid\"");
		$this->db->delete('Share');
		return '';
	}
	private function getShareById($shareId)
	{
		$this->db->where("shareId = \"$shareId\"");
		$query = $this->db->get('Share');
		return $query->row();
	}
	private function convertImageName($imageNameStr) 
	{
		if (empty($imageNameStr)) {
			return array('rigion'=>array(),
						'thumbnail'=>array());
		}
		$imageNames = explode(',', $imageNameStr);
		$rigionArray = array();
		$thumbnailArray = array();

		foreach ($imageNames as $ina) {
			array_push($rigionArray, $this->default_img_rigion_prepath.$ina);
			$path = pathinfo($ina);
			array_push($thumbnailArray, $this->default_img_thu_prepath.$path['filename'].'.jpg');
		}
		return array('rigion'=>$rigionArray,
						'thumbnail'=>$thumbnailArray);
	}

	// comment
	public function insertComment($comment)
	{
		$shareInfo = $this->getShareById($comment->shareId);
		if ($shareInfo == NULL) {
			return new ResponseModel(null,"评论不存在",0);
		}
		if (mb_strlen($comment->content,'utf8')>200 || mb_strlen($comment->content,'utf8')<20) {
			return new ResponseModel(null,"评论长度必须大于20个字，小于200个字",0);
		}
		if(intval($comment->isReply) == 1) 
		{
			$replyUser = $this->getUserByUid($comment->replyUserId);
			if ($replyUser == NULL) {
				return new ResponseModel(null,"没有此回复用户",0);
			}
			$comment->replyUserName = $replyUser->nickName;
		}
		if ($this->db->insert('ShareComment',$comment) == false) {
			return new ResponseModel(null,"数据库插入失败",403);
		} else {
			//修改评论数量
			$this->db->set('commentCount', $shareInfo->commentCount+1);
			$this->db->where("shareId = \"$shareInfo->shareId\"");
			$this->db->update('Share');
			//返回最新评论
			return $this->getComment($shareInfo->shareId);
		}
	}
	public function getComment($pageIdx = 0, $pageSize = 20, $shareId) {

		if ($pageIdx < 0) {
			return new ResponseModel(null,"无效的页数",0);
		}
		if ($pageSize < 1) {
			return new ResponseModel(null,"无效的页面大小",0);
		}
		$this->db->order_by('publicDate', 'DESC');
		$this->db->limit($pageSize,$pageIdx*$pageSize);
		$this->db->where("shareId = \"$shareId\"");
		$query = $this->db->get('ShareComment');
		return $query->result();
	}
	public function getApprove($shareId, $userId) {
		
		$this->db->where("shareId = \"$shareId\" AND userId = \"$userId\"");
		$query = $this->db->get('ShareApprove');
		return $query->row();
	}
	public function insertApprove($approveInfo) {
		$shareInfo = $this->getShareById($approveInfo->shareId);
		if ($shareInfo == NULL) {
			return new ResponseModel(null,"没有此分享",0);
		}
		$approveResult = $this->getApprove($approveInfo->shareId, $approveInfo->userId);
		if ($approveResult == NULL) {
			$this->db->insert('ShareApprove', $approveInfo);
			// 更新share 的 点赞数
			$this->db->set('approveCount', $shareInfo->approveCount+1);
			$this->db->where("shareId = \"$shareInfo->shareId\"");
			$this->db->update('Share');
			return array('approveCount' => $shareInfo->approveCount+1,
					'isApprove' => 1);
		}
		return array('approveCount' => $shareInfo->approveCount,
					'isApprove' => 1);
	}
	public function deleteApprove($approveInfo) {
		$shareInfo = $this->getShareById($approveInfo->shareId);
		if ($shareInfo == NULL) {
			return new ResponseModel(null,"没有此分享",0);
		}
		$approveResult = $this->getApprove($approveInfo->shareId, $approveInfo->userId);
		if ($approveResult != NULL) {
			$this->db->where("approveId = \"$approveResult->approveId\"");
			$this->db->delete('ShareApprove');
			// 更新share 的 点赞数
			$this->db->set('approveCount', $shareInfo->approveCount-1);
			$this->db->where("shareId = \"$shareInfo->shareId\"");
			$this->db->update('Share');
			return array('approveCount' => $shareInfo->approveCount-1,
					'isApprove' => 0);
		}
		return array('approveCount' => $shareInfo->approveCount,
					'isApprove' => 0);
	}
}

?>