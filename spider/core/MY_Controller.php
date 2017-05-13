<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 继承自CI_Controller 基类
 */
class MY_Controller extends CI_Controller {

  public function __construct() {
    parent::__construct();
    //开启分析
    $this->output->enable_profiler(TRUE);
  }
}
