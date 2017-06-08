<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Daemon extends MY_Controller {

	/**
	 * 开始
	 * @return [type] [description]
	 */
	public function index($projectId = 1, $page = 1)
	{
		$this->load->model('spidermodel');
		$this->spidermodel->runUrls($projectId, $page);
	}

	/**
	 * 采集内容
	 * @return [type] [description]
	 */
	public function content($projectId = 1) {
		$this->load->model('spidermodel');
		$this->spidermodel->runContent($projectId);
	}

	/**
	 * 采集内容
	 * @return [type] [description]
	 */
	public function importContent($projectId = 1) {
		$this->load->model('importmodel');
		$this->importmodel->importContent($projectId);
	}

	public function importImage($projectId = 1) {
		$this->load->model('importmodel');
		$this->importmodel->importImage($projectId);
	}

}
