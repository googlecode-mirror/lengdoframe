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
$_CFG['TMP_PLUGIN_HEADER']      = "\r\n\r\n// LengdoFrame Plugin Codes";
$_CFG['TMP_PLUGIN_FOOTER']      = "\r\n\r\n// LengdoFrame Plugin Codes EOF";

$_CFG['TMP_PLUGIN_ID_HEADER']   = "\r\n\r\n// LengdoFrame Plugin Code Header #Id %s[Install:%s]";
$_CFG['TMP_PLUGIN_ID_FOOTER']   = "\r\n// LengdoFrame Plugin Code Footer #Id %s[Install:%s]";

$_CFG['TMP_PLUGIN_HEADER_PREG'] = "\\r\\n\\r\\n\/\/ LengdoFrame Plugin Codes";
$_CFG['TMP_PLUGIN_FOOTER_PREG'] = "\\r\\n\\r\\n\/\/ LengdoFrame Plugin Codes EOF";


/* ------------------------------------------------------ */
// - 异步 - 组件安装
/* ------------------------------------------------------ */
if( $_REQUEST['act'] == 'install' ){
    /* 所有组件 */
    $tpl['list'] = list_plugin();

    /* 安装所有组件 */
    install_plugins();
}


/* ------------------------------------------------------ */
// - 异步 - 组件列表
/* ------------------------------------------------------ */
else{
    /* 组件列表 */
    $tpl['list'] = list_plugin();
}


/* 加载视图 */
make_json_response( (empty($tpl['list']['data']) ? -1 : 0), '', tpl_fetch('sysplugin.html',$tpl) );
?>

<?php
/**
 * 获取所有组件
 * 过滤没有配置文件或者无效配置信息的组件
 */
function all_plugin()
{
    global $_CFG;

    /* 初始化 */
    $plugins = array();
    $fdgroups = array('php', 'javascript'); // 组件的父文件夹组

    /* 遍历父文件夹 */
    foreach( $fdgroups AS $fdgroup ){
        /* 打开父文件夹 */
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

            /* 数据重构 - 基础数据 */
            $cfg['type'] = $fdgroup;
            $cfg['folder'] = $fdgroup.'/'.$fdplugin.'/';  // 相对于 $_CFG['DIR_PLUGIN'] 的文件夹路径
            $cfg['fdpath'] = $_CFG['DIR_PLUGIN'].$cfg['folder']; // 全路径

            /* 数据重构 - 检验数据 */
            $cfg['errors'] = valid_plugin($cfg);
            $cfg['installed'] = empty($cfg['errors']) ? installed_plugin($cfg) : 0;

            /* 数据保存 */
            $plugins[] = $cfg;
        }
    }

    return $plugins;
}


/**
 * 获取组件列表
 *
 * @params arr  $plugins  所有组件
 */
