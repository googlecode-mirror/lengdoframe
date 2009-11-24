<?php
// +----------------------------------------------------------------------
// | LengdoFrame - 安装
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://lengdo.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Yangfan Dai <dmlk31@163.com>
// +----------------------------------------------------------------------
// $Id$


/* ------------------------------------------------------ */
// - 文件加载
/* ------------------------------------------------------ */

/* 配置文件 */
require('../includes/config.php');

/* 功能函数文件 */
require($_CFG['DIR_INC'] .'func.php');
require($_CFG['DIR_ADMIN_INC'].'lib_func.php');


/* ------------------------------------------------------ */
// - 初始化操作步骤
/* ------------------------------------------------------ */
$acts = array('envcheck', 'dbcreate');
$_REQUEST['step'] = 1;
$step = intval($_REQUEST['step']);
$step = $step >= 0 && $step < count($acts) ? $step : 0;

$_REQUEST['act'] = $acts[$step];


/* ------------------------------------------------------ */
// - 环境检查
/* ------------------------------------------------------ */
if( $_REQUEST['act'] == 'envcheck' ){
    /* 目录权限检查 */
    $tpl['files'][] = array('type'=>'dir', 'file'=>$_CFG['DIR_ADMIN_PFILE'], 'remark'=>'权限系统缓存文件夹！');
    
    foreach( $tpl['files'] AS $i=>$r ){
        $tpl['files'][$i]['pcode'] = file_privilege($r['file']);
    }

    /* 函数依赖检查 */
    $tpl['funcs'][] = array('func'=>'json_encode');
    $tpl['funcs'][] = array('func'=>'mysql_connect');
    $tpl['funcs'][] = array('func'=>'file_get_contents');

    foreach( $tpl['funcs'] AS $i=>$r ){
        $tpl['funcs'][$i]['exist'] = function_exists($r['func'])*1;
    }

    /* 初始化页面信息 */
    $tpl['_body'] = 'envcheck';
}


/* ------------------------------------------------------ */
// - 数据库安装
/* ------------------------------------------------------ */
elseif( $_REQUEST['act'] == 'dbcreate' ){
    /* 初始化页面信息 */
    $tpl['_body'] = 'dbcreate';
}


/* 加载视图 */
include('index.html')
?>