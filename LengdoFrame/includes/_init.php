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

/* 加载整站公用配置库和公用函数库 */
require( str_replace('includes/init.php', '', str_replace("\\", '/', __FILE__) ) . 'includes/config.php' );
require( str_replace('includes/init.php', '', str_replace("\\", '/', __FILE__) ) . 'includes/func.php' );

/* 加载整站公用配置库 */
@include(DIR_INC. '/systemconfig.php');

/* 加载数据辅助库 */
require(DIR_CLS . '/mysql.class.php');

/* 加载前台公用函数 */
@include(DIR_INC . 'systemfunc.php');

/* 加载前台公用语言库，加载全局变量 $_LANG */
@include(DIR_ROOT . 'lang/zh.php');


/* ------------------------------------------------------ */
// - 配置
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

    $_COOKIE  = addslashes_deep($_COOKIE);
}

/* 重构$_REQUEST数据(只保留$_GET和$_POST) */
$_REQUEST = array_merge($_GET, $_POST);

/* 初始化数据库类, 设置全局变量 $db */
$db = new Mysql($_CFG['dbhost'], $_CFG['dbuser'], $_CFG['dbpass'], $_CFG['dbname'], $_CFG['dbcset'], $_CFG['dbpcon']);

/* 初始化 $_REQUEST['act'] */
if( !isset($_REQUEST['act']) ) $_REQUEST['act'] = '';

/* 初始化模板变量 */
$tpl = array();

/* 加载整站公用数据库数据(文件格式), 加载全局变量 $_DBD */
@include(DIR_INC . 'systemdbd.php');

/* 文件头信息 */
header('Content-Type:text/html; charset=utf-8');
?>