function list_plugin()
{
    /* 获取所有组件 */
    $plugins = all_plugin();

    /* 设置分页数据和信息 */
    $p['rows_page']  = intval($_REQUEST['rows_page']) ? intval($_REQUEST['rows_page']) : 5;
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
 * 组件配置检查
 *
 * @return arr  错误信息
 */
function valid_plugin( $plugin )
{
    global $_CFG;

    /* 初始化 */
    $errors = array();

    /* 组件安装配置检查 */
    if( !is_array($plugin['install']) ){
        /* 安装配置变量有效性检查 */
        $errors[] = '无法找到组件的安装配置';
    }else{
        /* 安装配置文件有效性检查 */
        foreach( $plugin['install'] AS $i=>$install ){
            /* 组件文件路径和安装文件路径 */
            $fpath_plugin  = $_CFG['DIR_PLUGIN'].$plugin['folder'].$install[1];
            $fpath_install = $install[0];

            /* 无效的组件文件 */
            if( !is_file($fpath_plugin) ){
                $errors[] = '无法找到组件文件 ' . $fpath_plugin;
            }

            /* 无效的安装文件 */
            if( !in_array($fpath_install,$_CFG['TMP_INSTALL_FILES']) ){ 
                $errors[] = '不允许的安装文件 ' . $fpath_install;
            }
            elseif( !is_file($fpath_install) ){
                $errors[] = '无法找到安装文件 ' . $fpath_install;
            }
        }
    }

    return $errors;
}


/**
 * 组件已安装检查
 *
 * @params arr  $cfg  组件配置数据
 *
 * @return int  0表示未安装过
 *              1表示安装成功
 *              2表示无须安装
 */
function installed_plugin( $plugin )
{
    global $_CFG;

    /* 无须安装 */
    if( empty($plugin['install']) ) return 2;

    /* 检查安装情况 */
    foreach( $plugin['install'] AS $i=>$install ){
        /* 初始化安装检查数据 */
        $match = sprintf($_CFG['TMP_PLUGIN_ID_HEADER'],$plugin['folder'],$i);
        $content = file_get_contents($install[0]);

        /* 未安装 */
        if( strpos($content,$match) === false ) return 0;
    }

    /* 已安装 */
    return 1;
}


/**
 * 安装所有组件
 */
function install_plugins()
{
    global $_CFG;
    
    /* 获取所有组件 */
    $plugins = all_plugin();

    /* 过滤无效组件 */
    foreach( $plugins AS $i=>$plugin ){
        /* 过滤无效组件 */
        if( !empty($plugin['errors']) ) unset($plugins[$i]);
    }

    /* 卸载所有组件 */
    uninstall_plugins();

    /* 初始化组件安装代码集数据(按安装文件分组) */
    $codes = install_plugins_codes($plugins);

    /* 安装代码 */
    foreach( $codes AS $fpath_install=>$code ){
        /* 重构组件代码集 */
        $code = $_CFG['TMP_PLUGIN_HEADER'] ."\r\n". implode("\r\n", $code);
        $code.= "\r\n". $_CFG['TMP_PLUGIN_FOOTER'];

        /* 追加组件代码到安装文件 */
        file_put_contents($fpath_install, $code, FILE_APPEND);
    }
}
/**
 * 初始化组件安装代码集数据
 *
 * @params arr  $cfgs  安装组件的配置集
 *
 * @return arr  按照安装文件分组的组件代码集
 */
function install_plugins_codes( $plugins )
{
    global $_CFG;

    /* 初始化 */
    $codes = array();

    /* 遍历所有组件 */
    foreach( $plugins AS $i=>$plugin ){
        /* 初始化分组代码集 */
        $codes_plugin = array();

        /* 构建组件代码 */
        foreach( $plugin['install'] AS $ii=>$install ){
            /* 构建组件文件和安装文件路径 */
            $fpath_plugin  = $_CFG['DIR_PLUGIN'].$plugin['folder'].$install[1];
            $fpath_install = $install[0];

            /* 获取并重构组件文件的代码 */
            $code_plugin = sprintf($_CFG['TMP_PLUGIN_ID_HEADER'],$plugin['folder'],$ii) ."\r\n";
            $code_plugin.= trim(file_get_contents($fpath_plugin),"\r\n ");
            $code_plugin.= sprintf($_CFG['TMP_PLUGIN_ID_FOOTER'],$plugin['folder'],$ii);

            /* 保存安装文件分组的组件代码(按照安装文件路径分组) */
            $codes_plugin[$fpath_install][] = $code_plugin;
        }

        /* 保存安装文件分组的组件代码(按照安装文件路径分组) */
        if( !empty($codes_plugin[$fpath_install]) ){
            /* 初始化 */
            if( empty($codes[$fpath_install]) ) $codes[$fpath_install] = array();
            
            /* 保存 */
            $codes[$fpath_install] = array_merge($codes[$fpath_install], $codes_plugin[$fpath_install]);
        }
    }

    return $codes;
}


/**
 * 卸载所有组件
 *
 * @params arr  按照组件下标索引的错误信息
 */
function uninstall_plugins()
{
    global $_CFG;

    foreach( $_CFG['TMP_INSTALL_FILES'] AS $i=>$fpath_install ){
        /* 未创建安装文件 */
        if( !is_file($fpath_install) ) continue;

        /* 获取安装文件的代码 */
        $code_install = file_get_contents($fpath_install);
        
        /* 无组件代码集的安装文件 */
        if( strpos($code_install,trim($_CFG['TMP_PLUGIN_HEADER'],"\r\n ")) === false ) continue;

        /* 匹配安装文件中组件代码集 */
        $preg  = '/'. $_CFG['TMP_PLUGIN_HEADER_PREG'] .'[\s\S]*'. $_CFG['TMP_PLUGIN_FOOTER_PREG'] .'/';
    
        /* 删除组件代码集 */
        @file_put_contents($fpath_install, preg_replace($preg,'',$code_install));
    }
}
?>