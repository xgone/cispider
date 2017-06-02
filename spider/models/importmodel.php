<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class importModel extends MY_Model {

  const TBL_CONTENT = 'content';                  //采集内容表
  const TBL_WEB_CONTENTS = 'contents';            //web项目内容表
  const TBL_WEB_RELATIONSHIPS = 'relationships';  //web项目关联表

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

      print_r($value);
      var_dump($content,$insertId);exit;
    }
  }

  /**
   * 下载已采集内容的图片,并且替换
   * @param  [type]  $projectId [description]
   * @param  integer $limit     [description]
   * @return [type]             [description]
   */
  public function importImage($projectId, $limit = 5) {
    $this->uploadFile();
  }

  /**
   * 获取采集完成的内容
   * @param  [type] $projectId [description]
   * @param  [type] $limit     [description]
   * @return [type]            [description]
   */
  private function getSpiderContent($projectId, $limit) {
    $where = array(
      'project_id'  => $projectId,
      'status'      => 1,
      'img_status'  => 2,
    );
    $list = $this->master->select('id,cate_id,title,content')
      ->where($where)
      ->order_by('create_time asc')
      ->limit($limit)
      ->get(self::TBL_CONTENT)
      ->result_array();

      return $list;
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

  private function uploadFile() {

    $accessKey = 'um3DKzr0CaF0jPJF3jgvBZ6huUYC2HyZJ0xm90Mr';
    $secretKey = '2bAxDc-Jjca7FpHGuhb5K70GSoCMJqHsBpo0rO3Y';
    $auth = new Qiniu\Auth($accessKey, $secretKey);

    // 空间名  https://developer.qiniu.io/kodo/manual/concepts
    $bucket = 'm123-01';
    // 生成上传Token
    $token = $auth->uploadToken($bucket);
    // 构建 UploadManager 对象
    $uploadMgr = new Qiniu\Storage\UploadManager();
    // 要上传文件的本地路径
    $filePath = '/tmp/1.jpg';
    // 上传到七牛后保存的文件名
    $key = '1.jpg';

    // 调用 UploadManager 的 putFile 方法进行文件的上传
    list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
    echo "\n====> putFile result: \n";
    if ($err !== null) {
        var_dump($err);
    } else {
        var_dump($ret);
    }
  }
}
