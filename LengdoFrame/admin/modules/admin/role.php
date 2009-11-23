<?php
// +----------------------------------------------------------------------
// | LengdoFrame - 管理员角色模块
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
require('../../includes/lib_module.php');
require('../../includes/lib_lrtree.php');


/* ------------------------------------------------------ */
// - 运行时语言
/* ------------------------------------------------------ */
init_temp_lang('role.php');


/* ------------------------------------------------------ */
// - 异步 - 增加
/* ------------------------------------------------------ */
if( $_REQUEST['act'] == 'add' ){
    /* 权限检查 */
    admin_privilege_valid('role.php', 'add');

    /* 子角色(包括自身) */
    $sub_role = sub_role( array('info'=>$_PRIV['role']), true );

    /* 角色权限IDS */
    $role_priv_ids = all_role_privilege_id( array('role_id'=>$_PRIV['role']['role_id']) );

    /* 角色改变JS事件，触发更改角色的权限表 */
    $onchange = "Ajax.call('modules/admin/role.php?act=privtable&prole_id='+this.value,'',function(result,text){document.getElementById('div-role-privilegetbl').innerHTML=text})";

    /* HTML控件 */
    $tpl['formc_role'] = ddl_role_custom( $sub_role, 'parent_id', '', array(), array('style'=>'width:130px','onchange'=>$onchange) );
    $tpl['html_privilege_table'] = html_privilege_table('privilege_ids', '', $role_priv_ids);

    /* 初始化页面信息 */
    $tpl['_body']  = 'add';
    $tpl['_block'] = true;
}
/* ------------------------------------------------------ */
// - 异步 - 写入数据库
/* ------------------------------------------------------ */
elseif( $_REQUEST['act'] == 'insert' ){
    /* 权限检查 */
    admin_privilege_valid('role.php', 'add');

    /* 父角色信息 */
    $info_p = info_role( array('role_id'=>$_POST['parent_id']) );

    /* 数据提取并初始化$_POST */
    $fields = post_role('add');

    /* 允许写入检查 */
    allow_write($info_p);

    /* 参照信息 */
    $filter = array();
    $filter['table']   = tname('role');
    $filter['info_p']  = $info_p;
    $filter['primary'] = 'role_id';

    /* 数据写入 */
    if( lrtree_insert($fields, $filter) ){
        /* 写入角色权限 */
        post_privilege_insert( $db->insertId() );

        /* 写入日志 */
        admin_log($_LANG['add:'].$fields['name']);

        /* 初始化管理员的权限文件时间, 刷新权限系统和系统提示 */
        admin_pfile_init(0); flush_privilege_sys(); make_json_ok();
    }
}


