<?php
// +----------------------------------------------------------------------
// | LengdoFrame - 管理员模块
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
// - 运行时语言
/* ------------------------------------------------------ */
init_temp_lang('admin.php');


/* ------------------------------------------------------ */
// - 异步 - 增加
/* ------------------------------------------------------ */
if( $_REQUEST['act'] == 'add' ){
    /* 权限检查 */
    admin_privilege_valid('admin.php', 'add');

    /* HTML 控件 */
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
    admin_privilege_valid('admin.php', 'add');

    /* 数据提取并初始化$_POST */
    $fields = post_admin('add');

    /* 允许写入检查 */
    allow_write($fields);

    /* 数据写入 */
    if( $db->insert(tname('admin'), $fields) ){
        /* 写入管理员权限 */
        post_privilege_insert( $db->insertId() );

        /* 写入日志和系统提示 */
        admin_log($_LANG['add:'].$fields['name']); make_json_ok();
    }
}


/* ------------------------------------------------------ */
// - 异步 - 编辑
/* ------------------------------------------------------ */
elseif( $_REQUEST['act'] == 'edit' ){
    /* 权限检查 */
    admin_privilege_valid('admin.php', 'edit');

    /* 管理员信息(连表角色，拥有信任的角色信息) */
    $tpl['admin'] = info_admin( array('admin_id'=>$_GET['admin_id']) );

    /* 允许编辑检查 */
    allow_edit($tpl['admin']);

    /* HTML 控件 */
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
    admin_privilege_valid('admin.php', 'edit');

    /* 管理员信息(连表角色，拥有信任的角色信息) */
    $info = info_admin( array('admin_id'=>$_POST['admin_id']) );

    /* 允许编辑检查 */
    allow_edit($info);

    /* 数据提取并初始化$_POST */
    $fields = post_admin('edit');

    /* 允许写入检查 */
    allow_write($fields);

    /* 数据更新 */
    if( $db->update(tname('admin'), $fields, 'admin_id='.$info['admin_id']) ){
        /* 更新管理员权限 - 准备 - 删除管理员细粒度权限(仅删除所有在当前管理员的细粒度权限里的权限) */
        del_admin_privilege( array('admin_id'=>$info['admin_id'], 'privilege_ids'=>admin_privilege_ids()) );

        /* 更新管理员权限 */
        post_privilege_insert($info['admin_id']);

        /* 删除管理员的权限文件 */
        @unlink( admin_pfile($info['username']) );

        /* 写入日志和系统提示 */
        admin_log($_LANG['edit:'].$info['name']); make_json_ok();
    }
}

