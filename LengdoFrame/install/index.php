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
    /* 错误标识 */
    $tpl['error'] = 0;

    /* 目录权限检查 */
    $tpl['files'][] = array('type'=>'dir', 'file'=>$_CFG['DIR_ADMIN_PFILE'], 'url'=>$_CFG['URL_ADMIN_PFILE'], 'need'=>$_DBD['filewrite'][1]);

    foreach( $tpl['files'] AS $i=>$r ){
        /* 文件权限检测 */
        $filepriv = file_privilege($r['file']);

        /* 重构数据 */
        $tpl['files'][$i]['error'] = isset($_POST['submit']) ? (!($filepriv>=3))*1 : 0;
        $tpl['files'][$i]['filewrite'] = $_DBD['filewrite'][ ($filepriv>=3)*1 ];

        /* 统计错误 */
        $tpl['error'] += $tpl['files'][$i]['error'];
    }


    /* 函数依赖检查 */
    $tpl['funcs'][] = array('func'=>'json_encode',       'need'=>$_DBD['support'][1]);
    $tpl['funcs'][] = array('func'=>'mysql_connect',     'need'=>$_DBD['support'][1]);
    $tpl['funcs'][] = array('func'=>'file_get_contents', 'need'=>$_DBD['support'][1]);

    foreach( $tpl['funcs'] AS $i=>$r ){
        /* 函数存在检测 */
        $funcexists = function_exists($r['func']);
        
        /* 重构数据 */
        $tpl['funcs'][$i]['error'] = isset($_POST['submit']) ? (!$funcexists)*1 : 0;
        $tpl['funcs'][$i]['support'] = $_DBD['support'][ $funcexists*1 ];

        /* 统计错误 */
        $tpl['error'] += $tpl['funcs'][$i]['error'];
    }


    /* 初始化页面信息 */
    $tpl['_body'] = 'envcheck';

    $tpl['step'] = $step;
    $tpl['title'] = '环境检查';
    $tpl['subtitle'] = '文件目录权限和函数依赖性的检查';


    /* ------------------------------------------------------ */
    // - 环境检查 - 提交
    /* ------------------------------------------------------ */
    if( isset($_POST['submit']) ){
        if( $tpl['error'] == 0 ){
            header('location:?step='.($step+1)); exit();
        }
    }
}


/* ------------------------------------------------------ */
// - 安装数据库
/* ------------------------------------------------------ */
elseif( $_REQUEST['act'] == 'dbcreate' ){
    /* 初始化注释信息 */
    $tpl['remarks']['dbhost'] = '数据库服务地址，格式为 IP[:PORT]';
    $tpl['remarks']['admin_username'] = '管理员账号不能为空';

    /* 初始化页面信息 */
    $tpl['_body'] = 'dbcreate';

    $tpl['step'] = $step;
    $tpl['title'] = '安装数据库';
    $tpl['subtitle'] = '配置数据库的连接数据和管理员信息';


    /* ------------------------------------------------------ */
    // - 安装数据库 - 提交
    /* ------------------------------------------------------ */
    if( isset($_POST['submit']) ){
        /* 初始化 */
        $tpl['errors'] = array();
        
        /* 填写错误检查 */
        if( trim($_POST['dbname']) == '' ) $tpl['errors']['dbname'] = '数据库名称不能为空!';
        if( trim($_POST['dbuser']) == '' ) $tpl['errors']['dbuser'] = '数据库用户名不能为空!';

        if( trim($_POST['admin_username']) == '' ) $tpl['errors']['admin_username'] = '管理员账号不能为空!';
        
        /* 填写错误检查 - 重置备注 */
        foreach( $tpl['errors'] AS $k=>$v ){
            $tpl['remarks'][$k] = $v;
        }
    }
}


/* 加载视图 */
include('index.html')
?>