/* ------------------------------------------------------ */
// - 异步 - 编辑
/* ------------------------------------------------------ */
elseif( $_REQUEST['act'] == 'edit' ){
    /* 权限检查 */
    admin_privilege_valid('role.php', 'edit');

    /* 角色信息 */
    $tpl['role'] = info_role( array('role_id'=>$_GET['role_id']) );

    /* 允许编辑检查 */
    allow_edit($tpl['role']);

    /* 父角色信息 */
    $info_p = info_parent_role( array('info'=>$tpl['role']) );

    /* 角色权限 */
    $seled_priv_ids = all_role_privilege_id( array('role_id'=>$tpl['role']['role_id']) );
    $shows_priv_ids = all_role_privilege_id( array('role_id'=>$info_p['role_id']) );

    /* HTML 控件 */
    $tpl['formc_role'] = '<h2>'. f($info_p['name'], 'html') .'</h2>';
    $tpl['html_privilege_table'] = html_privilege_table('privilege_ids', $seled_priv_ids, $shows_priv_ids);

    /* 初始化页面信息 */
    $tpl['_body']  = 'edit';
    $tpl['_block'] = true;
}
/* ------------------------------------------------------ */
// - 异步 - 更新数据库
/* ------------------------------------------------------ */
elseif( $_REQUEST['act'] == 'update' ){
    /* 权限检查 */
    admin_privilege_valid('role.php', 'edit');

    /* 角色信息 */
    $info = info_role( array('role_id'=>$_POST['role_id']) );

    /* 允许编辑检查 */
    allow_edit($info);

    /* 数据提取并初始化$_POST */
    $fields = post_role('edit');

    /* 允许写入检查 */
    allow_write( info_parent_role(array('info'=>$info)) );


    /* ------ 当对角色的某一权限撤销时，其下级角色对该权限的拥有权也将被撤销 ---- */

    /* 取得提交的角色丢失的权限IDS */
    $role_privilege_ids = all_role_privilege_id( array('role_id'=>$info['role_id']) );
    $lost_privilege_ids = array_diff($role_privilege_ids, $_POST['privilege_ids']);

    /* 删除角色小于被编角色的且权限在 $lost_privilege_ids 中的权限 */
    $sub_role_ids = sub_role_id( array('info'=>$info), false );
    del_role_privilege( array('role_ids'=>$sub_role_ids,'privilege_ids'=>$lost_privilege_ids) );

    /* ---------------------------------- END ----------------------------------- */


    /* 数据更新 */
    if( $db->update(tname('role'), $fields, 'role_id='.$info['role_id']) ){
        /* 删除角色权限 */
        del_role_privilege( array('role_id'=>$info['role_id']) );

        /* 写入角色权限 */
        post_privilege_insert($info['role_id']);

        /* 写入日志 */
        admin_log($_LANG['edit:'].$info['name']);

        /* 初始化管理员的权限文件时间，刷新权限系统和系统提示 */
        admin_pfile_init(0); flush_privilege_sys(); make_json_ok();
    }
}


/* ------------------------------------------------------ */
// - 异步 - 更新字段
/* ------------------------------------------------------ */
elseif( $_REQUEST['act'] == 'ufield' ){
    /* 权限检查 */
    admin_privilege_valid('role.php', 'edit');

    /* 更新字段 - 角色名称 */
    if( in_array($_POST['field'], array('name')) ){
        /* 数据检查 */
        post_role_check( array($_POST['field']=>$_POST['val']) );

        /* 角色信息 */
        $info = info_role( array('role_id'=>$_POST['id']) );

        /* 更新数据库 */
        $db->update( tname('role'), array($_POST['field']=>trim($_POST['val'])), 'role_id='.$info['role_id'] );

        /* 写入日志和系统提示 */
        admin_log($_LANG['edit:'].$info['name']); make_json_ok();
    }

    make_json_fail();
}


/* ------------------------------------------------------ */
// - 异步 - 删除
/* ------------------------------------------------------ */
elseif( $_REQUEST['act'] == 'del' ){
    /* 权限检查 */
    admin_privilege_valid('role.php', 'del');

    /* 角色信息 */
    $info = info_role( array('role_id'=>$_POST['id']) );

    /* 允许删除检查 */
    allow_del($info);

    /* 删除角色 */
    del_role( array('info'=>$info) );

    /* 写入日志 */
    admin_log($_LANG['del:'].$info['name']);

    /* 初始化管理员的权限文件时间，刷新权限系统和系统提示 */
    admin_pfile_init(0); flush_privilege_sys(); make_json_ok();
}


/* ------------------------------------------------------ */
// - 异步 - 移动
/* ------------------------------------------------------ */
elseif( $_REQUEST['act'] == 'updown' ){
    /* 权限检查 */
    admin_privilege_valid('role.php', 'list');

    /* 参照信息 */
    $filter = array();
    $filter['table']      = tname('role');
    $filter['primary']    = 'role_id';
    $filter['primary_id'] = $_POST['id'];

    /* 节点移动 */
    if( $_POST['updown'] == 'up' ){
        lrtree_umove($filter) ? make_json_ok() : make_json_fail();
    }else{
        lrtree_dmove($filter) ? make_json_ok() : make_json_fail();
    }
}


