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
// - $_CFG数据
/* ------------------------------------------------------ */
$_CFG['DIR_ADMIN_PFILE']  = $_CFG['DIR_ADMIN_PFILE'];
$_CFG['URL_ADMIN_PFILE']  = './admin/data/pfile/';

$_CFG['DIR_SYSTEMCONFIG'] = $_CFG['DIR_INC'].'_systemconfig.php';
$_CFG['URL_SYSTEMCONFIG'] = './includes/_systemconfig.php';

$_CFG['F_INSTALL_SQL'] = './lengdoframe.sql';
$_CFG['F_SYSTEMCONFIG'] = '../includes/systemconfig.php';
$_CFG['F_SYSTEMCONFIG_SRC'] = '../includes/_systemconfig.php';


/* ------------------------------------------------------ */
// - $_DBD数据
/* ------------------------------------------------------ */
$_DBD['support'] = array('0'=>'<span class="no"><i></i>不支持</span>', '1'=>'<span class="yes"><i></i>支持</span>');
$_DBD['filewrite'] = array('0'=>'<span class="no"><i></i>不可写</span>', '1'=>'<span class="yes"><i></i>可写</span>');
$_DBD['filerename'] = array('0'=>'<span class="no"><i></i>不可重命名</span>', '1'=>'<span class="yes"><i></i>可重命名</span>');

$_DBD['dberror_1007'] = '数据库 "%s" 已存在，请重新填写数据库名!';
$_DBD['dberror_1044'] = '无法创建新的数据库，请检查数据库名称填写是否正确!';
$_DBD['dberror_1045'] = '无法连接数据库，请检查数据库用户名或者密码是否正确!';
$_DBD['dberror_2003'] = '无法连接数据库，请检查数据库是否启动，数据库服务器地址是否正确!';
$_DBD['dberror_conn'] = '数据库连接错误!';
$_DBD['dberror_cret'] = '数据库创建失败!';


/* ------------------------------------------------------ */
// - 初始化步骤和操作
/* ------------------------------------------------------ */

/* 初始化 */
$acts = array('envcheck', 'dbcreate', 'complete');

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

    $tpl['files'][] = array('type'=>'dir', 'file'=>$_CFG['DIR_ADMIN_PFILE'], 'url'=>$_CFG['URL_ADMIN_PFILE']);
    $tpl['files'][] = array('type'=>'file', 'file'=>$_CFG['DIR_SYSTEMCONFIG'], 'url'=>$_CFG['URL_SYSTEMCONFIG']);

    foreach( $tpl['files'] AS $i=>$r ){
        /* 文件权限检测 */
        $filepriv = file_privilege($r['file']);

        /* 重构数据 */
        if( $r['type'] == 'dir' ){
            $tpl['files'][$i]['need'] = $_DBD['filewrite'][1];
            $tpl['files'][$i]['error'] = isset($_POST['submit']) ? 1*(($filepriv&2)==0) : 0;
            $tpl['files'][$i]['filepriv'] = $_DBD['filewrite'][ (($filepriv&2)==2)*1 ];
        }else{
            $tpl['files'][$i]['need'] = ($filepriv&8) == 0 ? $_DBD['filerename'][1] : $_DBD['filewrite'][1];
            $tpl['files'][$i]['error'] = isset($_POST['submit']) ? 1*(($filepriv&10)!=10) : 0;
            $tpl['files'][$i]['filepriv'] = ($filepriv&8) == 0 ? $_DBD['filerename'][0] : $_DBD['filewrite'][ (($filepriv&2)==2)*1 ];
        }

        /* 统计错误 */
        $tpl['error'] += $tpl['files'][$i]['error'];
    }


    /* 函数依赖检查 */

    $tpl['funcs'][] = array('func'=>'json_encode');
    $tpl['funcs'][] = array('func'=>'mysql_connect');
    $tpl['funcs'][] = array('func'=>'file_get_contents');

    foreach( $tpl['funcs'] AS $i=>$r ){
        /* 函数存在检测 */
        $funcexists = function_exists($r['func']);

        /* 重构数据 */
        $tpl['funcs'][$i]['need'] = $_DBD['support'][1];
        $tpl['funcs'][$i]['error'] = isset($_POST['submit']) ? (!$funcexists)*1 : 0;
        $tpl['funcs'][$i]['support'] = $_DBD['support'][ $funcexists*1 ];

        /* 统计错误 */
        $tpl['error'] += $tpl['funcs'][$i]['error'];
    }


    /* 初始化页面信息 */
    $tpl['_body'] = 'envcheck';
    
    $tpl['acts'] = 'n';
    $tpl['step'] = $step;
    $tpl['title'] = '环境检查';
    $tpl['subtitle'] = '文件目录权限和函数依赖性的检查';


    /* ------------------------------------------------------ */
    // - 环境检查 - 提交
    /* ------------------------------------------------------ */
    if( isset($_POST['submit']) ){
        if( $tpl['error'] == 0 ){
            /* 下一步 */
            header('location:index.php?step='.($step+1)); exit();
        }
    }
}


