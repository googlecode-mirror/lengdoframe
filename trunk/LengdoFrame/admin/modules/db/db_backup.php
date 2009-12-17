<?php
// +----------------------------------------------------------------------
// | LengdoFrame - 数据库备份模块
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
require('../../../class/dumpsql.class.php');


/* ------------------------------------------------------ */
// - 查看备份文件
/* ------------------------------------------------------ */
if( $_REQUEST['act'] == 'view' ){
    /* 权限检查 */
    admin_privilege_valid('db_backup.php', 'backup');

    /* 构建备份文件路径 */
    $file = $_CFG['DIR_DB_DUMPSQL'].$_GET['file'];
    
    /* 无效参数 */
    if( substr($file,-8) != '.sql.php' || !is_file($file) ){
        sys_msg($_LANG['lawless_submit']);
    }

    /* 构建HTML */
    $html = file_get_contents($file);
    $html = str_replace(' ', '&nbsp;', $html);
    $html = str_replace(array("\r\n","\r","\n"), '<br />', $html);
    $html = substr($html, strpos($html,'<br />')+6);
    
    /* 输出HTML */
    echo $html; exit();
}


/* ------------------------------------------------------ */
// - 下载备份文件
/* ------------------------------------------------------ */
elseif( $_REQUEST['act'] == 'download' ){
    /* 权限检查 */
    admin_privilege_valid('db_backup.php', 'backup');

    /* 构建备份文件路径 */
    $file = $_CFG['DIR_DB_DUMPSQL'].$_GET['file'];

    /* 无效参数 */
    if( substr($file,-8) != '.sql.php' || !is_file($file) ){
        sys_msg($_LANG['lawless_submit']);
    }

    $str = file_get_contents($file);

    http_export( basename($file,'.php'), $str );
}


/* ------------------------------------------------------ */
// - 异步 - 备份界面
/* ------------------------------------------------------ */
elseif( $_REQUEST['act'] == 'backup' ){
    /* 权限检查 */
    admin_privilege_valid('db_backup.php', 'backup');

    /* 生成备份的文件名 */
    $tpl['file_name'] = DumpSql::getRandName().'.sql';

    /* 生成所有表 */
    $tables = $db->getCol("SHOW TABLES");
    $onclick= "Formc.cbgSyncCb(null,'wfm-dbbackup-customtable','wfm-dbbackup-selall')";

    foreach( $tables AS $table ){
        $items[] = array('value'=>$table, 'text'=>$table, 'onclick'=>$onclick, 'class'=>'checkbox');
    }

    /* HTML 控件 */
    $formc = new Formc();
    $tpl['cbg_custom_table'] = $formc->cbg('custom_tables[]', $items);

    /* 初始化页面信息 */
    $tpl['_body']  = 'backup';
    $tpl['_block'] = true;
}
/* ------------------------------------------------------ */
// - 异步 - 备份导出SQL
/* ------------------------------------------------------ */
elseif( $_REQUEST['act'] == 'dumpsql' ){
    /* 权限检查 */
    admin_privilege_valid('db_backup.php', 'backup');

    /* 初始化常量 */
    $vol      = intval($_POST['vol']) > 0 ? intval($_POST['vol']) : 1;
    $vol_size = intval($_POST['vol_size']) > 0 ? intval($_POST['vol_size']) : 1536; //分卷文件大小( KB为单位 )

    $columns  = intval($_POST['columns']);
    $extended = intval($_POST['extended']);

    /* 初始化常量 - 备份文件名(去除扩展名) */
    $file_name = empty($_POST['file']) || trim($_POST['file']) == '.sql' ? DumpSql::getRandName() : trim($_POST['file']);
    if( substr($file_name, -4) == '.sql' ){
        $file_name = substr($file_name, 0, -4);
    }

    /* 创建 DumpSql 对象 */
    $dump = new DumpSql($db);

    /* 设置 DumpSql 对象属性 - 显示字段，扩展插入 */
    $dump->bColumns  = $columns;
    $dump->bExtended = $extended;

    /* 设置 DumpSql 对象属性 - 卷大小限制 */
    $dump->iMaxSize = $vol_size * 1024;

    /* 文件路径 */
    $log_path  = $_CFG['DIR_DB_DUMPSQL'] . 'run.log';
    $file_url  = $_CFG['DIR_DB_DUMPSQL'] . $file_name;
    $file_path = $_CFG['DIR_DB_DUMPSQL'] . $file_name;

    /* 取得要备份的表 */
    $tables = array();

    /* 全部备份 */
    if( trim($_POST['backup_type']) == 'full' ){ 
        $temp = $db->getCol('SHOW TABLES'); 

        foreach( $temp AS $table ){
            $tables[$table] = -1;
        }

        $dump->putTablesList($log_path, $tables);
    }

    /* 自定义备份 */
    else if( trim($_POST['backup_type']) == 'custom' ){
        $temp = is_array($_POST['custom_tables']) ? $_POST['custom_tables'] : array();  

        foreach( $temp AS $table ){
            $tables[$table] = -1;
        }

        $dump->putTablesList($log_path, $tables);
    }

    /* 备份表(根据数据表位置文件) */
    $tables = $dump->dumpTables($log_path, $vol);

    /* 备份失败 */
    if( $tables === false ){
        make_json_fail($_LANG['fail_dbbackup_position']);
    }

    /* 所有表备份完成 */
    if( empty($tables) ){
        /* 多个文件 */
        if( $vol > 1 ){
            if( @file_put_contents("{$file_path}_{$vol}.sql.php","-- <?php exit(); ?>\r\n".$dump->sDumpSql) === false ){
                make_json_fail($_LANG['fail_dbbackup_write']);
            }

            make_json_ok( sprintf($_LANG['spr_dbbackup_ok'],$vol) );
        }

        /* 单个文件 */
        else{
            if( @file_put_contents("{$file_path}.sql.php","-- <?php exit(); ?>\r\n".$dump->sDumpSql) === false ){
                make_json_fail($_LANG['fail_dbbackup_write']);
            }

            make_json_ok($_LANG['ok_dbbackup']);
        }
    }
    /* 部分表未备份完成 */
    else{
        if( @file_put_contents( "{$file_path}_{$vol}.sql.php","-- <?php exit(); ?>\r\n".$dump->sDumpSql) === false ){;
            make_json_fail($_LANG['fail_dbbackup_write']);
        }

        /* 下一卷 */
        $vol++;

        /* 构建下一个部分备份的提交参数 */
        $params = "file={$file_name}&vol={$vol}&vol_size={$vol_size}&columns={$columns}&extended={$extended}";

        /* 沉睡1秒再返回 */
        sleep(1); make_json_response('-1', sprintf($_LANG['spr_dbbackup_ok_part'],$vol-1), $params);
    }
}