/* ------------------------------------------------------ */
// - 异步 - 角色的权限表
/* ------------------------------------------------------ */
elseif( $_REQUEST['act'] == 'privtable' ){
    /* 权限检查 */
    admin_privilege_valid('role.php', 'list');

    /* 上级角色信息 */
    $info_p = info_role( array('role_id'=>$_GET['prole_id']) );

    /* 允许获取权限表检查 */
    allow_privtable($info_p);

    /* 角色权限IDS */
    $role_priv_ids = all_role_privilege_id( array('role_id'=>$info_p['role_id']) );

    /* 角色权限表 */
    if( empty($role_priv_ids) ){
        exit('<div class="warn-div"><span>'. $_LANG['str_role_nopriv'] .'</span></div>' );
    }else{
        exit( html_privilege_table('privilege_ids', '', $role_priv_ids) );
    }
}


/* ------------------------------------------------------ */
// - 异步 - 默认首页，列表页
/* ------------------------------------------------------ */
else{
    /* 权限检查 */
    admin_privilege_valid('role.php', 'list');

    /* 取得管理员的非增加、列表操作 */
    $m_aa = admin_module_acts('role.php');
    $m_ac = filter_module_acts($m_aa, array('add','list'), false);

    array_unshift( $m_ac, array('module_act_name'=>$_LANG['act_dmove'], 'module_act_code'=>'dmove') );
    array_unshift( $m_ac, array('module_act_name'=>$_LANG['act_umove'], 'module_act_code'=>'umove') );

    /* 角色列表，小于当前管理员角色的角色 */
    $tpl['all'] = sub_role( array('info'=>$_PRIV['role']), false );

    /* 角色列表 - 数据重构，绑定操作权限 */
    foreach( $tpl['all'] AS $i => $r ){
        $tpl['all'][$i]['pre']  = '<span class="';
        $tpl['all'][$i]['pre'] .= ($r['lvl']==1&&$r['rht']-$r['lft']>1 ? 'plus':'minus') .'" style="';
        $tpl['all'][$i]['pre'] .= ($r['rht']-$r['lft']) > 1 ? 'cursor:pointer;' : '';
        $tpl['all'][$i]['pre'] .= 'margin-left:'. intval($r['lvl']-$tpl['all'][0]['lvl'])*2 .'em;" ';
        $tpl['all'][$i]['pre'] .= 'onclick="tabletree_click(this)"></span>';

        /* 编辑操作 */
        $attribs = array();
        $attribs['edit']['onclick'] = "wnd_role_fill(this,'edit',{$r[role_id]})";

        /* 上/下移操作 */
        $attribs['umove']['onclick'] = "deal_tbltr_move(this,'up',{$r[role_id]},'modules/admin/role.php')";
        $attribs['dmove']['onclick'] = "deal_tbltr_move(this,'down',{$r[role_id]},'modules/admin/role.php')";

        /* 删除操作 */
        if( ($r['rht'] - $r['lft']) > 1 ){
            $del = "var self=this;wnd_confirm('{$_LANG[warn_roles_del]}',{'ok':function(){ListTable.del(self,{$r[role_id]},'";
            $del.= f(sprintf($_LANG['spr_confirm_del'],$r['name']),'hstr');
            $del.= "')}})";
        }else{
            $del = "ListTable.del(this,{$r[role_id]},'";
            $del.= f(sprintf($_LANG['spr_confirm_del'],$r['name']),'hstr') ."')";
        }
        $attribs['del']['onclick'] = $del;

        /* 绑定操作 */
        $tpl['all'][$i]['_acts'] = format_module_acts($m_ac, $attribs, 'a');
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
        make_json_ok( '', tpl_fetch('role.html',$tpl) );
    }

    /* ------------------------------------------------------ */
    // - 异步 - 默认首页
    /* ------------------------------------------------------ */
    else{
        /* 取得管理员的增加操作 */
        $m_ab = filter_module_acts($m_aa, array('add'), true);

        /* 操作属性 */
        $attribs = array();
        $attribs['add']['onclick'] = "wnd_role_fill(this,'add')";

        /* 初始化页面信息 */
        $tpl['acts']  = format_module_acts($m_ab, $attribs, 'btn'); //格式化模块的操作(非内嵌)
        $tpl['title'] = admin_privilege_name_fk('role.php', 'list'); //权限名称
    }
}


