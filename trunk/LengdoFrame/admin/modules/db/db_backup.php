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
// - 备份 - 界面
/* ------------------------------------------------------ */
if( $_REQUEST['act'] == 'backup' ){
    /* 权限检查 */
    admin_privilege_valid('db_backup.php', 'backup');

    /* 生成备份的文件名 */
    $tpl['file_name'] = DumpSql::getRandName().'.sql';

    /* 生成所有表 */
    $tables = $db->getCol("SHOW TABLES");
    $onclick= "Formc.cbgSyncCb(null, 'wfm-dbbackup-customtable','wfm-dbbackup-selall')";

    foreach( $tables AS $table ){
        $items[] = array('value'=>$table, 'text'=>$table, 'onclick'=>$onclick, 'class'=>'checkbox');
    }

    /* HTML 控件 */
    $formc = new FormControl();
    $tpl['cbg_custom_table'] = $formc->cbg('custom_tables', $items);

    /* 初始化页面信息 */
    $tpl['_body']  = 'backup';
    $tpl['_block'] = true;
}
/* ------------------------------------------------------ */
// - 备份 - 导出SQL
/* ------------------------------------------------------ */
elseif( $_REQUEST['act'] == 'dumpsql' ){
    /* 权限检查 */
    admin_privilege_valid('db_backup.php', 'backup');

    /* 初始化常量 */
    $vol      = intval($_POST['vol']) > 0 ? intval($_POST['vol']) : 1;
    $vol_size = intval($_POST['vol_size']) > 0 ? intval($_POST['vol_size']) : 2048; //分卷文件大小( KB为单位 )

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
    $log_path  = DIR_DB_DUMPSQL . 'run.log';
    $file_url  = URL_DB_DUMPSQL . $file_name;
    $file_path = DIR_DB_DUMPSQL . $file_name;

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
            if( @file_put_contents("{$file_path}_{$vol}.sql",$dump->sDumpSql) === false ){
                make_json_fail($_LANG['fail_dbbackup_write']);
            }

            make_json_ok( sprintf($_LANG['spr_dbbackup_ok'],$vol) );
        }

        /* 单个文件 */
        else{
            if( @file_put_contents("{$file_path}.sql",$dump->sDumpSql) === false ){
                make_json_fail($_LANG['fail_dbbackup_write']);
            }

            make_json_ok($_LANG['ok_dbbackup']);
        }
    }
    /* 部分表未备份完成 */
    else{
        if( @file_put_contents( "{$file_path}_{$vol}.sql", $dump->sDumpSql) === false ){;
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

    if( substr($file_name,-4) != '.sql' ){
        make_json_fail($_LANG['file_ext_error']);
    }

    /* 分卷导入 */
    if( preg_match('/_[0-9]+\.sql$/', $file_name) ){
        /* 初始化 */
        $num = intval( substr($file_name, strrpos($file_name, '_')+1) );
        $short_name = substr( $file_name, 0, strrpos($file_name, '_') );

        /* 无效文件 */
        $str = DIR_DB_DUMPSQL. $short_name .'_'. $num .'.sql';
        if( !is_file($str) ){
            make_json_fail($_LANG['fail_dbbackup_import']);
        }

        /* 导入初始化 */
        if( intval($_POST['init']) == 1 ){
            /* 取得分卷总数 */
            for( $i=1; $i < 100; $i++ ){
                $str = DIR_DB_DUMPSQL. $short_name .'_'. $i .'.sql';

                if( !is_file($str) ){
                    $_POST['total'] = $i-1; break;
                }
            }

            /* 构建返回消息 */
            $str = 'file='. $short_name .'_'. $num .'.sql&total='.$_POST['total'];
            $msg = sprintf($_LANG['spr_dbbackup_import_part'], $num, $_POST['total']);

            /* 返回消息 */
            make_json_response(-1, $msg, $str);
        }

        /* 开始导入SQL数据 */
        if( !sql_import(DIR_DB_DUMPSQL.$file_name) ){
            make_json_fail($_LANG['fail_dbbackup_import']);
        }else{
            /* 导入完成 */
            $str = DIR_DB_DUMPSQL. $short_name .'_'. ($num+1) .'.sql';
            if( !is_file($str) ){
                make_json_ok($_LANG['ok_dbbackup_import']);
            }

            /* 构建返回消息 */
            $str = 'file='. $short_name .'_'. ($num+1) .'.sql&total='.$_POST['total'];
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
        if( !sql_import(DIR_DB_DUMPSQL.$file_name) ){
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
    $file_path = DIR_DB_DUMPSQL.'_upload_sql_file.sql';

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
        if( substr($file,-4) != '.sql' ) continue;

        if( preg_match('/_[0-9]+\.sql$/', $file) ){
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

        $folder = opendir(DIR_DB_DUMPSQL);
        while( $file = readdir($folder) ){
            if( preg_match('/_[0-9]+\.sql$/',$file) && is_file(DIR_DB_DUMPSQL.$file) ){
                $files[] = $file;
            }
        }

        foreach( $files AS $file ){
            $short_file = substr($file, 0, strrpos($file, '_'));
            if( in_array($short_file, $m_files) ){
                @unlink(DIR_DB_DUMPSQL.$file);
            }
        }
    }
    
    /* 单卷文件删除 */
    if( !empty($s_files) ){
        foreach( $s_files AS $file ){
            @unlink(DIR_DB_DUMPSQL.$file);
        }
    }

    make_json_ok();
}


/* ------------------------------------------------------ */
// - 异步 - 备份文件列表(默认页)
/* ------------------------------------------------------ */
else{
    /* 权限检查 */
    admin_privilege_valid('db_backup.php', 'backup');

    /* 备份文件夹权限检查 */
    valid_dbbackup_folder();

    /* SQL文件列表 - 数据格式化 */
    $tpl['all'] = list_sqlfile_format( list_sqlfile() );

    /* 初始化页面信息 */
    $tpl['_body'] = 'list';


    /* ------------------------------------------------------ */
    // - 异步 - 列表查询
    /* ------------------------------------------------------ */
    if( $_REQUEST['act'] == 'query' ){
        /* 初始化页面信息 */
        $tpl['_block'] = true;

        /* 返回JSON */
        make_json_ok( '', tpl_fetch('db_backup.html',$tpl) );
    }

    /* ------------------------------------------------------ */
    // - 列表
    /* ------------------------------------------------------ */
    else{
        /* 取得管理员的备份操作 */
        $m_aa = admin_module_acts('db_backup.php');
        $m_ab = filter_module_acts($m_aa, array('backup'), true);
        
        /* 操作属性 */
        $attribs = array();
        $attribs['backup']['onclick'] = 'wnd_dbbackup_fill()';

        /* 初始化页面信息 */
        $tpl['acts']  = format_module_acts($m_ab, $attribs, 'btn'); //格式化模块的操作(非内嵌)
        $tpl['title'] = admin_privilege_name_fk('db_backup.php', 'backup'); //权限名称
    }
}


/* 加载视图 */
include(DIR_ADMIN_TPL.'db_backup.html');
?>

<?php
/**
 * 备份文件夹权限检查
 */
function valid_dbbackup_folder()
{
    global $_LANG;

    $mask = file_privilege(DIR_DB_DUMPSQL);
 
    if( $mask === false ){
        $msg = $_LANG['fail_dbbackup_fdno'].'<br />'.DIR_DB_DUMPSQL;
    }
    elseif( $mask < 7 ){
        $msg = $_LANG['fail_dbbackup_fdpriv'];
        if( ($mask&1) < 1 ) $tpl['error'] .= $_LANG['cannot_read']  .', ';
        if( ($mask&2) < 1 ) $tpl['error'] .= $_LANG['cannot_write'] .', ';
        if( ($mask&4) < 1 ) $tpl['error'] .= $_LANG['cannot_edit']  .'.';

        $msg .= '<br />'.DIR_DB_DUMPSQL;
    }

    $msg ? sys_msg($msg) : '';
}


/**
 * SQL文件列表
 */
function list_sqlfile()
{
    $files  = array();
    $folder = @opendir(DIR_DB_DUMPSQL);

    while( $file = @readdir($folder) ){
        if( substr($file, -4) == '.sql' ){
            $files[] = $file;
        }
    }

    return $files;
}
function list_sqlfile_format( $files )
{
    global $_LANG;

    /* 初始化 */
    $list = array();

    /* 构建列表数据 */
    foreach( $files AS $file ){
        /* 设置备份文件的层级，类型和当前卷 */
        if( preg_match('/_[0-9]+\.sql$/', $file, $matchs) ){
            $level = 2; $type = 'volumes'; $volume = intval( substr($matchs[0],1) );
        }else{
            $level = 1; $type = 'volume';  $volume = 1;
        }

        /* 构建用于排序的文件名和引导的文件名 */
        $filearr = explode('_', $file);  $offset = count($filearr) - 1;
        $filearr[$offset] = $type == 'volumes' ? str_pad($filearr[$offset], 8, '0', STR_PAD_LEFT) : $filearr[$offset];

        $filesort  = implode('_', $filearr);
        $fileindex = preg_replace('/_[0-9]+\.sql$/', '.sql', $file);

        /* 备份文件信息 */
        $info = DumpSql::getHeader(DIR_DB_DUMPSQL.$file);
        $info['file_size'] = filesize(DIR_DB_DUMPSQL.$file);

        /* 记录操作 */
        if( $acts = '' || $volume == 1 ){
            $acts = '<a href="javascript:void(0)" onclick="deal_dbbackup_import(\'file='. $file;
            $acts.= '&init=1\',\''. $fileindex .'\')">'. $_LANG['act_import'] .'</a>';
        }

        /* 构建引导文件名的HTML */
        if( $type == 'volume' ){
            /* 文件名HTML格式化 */
            $name = '<a style="margin-left:16px;" target="_blank" href="'. URL_DB_DUMPSQL.$file .'">'. f($file,'html') .'</a>';
        }
        elseif( $volume == 1 ){
            /* 文件名HTML格式化 */
            $name = '<span class="plus" style="cursor:pointer;margin-left:0em" ';
            $name.= 'onclick="tabletree_click(this)"></span><a target="_blank" href="'. URL_DB_DUMPSQL.$file .'">'. f($fileindex,'html') .'</a>';

            /* 文件信息写入列表 - 引导文件 - 多卷情况下 */
            $list[$fileindex] = array( 'vol'       => 0,               //文件卷
                                       'file'      => $file,           //文件名(真实的文件名)
                                       'name'      => $name,           //文件名(经过HTML格式化)
                                       'date'      => $info['date'],   //文件创建时间
                                       'type'      => 'volumesindex',  //文件类型 - volume表示单卷文件, volumes表示多卷文件, volumes表示多卷索引文件
                                       'level'     => 1,               //文件层级
                                       'file_size' => 0,               //文件大小
                                       '_acts'     => $acts            //记录操作
                                     );

            /* 重置 */
            $acts = '';
        }

        /* 构建真实文件名的HTML - 单卷时引导文件名HTML即为真实文件名HTML */
        if( $type == 'volumes' ){
            $name = '<span style="display:none"></span><a target="_blank" href="'. URL_DB_DUMPSQL.$file .'" ';
            $name.= 'style="color:#999;margin-left:16px;">'. f($file,'html') .'</a>';
        }

        /* 文件信息写入列表 - 多卷情况下 */
        $list[$filesort] = array( 'vol'       => $info['vol'],
                                  'date'      => $info['date'], 
                                  'file'      => $file,
                                  'name'      => $name,
                                  'type'      => $type,
                                  'level'     => $level,
                                  'file_size' => $info['file_size'], 
                                  '_acts'     => $acts
                                );

        /* 重构引导文件信息 */
        if( $type == 'volumes' ){
            $list[$fileindex]['vol']++;
            $list[$fileindex]['file_size'] += $list[$filesort]['file_size'];
        }
    }

    /* 格式化列表数据 */
    foreach( $list AS $i=>$r ){
        $list[$i]['file_size'] = bitunit($r['file_size']);
    }

    /* 下标排序 */
    ksort($list);

    return $list;
}


/**
 * 将文件中的sql语句导入到数据库
 *
 * @params str  $path_file  文件绝对路径
 *
 * @return bol  true表示导入成功，false表示导入失败
 */
function sql_import( $path_file )
{
    $db_ver  = $GLOBALS['db']->version();

    $sql_str = array_filter(file($path_file), 'remove_comment');
    $sql_str = str_replace( "\r", '', implode('',$sql_str) );

    $ret = explode(";\n", $sql_str);

    /* 执行sql语句 */
    for($i = 0; $i < count($ret); $i++){
        $ret[$i] = trim($ret[$i], " \r\n;"); //剔除多余信息

        if( empty($ret[$i]) ) continue;

        if( !$GLOBALS['db']->query($ret[$i], false) ){
            return false;
        }
    }

    return true;
}

/**
 * 移除SQL注释
 */
function remove_comment($var)
{
    return (substr(trim($var), 0, 2) != '--');
}
?>