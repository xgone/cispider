/*
Navicat MySQL Data Transfer

Source Server         : 127.0.0.1
Source Server Version : 50553
Source Host           : localhost:3306
Source Database       : spider

Target Server Type    : MYSQL
Target Server Version : 50553
File Encoding         : 65001

Date: 2017-05-13 22:59:38
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for content
-- ----------------------------
DROP TABLE IF EXISTS `content`;
CREATE TABLE `content` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `urls_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'urls id',
  `content` longtext NOT NULL COMMENT '内容',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of content
-- ----------------------------

-- ----------------------------
-- Table structure for project
-- ----------------------------
DROP TABLE IF EXISTS `project`;
CREATE TABLE `project` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '项目名称',
  `base_url` varchar(255) NOT NULL DEFAULT '' COMMENT '网站根目录',
  `encoding` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '编码 默认utf8',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of project
-- ----------------------------

-- ----------------------------
-- Table structure for urls
-- ----------------------------
DROP TABLE IF EXISTS `urls`;
CREATE TABLE `urls` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '项目ID',
  `url` varchar(500) NOT NULL DEFAULT '' COMMENT '采集地址',
  `title` varchar(500) NOT NULL DEFAULT '' COMMENT '标题',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '默认 1=未采集 2=已采集 3=已入库 4=已过滤',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `cap_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '抓取时间',
  `save_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '入库时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of urls
-- ----------------------------