/* 加载视图 */
include(DIR_ADMIN_TPL.'role.html');
?>

<?php
/**
 * 取得POST过来的角色字段
 */
function post_role( $act )
{
    global $_LANG;

    /* 基本字段提取 */
    $fields['name'] = trim($_POST['name']);

    /* 字段值检查 */
    post_role_check($fields, $act);

    /* $_POST值初始化 */
    if( !is_array($_POST['privilege_ids']) ){
        $_POST['privilege_ids'] = array();
    }else{
        $_POST['privilege_ids'] = array_unique($_POST['privilege_ids']);
    }

    return $fields;
}
function post_role_check( $fields = array(), $act = '' )
{
    global $_LANG;

    if( isset($fields['name']) && $fields['name'] == '' ){
        make_json_fail($_LANG['fill_role_name']);
    }
}

/**
 * 权限写入数据库
 */
function post_privilege_insert( $role_id )
{
    if( empty($_POST['privilege_ids']) ) return ;

    /* 构建权限写入SQL */
    $sql = 'INSERT '. tname('role_privilege') .'(role_id,privilege_id) VALUES ';
    foreach( $_POST['privilege_ids'] AS $id ){
        $sql .= '("'. intval($role_id) .'","'. intval($id) .'"),';
    }
    $sql = substr($sql, 0, -1);

    /* 写入数据库 */
    $GLOBALS['db']->query($sql);
}

/**
 * 允许编辑检查 - 角色越权检查
 */
function allow_edit( $info )
{
    global $_LANG, $_PRIV;

    /* 无效的角色信息 */
    if( empty($info) ){
        sys_msg($_LANG['lawless_submit']);
    }

    /* 对编辑提交的角色进行越权检查(当前管理员的角色必须大于编辑提交的角色) */
    $filter1 = array('info'=>$_PRIV['role']);
    $filter2 = array('info'=>$info);

    if( cmp_role($filter1, $filter2) != '>' ){
        sys_msg($_LANG['lawless_submit']);
    }
}

/**
 * 允许删除检查 - 角色权限越权检查
 */
function allow_del( $info )
{
    allow_edit($info);
}

/**
 * 允许写入检查 - 角色权限越权检查
 */
function allow_write( $info_p )
{
    global $_LANG, $_PRIV;

    /* 无效的父角色信息 */
    if( empty($info_p) ){
        sys_msg($_LANG['lawless_submit']);
    }

    /* 对提交的父角色进行越权检查(当前管理员的角色必须大于等于提交的父角色) */
    $filter1 = array('info'=>$_PRIV['role']);
    $filter2 = array('info'=>$info_p);

    $flag = cmp_role($filter1, $filter2);

    if( $flag == '<' || $flag == false ){
        sys_msg($_LANG['lawless_submit']);
    }

    /* 对提交的新角色权限进行越权检查(父角色的权限集必须大于等于新角色权限集) */
    $filter1 = array('role_id'=>$info_p['role_id']);
    $filter2 = array('privilege_ids'=>$_POST['privilege_ids']);

    $flag = cmp_role_privilege($filter1, $filter2);

    if( $flag == '<' || $flag == false ){
        sys_msg($_LANG['lawless_submit']);
    }
}

/**
 * 允许获取权限表检查 - 角色越权检查
 */
function allow_privtable( $info_p )
{
    global $_LANG, $_PRIV;

    /* 无效的父角色信息 */
    if( empty($info_p) ){
        sys_msg($_LANG['lawless_submit']);
    }

    /* 对申请的上级角色进行越权检查(当前管理员的角色必须大于等于申请的上级父角色) */
    $filter1 = array('info'=>$_PRIV['role']);
    $filter2 = array('info'=>$info_p);

    $flag = cmp_role($filter1, $filter2);

    if( $flag == '<' || $flag == false ){
        sys_msg($_LANG['lawless_submit']);
    }
}
?>