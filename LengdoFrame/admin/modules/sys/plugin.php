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
// - 异步 - 组件安装
/* ------------------------------------------------------ */
if( $_REQUEST['act'] == 'install' ){
    /* 权限检查 */
    //admin_privilege_valid('sysmodule.php', 'plugin');
    
    /* 允许追加组件代码的文件 */
    $_CFG['TMP_APPEND_FILES']   = array();
    $_CFG['TMP_APPEND_FILES'][] = $_CFG['DIR_ROOT']  . 'js/system.js';
    $_CFG['TMP_APPEND_FILES'][] = $_CFG['DIR_ADMIN'] . 'js/system.js';
    
    /* 组件首尾表示字符 */
    $_CFG['TMP_PLUGIN_HEADER']  = "\r\n\r\n// LengdoFrame Javascript Plugin Code \r\n\r\n";
    $_CFG['TMP_PLUGIN_FOOTER']  = "\r\n\r\n// LengdoFrame Javascript Plugin Code EOF";


    /* 获取所有组件 */
    $plugins = array();
    $plugins = array_merge( $plugins, all_plugin($_CFG['DIR_PLUGIN'].'php/') );
    $plugins = array_merge( $plugins, all_plugin($_CFG['DIR_PLUGIN'].'javascript/') );

    /* 遍历所有组件 */
    foreach( $plugins AS $i=>$plugin ){
        /* 遍历组件的文件融合 */
        foreach( $plugin['append'] AS $ii=>$append ){
            if( is_file($plugin['folder'].$append[1]) ){
                $appends[$append[0]] .= file_get_contents($plugin['folder'].$append[1]);
            }
        }
    }

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
// - 异步 - 组件列表
/* ------------------------------------------------------ */
else{
    /* 获取所有组件 */
    $appends = array();
    $plugins = array_merge( , all_plugin($_CFG['DIR_PLUGIN'].'javascript/') );

    foreach( $plugins AS $i=>$plugin ){
        foreach( $plugin['append'] AS $j=>$append ){
            if( is_file($plugin['folder'].$append[1]) ){
                $appends[$append[0]] .= file_get_contents($plugin['folder'].$append[1]);
            }
        }
    }

    foreach( $appends AS $fpath=>$content ){
        $ncontent = "".rand().$content;
        $ncontent.= "";

        $ccontent = file_get_contents($fpath);

        $rcontent = preg_match('/\/\/ LengdoFrame Javascript Plugin Code [\s\S]*\/\/ LengdoFrame Javascript Plugin Code EOF/', $ccontent, $match);
print_r($match);
        if( $rcontent == $ccontent ){
            //file_put_contents($fpath, $ncontent, FILE_APPEND);
        }
    }
}


/* 加载视图 */
//include($_CFG['DIR_ADMIN_TPL'] . 'myaccount.html');
?>

<?php
/**
 * 获取文件夹下所有组件
 */
function all_plugin( $fdpath )
{
    global $_CFG;

    /* 初始化 */
    $plugin = array();
    $folder = @opendir($fdpath);

    /* 遍历组件文件夹 */
    while( $fname = @readdir($folder) ){
        if( is_dir($fdpath.$fname) && preg_match('/[^\.]$/',$fname) ){
            /* 获取组件配置信息 */
            $cfg = @include($fdpath.$fname.'/plugin.cfg.php');

            /* 无效的配置文件 */
            if( !is_array($cfg) ) continue;

            /* 数据重构 */
            $cfg['folder'] = $fdpath.$fname.'/';

            /* 数据保存 */
            $plugin[] = $cfg;
        }
    }

    return $plugin;
}
?>