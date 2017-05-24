<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Run extends MY_Controller {

	/**
	 * 开始
	 * @return [type] [description]
	 */
	public function index()
	{
		$projectId = 1;
		$page = 1;

		$this->load->model('spidermodel');
		$this->spidermodel->getUrls($projectId, $page);
	}

	/**
	 * 采集内容
	 * @return [type] [description]
	 */
	public function content() {
		$projectId = 1;
		$this->load->model('spidermodel');
		$this->spidermodel->getContent($projectId);
	}

}
