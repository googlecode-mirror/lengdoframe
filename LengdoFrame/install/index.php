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
$_DBD['filerename'] = array('0'=>'<span class="no"><i></i>不可重命名</span>', '1'=>'<span class="yes"><i></i>可重命名</span>');

$_DBD['dberror_1007'] = '数据库已存在，请重新填写数据库名!';
$_DBD['dberror_1044'] = '无法创建新的数据库，请检查数据库名称填写是否正确!';
$_DBD['dberror_1045'] = '无法连接数据库，请检查数据库用户名或者密码是否正确!';
$_DBD['dberror_2003'] = '无法连接数据库，请检查数据库是否启动，数据库服务器地址是否正确!';
$_DBD['dberror_conn'] = '数据库连接错误!';
$_DBD['dberror_cret'] = '数据库创建失败!';


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
    $tpl['files'][] = array('type'=>'dir', 'file'=>$_CFG['DIR_ADMIN_PFILE'], 'url'=>'./admin/data/pfile/');
    $tpl['files'][] = array('type'=>'file', 'file'=>$_CFG['DIR_INC'].'_systemconfig.php', 'url'=>'./includes/_systemconfig.php');

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
    $tpl['remarks']['admin_username'] = '管理员账号不能为空';

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
        $tpl['errors'] = array();
        
        /* 填写错误检查 */
        if( trim($_POST['dbhost']) == '' ) $tpl['errors']['dbhost'] = '数据库服务地址不能为空!';
        if( trim($_POST['dbname']) == '' ) $tpl['errors']['dbname'] = '数据库名称不能为空!';
        if( trim($_POST['dbuser']) == '' ) $tpl['errors']['dbuser'] = '数据库用户名不能为空!';

        if( trim($_POST['admin_username']) == '' ) $tpl['errors']['admin_username'] = '管理员账号不能为空!';

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
                    $tpl['errors']['error'] = $_DBD['dberror_'.$errno] ? $_DBD['dberror_'.$errno] : $_DBD['dberror_cret'];
                }else{
                    mysql_select_db($_POST['dbname']);
                }
            }

            /* 构建系统配置文件 */
            //file_systemconfig();

            sql_import();
        }

        /* 初始化页面信息 */
        $tpl['acts'] = 'rpn';
    }
}


/* 加载视图 */
include('index.html')
?>

<?php
/**
 * SQL导入
 */
function sql_import()
{
    /* 初始化 */
    $file = './lengdoframe.sql';

    /* 初始化SQLS */
    $sqls = array_filter(file($file), 'sql_comment_remove');
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
?>