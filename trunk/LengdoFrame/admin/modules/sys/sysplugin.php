<?php
// +----------------------------------------------------------------------
// | LengdoFrame - 组件管理
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


/* ------------------------------------------------------ */
// - 初始化配置
/* ------------------------------------------------------ */

/* 允许追加组件代码的文件 */
$_CFG['TMP_INSTALL_FILES']   = array();
$_CFG['TMP_INSTALL_FILES'][] = $_CFG['DIR_ROOT']  . 'js/system.js';
$_CFG['TMP_INSTALL_FILES'][] = $_CFG['DIR_ADMIN'] . 'js/system.js';

/* 组件首尾表示字符 */
$_CFG['TMP_PLUGIN_ID']          = "\r\n\r\n// LengdoFrame Plugin #Id %s[Install:%s]";
$_CFG['TMP_PLUGIN_HEADER']      = "\r\n\r\n// LengdoFrame Plugin Code";
$_CFG['TMP_PLUGIN_FOOTER']      = "\r\n\r\n// LengdoFrame Plugin Code EOF";

$_CFG['TMP_PLUGIN_HEADER_PREG'] = "\\r\\n\\r\\n\/\/ LengdoFrame Plugin Code";
$_CFG['TMP_PLUGIN_FOOTER_PREG'] = "\\r\\n\\r\\n\/\/ LengdoFrame Plugin Code EOF";


/* ------------------------------------------------------ */
// - 异步 - 组件安装
/* ------------------------------------------------------ */
if( $_REQUEST['act'] == 'install' ){
    /* 所有组件 */
    $tpl['list'] = all_plugin();

    /* 安装组件 */
    $errors = install_plugin($tpl['list']['data']);

    /* 所有组件 - 数据重构 */
    foreach( $tpl['list']['data'] AS $i=>$r ){
        /* 错误信息 */
        if( $errors[$i] ){
            $tpl['list']['data'][$i]['error'] = implode("\r\n", $errors[$i]);
        }

        /* 补全文件夹 */
        $tpl['list']['data'][$i]['folder'] = $_CFG['DIR_PLUGIN'].$r['folder'];
    }
}


/* ------------------------------------------------------ */
// - 异步 - 组件列表
/* ------------------------------------------------------ */
else{
    /* 组件列表 */
    $tpl['list'] = list_plugin();
    
    /* 所有组件 - 数据重构 */
    foreach( $tpl['list']['data'] AS $i=>$r ){
        $tpl['list']['data'][$i]['folder'] = $_CFG['DIR_PLUGIN'].$r['folder'];
    }
}


/* 加载视图 */
make_json_response( (empty($tpl['list']['data']) ? -1 : 0), '', tpl_fetch('sysplugin.html',$tpl) );
?>

<?php
function list_plugin()
{
    /* 所有组件 */
    $plugins = all_plugin();

    /* 设置分页数据和信息 */
    $p['rows_page']  = intval($_REQUEST['rows_page']) ? intval($_REQUEST['rows_page']) : 1;
    $p['rows_total'] = count($plugins);
    $p['html']       = pager( $p['rows_page'], $p['rows_total'] );
    $p['cur_page']   = pager_current( $p['rows_page'], $p['rows_total'] );
    $p['row_start']  = ($p['cur_page']-1) * $p['rows_page'];

    $list['data']    = array_slice($plugins, $p['row_start'], $p['rows_page']);
    $list['pager']   = $p;

    /* 返回 */
    return $list;
}

/**
 * 获取文件夹下所有组件
 */
function all_plugin()
{
    global $_CFG;

    /* 初始化 */
    $plugins = array();
    $fdgroups = array('php', 'javascript');

    /* 遍历文件夹 */
    foreach( $fdgroups AS $fdgroup ){
        /* 打开文件夹 */
        $fd = @opendir($_CFG['DIR_PLUGIN'].$fdgroup);

        /* 遍历子文件夹 */
        while( $fdplugin = @readdir($fd) ){
            /* 组件的配置文件夹 */
            $fcfg = $_CFG['DIR_PLUGIN'].$fdgroup.'/'.$fdplugin.'/plugin.cfg.php';

            /* 无效的组件文件夹 */
            if( !preg_match('/[^\.]$/',$fdplugin) || !is_file($fcfg) ) continue;

            /* 获取组件配置信息。使用缓存清除include时的不明输出 */
            ob_start(); $cfg = @include($fcfg); ob_end_clean();

            /* 无效的配置文件 */
            if( !is_array($cfg) ) continue;

            /* 数据重构 - 初始化 */
            $cfg['type'] = $fdgroup;
            $cfg['folder'] = $fdgroup.'/'.$fdplugin.'/';  // 相对于 $_CFG['DIR_PLUGIN'] 的文件夹路径
            $cfg['installed'] = installed_plugin($cfg);  // 组件安装检查

            /* 数据保存 */
            $plugins[] = $cfg;
        }
    }

    return $plugins;
}

/**
 * 安装组件
 *
 * @params arr  按照组件下标索引的错误信息
 */
