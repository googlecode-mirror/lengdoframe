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
require($_CFG['DIR_ADMIN_LNG'].'zh.php');
require($_CFG['DIR_ADMIN_INC'].'lib_func.php');


/* ------------------------------------------------------ */
// - $_DBD数据
/* ------------------------------------------------------ */
$_DBD['support'] = array('0'=>'<span class="no"><i></i>不支持</span>', '1'=>'<span class="yes"><i></i>支持</span>');
$_DBD['filewrite'] = array('0'=>'<span class="no"><i></i>不可写</span>', '1'=>'<span class="yes"><i></i>可写</span>');


/* ------------------------------------------------------ */
// - 初始化操作步骤
/* ------------------------------------------------------ */

/* 初始化 */
$acts = array('envcheck', 'dbcreate');

/* 初始化STEP */
$step = intval($_REQUEST['step']);
$step = $step >= 1 && $step <= count($acts) ? $step : 1;

/* 构建ACT */
$_REQUEST['act'] = $acts[$step-1];



/* ------------------------------------------------------ */
// - 环境检查
/* ------------------------------------------------------ */
if( $_REQUEST['act'] == 'envcheck' ){
    /* 目录权限检查 */
    $tpl['files'][] = array('type'=>'dir', 'file'=>$_CFG['DIR_ADMIN_PFILE'], 'url'=>$_CFG['URL_ADMIN_PFILE'], 'need'=>$_DBD['filewrite'][1]);

    foreach( $tpl['files'] AS $i=>$r ){
        $tpl['files'][$i]['filewrite'] = $_DBD['filewrite'][ (file_privilege($r['file'])>=3)*1 ];
    }


    /* 函数依赖检查 */
    $tpl['funcs'][] = array('func'=>'json_encode',       'need'=>$_DBD['support'][1]);
    $tpl['funcs'][] = array('func'=>'mysql_connect',     'need'=>$_DBD['support'][1]);
    $tpl['funcs'][] = array('func'=>'file_get_contents', 'need'=>$_DBD['support'][1]);

    foreach( $tpl['funcs'] AS $i=>$r ){
        $tpl['funcs'][$i]['support'] = $_DBD['support'][ function_exists($r['func'])*1 ];
    }


    /* 初始化页面信息 */
    $tpl['_body'] = 'envcheck';
    
    $tpl['step'] = $step;
    $tpl['title'] = '环境检查';
    $tpl['subtitle'] = '文件目录权限和函数依赖性的检查';
}


/* ------------------------------------------------------ */
// - 数据库安装
/* ------------------------------------------------------ */
elseif( $_REQUEST['act'] == 'dbcreate' ){
    /* 初始化页面信息 */
    $tpl['_body'] = 'dbcreate';

    $tpl['step'] = $step;
    $tpl['title'] = '安装数据库';
    $tpl['subtitle'] = '配置数据库的连接数据和管理员信息';
}


/* 加载视图 */
include('index.html')
?>