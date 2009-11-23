<?php
// +----------------------------------------------------------------------
// | LengdoFrame - 我的帐号模块
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
// - 异步 - 更新我的帐号
/* ------------------------------------------------------ */
if( $_REQUEST['act'] == 'update' ){
    /* 权限检查 */
    admin_privilege_valid('sysmodule.php', 'myaccount');

    /* 数据提取 */
    $fields = post_myaccount();

    /* 更新数据库 */
    if( !empty($fields) ){
        /* 更新数据库 */
        $db->update( tname('admin'), $fields, 'admin_id='.admin_id() );

        /* 系统提示 */
        make_json_ok($_LANG['ok_myaccount_upassword']);
    }

    /* 系统提示 */
    make_json_ok();
}


/* ------------------------------------------------------ */
// - 异步 - 我的帐号(默认页)
/* ------------------------------------------------------ */
else{
    /* 权限检查 */
    admin_privilege_valid('sysmodule.php', 'myaccount');

    /* 管理员信息 */
    $tpl['info'] = info_admin( array('admin_id'=>admin_id()) );
}


/* 加载视图 */
include(DIR_ADMIN_TPL.'my_account.html');
?>

<?php
/**
 * 取得POST过来的帐号字段
 */
function post_myaccount()
{
    global $_LANG;

    /* 基本字段提取 */
    $fields = array();
    $fields['password'] = trim($_POST['password']);

    /* 字段值检查 */
    if( $fields['password'] != trim($_POST['passwordr']) ){
        make_json_fail($_LANG['fill_myaccount_pwdr']);
    }

    /* 字段值重构 */
    if( $fields['password'] == '' ){
        unset($fields['password']);
    }else{
        $fields['password'] = md5($fields['password']);
    }

    return $fields;
}
?>