<?php
// +----------------------------------------------------------------------
// | LengdoFrame - 整站公用配置
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://lengdo.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Yangfan Dai <dmlk31@163.com>
// +----------------------------------------------------------------------
// $Id$


/* ----------------------------------------------------------------------- */
// - 数据库登陆信息
/* ----------------------------------------------------------------------- */
$_CFG['dbhost'] = 'localhost';      //数据库服务器
$_CFG['dbname'] = 'lengdoframe';    //数据库名称
$_CFG['dbuser'] = 'root';           //数据库登陆帐号
$_CFG['dbpass'] = '';               //数据库登陆密码
$_CFG['tblpre'] = '';               //数据表名称前辍

$_CFG['dbpcon'] = false;            //数据库持续连接
$_CFG['dbcset'] = 'utf8';           //数据库连接字符集


/* ----------------------------------------------------------------------- */
// - 运行时配置
/* ----------------------------------------------------------------------- */

/* 时区 */
$_CFG['timezone'] = 'PRC';


/* ----------------------------------------------------------------------- */
// - 管理员 SESSION 的下标
/* ----------------------------------------------------------------------- */
define( 'SN_ADMIN', md5(__FILE__) );


/* ----------------------------------------------------------------------- */
// - 目录路径信息(保留末尾斜杠)
/* ----------------------------------------------------------------------- */

/* 根路径的相对路径和绝对路径(开头保留斜杠) */
define('DIR_ROOT', str_ireplace( 'includes/config.php', '', str_replace("\\",'/',__FILE__) ) );
define('URL_ROOT', str_ireplace( rtrim(str_replace("\\",'/',$_SERVER['DOCUMENT_ROOT']),'/'), '', DIR_ROOT) );

/* 前台基本文件夹路径 */
define('DIR_TPL'         , DIR_ROOT  . 'tpl/');
define('DIR_INC'         , DIR_ROOT  . 'includes/');
define('DIR_CLS'         , DIR_ROOT  . 'class/');

/* 后台基本文件夹路径 */
define('DIR_ADMIN'       , DIR_ROOT  . 'admin/');
define('URL_ADMIN'       , URL_ROOT  . 'admin/');
define('DIR_ADMIN_INC'   , DIR_ADMIN . 'includes/');
define('DIR_ADMIN_TPL'   , DIR_ADMIN . 'tpl/');

/* 开放权限文件夹路径 - 数据库备份 */
define('DIR_DB_DUMPSQL'  , DIR_ADMIN . 'data/dumpsql/');
define('URL_DB_DUMPSQL'  , URL_ADMIN . 'data/dumpsql/');

/* 开放权限文件夹路径 - 数据库SQL缓存 */
define('DIR_DB_CACHESQL' , DIR_ADMIN . 'data/cachesql/');
define('URL_DB_CACHESQL' , URL_ADMIN . 'data/cachesql/');

/* 开放权限文件夹路径 - 自定义数据缓存 */
define('DIR_DB_DATA'     , DIR_ADMIN . 'data/dbd/');
define('URL_DB_DATA'     , URL_ADMIN . 'data/dbd/');

/* 开放权限文件夹路径 - 管理员权限文件 */
define('DIR_ADMIN_PFILE' , DIR_ADMIN . 'data/pfile/');
define('URL_ADMIN_PFILE' , URL_ADMIN . 'data/pfile/');
?>