/* ------------------------------------------------------ */
// - 异步 - 导入备份文件 - 服务器SQL文件导入
/* ------------------------------------------------------ */
elseif( $_REQUEST['act'] == 'import' ){
    /* 权限检查 */
    admin_privilege_valid('db_backup.php', 'backup');

    /* 文件名初始化并检查 */
    $file_name = empty($_POST['file']) ? '' : trim($_POST['file']);

    if( substr($file_name,-8) != '.sql.php' ){
        make_json_fail($_LANG['file_ext_error']);
    }

    /* 分卷导入 */
    if( preg_match('/_[0-9]+\.sql\.php$/', $file_name) ){
        /* 初始化 */
        $num = intval( substr($file_name, strrpos($file_name, '_')+1) );
        $short_name = substr( $file_name, 0, strrpos($file_name, '_') );

        /* 无效文件 */
        $str = $_CFG['DIR_DB_DUMPSQL'].$short_name .'_'. $num .'.sql.php';
        if( !is_file($str) ){
            make_json_fail($_LANG['fail_dbbackup_import']);
        }

        /* 导入初始化 */
        if( intval($_POST['init']) == 1 ){
            /* 取得分卷总数 */
            for( $i=1; $i < 100; $i++ ){
                $str = $_CFG['DIR_DB_DUMPSQL'].$short_name .'_'. $i .'.sql.php';

                if( !is_file($str) ){
                    $_POST['total'] = $i-1; break;
                }
            }

            /* 构建返回消息 */
            $str = 'file='. $short_name .'_'. $num .'.sql.php&total='.$_POST['total'];
            $msg = sprintf($_LANG['spr_dbbackup_import_part'], $num, $_POST['total']);

            /* 返回消息 */
            make_json_response(-1, $msg, $str);
        }

        /* 开始导入SQL数据 */
        if( !sql_import($_CFG['DIR_DB_DUMPSQL'].$file_name) ){
            make_json_fail($_LANG['fail_dbbackup_import']);
        }else{
            /* 导入完成 */
            $str = $_CFG['DIR_DB_DUMPSQL'].$short_name .'_'. ($num+1) .'.sql.php';
            if( !is_file($str) ){
                make_json_ok($_LANG['ok_dbbackup_import']);
            }

            /* 构建返回消息 */
            $str = 'file='. $short_name .'_'. ($num+1) .'.sql.php&total='.$_POST['total'];
            $msg = sprintf($_LANG['spr_dbbackup_import_part'], ($num+1), $_POST['total']);

            /* 返回消息 */
            make_json_response(-1, $msg, $str);
        }
    }

    /* 单卷导入 */
    else{
        /* 导入初始化 */
        if( intval($_POST['init']) == 1 ){
            /* 返回消息 */
            make_json_response(-1, $_LANG['ok_dbbackup_importing'], 'file='.$file_name);
        }

        /* 开始导入SQL数据 */
        if( !sql_import($_CFG['DIR_DB_DUMPSQL'].$file_name) ){
            make_json_fail($_LANG['fail_dbbackup_import']);
        }
    }

    make_json_ok($_LANG['ok_dbbackup_import']);
}
/* ------------------------------------------------------ */
// - 异步 - 导入备份文件 - 上传SQL文件导入
/* ------------------------------------------------------ */
elseif( $_REQUEST['act'] == 'upload' ){
    /* 权限检查 */
    admin_privilege_valid('db_backup.php', 'backup');

    /* 检查上传是否成功 */
    if( (isset($_FILES['file']['error']) && $_FILES['file']['error'] != 0) ){
        make_json_fail($_LANG['fill_dbbackup_sqlfile']);
    }

    /* 检查文件格式 */
    if( substr($_FILES['file']['name'],-4) != '.sql' ){
        make_json_fail($_LANG['file_ext_error']);
    }

    /* 设置文件路径 */
    $file_path = $_CFG['DIR_DB_DUMPSQL'] . 'upload_sql_file_temp.sql';

    /* 将文件移动到备份文件夹下 */
    if( !move_uploaded_file($_FILES['file']['tmp_name'] , $file_path) ){
        make_json_fail($_LANG['file_move_fail']);
    }

    /* 导入SQL文件 */
    if( sql_import($file_path) === false ){
        @unlink($file_path); make_json_ok($_LANG['fail_dbbackup_import']);
    }else{
        @unlink($file_path); make_json_ok($_LANG['ok_dbbackup_import']);
    }
}


