<?php
// +----------------------------------------------------------------------
// | LengdoFrame - 后台入口
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
require_once( preg_replace('/[^\/]+\/includes\/init.php/i', '', str_replace("\\",'/', __FILE__)) . 'includes/config.php' );

/* 加载整站公用函数库 */
require_once($_CFG['DIR_INC'] . 'func.php');

/* 加载Mysql数据库类 */
require_once($_CFG['DIR_CLS'] . 'mysql.class.php');

/* 加载后台公用函数库 */
require_once($_CFG['DIR_ADMIN_INC'] . 'lib_func.php');

/* 加载权限系统库 */
require_once($_CFG['DIR_ADMIN_INC'] . 'lib_privilege.php');

/* 加载后台公用语言库 - 加载全局变量 $_LANG */
require_once($_CFG['DIR_ADMIN_LNG'] . 'zh.php');
@include_once($_CFG['DIR_ADMIN_LNG'] . 'system_zh.php');


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

/* 重构$_REQUEST数据(只保留$_GET和$_POST) */
$_REQUEST = array_merge($_GET, $_POST);

/* 初始化数据库类, 设置全局变量 $db */
$db = new Mysql($_CFG['dbhost'], $_CFG['dbuser'], $_CFG['dbpass'], $_CFG['dbname'], $_CFG['dbcset'], $_CFG['dbpcon']);

/* 初始化 $_REQUEST['act'] */
if( !isset($_REQUEST['act']) ) $_REQUEST['act'] = '';

/* 初始化模板变量 */
$tpl = array();


/* ------------------------------------------------------ */
// - 登陆前后逻辑层
/* ------------------------------------------------------ */

/* [管理员登陆前]检测URL，过滤可用URL */
if( admin_logined() == false ){
    /* 文件访问保护，开放index.php访问权限 */
    if( trim($_SERVER['PHP_SELF'],'/') != trim($_CFG['URL_ADMIN'].'index.php','/') ){
        redirect($_CFG['URL_ADMIN'] . 'index.php?act=login');
    }

    /* 操作访问保护，开放index.php的 vcode, login, loginsubmit 这三个模块权限 */
    switch( $_REQUEST['act'] ){
        case 'vcode'      : break;  //验证码模块
        case 'login'      : break;  //登陆页面模块
        case 'loginsubmit': break;  //登陆提交模块

        default: redirect($_CFG['URL_ADMIN'] . 'index.php?act=login');
    }
}

/* [管理员登陆后]要加载的文件或初始化的变量 */
else{
    /* 权限文件运行时异常，重新刷新权限系统 */
    if( !admin_pfile_valid() ){ flush_privilege_sys(); } 

    /* 解析权限文件, 设置全局变量 $_RPIV */
    $_PRIV = admin_pfile_parse();

    /* 加载数据辅助库 */
    require_once($_CFG['DIR_CLS'] . 'formc.class.php');

    /* 加载数据库数据(文件格式) - 加载全局变量 $_DBD */
    @include_once($_CFG['DIR_INC'] . 'systemdbd.php');
    @include_once($_CFG['DIR_DB_DATA'] . 'dbd/systemdbd.php');
}


/* 文件头信息 */
header('Content-Type:text/html; charset=utf-8');
?>