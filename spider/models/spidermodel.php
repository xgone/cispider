<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class spidermodel extends MY_Model {

  const TBL_URLS = 'urls';        //URL 表
  const TBL_CONTENT = 'content';  //内容表

  public function __construct() {
    parent::__construct();
  }

  /**
   * [getUrls 获取目标列表地址]
   * @param string $url 列表地址
   * @param int $type 类型 默认1=html 2=json
   * @return [type] [description]
   */
  public function getUrls($projectId, $page) {

    //获取项目类型数据
    $type = 2;

    switch ($type) {
			case 1:
					$startAreaHtml = '';
					$endAreaHtml = '';
					$urlRegExp = '//';

					//获取目标URL 内容
					//$html = $this->
				break;
			case 2:
          $options = array(
              CURLOPT_USERAGENT => "Mozilla/5.0 (compatible; haosouSpider/1.0; +http://www.haosou.com)",
              CURLOPT_REFERER		=> 'http://www.cnbeta.com/',
          );
          $startUrl = "http://www.cnbeta.com/home/more?&type=catid|7&page={$page}";

					$html = json_decode(get($startUrl, array(), $options), TRUE);

          $data = $html['result']['list'];
          $insertData = array();
          if(!empty($data)) {
            foreach ($data as $k => $v) {
              $this->addUrls($v['url_show'], $v['title'], $projectId);
            }
          }
				break;
			default:
				# code...
				break;
		}
  }

  /**
   * 采集正文内容
   * @return [type] [description]
   */
  public function getContent($projectId) {
    //获取待采集url
    $urls = $this->getUrls($projectId);

  }

  /**
   * 添加待采集
   * @param [type]  $url       [description]
   * @param [type]  $title     [description]
   * @param integer $projectId [description]
   */
  public function addUrls($url, $title, $projectId = 1) {
    $data = array(
      'project_id'  => $projectId,
      'url'         => trim($url),
      'title'       => strip_tags($title),
      'create_time' => time()
    );
    $this->master->insert(self::TBL_URLS, $data);
    $insertId = $this->master->insert_id();
    return $insertId;
  }


  public function getUrls($projectId, $limit = 100) {
      $where = array(
        'project_id'  => $projectId,
        'status'      => 1
      );
      $data = $this->master->select('id,url,title')
        ->where($where)
        ->limit($limit)
        ->get(self::TBL_URLS)
        ->result_array();
      return $data;
      //if($query->num_rows > 0) {}
  }

}