/* ------------------------------------------------------ */
// - 异步 - 删除备份文件
/* ------------------------------------------------------ */
elseif( $_REQUEST['act'] == 'del' ){
    /* 权限检查 */
    admin_privilege_valid('db_backup.php', 'backup');

    /* 无效参数 */
    if( !is_array($_POST['ids']) || empty($_POST['ids']) ){
        make_json_fail();
    }

    /* 多卷或单卷文件名处理 */
    $m_files = array(); //多卷文件
    $s_files = array(); //单卷文件
    foreach( $_POST['ids'] AS $file ){
        if( substr($file,-8) != '.sql.php' ) continue;

        if( preg_match('/_[0-9]+\.sql\.php$/', $file) ){
            $m_files[] = substr( $file, 0, strrpos($file, '_') );
        }else{
           $s_files[] = $file;
        }
    }

    /* 多卷文件删除 */
    if( !empty($m_files) ){
        $m_files = array_unique($m_files);

        /* 取得文件列表 */
        $files = array();

        $folder = opendir($_CFG['DIR_DB_DUMPSQL']);
        while( $file = readdir($folder) ){
            if( preg_match('/_[0-9]+\.sql\.php$/',$file) && is_file($_CFG['DIR_DB_DUMPSQL'].$file) ){
                $files[] = $file;
            }
        }

        foreach( $files AS $file ){
            $short_file = substr($file, 0, strrpos($file, '_'));
            if( in_array($short_file, $m_files) ){
                @unlink($_CFG['DIR_DB_DUMPSQL'].$file);
            }
        }
    }

    /* 单卷文件删除 */
    if( !empty($s_files) ){
        foreach( $s_files AS $file ){
            @unlink($_CFG['DIR_DB_DUMPSQL'].$file);
        }
    }

    make_json_ok();
}


