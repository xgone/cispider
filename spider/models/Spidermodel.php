<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class SpiderModel extends MY_Model {

  const TBL_PROJECT = 'project';  //项目信息
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
  public function runUrls($projectId, $page) {

    //获取项目类型数据
    $project = $this->getSpiderProject($projectId);

    switch ($project['type']) {
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
              CURLOPT_REFERER		=> $project['referer'],
          );

          $startUrl = str_replace('{page}', $page, $project['base_url']);

					$html = json_decode(get($startUrl, array(), $options), TRUE);

          $data = $html['result']['list'];
          $insertData = array();
          if(!empty($data)) {
            foreach ($data as $k => $v) {
              $insertRes = $this->addUrls($v['url_show'], $v['title'], $projectId, $project['cate_id']);
              if($insertRes == FALSE) {
                echo "URL:{$v['url_show']}=>URL重复,跳过\n";
              }
              echo "URL:{$v['url_show']}=>完成\n";
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
  public function runContent($projectId, $limit = 10) {
    echo "开始采集内容,项目ID->{$projectId}\n";
    //获取对象内容采集规则
    $titleRegExp = '/<h1>(.*?)<\/h1>/';  //标题正则
    $titleReplaceExp = '//';            //标题排除
    $contentRegExp = '/<div class=\"article-summary\">(.*?)<div class=\"tac\">/';   //匹配内容
    //排除内容
    $contentReplaceExp = array(
      '/<div class=\"article-relation\">(.*?)<\/div>/',
      '/<div class=\"topic\">(.*?)<\/div>/'
    );
    //获取待采集url
    $urls = $this->getUrls($projectId, $limit);

    foreach ($urls as $k => $v) {
      //获取该url html 内容
      $html = str_replace("\n", '', get($v['url']));
      $html = trim($html);

      //获取标题
      $pregRes = preg_match($titleRegExp, $html, $pregHtml);
      $title = trim($pregHtml[1]);
      $title = str_replace('[图]', '', $title);
      $title = str_replace('[视频]', '', $title);

      //获取正文内容
      $pregRes = preg_match($contentRegExp, $html, $pregHtml);

      //过滤特殊字符
      $content = trim($pregHtml[1]);

      //过滤指定区域标签
      $content = preg_replace($contentReplaceExp, '', $content);

      $content = strip_tags($content, '<p><br><img><embed>');

      $content = preg_replace('/<p(.*?)>/', '<p>', $content);

      //处理视频数据
      $content = preg_replace('/<embed.*?src=\"http:\/\/player\.youku\.com\/player\.php\/sid\/(.*?)\/v\.swf\".*?\/>/', "<iframe height=498 width=510 src='http://player.youku.com/embed/$1' frameborder=0 'allowfullscreen'></iframe>",$content);
      //print_r($content);exit;
      $content = trim($content);

      if(empty($title) || empty($content)) {
        echo "{$v['id']}->采集内容or标题失败\n";
        continue;
      }

      //添加到内容库
      $res = $this->addContent($v['id'], $v['cate_id'], $title, $content, $projectId);

      if(!empty($res)) {
        //更新url采集队列状态
        $this->updateUrls($v['id'], array('status' => 2, 'save_time' => time()));
        echo "{$v['id']}->采集内容完成\n";
      }
    }
  }

  public function getSpiderProject($id) {
    $where = array(
      'id'  => $id,
    );
    $data = $this->master->select('id,name,base_url,cate_id,type,domain,referer,encoding')
      ->where($where)
      ->get(self::TBL_PROJECT)
      ->row_array();
    return $data;
  }

  /**
   * 添加到内容库
   * @param [type] $urlId     [description]
   * @param [type] $cateId    [description]
   * @param [type] $title     [description]
   * @param [type] $content   [description]
   * @param [type] $projectId [description]
   */
  private function addContent($urlId, $cateId, $title, $content, $projectId) {
    $data = array(
      'project_id'  => $projectId,
      'cate_id'         => $cateId,
      'urls_id'       => $urlId,
      'title'       => $title,
      'content'     => $content,
      'create_time' => time()
    );
    $this->master->insert(self::TBL_CONTENT, $data);
    $insertId = $this->master->insert_id();
    return $insertId;
  }

  /**
   * 添加待采集队列
   * @param [type]  $url       [description]
   * @param [type]  $title     [description]
   * @param integer $projectId [description]
   */
  private function addUrls($url, $title, $projectId = 1, $cateId = 1) {

    //检查url是否已经存在
    $query = $this->master->select('url')->where('url', $url)->get(self::TBL_URLS)->row_array();

    if(!empty($query)) {
      return FALSE;
    }
    $data = array(
      'project_id'  => $projectId,
      'cate_id'     => $cateId,
      'url'         => trim($url),
      'title'       => strip_tags($title),
      'create_time' => time()
    );
    $this->master->insert(self::TBL_URLS, $data);
    $insertId = $this->master->insert_id();
    return $insertId;
  }

  /**
   * 更新URL采集队列
   * @param  [type] $urlId [description]
   * @param  [type] $data  [description]
   * @return [type]        [description]
   */
  private function updateUrls($urlId, $data) {
    $this->master->where('id', $urlId)->update(self::TBL_URLS, $data);
    $affectedRows = $this->master->affected_rows();
    return $affectedRows;
  }

  /**
   * 获取url采集队列
   * @param  [type]  $projectId [description]
   * @param  integer $limit     [description]
   * @return [type]             [description]
   */
  private function getUrls($projectId, $limit = 100) {
      $where = array(
        'project_id'  => $projectId,
        'status'      => 1
      );
      $data = $this->master->select('id,url,title,cate_id')
        ->where($where)
        ->limit($limit)
        ->get(self::TBL_URLS)
        ->result_array();
      return $data;
      //if($query->num_rows > 0) {}
  }

}