/* ------------------------------------------------------ */
// - 安装数据库
/* ------------------------------------------------------ */
elseif( $_REQUEST['act'] == 'dbcreate' ){
    /* 初始化注释信息 */
    $tpl['remarks']['dbhost'] = '数据库服务地址，格式为 IP[:PORT]';
    $tpl['remarks']['dbname'] = '<font style="color:#aaa">＊</font>';
    $tpl['remarks']['dbuser'] = '<font style="color:#aaa">＊</font>';
    $tpl['remarks']['admin_username'] = '<font style="color:#aaa">＊</font>';
    $tpl['remarks']['admin_password'] = '<font style="color:#aaa">＊</font>';

    /* 初始化页面信息 */
    $tpl['_body'] = 'dbcreate';

    $tpl['acts'] = 'pn';
    $tpl['step'] = $step;
    $tpl['title'] = '安装数据库';
    $tpl['subtitle'] = '配置数据库的连接数据和管理员信息';


    /* ------------------------------------------------------ */
    // - 安装数据库 - 提交
    /* ------------------------------------------------------ */
    if( isset($_POST['submit']) ){
        /* 初始化 */
        $tpl['acts'] = 'rpn';
        $tpl['errors'] = array();

        /* 填写错误检查 */
        if( trim($_POST['dbhost']) == '' ) $tpl['errors']['dbhost'] = '数据库服务地址不能为空!';
        if( trim($_POST['dbname']) == '' ) $tpl['errors']['dbname'] = '数据库名称不能为空!';
        if( trim($_POST['dbuser']) == '' ) $tpl['errors']['dbuser'] = '数据库用户名不能为空!';

        if( trim($_POST['admin_username']) == '' ) $tpl['errors']['admin_username'] = '管理员账号不能为空!';
        if( trim($_POST['admin_password']) == '' ) $tpl['errors']['admin_password'] = '管理员密码不能为空!';

        /* 填写错误检查 - 重置备注 */
        foreach( $tpl['errors'] AS $k=>$v ){
            $tpl['remarks'][$k] = $v;
        }
        
        /* 无填写错误 */
        if( empty($tpl['errors']) ){
            /* 连接数据库 */
            $link = @mysql_connect($_POST['dbhost'], $_POST['dbuser'], $_POST['dbpass']);

            /* 连接数据库错误 */
            if( !$link ){
                /* 错误信息 */
				$errno = mysql_errno();
                $tpl['errors']['error'] = $_DBD['dberror_'.$errno] ? $_DBD['dberror_'.$errno] : $_DBD['dberror_conn'];
            }
            /* 连接数据库成功 */
            else{
                /* 创建数据表 */
                if( mysql_get_server_info() > '4.1' ){
                    mysql_query("CREATE DATABASE `{$_POST['dbname']}` DEFAULT CHARACTER SET utf8");
                }else{
                    mysql_query("CREATE DATABASE `{$_POST['dbname']}`");
                }

                if( $errno = mysql_errno() ){
                    $tpl['errors']['error'] = $_DBD['dberror_'.$errno] ? sprintf($_DBD['dberror_'.$errno],$_POST['dbname']) : $_DBD['dberror_cret'];
                }else{
                    mysql_select_db($_POST['dbname']);
                }
            }

            /* 数据库创建成功 */
            if( empty($tpl['errors']['error']) ){
                /* 数据库导入 */
                if( sql_import() ){
                    /* 构建系统配置文件 */
                    file_systemconfig();

                    /* 更新管理员 */
                    update_administrator();
                    
                    /* 下一步 */
                    header('location:index.php?step='.($step+1)); exit();
                }
            }
        }
    }
}


