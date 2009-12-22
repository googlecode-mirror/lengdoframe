<?php
// +----------------------------------------------------------------------
// | LengdoFrame - 权限模块
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
require('../../includes/lib_role.php');
require('../../includes/lib_admin.php');
require('../../includes/lib_module.php');


/* ------------------------------------------------------ */
// - 异步 - 增加
/* ------------------------------------------------------ */
if( $_REQUEST['act'] == 'add' ){
    /* 权限检查 */
    admin_privilege_valid('privilege.php', 'add');

    /* HTML控件 */
    ctl_fill('add');

    /* 初始化页面信息 */
    $tpl['_body']  = 'add';
    $tpl['_block'] = true;
}
/* ------------------------------------------------------ */
// - 异步 - 写入数据库
/* ------------------------------------------------------ */
elseif( $_REQUEST['act'] == 'insert' ){
    /* 权限检查 */
    admin_privilege_valid('privilege.php','add');

    /* 数据提取 */
    $fields = post_privilege('add');

    /* 数据写入 */
    if( $db->insert(tname('privilege'), $fields) ){
        /* 初始化管理员的权限文件时间，刷新权限系统和系统提示 */
        admin_pfile_init(0); flush_privilege_sys(); make_json_ok();
    }
}


/* ------------------------------------------------------ */
// - 异步 - 编辑
/* ------------------------------------------------------ */
elseif( $_REQUEST['act'] == 'edit' ){
    /* 权限检查 */
    admin_privilege_valid('privilege.php','edit');

    /* 权限信息 */
    $tpl['privilege'] = info_privilege( array('privilege_id'=>$_GET['privilege_id']) );

    /* HTML控件 */
    ctl_fill('edit');

    /* 初始化页面信息 */
    $tpl['_body']  = 'edit';
    $tpl['_block'] = true;
}
/* ------------------------------------------------------ */
// - 异步 - 更新数据库
/* ------------------------------------------------------ */
elseif( $_REQUEST['act'] == 'update' ){
    /* 权限检查 */
    admin_privilege_valid('privilege.php','edit');

    /* 数据提取 */
    $fields = post_privilege('edit');

    /* 数据更新 */
    if( $db->update(tname('privilege'), $fields, 'privilege_id='.intval($_POST['privilege_id'])) ){
        /* 初始化管理员的权限文件时间，刷新权限系统和系统提示 */
        admin_pfile_init(0); flush_privilege_sys(); make_json_ok();
    }
}


/* ------------------------------------------------------ */
// - 异步 - 删除
/* ------------------------------------------------------ */
elseif( $_REQUEST['act'] == 'del' ){
    /* 权限检查 */
    admin_privilege_valid('privilege.php', 'del');

    /* 删除权限 */
    del_privilege( array('privilege_id'=>$_POST['id']) );

    /* 初始化管理员的权限文件时间，刷新权限系统和系统提示 */
    admin_pfile_init(0); flush_privilege_sys(); make_json_ok();
}


/* ------------------------------------------------------ */
// - 异步 - 默认首页，列表页
/* ------------------------------------------------------ */
else{
    /* 权限检查 */
    admin_privilege_valid('privilege.php', 'list');

    /* 取得管理员的非增加、列表操作 */
    $m_aa = admin_module_acts('privilege.php');
    $m_ac = filter_module_acts($m_aa, array('add','list'), false);

    /* 权限列表 */
    $tpl['list'] = list_privilege();

    /* 权限列表 - 数据重构，绑定操作权限 */
    foreach( $tpl['list']['data'] AS $i => $r ){
        /* 编辑操作 */
        $attribs = array();
        $attribs['edit']['onclick'] = "wnd_privilege_fill(this,'edit',{$r[privilege_id]})";

        /* 删除操作 */
        $attribs['del']['onclick']  = "ListTable.del(this,{$r[privilege_id]},'";
        $attribs['del']['onclick'] .= f(sprintf($_LANG['spr_confirm_del'],$r['name']),'hstr') ."')";

        /* 绑定操作 */
        $tpl['list']['data'][$i]['acts'] = format_module_acts($m_ac, $attribs, 'a');
    }

    /* HTML控件 */
    $append = array('value'=>'', 'text'=>$_LANG['ddl_all_module']);
    $tpl['formc_module'] = ddl_module('module_id', $_GET['module_id'], $append);

    /* 初始化页面信息 */
    $tpl['_body'] = 'list';


    /* ------------------------------------------------------ */
    // - 异步 - 列表页，列表查询
    /* ------------------------------------------------------ */
    if( $_REQUEST['act'] == 'list' ){
        /* 初始化页面信息 */
        $tpl['_block'] = true;

        /* 列表查询 */
        if( $_REQUEST['actsub'] == 'query' ){
            /* 初始化页面信息 */
            $tpl['_bodysub'] = 'query';
        }

        /* 返回JSON */
        make_json_ok( '', tpl_fetch('privilege.html',$tpl) );
    }

    /* ------------------------------------------------------ */
    // - 异步 - 默认首页
    /* ------------------------------------------------------ */
    else{
        /* 初始化参数 */
        $_GET['module_id'] = trim($_GET['module_id']);

        /* 取得管理员的增加操作 */
        $m_ab = filter_module_acts($m_aa, array('add'), true);

        /* 操作属性 */
        $attribs = array();
        $attribs['add']['onclick'] = "wnd_privilege_fill(this,'add')";

        /* 初始化页面信息 */
        $tpl['title'] = admin_privilege_name_fk('privilege.php', 'list'); //权限名称
        $tpl['titleacts'] = format_module_acts($m_ab, $attribs, 'btn'); //格式化模块的操作(非内嵌)
    }
}


/* 加载视图 */
include($_CFG['DIR_ADMIN_TPL'] . 'privilege.html');
?>

<?php
/**
 * 填写时所需的HTML控件
 */
function ctl_fill( $act )
{
    global $_LANG, $tpl;

    /* 所属模块 */
    $attrib = array('style'=>'width:153px');
    $append = array('value'=>'', 'text'=>$_LANG['ddl_sel']);
    $tpl['formc_module'] = ddl_module('parent_id', $tpl['privilege']['module_id'], $append, $attrib);
}

/**
 * 取得POST过来的权限字段
 */
function post_privilege( $act )
{
    global $_LANG;

    /* 基本字段提取 */
    $fields = array();
    $fields['name']            = trim($_POST['name']);
    $fields['order']           = intval($_POST['order']);
    $fields['module_id']       = intval($_POST['parent_id']);
    $fields['module_act_code'] = trim($_POST['module_act_code']);
    $fields['module_act_name'] = trim($_POST['module_act_name']);

    /* 字段值检查 */
    if( $fields['name'] == '' ){
        make_json_fail($_LANG['fill_privilege_name']);
    }
    if( $fields['module_id'] == 0 ){
        make_json_fail($_LANG['fill_privilege_module']);
    }
    if( $fields['module_act_name'] == '' ){
        make_json_fail($_LANG['fill_privilege_aname']);
    }
    if( $fields['module_act_code'] == '' ){
        make_json_fail($_LANG['fill_privilege_acode']);
    }

    /* 字段值检查 - 权限重复检查 */
    $filter = array();
    $filter['module_id'] = $fields['module_id'];
    $filter['privilege_id'] = $_POST['privilege_id'];
    $filter['module_act_code'] = $fields['module_act_code'];

    if( exist_privilege($filter) ){
        make_json_fail($_LANG['fill_privilege_exist']);
    }

    return $fields;
}
?>