/* ------------------------------------------------------ */
// - 异步 - 更新字段
/* ------------------------------------------------------ */
elseif( $_REQUEST['act'] == 'ufield' ){
    /* 权限检查 */
    admin_privilege_valid('admin.php', 'edit');

    /* 更新字段 - 管理员姓名，管理员帐号 */
    if( in_array($_POST['field'], array('name','username')) ){
        /* 数据检查 */
        post_admin_check( array($_POST['field']=>$_POST['val']) );

        /* 管理员信息 */
        $info = info_admin( array('admin_id'=>$_POST['id']) );

        /* 更新数据库 */
        $db->update( tname('admin'), array($_POST['field']=>trim($_POST['val'])), 'admin_id='.$info['admin_id'] );

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
    admin_privilege_valid('admin.php', 'del');

    /* 管理员信息(连表角色，拥有信任的角色信息) */
    $info = info_admin( array('admin_id'=>$_POST['id']) );

    /* 允许删除检查 */
    allow_del($info);

    /* 删除管理员 */
    del_admin( array('admin_id'=>$info['admin_id']) );

    /* 删除管理员的权限文件 */
    @unlink( admin_pfile($info['username']) );

    /* 写入日志和系统提示 */
    admin_log($_LANG['del:'].$info['name']); make_json_ok();
}


/* ------------------------------------------------------ */
// - 异步 - 导出
/* ------------------------------------------------------ */
elseif( $_REQUEST['act'] == 'exportdo' ){
    /* 权限检查 */
    admin_privilege_valid('admin.php', 'list');

    /* 要导出的字段 */
    $fields['sql'] = tname('admin').'.username,'. tname('admin') .'.name,'. tname('role') .'.name AS role_name';
    $fields['filter'] = 'username,name,role_name';

    /* 导出当页数据 */
    if( $_POST['limit'] == 'page' ){
        /* 取得列表数据 */
        $list = list_admin( array('role_info'=>$_PRIV['role']) );

        /* 导出数据 */
        list_export('admin.csv', $list['data'], $fields['filter']);
    }


    /* 导出所有数据或选中数据 */

    /* 构建数据提取SQL - 导出所有比自己角色等级低的管理员 */
    $sql = 'SELECT '. $fields['sql'] .' FROM '. tname('admin') .' LEFT JOIN '. tname('role') .' USING(role_id) WHERE';

    $sql.= admin_id() == 1 ? ' ('. tname('admin') .'.role_id = 0' : ' 1<>1';
    $sql.= ' OR ('. tname('role') .'.lft>'. $_PRIV['role']['lft'];
    $sql.= ' AND '. tname('role') .'.rht<'. $_PRIV['role']['rht'] .'))';

    if( $_POST['limit'] == 'choice' ){
        if( is_array($_POST['ids']) || !empty($_POST['ids']) ){
            $sql .= ' AND admin_id IN("'. implode('","', $_POST['ids']) .'")';
        }else{
            $sql .= ' LIMIT 0,0';
        }
    }

    /* 导出数据 */
    list_export( 'admin.csv', $db->getAll($sql) );
}


/* ------------------------------------------------------ */
// - 异步 - 默认首页，列表页
/* ------------------------------------------------------ */
else{
    /* 权限检查 */
    admin_privilege_valid('admin.php', 'list');

    /* 取得管理员的非增加、列表操作 */
    $m_aa = admin_module_acts('admin.php');
    $m_ac = filter_module_acts($m_aa, array('add','list'), false);

    /* 管理员列表(子级管理员) - 如果是当前管理员ID为1，则列出所有无角色的管理员 */
    $tpl['list'] = list_admin( array('role_info'=>$_PRIV['role']) );

    /* 管理员列表 - 数据重构，绑定操作权限 */
    foreach( $tpl['list']['data'] AS $i => $r ){
        /* 编辑操作 */
        $attribs = array();
        $attribs['edit']['onclick'] = "wnd_admin_fill(this,'edit',{$r[admin_id]})";

        /* 删除操作 */
        $attribs['del']['onclick']  = "ListTable.del(this,{$r[admin_id]},'";
        $attribs['del']['onclick'] .= f(sprintf($_LANG['spr_confirm_del'],$r['name']),'hstr') ."')";

        /* 绑定操作 */
        $tpl['list']['data'][$i]['acts'] = format_module_acts($m_ac, $attribs, 'a');
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
            /* 排序图片 */
            $flag = order_img($tpl['list']['filter']);
            $tpl[$flag['var']] = $flag['img'];

            /* 初始化页面信息 */
            $tpl['_bodysub'] = 'query';
        }

        /* 返回JSON */
        make_json_ok( '', tpl_fetch('admin.html',$tpl) );
    }

    /* ------------------------------------------------------ */
    // - 异步 - 默认首页
    /* ------------------------------------------------------ */
    else{
        /* 取得管理员的增加操作 */
        $m_ab   = filter_module_acts($m_aa, array('add'), true);
        $m_ab[] = array('module_act_name'=>$_LANG['act_export'],'module_act_code'=>'export');

        /* 操作属性 */
        $attribs = array();
        $attribs['add'] = array('onclick'=>"wnd_admin_fill(this,'add')");

        $attribs['export']['icon']     = 'xls';
        $attribs['export']['type']     = 'cddl';
        $attribs['export']['title']    = $_LANG['act_export_choice'];
        $attribs['export']['onclick']  = "deal_list_export('listtable-admin',null,'modules/admin/admin.php?act=exportdo','choice')";
        $attribs['export']['ddlwidth'] = '120';

        /* 构建管理员的导出操作 */
        $items = array();
        $items['export'][] = array('icon'=>'all'    ,'text'=>$_LANG['act_export_all']    ,'onclick'=>"deal_list_export('listtable-admin',null,'modules/admin/admin.php?act=exportdo','all')");
        $items['export'][] = array('icon'=>'page'   ,'text'=>$_LANG['act_export_page']   ,'onclick'=>"deal_list_export('listtable-admin',null,'modules/admin/admin.php?act=exportdo','page')");
        $items['export'][] = array('icon'=>'choice' ,'text'=>$_LANG['act_export_choice'] ,'onclick'=>"deal_list_export('listtable-admin',null,'modules/admin/admin.php?act=exportdo','choice')");

        /* 初始化页面信息 */
        $tpl['title'] = admin_privilege_name_fk('admin.php', 'list'); //权限名称
        $tpl['titleacts'] = format_module_acts($m_ab, $attribs, 'btn', $items); //格式化模块的操作(非内嵌)
    }
}


/* 加载视图 */
include($_CFG['DIR_ADMIN_TPL'] . 'admin.html');
?>

<?php
/**
 * 填写时所需的HTML控件
 */
function ctl_fill( $act )
{
    global $_PRIV, $_LANG, $tpl;

    /* 小于当前管理员角色的角色下拉框 */
    $sub_role = sub_role( array('info'=>$_PRIV['role']), false );

    if( empty($sub_role) ){
        $tpl['formc_role'] = '<span style="color:#ff0000">&nbsp;'. $_LANG['fill_admin_role'] .'</span>';
    }else{
        $tpl['formc_role'] = ddl_role_custom($sub_role, 'role_id', $tpl['admin']['role_id'], array(), array('style'=>'width:153px'));
    }

    /* 辅助权限表 */
    $privilege_ids = $act == 'add' ? array() : privilege_ids( array('admin_id'=>$_GET['admin_id']) );  //提交的管理员的细粒度权限IDS
    $tpl['html_privilege_table'] = html_privilege_table( 'privilege_ids', $privilege_ids, admin_privilege_ids() );
}

