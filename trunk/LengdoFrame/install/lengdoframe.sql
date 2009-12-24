-- --------------------------------------------------
--
-- LengdoFrame SQL file for installation
-- $Id$
--
-- --------------------------------------------------

DROP TABLE IF EXISTS `%tblpre%admin`;
CREATE TABLE `%tblpre%admin` (
  `admin_id` smallint(5) unsigned NOT NULL auto_increment,
  `role_id` smallint(5) unsigned NOT NULL default '0',
  `name` varchar(30) NOT NULL,
  `username` varchar(20) NOT NULL,
  `password` varchar(32) NOT NULL,
  `pfile_time` int(10) unsigned NOT NULL default '0',
  `in_time` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`admin_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='管理员表';

INSERT INTO `%tblpre%admin` ( `admin_id`, `role_id`, `name`, `username`, `password`, `pfile_time`, `in_time` ) VALUES ('1','1','','','','0','0');


DROP TABLE IF EXISTS `%tblpre%admin_log`;
CREATE TABLE `%tblpre%admin_log` (
  `admin_log_id` mediumint(8) unsigned NOT NULL auto_increment,
  `admin_id` smallint(5) unsigned NOT NULL default '0',
  `admin_name` varchar(30) NOT NULL,
  `admin_username` varchar(20) NOT NULL,
  `ip` varchar(15) NOT NULL,
  `info` varchar(255) NOT NULL,
  `in_time` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`admin_log_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='管理员日志表';


DROP TABLE IF EXISTS `%tblpre%admin_privilege`;
CREATE TABLE `%tblpre%admin_privilege` (
  `admin_id` smallint(5) unsigned NOT NULL default '0',
  `privilege_id` smallint(5) unsigned NOT NULL default '0',
  PRIMARY KEY  (`admin_id`,`privilege_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='管理员权限表';


DROP TABLE IF EXISTS `%tblpre%module`;
CREATE TABLE `%tblpre%module` (
  `module_id` smallint(5) unsigned NOT NULL auto_increment,
  `file` varchar(30) NOT NULL,
  `name` varchar(30) NOT NULL,
  `hidden` tinyint(1) NOT NULL default '0',
  `lft` smallint(5) unsigned NOT NULL,
  `rht` smallint(5) unsigned NOT NULL,
  `lvl` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY  (`module_id`),
  UNIQUE KEY `file` (`file`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='模块表';

INSERT INTO `%tblpre%module` ( `module_id`, `file`, `name`, `hidden`, `lft`, `rht`, `lvl` ) VALUES ( '1','','所有模块','0','1','24','0' );
INSERT INTO `%tblpre%module` ( `module_id`, `file`, `name`, `hidden`, `lft`, `rht`, `lvl` ) VALUES ( '2','kernel','内核','0','16','23','1' );
INSERT INTO `%tblpre%module` ( `module_id`, `file`, `name`, `hidden`, `lft`, `rht`, `lvl` ) VALUES ( '3','module.php','模块管理','0','17','18','2' );
INSERT INTO `%tblpre%module` ( `module_id`, `file`, `name`, `hidden`, `lft`, `rht`, `lvl` ) VALUES ( '4','privilege.php','权限管理','0','19','20','2' );
INSERT INTO `%tblpre%module` ( `module_id`, `file`, `name`, `hidden`, `lft`, `rht`, `lvl` ) VALUES ( '5','sysmodule.php','系统模块','1','21','22','2' );
INSERT INTO `%tblpre%module` ( `module_id`, `file`, `name`, `hidden`, `lft`, `rht`, `lvl` ) VALUES ( '6','db','数据库','0','10','15','1' );
INSERT INTO `%tblpre%module` ( `module_id`, `file`, `name`, `hidden`, `lft`, `rht`, `lvl` ) VALUES ( '7','db_backup.php','数据库备份','0','11','12','2' );
INSERT INTO `%tblpre%module` ( `module_id`, `file`, `name`, `hidden`, `lft`, `rht`, `lvl` ) VALUES ( '8','db_optimize.php','数据库优化','0','13','14','2' );
INSERT INTO `%tblpre%module` ( `module_id`, `file`, `name`, `hidden`, `lft`, `rht`, `lvl` ) VALUES ( '9','admin','管理员','0','2','9','1' );
INSERT INTO `%tblpre%module` ( `module_id`, `file`, `name`, `hidden`, `lft`, `rht`, `lvl` ) VALUES ( '10','admin.php','管理员管理','0','3','4','2' );
INSERT INTO `%tblpre%module` ( `module_id`, `file`, `name`, `hidden`, `lft`, `rht`, `lvl` ) VALUES ( '11','admin_log.php','管理员日志','0','5','6','2' );
INSERT INTO `%tblpre%module` ( `module_id`, `file`, `name`, `hidden`, `lft`, `rht`, `lvl` ) VALUES ( '12','role.php','管理员角色','0','7','8','2' );


DROP TABLE IF EXISTS `%tblpre%privilege`;
CREATE TABLE `%tblpre%privilege` (
  `privilege_id` smallint(5) unsigned NOT NULL auto_increment,
  `name` varchar(50) NOT NULL,
  `order` tinyint(3) unsigned NOT NULL default '1',
  `module_id` smallint(5) unsigned NOT NULL,
  `module_act_code` varchar(20) NOT NULL,
  `module_act_name` varchar(50) NOT NULL,
  PRIMARY KEY  (`privilege_id`),
  UNIQUE KEY `module_id` (`module_id`,`module_act_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='权限表';

INSERT INTO `%tblpre%privilege` ( `privilege_id`, `name`, `order`, `module_id`, `module_act_code`, `module_act_name` ) VALUES ( '1','模块列表','1','3','list','列表' );
INSERT INTO `%tblpre%privilege` ( `privilege_id`, `name`, `order`, `module_id`, `module_act_code`, `module_act_name` ) VALUES ( '2','增加模块','2','3','add','增加' );
INSERT INTO `%tblpre%privilege` ( `privilege_id`, `name`, `order`, `module_id`, `module_act_code`, `module_act_name` ) VALUES ( '3','编辑模块','3','3','edit','编辑' );
INSERT INTO `%tblpre%privilege` ( `privilege_id`, `name`, `order`, `module_id`, `module_act_code`, `module_act_name` ) VALUES ( '4','删除模块','4','3','del','删除' );
INSERT INTO `%tblpre%privilege` ( `privilege_id`, `name`, `order`, `module_id`, `module_act_code`, `module_act_name` ) VALUES ( '5','权限列表','1','4','list','列表' );
INSERT INTO `%tblpre%privilege` ( `privilege_id`, `name`, `order`, `module_id`, `module_act_code`, `module_act_name` ) VALUES ( '6','增加权限','2','4','add','增加' );
INSERT INTO `%tblpre%privilege` ( `privilege_id`, `name`, `order`, `module_id`, `module_act_code`, `module_act_name` ) VALUES ( '7','编辑权限','3','4','edit','编辑' );
INSERT INTO `%tblpre%privilege` ( `privilege_id`, `name`, `order`, `module_id`, `module_act_code`, `module_act_name` ) VALUES ( '8','删除权限','4','4','del','删除' );
INSERT INTO `%tblpre%privilege` ( `privilege_id`, `name`, `order`, `module_id`, `module_act_code`, `module_act_name` ) VALUES ( '9','我的帐号','1','5','myaccount','我的帐号' );
INSERT INTO `%tblpre%privilege` ( `privilege_id`, `name`, `order`, `module_id`, `module_act_code`, `module_act_name` ) VALUES ( '10','数据库备份','1','7','backup','备份' );
INSERT INTO `%tblpre%privilege` ( `privilege_id`, `name`, `order`, `module_id`, `module_act_code`, `module_act_name` ) VALUES ( '11','数据库优化','1','8','optimize','优化' );
INSERT INTO `%tblpre%privilege` ( `privilege_id`, `name`, `order`, `module_id`, `module_act_code`, `module_act_name` ) VALUES ( '12','管理员列表','1','10','list','列表' );
INSERT INTO `%tblpre%privilege` ( `privilege_id`, `name`, `order`, `module_id`, `module_act_code`, `module_act_name` ) VALUES ( '13','增加管理员','2','10','add','增加' );
INSERT INTO `%tblpre%privilege` ( `privilege_id`, `name`, `order`, `module_id`, `module_act_code`, `module_act_name` ) VALUES ( '14','编辑管理员','3','10','edit','编辑' );
INSERT INTO `%tblpre%privilege` ( `privilege_id`, `name`, `order`, `module_id`, `module_act_code`, `module_act_name` ) VALUES ( '15','删除管理员','4','10','del','删除' );
INSERT INTO `%tblpre%privilege` ( `privilege_id`, `name`, `order`, `module_id`, `module_act_code`, `module_act_name` ) VALUES ( '16','日志列表','1','11','list','列表' );
INSERT INTO `%tblpre%privilege` ( `privilege_id`, `name`, `order`, `module_id`, `module_act_code`, `module_act_name` ) VALUES ( '17','角色列表','1','12','list','列表' );
INSERT INTO `%tblpre%privilege` ( `privilege_id`, `name`, `order`, `module_id`, `module_act_code`, `module_act_name` ) VALUES ( '18','增加角色','2','12','add','增加' );
INSERT INTO `%tblpre%privilege` ( `privilege_id`, `name`, `order`, `module_id`, `module_act_code`, `module_act_name` ) VALUES ( '19','编辑角色','3','12','edit','编辑' );
INSERT INTO `%tblpre%privilege` ( `privilege_id`, `name`, `order`, `module_id`, `module_act_code`, `module_act_name` ) VALUES ( '20','删除角色','4','12','del','删除' );


DROP TABLE IF EXISTS `%tblpre%role`;
CREATE TABLE `%tblpre%role` (
  `role_id` smallint(5) unsigned NOT NULL auto_increment,
  `name` varchar(50) NOT NULL,
  `remark` varchar(255) NOT NULL,
  `lft` smallint(5) unsigned NOT NULL,
  `rht` smallint(5) unsigned NOT NULL,
  `lvl` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY  (`role_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='角色表';

INSERT INTO `%tblpre%role` ( `role_id`, `name`, `remark`, `lft`, `rht`, `lvl` ) VALUES ( '1','超级管理员','','1','2','1' );


DROP TABLE IF EXISTS `%tblpre%role_privilege`;
CREATE TABLE `%tblpre%role_privilege` (
  `role_id` smallint(5) unsigned NOT NULL,
  `privilege_id` smallint(5) unsigned NOT NULL,
  PRIMARY KEY  (`role_id`,`privilege_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='角色权限表';