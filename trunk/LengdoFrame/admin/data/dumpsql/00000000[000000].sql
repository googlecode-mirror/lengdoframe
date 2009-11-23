-- LengdoFrame SQL Dump
-- 
-- date: 2009-11-23 21:56:41
-- php: 5.2.3
-- mysql: 5.0.45-community-nt-log
-- vol: 1

-- --------------------------------------------------------

-- 
-- 表的结构 `admin` 
-- 

DROP TABLE IF EXISTS `admin`;
CREATE TABLE `admin` (
  `admin_id` smallint(5) unsigned NOT NULL auto_increment,
  `role_id` smallint(5) unsigned NOT NULL default '0',
  `name` varchar(30) NOT NULL,
  `username` varchar(20) NOT NULL,
  `password` varchar(32) NOT NULL,
  `pfile_time` int(10) unsigned NOT NULL default '0',
  `in_time` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`admin_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='管理员表';

-- 
-- 表的数据 `admin` 
-- 

INSERT INTO `admin` ( `admin_id`, `role_id`, `name`, `username`, `password`, `pfile_time`, `in_time` ) VALUES ( '1','1','administrator','administrator','200ceb26807d6bf99fd6f4f0d1ca54d4','1258984588','1231591428' );


-- --------------------------------------------------------

-- 
-- 表的结构 `admin_log` 
-- 

DROP TABLE IF EXISTS `admin_log`;
CREATE TABLE `admin_log` (
  `admin_log_id` mediumint(8) unsigned NOT NULL auto_increment,
  `admin_id` smallint(5) unsigned NOT NULL default '0',
  `admin_name` varchar(30) NOT NULL,
  `ip` varchar(15) NOT NULL,
  `info` varchar(255) NOT NULL,
  `in_time` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`admin_log_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='管理员日志表';

-- --------------------------------------------------------

-- 
-- 表的结构 `admin_privilege` 
-- 

DROP TABLE IF EXISTS `admin_privilege`;
CREATE TABLE `admin_privilege` (
  `admin_id` smallint(5) unsigned NOT NULL default '0',
  `privilege_id` smallint(5) unsigned NOT NULL default '0',
  PRIMARY KEY  (`admin_id`,`privilege_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='管理员权限表';

-- --------------------------------------------------------

-- 
-- 表的结构 `module` 
-- 

DROP TABLE IF EXISTS `module`;
CREATE TABLE `module` (
  `module_id` smallint(5) unsigned NOT NULL auto_increment,
  `file` varchar(30) NOT NULL,
  `name` varchar(30) NOT NULL,
  `hidden` tinyint(1) NOT NULL default '0',
  `lft` smallint(5) unsigned NOT NULL,
  `rht` smallint(5) unsigned NOT NULL,
  `lvl` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY  (`module_id`),
  UNIQUE KEY `file` (`file`)
) ENGINE=MyISAM AUTO_INCREMENT=17 DEFAULT CHARSET=utf8 COMMENT='模块表';

-- 
-- 表的数据 `module` 
-- 

INSERT INTO `module` ( `module_id`, `file`, `name`, `hidden`, `lft`, `rht`, `lvl` ) VALUES ( '1','','所有模块','0','1','24','0' );
INSERT INTO `module` ( `module_id`, `file`, `name`, `hidden`, `lft`, `rht`, `lvl` ) VALUES ( '2','kernel','内核','0','16','23','1' );
INSERT INTO `module` ( `module_id`, `file`, `name`, `hidden`, `lft`, `rht`, `lvl` ) VALUES ( '3','module.php','模块管理','0','17','18','2' );
INSERT INTO `module` ( `module_id`, `file`, `name`, `hidden`, `lft`, `rht`, `lvl` ) VALUES ( '4','privilege.php','权限管理','0','19','20','2' );
INSERT INTO `module` ( `module_id`, `file`, `name`, `hidden`, `lft`, `rht`, `lvl` ) VALUES ( '5','sysmodule.php','系统模块','1','21','22','2' );
INSERT INTO `module` ( `module_id`, `file`, `name`, `hidden`, `lft`, `rht`, `lvl` ) VALUES ( '6','db','数据库','0','10','15','1' );
INSERT INTO `module` ( `module_id`, `file`, `name`, `hidden`, `lft`, `rht`, `lvl` ) VALUES ( '7','db_backup.php','数据库备份','0','11','12','2' );
INSERT INTO `module` ( `module_id`, `file`, `name`, `hidden`, `lft`, `rht`, `lvl` ) VALUES ( '8','db_optimize.php','数据库优化','0','13','14','2' );
INSERT INTO `module` ( `module_id`, `file`, `name`, `hidden`, `lft`, `rht`, `lvl` ) VALUES ( '9','admin','管理员','0','2','9','1' );
INSERT INTO `module` ( `module_id`, `file`, `name`, `hidden`, `lft`, `rht`, `lvl` ) VALUES ( '10','admin.php','管理员管理','0','3','4','2' );
INSERT INTO `module` ( `module_id`, `file`, `name`, `hidden`, `lft`, `rht`, `lvl` ) VALUES ( '11','admin_log.php','管理员日志','0','5','6','2' );
INSERT INTO `module` ( `module_id`, `file`, `name`, `hidden`, `lft`, `rht`, `lvl` ) VALUES ( '12','role.php','管理员角色','0','7','8','2' );


-- --------------------------------------------------------

-- 
-- 表的结构 `privilege` 
-- 

DROP TABLE IF EXISTS `privilege`;
CREATE TABLE `privilege` (
  `privilege_id` smallint(5) unsigned NOT NULL auto_increment,
  `name` varchar(50) NOT NULL,
  `order` tinyint(3) unsigned NOT NULL default '1',
  `module_id` smallint(5) unsigned NOT NULL,
  `module_act_code` varchar(20) NOT NULL,
  `module_act_name` varchar(50) NOT NULL,
  PRIMARY KEY  (`privilege_id`),
  UNIQUE KEY `module_id` (`module_id`,`module_act_code`)
) ENGINE=MyISAM AUTO_INCREMENT=24 DEFAULT CHARSET=utf8 COMMENT='权限表';

-- 
-- 表的数据 `privilege` 
-- 

INSERT INTO `privilege` ( `privilege_id`, `name`, `order`, `module_id`, `module_act_code`, `module_act_name` ) VALUES ( '1','模块列表','1','3','list','列表' );
INSERT INTO `privilege` ( `privilege_id`, `name`, `order`, `module_id`, `module_act_code`, `module_act_name` ) VALUES ( '2','增加模块','2','3','add','增加' );
INSERT INTO `privilege` ( `privilege_id`, `name`, `order`, `module_id`, `module_act_code`, `module_act_name` ) VALUES ( '3','编辑模块','3','3','edit','编辑' );
INSERT INTO `privilege` ( `privilege_id`, `name`, `order`, `module_id`, `module_act_code`, `module_act_name` ) VALUES ( '4','删除模块','4','3','del','删除' );
INSERT INTO `privilege` ( `privilege_id`, `name`, `order`, `module_id`, `module_act_code`, `module_act_name` ) VALUES ( '5','权限列表','1','4','list','列表' );
INSERT INTO `privilege` ( `privilege_id`, `name`, `order`, `module_id`, `module_act_code`, `module_act_name` ) VALUES ( '6','增加权限','2','4','add','增加' );
INSERT INTO `privilege` ( `privilege_id`, `name`, `order`, `module_id`, `module_act_code`, `module_act_name` ) VALUES ( '7','编辑权限','3','4','edit','编辑' );
INSERT INTO `privilege` ( `privilege_id`, `name`, `order`, `module_id`, `module_act_code`, `module_act_name` ) VALUES ( '8','删除权限','4','4','del','删除' );
INSERT INTO `privilege` ( `privilege_id`, `name`, `order`, `module_id`, `module_act_code`, `module_act_name` ) VALUES ( '9','我的帐号','1','5','myaccount','我的帐号' );
INSERT INTO `privilege` ( `privilege_id`, `name`, `order`, `module_id`, `module_act_code`, `module_act_name` ) VALUES ( '10','数据库备份','1','7','backup','备份' );
INSERT INTO `privilege` ( `privilege_id`, `name`, `order`, `module_id`, `module_act_code`, `module_act_name` ) VALUES ( '11','数据库优化','1','8','optimize','优化' );
INSERT INTO `privilege` ( `privilege_id`, `name`, `order`, `module_id`, `module_act_code`, `module_act_name` ) VALUES ( '12','管理员列表','1','10','list','列表' );
INSERT INTO `privilege` ( `privilege_id`, `name`, `order`, `module_id`, `module_act_code`, `module_act_name` ) VALUES ( '13','增加管理员','2','10','add','增加' );
INSERT INTO `privilege` ( `privilege_id`, `name`, `order`, `module_id`, `module_act_code`, `module_act_name` ) VALUES ( '14','编辑管理员','3','10','edit','编辑' );
INSERT INTO `privilege` ( `privilege_id`, `name`, `order`, `module_id`, `module_act_code`, `module_act_name` ) VALUES ( '15','删除管理员','4','10','del','删除' );
INSERT INTO `privilege` ( `privilege_id`, `name`, `order`, `module_id`, `module_act_code`, `module_act_name` ) VALUES ( '16','日志列表','1','11','list','列表' );
INSERT INTO `privilege` ( `privilege_id`, `name`, `order`, `module_id`, `module_act_code`, `module_act_name` ) VALUES ( '17','角色列表','1','12','list','列表' );
INSERT INTO `privilege` ( `privilege_id`, `name`, `order`, `module_id`, `module_act_code`, `module_act_name` ) VALUES ( '18','增加角色','2','12','add','增加' );
INSERT INTO `privilege` ( `privilege_id`, `name`, `order`, `module_id`, `module_act_code`, `module_act_name` ) VALUES ( '19','编辑角色','3','12','edit','编辑' );
INSERT INTO `privilege` ( `privilege_id`, `name`, `order`, `module_id`, `module_act_code`, `module_act_name` ) VALUES ( '20','删除角色','4','12','del','删除' );


-- --------------------------------------------------------

-- 
-- 表的结构 `role` 
-- 

DROP TABLE IF EXISTS `role`;
CREATE TABLE `role` (
  `role_id` smallint(5) unsigned NOT NULL auto_increment,
  `name` varchar(50) NOT NULL,
  `remark` varchar(255) NOT NULL,
  `lft` smallint(5) unsigned NOT NULL,
  `rht` smallint(5) unsigned NOT NULL,
  `lvl` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY  (`role_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COMMENT='角色表';

-- 
-- 表的数据 `role` 
-- 

INSERT INTO `role` ( `role_id`, `name`, `remark`, `lft`, `rht`, `lvl` ) VALUES ( '1','超级管理员','','1','2','1' );


-- --------------------------------------------------------

-- 
-- 表的结构 `role_privilege` 
-- 

DROP TABLE IF EXISTS `role_privilege`;
CREATE TABLE `role_privilege` (
  `role_id` smallint(5) unsigned NOT NULL,
  `privilege_id` smallint(5) unsigned NOT NULL,
  PRIMARY KEY  (`role_id`,`privilege_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='角色权限表';