/**
 * 取得POST过来的管理员字段
 */
function post_admin( $act )
{
    /* 基本字段提取 */
    $fields = array();
    $fields['name']     = trim($_POST['name']);
    $fields['role_id']  = intval($_POST['role_id']);
    $fields['username'] = trim($_POST['username']);
    $fields['password'] = trim($_POST['password']);

    /* 增加时字段提取 */
    if( $act == 'add' ) $fields['in_time'] = time();

    /* 字段值检查 */
    post_admin_check($fields, $act);

    /* 字段值重构 */
    if( $act == 'edit' && $fields['password'] == '' ){
        unset($fields['password']);
    }
    if( isset($fields['password']) ){
        $fields['password'] = md5($fields['password']);
    }

    /* $_POST值初始化 */
    if( !is_array($_POST['privilege_ids']) ){
        $_POST['privilege_ids'] = array();
    }

    return $fields;
}
function post_admin_check( $fields = array(), $act = '' )
{
    global $_LANG;

    if( isset($fields['role_id']) && $fields['role_id'] <= 0 ){
        make_json_fail($_LANG['fill_admin_role']);
    }
    if( isset($fields['username']) && $fields['username'] == '' ){
        make_json_fail($_LANG['fill_admin_usr']);
    }
    if( isset($fields['username']) && exist_admin( array('username'=>$fields['username'],'admin_id'=>$_POST['admin_id']) ) ){
        make_json_fail($_LANG['fill_admin_exist']);
    }
    if( isset($fields['name']) && $fields['name'] == '' ){
        make_json_fail($_LANG['fill_admin_name']);
    }

    if( $act == 'add' && isset($fields['password']) && $fields['password'] == '' ){
        make_json_fail($_LANG['fill_admin_pwd']);
    }
}

/**
 * 权限写入数据库
 */
function post_privilege_insert( $admin_id )
{
    if( empty($_POST['privilege_ids']) ) return ;

    /* 构建权限写入SQL */
    $sql = 'INSERT '. tname('admin_privilege') .'(admin_id,privilege_id) VALUES ';
    foreach( $_POST['privilege_ids'] AS $id ){
        $sql .= '("'. intval($admin_id) .'","'. intval($id) .'"),';
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

    /* 无效的提交管理员信息 */
    if( empty($info) ){
        sys_msg($_LANG['lawless_submit']);
    }

    /* 提交的管理员ID自身限制检查(必须不等于当前管理员ID和系统内置管理员ID) */
    if( $info['admin_id'] == admin_id() || $info['admin_id'] == 1 ){
        sys_msg($_LANG['lawless_submit']);
    }

    /* 提交的管理员为空角色时检查(只能有超级管理员能编辑) */
    if( empty($info['lft']) ){
        if( $_PRIV['role']['role_id'] != 1 ){
            sys_msg($_LANG['lawless_submit']);
        }
    }else{
        /* 当前管理员越权编辑管理员检查(当前管理员角色必须大于提交的管理员角色) */
        $filter1 = array('info'=>$_PRIV['role']);
        $filter2 = array('info'=>$info);

        if( cmp_role($filter1, $filter2) != '>' ){
            sys_msg($_LANG['lawless_submit']);
        }
    }
}

/**
 * 允许删除检查 - 角色越权检查
 */
function allow_del( $info )
{
    allow_edit($info);
}

/**
 * 允许写入检查 - 角色和细粒度权限越权检查
 */
function allow_write( $fields )
{
    global $_LANG, $_PRIV;

    /* 提交的管理员的角色信息 */
    $role_info = info_role( array('role_id'=>$fields['role_id']) );

    /* 无效的角色信息 */
    if( empty($role_info) ){
        sys_msg($_LANG['lawless_submit']);
    }

    /* 对提交的管理员的角色进行越权检查(当前管理员角色必须大于提交的角色) */
    $filter1 = array('info'=>$_PRIV['role']);
    $filter2 = array('info'=>$role_info);

    if( cmp_role($filter1, $filter2) != '>' ){
        sys_msg($_LANG['lawless_submit']);
    }

    /* 对提交的管理员的细粒度权限进行越权检查(当前管理员细粒度权限必须大于等于提交的细粒度权限) */
    $filter1 = array('privilege_ids'=>admin_privilege_ids());
    $filter2 = array('privilege_ids'=>$_POST['privilege_ids']);

    $flag = cmp_privilege($filter1, $filter2);

    if( $flag == '<' || $flag == false ){
        sys_msg($_LANG['lawless_submit']);
    }
}
?>