/* ------------------------------------------------------ */
// - 异步 - 默认首页，列表页
/* ------------------------------------------------------ */
else{
    /* 权限检查 */
    admin_privilege_valid('db_backup.php', 'backup');

    /* 备份文件夹权限检查 */
    valid_dbbackup_folder();

    /* SQL文件列表 - 数据格式化 */
    $tpl['all'] = list_sqlfile_format( list_sqlfile() );

    /* 初始化页面信息 */
    $tpl['_body'] = 'index';


    /* ------------------------------------------------------ */
    // - 异步 - 列表页，列表查询
    /* ------------------------------------------------------ */
    if( $_REQUEST['act'] == 'list' ){
        /* 初始化页面信息 */
        $tpl['_body'] = 'list';
        $tpl['_block'] = true;

        /* 列表查询 */
        if( $_REQUEST['actsub'] == 'query' ){
            /* 初始化页面信息 */
            $tpl['_bodysub'] = 'query';
        }

        /* 返回JSON */
        make_json_ok( '', tpl_fetch('db_backup.html',$tpl) );
    }

    /* ------------------------------------------------------ */
    // - 异步 - 默认首页
    /* ------------------------------------------------------ */
    else{
        /* 取得管理员的备份操作 */
        $m_aa = admin_module_acts('db_backup.php');
        $m_ab = filter_module_acts($m_aa, array('backup'), true);

        /* 操作属性 */
        $attribs = array();
        $attribs['backup']['onclick'] = 'wnd_dbbackup_fill()';

        /* 初始化页面信息 */
        $tpl['title'] = admin_privilege_name_fk('db_backup.php', 'backup'); //权限名称
        $tpl['titleacts'] = format_module_acts($m_ab, $attribs, 'btn'); //格式化模块的操作(非内嵌)
    }
}


/* 加载视图 */
include($_CFG['DIR_ADMIN_TPL'] . 'db_backup.html');
?>

<?php
/**
 * 备份文件夹权限检查
 */
function valid_dbbackup_folder()
{
    global $_LANG, $_CFG;

    $mask = file_privilege($_CFG['DIR_DB_DUMPSQL']);
 
    if( $mask === false ){
        $msg = $_LANG['fail_dbbackup_fdno'] .'<br />'. $_CFG['DIR_DB_DUMPSQL'];
    }
    elseif( $mask < 7 ){
        $msg = $_LANG['fail_dbbackup_fdpriv'];
        if( ($mask&1) < 1 ) $tpl['error'] .= $_LANG['file_unread']  .', ';
        if( ($mask&2) < 1 ) $tpl['error'] .= $_LANG['file_unwrite'] .', ';
        if( ($mask&4) < 1 ) $tpl['error'] .= $_LANG['file_unedit']  .'.';

        $msg .= '<br />' . $_CFG['DIR_DB_DUMPSQL'];
    }

    $msg ? sys_msg($msg) : '';
}


/**
 * SQL文件列表
 */
function list_sqlfile()
{
    global $_CFG;

    $files  = array();
    $folder = @opendir($_CFG['DIR_DB_DUMPSQL']);

    while( $file = @readdir($folder) ){
        if( substr($file, -8) == '.sql.php' ){
            $files[] = $file;
        }
    }

    return $files;
}

/**
 * 格式化SQL文件列表
 */
