<?php
// +----------------------------------------------------------------------
// | LengdoFrame - 前台入口
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://lengdo.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Yangfan Dai <dmlk31@163.com>
// +----------------------------------------------------------------------
// $Id$


/* ------------------------------------------------------ */
// - 环境初始化
/* ------------------------------------------------------ */

/* SESSION启动 */
session_start();

/* 设置错误警报等级 */
error_reporting(E_ALL & ~E_NOTICE);


/* ------------------------------------------------------ */
// - 加载
/* ------------------------------------------------------ */

/* 加载整站公用配置库 */
require_once( str_replace('includes/init.php', '', str_replace("\\", '/', __FILE__) ) . 'includes/config.php' );

/* 加载整站公用函数库 */
require_once($_CFG['DIR_INC'] . 'func.php');

/* 加载前台公用函数 */
@include($_CFG['DIR_INC'] . 'systemfunc.php');

/* 加载Mysql数据库类 */
require_once($_CFG['DIR_CLS'] . 'mysql.class.php');

/* 加载前台公用语言库 - 加载全局变量 $_LANG */
@include_once($_CFG['DIR_LNG'] . 'zh.php');

/* 加载整站公用数据库数据(文件格式) - 加载全局变量 $_DBD */
@include_once($_CFG['DIR_INC'] . 'systemdbd.php');


/* ------------------------------------------------------ */
// - 环境配置
/* ------------------------------------------------------ */

/* 设置时区 */
if( PHP_VERSION >= '5.1' && !empty($_CFG['timezone']) ){
    date_default_timezone_set($_CFG['timezone']);
}


/* ------------------------------------------------------ */
// - 变量初始化
/* ------------------------------------------------------ */

/* 对用户传入的变量进行转义操作。*/
if( !get_magic_quotes_gpc() ){
    if( !empty($_GET) )  $_GET  = addslashes_deep($_GET);
    if( !empty($_POST) ) $_POST = addslashes_deep($_POST);

    $_COOKIE = addslashes_deep($_COOKIE);
}

/* 初始化模板变量 */
$tpl = array();

/* 初始化请求变量 */
$_REQUEST = array_merge($_GET, $_POST);

/* 初始化操作变量 */
$_REQUEST['act'] = $_REQUEST['act'] ? trim($_REQUEST['act']) : '';

/* 初始化数据库类, 设置全局变量 $db */
$db = new Mysql($_CFG['dbhost'], $_CFG['dbuser'], $_CFG['dbpass'], $_CFG['dbname'], $_CFG['dbcset'], $_CFG['dbpcon']);


/* 文件头信息 */
header('Content-Type:text/html; charset=utf-8');
?>