function install_plugin( $plugins )
{
    global $_CFG;

    /* 初始化 */
    $inits = install_plugin_init($plugins);

    foreach( $inits['codes'] AS $fpath_install=>$codes ){
        /* 获取安装文件的代码 */
        $code_install = file_get_contents($fpath_install);

        /* 构建组件文件的代码集 */
        $code_plugin = $_CFG['TMP_PLUGIN_HEADER'] ."\r\n". implode("\r\n", $codes);
        $code_plugin.= "\r\n". $_CFG['TMP_PLUGIN_FOOTER'];

        /* 追加或者替换组件代码到安装文件 */
        if( strpos($code_install, trim($_CFG['TMP_PLUGIN_HEADER'],"\r\n ")) === false ){
            /* 追加组件代码到安装文件 */
            file_put_contents($fpath_install, $code_plugin, FILE_APPEND);
        }else{
            /* 匹配安装文件中已存在组件代码集 */
            $preg  = '/'. $_CFG['TMP_PLUGIN_HEADER_PREG'] .'[\s\S]*'. $_CFG['TMP_PLUGIN_FOOTER_PREG'] .'/';

            /* 替换安装文件中已存在组件代码集 */
            file_put_contents($fpath_install, preg_replace($preg,$code_plugin,$code_install));
        }
    }

    return $inits['errors'];
}
/**
 * 初始化要安装的组件代码
 *
 * @params str  $plugins  所有组件
 *
 * @return arr  按照组件下标索引的错误信息
 *              按照安装文件分组的组件代码
 */
function install_plugin_init( $plugins )
{
    global $_CFG;

    /* 初始化 */
    $codes = array();
    $errors = array();

    foreach( $plugins AS $i=>$plugin ){
        /* 初始化组件代码和分组代码集 */
        $code_plugin = '';
        $codes_plugin = array();
        
        /* 构建组件代码 */
        foreach( $plugin['install'] AS $ii=>$install ){
            /* 构建组件文件和安装文件路径 */
            $fpath_plugin  = $_CFG['DIR_PLUGIN'].$plugin['folder'].$install[1];
            $fpath_install = $install[0];

            /* 验证是否可以组件安装 */
            $error = install_plugin_init_valid($fpath_install, $fpath_plugin);
            if( $error ){ $errors[$i][] = $error; continue; }

            /* 获取并重构组件文件的代码 */
            $code_plugin.= sprintf($_CFG['TMP_PLUGIN_ID'],$plugin['folder'],$i) ."\r\n";
            $code_plugin.= trim(file_get_contents($fpath_plugin),"\r\n ");
            
            /* 保存安装文件分组的组件代码(按照安装文件分组) */
            $codes_plugin[$fpath_install][] = $code_plugin;
        }

        /* 保存安装文件分组的组件代码(按照安装文件分组) */
        if( empty($errors[$i]) ){
            foreach( $codes_plugin AS $fpath_install=>$code_plugin ){
                $codes[$fpath_install][] = $code_plugin;
            }
        }
    }

    return array('errors'=>$errors,'codes'=>$codes);
}
/**
 * 验证是否可以组件安装
 *
 * @return str  错误信息
 */
function install_plugin_init_valid( $fpath_install, $fpath_plugin )
{
    global $_CFG;
    
    /* 初始华 */
    $errors = array();

    /* 无效的组件文件 */
    if( !is_file($fpath_plugin) ){
        $errors[] = '无法找到组件文件 ' . $fpath_plugin;
    }

    /* 无效的安装文件 */
    if( !in_array($fpath_install,$_CFG['TMP_INSTALL_FILES']) ){ 
        $errors[] = '无效指定安装文件 ' . $fpath_install;
    }
    elseif( !is_file($fpath_install) ){
        $errors[] = '无法找到安装文件 ' . $fpath_install;
    }

    /* 返回 */
    return implode("\r\n", $errors);
}

/**
 * 组件安装检查
 *
 * @params arr  $cfg  组件配置数据
 *
 * @return int  0表示未安装
 *              1表示安装成功
 *              2表示无须安装
 */
function installed_plugin( $cfg )
{
    global $_CFG;

    /* 无须安装 */
    if( empty($cfg['install']) ) return 2;

    /* 检查安装情况 */
    foreach( $cfg['install'] AS $i=>$install ){
        /* 无效的安装文件 */
        if( !in_array($install[0],$_CFG['TMP_INSTALL_FILES']) ) return 0;

        /* 无效的安装文件或者组件文件 */
        if( !is_file($install[0]) || !is_file($_CFG['DIR_PLUGIN'].$cfg['folder'].$install[1]) ) return 0;

        /* 初始化安装检查数据 */
        $match = sprintf($_CFG['TMP_PLUGIN_ID'],$cfg['folder'],$i);
        $content = file_get_contents($install[0]);

        /* 未安装 */
        if( strpos($content,$match) === false ) return 0;
    }

    /* 已安装 */
    return 1;
}
?>