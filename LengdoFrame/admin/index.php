<?php
// +----------------------------------------------------------------------
// | LengdoFrame - 后台首页
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
require('includes/init.php');


/* ------------------------------------------------------ */
// - 输出图像验证码
/* ------------------------------------------------------ */
if( $_REQUEST['act'] == 'vcode' ){
    /* 加载VCode类 */
    require_once($_CFG['DIR_CLS'] . 'vcode.class.php');

    $vcode = new VCode();
    $vcode->image();
}


/* ------------------------------------------------------ */
// - 登陆
/* ------------------------------------------------------ */
elseif( $_REQUEST['act'] == 'login' ){
    /* 已登陆，直接转到系统框架页 */
    if( admin_logined() ){
        redirect($_CFG['URL_ADMIN'] . 'index.php');
    }

    /* 加载视图 */
    include($_CFG['DIR_ADMIN_TPL'] . 'login.html');
}

elseif( $_REQUEST['act'] == 'loginsubmit' ){
    /* 非法提交 */
    if( !isset($_POST['submit']) ) sys_msg($_LANG['lawless_submit']);

    /* 加载VCode类 */
    require_once($_CFG['DIR_CLS'] . 'vcode.class.php');

    $vcode = new VCode();

    /* 用户名或密码空检查 */
    if( !trim($_POST['username']) || !trim($_POST['password']) ){
        make_json_fail( trim($_POST['username']) ? $_LANG['fill_login_pwd'] : $_LANG['fill_login_usr'] );
    }

    /* [暂不使用]验证码检查 */
    if( false && $vcode->check($_POST['vcode']) == false ){
        make_json_fail($_LANG['msg_vcode_error']);
    }

    /* 登陆 */
    if( admin_login( array('username'=>$_POST['username'], 'password'=>$_POST['password']) ) ){
        admin_log($_LANG['str_login']); make_json_ok();
    }

    /* 登陆失败 */
    make_json_fail($_LANG['fail_login']);
}


/* ------------------------------------------------------ */
// - 登出
/* ------------------------------------------------------ */
elseif( $_REQUEST['act'] == 'logout' ){
    admin_log($_LANG['str_logout']); admin_logout();
}


/* ------------------------------------------------------ */
// - 系统刷新
/* ------------------------------------------------------ */
elseif( $_REQUEST['act'] == 'flush'){
    /* 刷新权限系统 */
    flush_privilege_sys(); 

    /* 跳转到后台首页 */
    redirect($_CFG['URL_ADMIN'] . 'index.php');
}


/* ------------------------------------------------------ */
// - 内容首页
/* ------------------------------------------------------ */
elseif( $_REQUEST['act'] == 'home' ){
    /* 初始化页面信息 */
    $tpl['_title'] = false; 
    
    /* 加载视图 */
    include($_CFG['DIR_ADMIN_TPL'] . 'home.html');
}


/* ------------------------------------------------------ */
// - 进入首页(默认,框架页)
/* ------------------------------------------------------ */
else{
    /* 初始化页面信息 */
    $tpl['home'] = 'index.php?act=home'; 

    /* 加载视图 */
    include($_CFG['DIR_ADMIN_TPL'] . 'index.html');
}
?>