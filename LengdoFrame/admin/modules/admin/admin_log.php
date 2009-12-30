<?php
// +----------------------------------------------------------------------
// | LengdoFrame - 管理员日志模块
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
require('../../includes/init.php');
require('../../includes/lib_admin.php');


/* ------------------------------------------------------ */
// - 异步 - 删除
/* ------------------------------------------------------ */
if( $_REQUEST['act'] == 'del' ){
    /* 权限检查 */
    admin_privilege_valid('admin_log.php', 'list');

    /* 删除日志 */
    $sql = 'DELETE FROM '. tname('admin_log') .' ORDER BY admin_log_id ASC LIMIT '.intval($_POST['id']);
    $db->query($sql);

    /* 系统提示 */
    make_json_ok();
}


/* ------------------------------------------------------ */
// - 异步 - 默认首页，列表页
/* ------------------------------------------------------ */
else{
    /* 权限检查 */
    admin_privilege_valid('admin_log.php', 'list');

    /* 日志列表 */
    $tpl['list'] = list_admin_log();

    /* 初始化页面信息 */
    $tpl['_body'] = 'list';


    /* ------------------------------------------------------ */
    // - 异步 - 列表页，列表查询
    /* ------------------------------------------------------ */
    if( $_REQUEST['act'] == 'list' ){
        /* 列表查询 */
        if( $_REQUEST['actsub'] == 'query' ){
            /* 初始化页面信息 */
            $tpl['_bodysub'] = 'query';
        }

        /* 返回JSON */
        make_json_ok( '', tpl_fetch('admin_log.html',$tpl) );
    }

    /* ------------------------------------------------------ */
    // - 异步 - 默认首页
    /* ------------------------------------------------------ */
    else{
        /* 初始化页面信息 */
        $tpl['_header'] = 'title';

        /* 初始化页面信息 */
        $tpl['title'] = admin_privilege_name_fk('admin_log.php', 'list'); //权限名称
    }
}


/* 加载视图 */
include($_CFG['DIR_ADMIN_TPL'] . 'admin_log.html');
?>