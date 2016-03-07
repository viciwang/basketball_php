<?php
error_reporting(E_ALL ^ E_DEPRECATED);
/**
* 
*/
class GameInformation 
{
	public $gameId;
	public $hostTeam;
	public $guestTeam;

	public $hostScore = 0;
	public $guestScore = 0;
	public $hostTeamWin = 0;

	//status = -1:未开始 0:进行中 1:已经结束
	public $status = 0;
	public $statusDesc ;

	public $startTime ;
	public $processTime;
	public $lastModifyDate;
	public $gamesDate;
	function __construct()
	{
		date_default_timezone_set('PRC');
		$this->lastModifyDate = date('Y-m-d H:i:s',time());
	}
}
/**
* 比分解析类
*/
class GameScoreManager extends CI_Controller
{
	
	private $link = 'http://g.hupu.com/nba/';
	private $db_manager;



	public function getGameScore($date) {

		date_default_timezone_set('PRC');

		if (empty($date)) {
			$date = date('Y-m-d');
		}
		$this->load->model('gamescoremodel', 'privateModel');
		$oldGames = $this->privateModel->getDateGames(strtotime($date));

		$code = 101;
		$msg = '';
		$result = array();
		if ($this->shouldUpdateGames($oldGames)) {
			$games = $this->fetchGameScore($date);
			if ($games && count($games)) {

				foreach ($games as $info) {
					$info->gamesDate = $date;
				}

				
				if ($this->privateModel) {

					$this->privateModel->updateGames($games, strtotime($date));
					$result = $games;

				} else {
					$msg = 'save data to sql failure!';
					$code = 402;
				}
			}
		} else {
			$msg = 'not modify !';
			$result = $oldGames;
		}
		
		$resultArray = array('code' => $code, 'msg' => $msg, 'data' => $result);
		return json_encode($resultArray);
	}

	public function shouldUpdateGames($games) {

		// 如果还未获取过相应函数，那么更新
		if ( empty($games) || count($games)==0) {
			return true;
		}

		$gameTime = $games[0]->gamesDate;
		date_default_timezone_set('PRC');
		// 如果比赛不是当前日期举行，则不更新
		if (date('Y-m-d',strtotime($gameTime))!=date('Y-m-d',time())) {
			return false;
		}

		$result = false;
		foreach ($games as $info) {
			// 如果有部分比赛未结束，那么更新
			if ($info->status!=1) {
				$result = true;
				break;
			}
		}

		return $result;
	}

	public function createDOM($link) {

		$html = file_get_contents($link);
		$source = mb_convert_encoding($html, 'HTML-ENTITIES', 'utf-8');

		// 预处理 HTML 标签，剔除冗余的标签等
		$source = $this->preparSource($source);

		$DOM = 	new DOMDocument('1.0', 'utf-8');
		try {
            //libxml_use_internal_errors(true);
            // 会有些错误信息，不过不要紧 :^)
            if (!@$DOM->loadHTML('<?xml encoding="'.'utf-8'.'">'.$source)) {
                throw new Exception("Parse HTML Error!");
            }

            foreach ($DOM->childNodes as $item) {
                if ($item->nodeType == XML_PI_NODE) {
                    $DOM->removeChild($item); // remove hack
                }
            }

            // insert proper
            $DOM->encoding = 'utf-8';
        } catch (Exception $e) {
            // ...
        }
        return $DOM;
	}

	/**
     * 预处理 HTML 标签，使其能够准确被 DOM 解析类处理
     *
     * @return String
     */
    private function preparSource($string) {
        // 剔除多余的 HTML 编码标记，避免解析出错
        preg_match("/charset=([\w|\-]+);?/", $string, $match);
        if (isset($match[1])) {
            $string = preg_replace("/charset=([\w|\-]+);?/", "", $string, 1);
        }

        // Replace all doubled-up <BR> tags with <P> tags, and remove fonts.
        $string = preg_replace("/<br\/?>[ \r\n\s]*<br\/?>/i", "</p><p>", $string);
        $string = preg_replace("/<\/?font[^>]*>/i", "", $string);

        // @see https://github.com/feelinglucky/php-readability/issues/7
        //   - from http://stackoverflow.com/questions/7130867/remove-script-tag-from-html-content
        $string = preg_replace("#<script(.*?)>(.*?)</script>#is", "", $string);

        return trim($string);
    }

    private function convertGameSatus($status) {

    	if (!strcmp(gettype(strpos($status, "未开始")),"integer")) {
    		return -1;
    	} else if (!strcmp(gettype(strpos($status, "已结束")),"integer")) {
    		return 1;
    	} else {
    		return 0;
    	}
    }

	public function fetchGameScore($date) {

		$gameArray = array();
		$link = $this->link.$date;
		$DOM = $this->createDOM($link);

		$xpath = new DOMXPath($DOM);
		$divs = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), 'gamecenter_content_l')]//*[@class='team_vs']");
		if ($divs) {
			foreach ($divs as $game) {
				$target = new DOMDocument;
				$target->appendChild($target->importNode($game,true));

				$gameInfo = new GameInformation();

				$xpath = new DOMXPath($target);
				$firstTeam = $xpath->query("//div[@class='team_vs_a_1 clearfix']//div[@class='txt']/span/a");
				$secondTeam = $xpath->query("//div[@class='team_vs_a_2 clearfix']//div[@class='txt']/span/a");

				$gameInfo->hostTeam = $firstTeam[0]->nodeValue;
				$gameInfo->guestTeam = $secondTeam[0]->nodeValue;

				$status = $xpath->query("//div[@class='team_vs_b']//span[@class='b']");
				$time = $status[0]->childNodes[0];

				$gameInfo->statusDesc = str_replace(' ', '', $status[0]->nodeValue);
				$gameInfo->status = $this->convertGameSatus($gameInfo->statusDesc);

				if ($gameInfo->status != -1) {
					
					$firstTeamScore = $xpath->query("//div[@class='team_vs_a_1 clearfix']//div[@class='txt']/span");
					$secondTeamScore = $xpath->query("//div[@class='team_vs_a_2 clearfix']//div[@class='txt']/span");

					$gameInfo->hostScore = $firstTeamScore[0]->nodeValue;
					$gameInfo->guestScore = $secondTeamScore[0]->nodeValue;

					$gameInfo->hostTeamWin = (intval($gameInfo->hostScore)>intval($gameInfo->guestScore))?1:0;

					$processTime = preg_replace('# #','',$time->nodeValue);
					$gameInfo->processTime = $processTime;
				} else {
					$startTime = preg_replace('# #','',$time->nodeValue);
					$gameInfo->startTime = NULL;//$startTime;
				}

				
				array_push($gameArray, $gameInfo);
			}
		}
		return $gameArray;
	}
}

?>