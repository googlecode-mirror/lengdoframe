<?php
// +----------------------------------------------------------------------
// | LengdoFrame - 模块管理
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
require('../../includes/lib_lrtree.php');
require('../../includes/lib_module.php');


/* ------------------------------------------------------ */
// - 运行时语言
/* ------------------------------------------------------ */
init_temp_lang('module.php');


/* ------------------------------------------------------ */
// - 异步 - 增加
/* ------------------------------------------------------ */
if( $_REQUEST['act'] == 'add' ){
    /* 权限检查 */
    admin_privilege_valid('module.php', 'add');

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
    admin_privilege_valid('module.php', 'add');

    /* 数据提取 */
    $fields = post_module('add');

    /* 参照信息 */
    $filter = array();
    $filter['table']     = tname('module');
    $filter['primary']   = 'module_id';
    $filter['parent_id'] = $_POST['parent_id'];

    /* 数据写入 */
    if( lrtree_insert($fields, $filter) ){
        /* 初始化所有管理员的权限文件时间，刷新权限系统和系统提示 */
        admin_pfile_init(0); flush_privilege_sys(); make_json_ok();
    }
}


/* ------------------------------------------------------ */
// - 异步 - 编辑
/* ------------------------------------------------------ */
elseif( $_REQUEST['act'] == 'edit' ){
    /* 权限检查 */
    admin_privilege_valid('module.php', 'edit');

    /* 模块信息 */
    $tpl['module'] = info_module( array('module_id'=>$_GET['module_id']) );

    /* 初始化页面信息 */
    $tpl['_body']  = 'edit';
    $tpl['_block'] = true;
}
/* ------------------------------------------------------ */
// - 异步 - 更新数据库
/* ------------------------------------------------------ */
elseif( $_REQUEST['act'] == 'update' ){
    /* 权限检查 */
    admin_privilege_valid('module.php', 'edit');

    /* 数据提取 */
    $fields = post_module('edit');

    /* 数据更新 */
    if( $db->update(tname('module'), $fields, 'module_id='.intval($_POST['module_id'])) ){
        /* 初始化所有管理员的权限文件时间，刷新权限系统和系统提示 */
        admin_pfile_init(0); flush_privilege_sys(); make_json_ok();
    }
}


/* ------------------------------------------------------ */
// - 异步 - 更新字段
/* ------------------------------------------------------ */
elseif( $_REQUEST['act'] == 'ufield' ){
    /* 权限检查 */
    admin_privilege_valid('module.php', 'edit');

    /* 更新字段 - 隐藏模块 */
    if( $_POST['field'] == 'hidden' ){
        /* 更新数据库 */
        $db->update( tname('module'), array( 'hidden'=>(intval($_POST['val'])?0:1) ), 'module_id='.intval($_POST['id']) ); 

        /* 初始化所有管理员的权限文件时间，刷新权限系统和系统提示 */
        admin_pfile_init(0); flush_privilege_sys(); make_json_ok();
    }

    make_json_fail();
}


/* ------------------------------------------------------ */
// - 异步 - 删除
/* ------------------------------------------------------ */
elseif( $_REQUEST['act'] == 'del' ){
    /* 权限检查 */
    admin_privilege_valid('module.php', 'del');

    /* 模块信息 */
    $info = info_module( array('module_id'=>$_POST['id']) );

    /* 删除模块 */
    del_module( array('info'=>$info) );

    /* 初始化所有管理员的权限文件时间，刷新权限系统和系统提示 */
    admin_pfile_init(0); flush_privilege_sys(); make_json_ok();
}


/* ------------------------------------------------------ */
// - 异步 - 移动
/* ------------------------------------------------------ */
elseif( $_REQUEST['act'] == 'updown' ){
    /* 权限检查 */
    admin_privilege_valid('module.php', 'list');

    /* 参照信息 */
    $filter = array();
    $filter['table']      = tname('module');
    $filter['primary']    = 'module_id';
    $filter['primary_id'] = $_POST['id'];

    /* 节点移动 */
    if( $_POST['updown'] == 'up' ){
        if( !lrtree_umove($filter) ) make_json_fail();
    }else{
        if( !lrtree_dmove($filter) ) make_json_fail();
    }

    /* 初始化所有管理员的权限文件时间，刷新权限系统和系统提示 */
    admin_pfile_init(0); flush_privilege_sys(); make_json_ok();
}


