<?php
// +----------------------------------------------------------------------
// | LengdoFrame - 数据库优化模块
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
// - 异步 - 数据表优化
/* ------------------------------------------------------ */
if( $_REQUEST['act'] == 'optimize' ){
    /* 权限检查 */
    admin_privilege_valid('db_optimize.php', 'optimize');

    /* 取得所有表*/
    $tables = $db->getCol("SHOW TABLES");

    foreach( $tables AS $table ){
        if( $row = $db->getRow("OPTIMIZE TABLE `$table`") ){
            /* 优化出错，尝试修复 */
            if( $row['Msg_type'] == 'error' && strpos($row['Msg_text'],'repair') !== false ){
                $db->query("REPAIR TABLE `$table`");
            }
        }
    }

    /* 返回 */
    $msg = admin_privilege_name_fk('db_optimize.php','optimize').$_LANG['msg_ok'];

    make_json_ok($msg);
}


/* ------------------------------------------------------ */
// - 异步 - 默认首页，列表页
/* ------------------------------------------------------ */
else{
    /* 权限检查 */
    admin_privilege_valid('db_optimize.php', 'optimize');

    /* 数据库版本和所有数据表的信息 */
    $tables = $db->getAll('SHOW TABLE STATUS');
    $db_ver = $db->version();

    /* 数据表的信息 - 数据格式化 */
    $tpl['all'] = array();
    $tpl['cnt'] = array('chip'=>0,'row'=>0,'size'=>0,'table'=>0);
    foreach( $tables AS $table ){
        $type    = $db_ver >= '4.1' ? $table['Engine'] : $table['Type'];
        $charset = $db_ver >= '4.1' ? $table['Collation'] : 'N/A';
        $comment = trim($table['Comment']);

        if( strtoupper($type) == 'MEMORY' ){
            $check['Msg_text']  = 'Ignore';
            $table['Data_free'] = '0';
        }else{
            $check = $db->GetRow("CHECK TABLE `$table[Name]`");
            $tpl['cnt']['row']   += $table['Rows'];
            $tpl['cnt']['chip']  += $table['Data_free'];
            $tpl['cnt']['size']  += $table['Data_length'];
            $tpl['cnt']['table'] += 1;
        }

        $tpl['all'][] = array( 'name'     => $table['Name'],
                               'type'     => $type,
                               'status'   => $check['Msg_text'],
                               'charset'  => $charset,
                               'comment'  => $comment,
                               'rec_row'  => $table['Rows'],
                               'rec_chip' => $table['Data_free'],
                               'rec_size' => bitunit($table['Data_length'])
                        );
    }

    /* 格式化数据量大小 */
    $tpl['cnt']['size'] = bitunit($tpl['cnt']['size']);

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
        make_json_ok( '', tpl_fetch('db_optimize.html',$tpl) );
    }

    /* ------------------------------------------------------ */
    // - 异步 - 默认首页
    /* ------------------------------------------------------ */
    else{
        /* 取得管理员的优化操作 */
        $m_aa = admin_module_acts('db_optimize.php');
        $m_ab = filter_module_acts($m_aa, array('optimize'), true);

        /* 操作属性 */
        $attribs = array();
        $attribs['optimize']['ico']     = 'optimize';
        $attribs['optimize']['onclick'] = 'deal_dboptimize()';

        /* 初始化页面信息 */
        $tpl['acts']  = format_module_acts($m_ab, $attribs, 'btn'); //格式化模块的操作(非内嵌)
        $tpl['title'] = admin_privilege_name_fk('db_optimize.php', 'optimize'); //权限名称
    }
}


/* 加载视图 */
include(DIR_ADMIN_TPL.'db_optimize.html');
?>