function list_sqlfile_format( $files )
{
    global $_LANG, $_CFG;

    /* 初始化 */
    $list = array();

    /* 构建列表数据 */
    foreach( $files AS $file ){
        /* 解析备份文件类型和当前卷 */
        if( preg_match('/_[0-9]+\.sql.php$/', $file, $matchs) ){
            $type = 'volumes'; $volume = intval( substr($matchs[0],1) );
        }else{
            $type = 'volume'; $volume = 1;
        }

        /* 构建用于排序的文件名和引导的文件名 */
        $filearr = explode('_', $file);  $offset = count($filearr) - 1;
        $filearr[$offset] = $type == 'volumes' ? str_pad($filearr[$offset], strlen('.sql.php')+4, '0', STR_PAD_LEFT) : $filearr[$offset];

        $fnamesort  = implode('_', $filearr);
        $fnameindex = preg_replace('/_[0-9]+\.sql\.php$/', '.sql.php', $file);

        /* 备份文件的信息 */
        $header = DumpSql::getHeader($_CFG['DIR_DB_DUMPSQL'].$file);
        $header['size'] = filesize($_CFG['DIR_DB_DUMPSQL'].$file);

        /* 记录操作 */
        if( $acts = '' || $volume == 1 ){
            $acts = '<a href="javascript:void(0)" onclick="deal_dbbackup_import(\'file='. $file;
            $acts.= '&init=1\',\''. f(preg_replace('/\.sql\.php$/','.sql',$fnameindex), 'html') .'\')">'. $_LANG['act_import'] .'</a>';
        }

        /* 单卷文件时 */
        if( $type == 'volume' ){
            /* 文件名HTML格式化 */
            $name = '<a style="margin-left:16px;" target="_blank" href="modules/db/db_backup.php?act=view&file='. $file;
            $name.= '">'. f( preg_replace('/\.sql\.php$/','.sql',$file), 'html' ) .'</a>';
        }
        /* 多卷文件且是第一卷时 */
        elseif( $volume == 1 ){
            /* 文件名HTML格式化 */
            $name = '<span class="plus" style="cursor:pointer;margin-left:0em" ';
            $name.= 'onclick="tabletree_click(this)"></span><a target="_blank" href="'. $_CFG['URL_DB_DUMPSQL'].$file;
            $name.= '">'. f( preg_replace('/\.sql\.php$/','.sql',$fnameindex), 'html' ) .'</a>';

            /* 文件信息写入列表 - 引导文件 */
            $list[$fnameindex] = array( 'vol'  => 0,               //文件卷
                                        'file' => $file,           //文件名(真实的文件名)
                                        'name' => $name,           //文件名(经过HTML格式化)
                                        'date' => $header['date'], //文件创建时间
                                        'type' => 'volumesindex',  //文件类型 - volume表示单卷文件, volumes表示多卷文件, volumesindex表示多卷的索引文件
                                        'size' => 0,               //文件大小
                                        'acts' => $acts            //记录操作
                                      );
        }

        /* 多卷文件时 */
        if( $type == 'volumes' ){
            /* 构建文件名的HTML */
            $name = '<span style="display:none"></span><a target="_blank" href="'. $_CFG['URL_DB_DUMPSQL'].$file .'" ';
            $name.= 'style="color:#999;margin-left:16px;">'. f( preg_replace('/\.sql\.php$/','.sql',$file), 'html' ) .'</a>';
            
            /* 重置操作 */
            $acts = '';
        }

        /* 文件信息写入列表 - 多卷情况下 */
        $list[$fnamesort] = array( 'vol'  => $header['vol'],
                                   'date' => $header['date'], 
                                   'file' => $file,
                                   'name' => $name,
                                   'type' => $type,
                                   'size' => $header['size'], 
                                   'acts' => $acts
                                 );

        /* 重构多卷的索引文件信息 */
        if( $type == 'volumes' ){
            $list[$fnameindex]['vol']++;
            $list[$fnameindex]['size'] += $list[$fnamesort]['size'];
        }
    }

    /* 格式化列表数据 */
    foreach( $list AS $i=>$r ){
        $list[$i]['size'] = bitunit($r['size']);
    }

    /* 下标排序 */
    ksort($list);

    return $list;
}


/**
 * 将文件中的sql语句导入到数据库
 *
 * @params str  $file_path  文件绝对路径
 *
 * @return bol  true表示导入成功，false表示导入失败
 */
function sql_import( $file_path )
{
    /* 初始化SQL数组 */
    $sqls = array_filter(file($file_path), 'sql_comment_remove');
    $sqls = str_replace( "\r", '', implode('',$sqls) );
    $sqls = explode(";\n", $sqls);

    /* 执行SQL语句 */
    foreach( $sqls AS $i=>$sql ){
        $sql = trim($sql, " \r\n;"); //移除多余信息

        if( empty($sql) ) continue;

        if( !$GLOBALS['db']->query($sql,false) ){
            return false;
        }
    }

    return true;
}

/**
 * 移除SQL注释
 */
function sql_comment_remove( $var )
{
    return (substr(trim($var), 0, 2) != '--');
}
?>