/* ------------------------------------------------------ */
// - 异步 - 默认首页，列表页
/* ------------------------------------------------------ */
else{
    /* 权限检查 */
    admin_privilege_valid('module.php', 'list');

    /* 取得管理员的增加操作和非增加、列表操作 */
    $m_aa = admin_module_acts('module.php');
    $m_ab = filter_module_acts($m_aa, array('add'), true);
    $m_ac = filter_module_acts($m_aa, array('add','list'), false);

    array_unshift( $m_ac, array('module_act_name'=>$_LANG['act_dmove'], 'module_act_code'=>'dmove') );
    array_unshift( $m_ac, array('module_act_name'=>$_LANG['act_umove'], 'module_act_code'=>'umove') );

    /* 模块列表 */
    $tpl['all'] = all_module();

    /* 模块列表 - 数据重构 */
    foreach( $tpl['all'] AS $i => $r ){
        $tpl['all'][$i]['pre']  = '<span class="';
        $tpl['all'][$i]['pre'] .= ($r['lvl']>1?'minus':'plus') .'" style="';
        $tpl['all'][$i]['pre'] .= ($r['rht']-$r['lft']) > 1 ? 'cursor:pointer;' : '';
        $tpl['all'][$i]['pre'] .= 'margin-left:'. intval($r['lvl']-$tpl['all'][0]['lvl'])*2 .'em;" ';
        $tpl['all'][$i]['pre'] .= 'onclick="tabletree_click(this)"></span>';

        /* 编辑操作 */
        $attribs = array();
        $attribs['edit']['onclick'] = "wnd_module_fill(this,'edit',{$r[module_id]})";

        /* 上/下移操作 */
        $attribs['umove']['onclick'] = "deal_tbltr_move(this,'up',{$r[module_id]},'modules/kernel/module.php')";
        $attribs['dmove']['onclick'] = "deal_tbltr_move(this,'down',{$r[module_id]},'modules/kernel/module.php')";

        /* 增加操作 */
        if( $r['lft'] != $r['rht']-1 ){
            $attribs['add']['onclick'] = "wnd_module_fill(this,'add',{$r[module_id]})";
            $tpl['all'][$i]['acts']    = format_module_acts($m_ab, $attribs, 'a');
        }

        /* 权限搜索操作 */
        if( $r['lft'] == $r['rht']-1 && admin_privilege_valid('privilege.php','list',false) ){
            $tpl['all'][$i]['acts'] .= "<a href=\"javascript:void(0)\" onclick=\"module_mtree_request('modules/kernel/privilege.php?act=index&module_id={$r[module_id]}";
            $tpl['all'][$i]['acts'] .= "',true)\">". $_LANG['act_priv'] .'</a> ';
        }

        /* 删除操作 */
        $attribs['del']['onclick'] = $r['lft'] == $r['rht'] - 1 ? 
                                     "ListTable.del(this,{$r[module_id]},'". f(sprintf($_LANG['spr_confirm_del'],$r['name']),'hstr') ."')" : 
                                     "wnd_alert('{$_LANG[warn_module_dels]}'); return false;";

        /* 绑定操作 */
        $tpl['all'][$i]['acts'] .= format_module_acts($m_ac, $attribs, 'a');
    }

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
        make_json_ok( '', tpl_fetch('module.html',$tpl) );
    }

    /* ------------------------------------------------------ */
    // - 异步 - 默认首页
    /* ------------------------------------------------------ */
    else{
        /* 操作属性 */
        $attribs = array();
        $attribs['add']['onclick'] = "wnd_module_fill(this,'add')";

        /* 初始化页面信息 */
        $tpl['title'] = admin_privilege_name_fk('module.php', 'list'); //权限名称
        $tpl['titleacts'] = format_module_acts($m_ab, $attribs, 'btn'); //格式化模块的操作(非内嵌)
    }
}


/* 加载视图 */
include($_CFG['DIR_ADMIN_TPL'] . 'module.html');
?>

<?php
/**
 * 填写时所需的HTML控件
 */
function ctl_fill( $act )
{
    global $_LANG, $tpl;

    /* 所属模块 */
    $attrib = array('style'=>'width:154px');
    $append = array('value'=>'1', 'text'=>$_LANG['ddl_top_module']);
    $tpl['formc_pmodule'] = ddl_module('parent_id', $_GET['parent_id'], $append, $attrib);
}

/**
 * 取得POST过来的模块字段
 */
function post_module( $act )
{
    global $_LANG;

    /* 基本字段提取 */
    $fields = array();
    $fields['name'] = trim($_POST['name']);
    $fields['file'] = trim($_POST['file']);

    /* 字段值检查 */
    if( $fields['name'] == '' ){
        make_json_fail($_LANG['fill_module_name']);
    }
    if( $fields['file'] == '' ){
        make_json_fail($_LANG['fill_module_file']);
    }
    if( exist_module(array('file'=>$fields['file'],'module_id'=>$_POST['module_id'])) ){
        make_json_fail($_LANG['fill_module_exist']);
    }

    return $fields;
}
?>