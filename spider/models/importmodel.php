<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class importModel extends MY_Model {

  const TBL_CONTENT = 'content';        //采集内容表
  const TBL_WEB_CONTENTS = 'contents';  //web项目内容表

  public function __construct() {
    parent::__construct();
  }

  public function importContent($projectId, $limit = 5) {
    //获取采集内容
    $list = $this->getSpiderContent($projectId, $limit);

    foreach ($list as $key => $value) {

      # code...
    }
  }

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

  private function addWebContent() {

  }
}
