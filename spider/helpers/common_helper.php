<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 功能： 向服务器发送GET请求
 * @access public
 * @param string $url  要请求的url地址。必选
 * @param array $get  请求参数。可选
 * @param array $options
 * @param array $options curl配置参数。可选
 * @return mixed
 * @time 2012-03-23
 */
function get($url, array $get = array(), array $options = array()) {
  $defaults = array(
    CURLOPT_URL 			=> $url . (strpos($url, '?') === FALSE ? '?' : '') . http_build_query($get, '', '&'),
    //CURLOPT_URL 			=> '',
    CURLOPT_TIMEOUT 		=> 10,
    CURLOPT_CONNECTTIMEOUT	=> 10,
    CURLOPT_HEADER 			=> 0,
    CURLOPT_RETURNTRANSFER	=> TRUE,
    //CURLOPT_REFERER			=> $referer,
      //CURLOPT_HTTPHEADER		=> array('Host:i.open.book.weibo.com'),
  );

  $ch = curl_init();
  curl_setopt_array($ch, ($options + $defaults));
  $result = curl_exec($ch);
  if (curl_error($ch)) {
    trigger_error(curl_error($ch));

    //记录日志
  } else {
    $info= curl_getinfo($ch);
    if($info['total_time'] > 4){} //记录日志
  }
  curl_close($ch);
  return $result;
}
