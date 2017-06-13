<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class importModel extends MY_Model {

  const IMG_DOMAIN = 'http://img.baidu.com/';
  const TBL_CONTENT = 'content';                  //采集内容表
  const TBL_WEB_CONTENTS = 'contents';            //web项目内容表
  const TBL_WEB_RELATIONSHIPS = 'relationships';  //web项目关联表
  const TBL_WEB_METAS = 'metas';                  //分类信息

  public function __construct() {
    parent::__construct();
  }

  /**
   * 入库
   * @param  [type]  $projectId [description]
   * @param  integer $limit     [description]
   * @return [type]             [description]
   */
  public function importContent($projectId, $limit = 5) {
    //获取采集内容
    $list = $this->getSpiderContent($projectId, $limit);

    $converter = new League\HTMLToMarkdown\HtmlConverter();
    foreach ($list as $key => $value) {

      //转换正文内容为markdown 格式
      $content = $converter->convert($value['content']);
      $content = "<!--markdown-->{$content}";

      $insertId = $this->addWebContent($value['cate_id'], $value['title'], $content);

      if(!empty($insertId)) {
        $this->updateSpiderContent($value['id'], array('status' => 2, 'import_time' => time(), 'import_id' => $insertId));

        //拼接完整url
        $cateInfo = $this->getCateInfo($value['cate_id']);
        $url = "http://www.xxx.me/{$cateInfo['slug']}/{$insertId}.html";
        $baiduRes = $this->postToBaidu($url);
        echo "文章ID:{$insertId},导入完成=>{$baiduRes}\n";
      }
    }
  }

  /**
   * 下载已采集内容的图片,并且替换
   * @param  [type]  $projectId [description]
   * @param  integer $limit     [description]
   * @return [type]             [description]
   */
  public function importImage($projectId, $limit = 10) {

    //获取采集内容
    $list = $this->getSpiderContent($projectId, $limit, 1);

    foreach ($list as $key => $value) {
      echo "内容ID:{$value['id']}=>开始处理:\n";
      $content = $value['content'];
      //分析内容图片
      //分析内容图片
      $html = preg_match_all('/<img.*?src=\"(.*?)\".*?\/>/', $value['content'], $match);

      $replace = array();
      echo " 开始下载图片:\n";
      foreach ($match[1] as $k => $v) {
        $filePath = $this->downFile($v);

        if(!empty($filePath)) {
          $uploadRes = $this->uploadFile($filePath);
          if(empty($uploadRes['key'])) continue;
          $filePath = '';
          $replace[] = self::IMG_DOMAIN.$uploadRes['key'];
          echo "  下载成功=>{$v}=>".self::IMG_DOMAIN."{$uploadRes['key']}\n";
        } else {
          echo "  下载失败=>{$filePath}\n";
        }
      }

      //替换图片路径
      $content = str_replace($match[1], $replace, $value['content']);
      $this->updateSpiderContent($value['id'], array('content' => $content, 'img_status' => 2));
      echo "处理完成\n";
    }
  }

  /**
   * 获取采集完成的内容
   * @param  [type] $projectId [description]
   * @param  [type] $limit     [description]
   * @return [type]            [description]
   */
  private function getSpiderContent($projectId, $limit, $imgStatus = 2) {
    $where = array(
      'project_id'  => $projectId,
      'status'      => 1,
      'img_status'  => $imgStatus,
    );
    $list = $this->master->select('id,cate_id,title,content')
      ->where($where)
      ->order_by('create_time asc')
      ->limit($limit)
      ->get(self::TBL_CONTENT)
      ->result_array();

      return $list;
  }

  private function updateSpiderContent($id, $data) {
    $this->master->where('id', $id)->update(self::TBL_CONTENT, $data);
    return $this->master->affected_rows();
  }

  /**
   * 添加内容到正式内容表
   * @param [type] $cateId  [description]
   * @param [type] $title   [description]
   * @param [type] $content [description]
   */
  private function addWebContent($cateId, $title, $content) {

    $data = array(
      'title'   => $title,
      //'slug'    => $slug,
      'created' => time(),
      'text'    => $content,
      'authorId'=> 1,
      'type'    => 'post',
      'status'  => 'publish'
    );

    $this->web_master->insert(self::TBL_WEB_CONTENTS, $data);
    $insertId = $this->web_master->insert_id();

    $this->web_master->where('cid', $insertId)->update(self::TBL_WEB_CONTENTS, array('slug' => $insertId));

    //添加内容关联
    $this->addRelationships($insertId, $cateId);

    return $insertId;
  }

  /**
   * 添加关联表数据
   * @param [type] $cid [description]
   * @param [type] $mid [description]
   */
  private function addRelationships($cid, $mid) {
    $data = array(
      'cid'   => $cid,
      'mid'   => $mid,
    );

    $this->web_master->insert(self::TBL_WEB_RELATIONSHIPS, $data);
    $insertId = $this->web_master->insert_id();
    return $insertId;
  }

  private function downFile($fileUrl) {
    //缓存路径
    $fileName = pathinfo($fileUrl);

    $savePath = '/tmp/img';
    $saveTo = "{$savePath}/{$fileName['basename']}";
    $fileContent = get($fileUrl);
    //$cmd = "wget -nv --tries=2 {$imgList[$k]['src']} -O {$imgSaveFile}";
		//exec( $cmd, $output, $ret );
    $downloadedFile = fopen($saveTo, 'w');
    fwrite($downloadedFile, $fileContent);
	  fclose($downloadedFile);
    if(file_exists($saveTo)) {
      return $saveTo;
    } else {
      return '';
    }
  }

  private function uploadFile($filePath) {

    $accessKey = '123';
    $secretKey = '456';
    $auth = new Qiniu\Auth($accessKey, $secretKey);

    // 空间名  https://developer.qiniu.io/kodo/manual/concepts
    $bucket = 'bucket';
    // 生成上传Token
    $token = $auth->uploadToken($bucket);
    // 构建 UploadManager 对象
    $uploadMgr = new Qiniu\Storage\UploadManager();
    // 要上传文件的本地路径
    //$filePath = '/tmp/1.jpg';
    // 上传到七牛后保存的文件名
    $year = date('Y');
    $day = date('md');
    $path = pathinfo($filePath);
    $key = "{$year}/{$day}/".mt_rand().'.'.$path['extension'];

    // 调用 UploadManager 的 putFile 方法进行文件的上传
    list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
    //echo "\n====> putFile result: \n";
    if ($err !== null) {
        var_dump($err);
    } else {
        unlink($filePath);
        return $ret;
    }
  }

  public function getCateInfo($cateId) {
    $cateInfo = $this->web_master->select('mid,name,slug,type')->where('mid', $cateId)
    ->get(self::TBL_WEB_METAS)->row_array();

    return $cateInfo;
  }

  //提交到百度
  private function postToBaidu($url) {
    $urls = array(
      $url,
    );
    $api = 'http://data.zz.baidu.com/urls?site=www.baidu.com&token=xxxxx';
    $ch = curl_init();
    $options =  array(
        CURLOPT_URL => $api,
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => implode("\n", $urls),
        CURLOPT_HTTPHEADER => array('Content-Type: text/plain'),
    );
    curl_setopt_array($ch, $options);
    $result = curl_exec($ch);
    echo $result;
  }
}