/* ------------------------------------------------------ */
// - 安装完成
/* ------------------------------------------------------ */
elseif( $_REQUEST['act'] == 'complete' ){
    /* 跳转地址 */
    $tpl['redirect'] = $_CFG[URL_ADMIN].'index.php';

    /* 初始化页面信息 */
    $tpl['_body'] = 'complete';
    
    $tpl['acts'] = 'c';
    $tpl['step'] = $step;
    $tpl['title'] = '安装成功';
    $tpl['subtitle'] = '完成LengdoFrame框架的安装';
}


/* 加载视图 */
include('tpl/index.html')
?>

<?php
/**
 * SQL导入
 */
function sql_import()
{
    /* 初始化 */
    global $_CFG;

    /* 初始化SQL数组 */
    $sqls = array_filter(file($_CFG['F_INSTALL_SQL']), 'sql_comment_remove');
    $sqls = str_replace( "\r", '', implode('',$sqls) );
    $sqls = explode(";\n", $sqls);

    /* 执行SQL语句 */
    foreach( $sqls AS $i=>$sql ){
        $sql = trim($sql, " \r\n;"); //移除多余信息
        $sql = str_replace('%tblpre%', $_POST['tblpre'], $sql); //替换数据表前辍

        if( empty($sql) ) continue;

        if( !mysql_query($sql) ){
            return false;
        }
    }

    return true;
}

/**
 * 移除SQL注释
 */
function sql_comment_remove($var)
{
    return (substr(trim($var), 0, 2) != '--');
}


/**
 * 构建系统配置文件
 */
function file_systemconfig()
{
    /* 初始化 */
    global $_CFG;
    
    /* 获取文件字符 */
    $str = file_get_contents($_CFG['F_SYSTEMCONFIG_SRC']);

    /* 配置数据 */
    $str = preg_replace('/\$_CFG\[\'dbhost\'\]\s+=\s+\'\'/i', '$_CFG[\'dbhost\'] = \''.$_POST['dbhost'].'\'', $str);
    $str = preg_replace('/\$_CFG\[\'dbname\'\]\s+=\s+\'\'/i', '$_CFG[\'dbname\'] = \''.$_POST['dbname'].'\'', $str);
    $str = preg_replace('/\$_CFG\[\'dbuser\'\]\s+=\s+\'\'/i', '$_CFG[\'dbuser\'] = \''.$_POST['dbuser'].'\'', $str);
    $str = preg_replace('/\$_CFG\[\'dbpass\'\]\s+=\s+\'\'/i', '$_CFG[\'dbpass\'] = \''.$_POST['dbpass'].'\'', $str);
    $str = preg_replace('/\$_CFG\[\'tblpre\'\]\s+=\s+\'\'/i', '$_CFG[\'tblpre\'] = \''.$_POST['tblpre'].'\'', $str);

    /* 写入文件 */
    file_put_contents($_CFG['F_SYSTEMCONFIG_SRC'], $str);

    /* 重命名系统配置文件 */
    rename($_CFG['F_SYSTEMCONFIG_SRC'], $_CFG['F_SYSTEMCONFIG']);
}

/**
 * 更新管理员
 */
function update_administrator()
{
    $sql = ' UPDATE `'. $_POST['tblpre'] .'admin` SET';
    $sql.= ' `name`="'. $_POST['admin_user'] .'",`username`="'. $_POST['admin_username'] .'"';
    $sql.= ' `password`="'. md5($_POST['admin_`password`']) .'" WHERE `admin_id`=1';

    mysql_query($sql);
}
?>