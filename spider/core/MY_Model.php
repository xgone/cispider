<?php	if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Model extends CI_Model {
	public static $dbConn = array();
	public function __construct() {
		parent::__construct();
	}

	/**
	 * [__get description]
	 * @param  [type] $key [description]
	 * @return [type]      [description]
	 */
   public function __get($key){
 		if(!isset(self::$dbConn[$key]) || empty(self::$dbConn[$key])){
   		switch($key){
   				case 'master':
   					$conn = $this->load->database("master", TRUE);
   					break;
   				case 'slave':
   					$dbSlaveCount = 1;
   					$conn = $this->load->database("slave{$dbSlaveCount}", TRUE);
   					break;
   				default:
   					return parent::__get($key);
   			}
        self::$dbConn[$key] = $conn;
 		}
 		return  self::$dbConn[$key];
 	}
}
