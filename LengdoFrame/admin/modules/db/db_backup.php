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
    
    /* 输出HTML */
    echo_sqlfile($_GET['file'], 'html'); exit();
}


/* ------------------------------------------------------ */
// - 下载备份文件
/* ------------------------------------------------------ */
elseif( $_REQUEST['act'] == 'download' ){
    /* 权限检查 */
    admin_privilege_valid('db_backup.php', 'backup');

    /* 输出文件下载头 */
    http_export_header( preg_replace('/\.sql\.php$/', '.sql', $_GET['file']) );

    /* 根据索引文件获取所有文件 */
    $all = all_sqlfile( array('findex'=> $_GET['file']) );

    /* 输出文件数据 */
    foreach( $all AS $i=>$fname ){
        echo_sqlfile($fname); echo "\r\n\r\n";
    }

    exit();
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
    $onclick= "Formc.cbgSyncCb(Formc.cbgByContainer('wfm-dbbackup-customtable'),'wfm-dbbackup-selall')";

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
        if( !import_sqlfile($_CFG['DIR_DB_DUMPSQL'].$file_name) ){
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
        if( !import_sqlfile($_CFG['DIR_DB_DUMPSQL'].$file_name) ){
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
    if( import_sqlfile($file_path) === false ){
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

    /* 初始化参数 */
    $_POST['ids'] = is_array($_POST['ids']) ? $_POST['ids'] : array();

    /* 删除SQL文件 */
    foreach( $_POST['ids'] AS $findex ){
        /* 根据索引获取所有SQL文件 */
        $fnames = all_sqlfile( array('findex'=>$findex) );

        foreach( $fnames AS $fname ){
            @unlink($_CFG['DIR_DB_DUMPSQL'].$fname);
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
    $tpl['all'] = all_sqlfile();

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

    /* 显示消息 */
    if( $msg ) sys_msg($msg);
}

/**
 * 获取所有SQL文件
 */
function all_sqlfile( $filter = array() )
{
    global $_CFG;

    /* 初始化 */
    $all = array();

    /* 获取全部SQL文件 */
    if( empty($filter) ){
        $fgroup = all_sqlfile_group();

        foreach( $fgroup AS $i=>$files ){
            $all = array_merge( $all, array_values($files) );
        }
    }

    /* 根据文件索引获取全部文件 */
    elseif( $findex = $filter['findex'] ){
        /* 单卷文件 */
        if( is_file($_CFG['DIR_DB_DUMPSQL'].$findex) ){
            $all = array($findex);
        }
        /* 多卷文件 */
        else{
            $volume = 1;
            $findex = preg_replace('/\.sql\.php$/', '_1.sql.php', $findex);

            while( is_file($_CFG['DIR_DB_DUMPSQL'].$findex) ){
                $all[] = $findex;
                $findex = preg_replace('/_'.($volume++).'\.sql\.php$/', '_'.$volume.'.sql.php', $findex);
            }
        }
    }

    return $all;
}
/**
 * 分组获取全部SQL文件
 */
function all_sqlfile_group()
{
    global $_CFG;
    
    /* 初始化 */
    $fgroup = array();
    $folder = @opendir($_CFG['DIR_DB_DUMPSQL']);

    /* 遍历备份文件夹 */
    while( $fname = @readdir($folder) ){
        /* 无效备份文件 */
        if( !preg_match('/\.sql\.php$/',$fname,$matchs) ) continue;

        /* 单卷时文件卷和索引文件名 */
        $volume = 1; $findex = $fname;

        /* 多卷时文件卷和索引文件名 */
        if( preg_match('/_[0-9]+\.sql\.php$/',$fname,$matchs) ){
            $volume = intval( substr($matchs[0],1) );
            $findex = preg_replace('/_[0-9]+\.sql\.php$/', '.sql.php', $fname);
        }

        /* 按索引文件名，文件卷分类存储 */
        $fgroup[$findex][str_pad($volume,4,'0',STR_PAD_LEFT)] = $fname;
    }

    /* 格式化文件组信息 */
    foreach( $fgroup AS $findex=>$files ){
        /* 格式化单卷或多卷文件组 */
        foreach( $fgroup[$findex] AS $volume=>$file ){
            /* 格式化单卷文件组 */
            if( count($fgroup[$findex]) == 1 ){
                $fgroup[$findex][$volume] = all_sqlfile_format_vol($file);
            }

            /* 格式化多卷文件组 */
            else{
                $fgroup[$findex][$volume] = all_sqlfile_format_vols($file, ltrim($volume,'0'));
            }
        }

        /* 格式化多卷索引文件 */
        if( count($fgroup[$findex]) > 1 ){
            $fgroup[$findex]['0000'] = all_sqlfile_format_volsi($findex, $fgroup[$findex]);
        }
        
        /* 排序数组属性 */
        ksort($fgroup[$findex]);
    }

    return $fgroup;
}
/**
 * 获取全部SQL文件的文件信息
 */
function all_sqlfile_format_vol( $fname )
{
    global $_CFG, $_LANG;

    /* 备份文件的头信息 */
    $header = DumpSql::getHeader($_CFG['DIR_DB_DUMPSQL'].$fname);

    /* 基本信息 */
    $info['vol']  = 1;
    $info['file'] = $fname;
    $info['type'] = 'volume';
    $info['date'] = $header['date'];
    $info['size'] = filesize($_CFG['DIR_DB_DUMPSQL'].$fname);

    /* 基本信息 - 显示的文件名 */
    $info['name'] = '<a style="margin-left:16px;" target="_blank" ';
    $info['name'].= 'href="modules/db/db_backup.php?act=view&file='. $fname;
    $info['name'].= '">'. f( preg_replace('/\.sql\.php$/','.sql',$fname), 'html' ) .'</a>';

    /* 基本信息 - 文件操作链接 */
    $info['acts'] = '<a href="javascript:void(0)" onclick="deal_dbbackup_download(\''. $fname;
    $info['acts'].= '\',\''. f(preg_replace('/\.sql\.php$/','.sql',$fname), 'html') .'\')">'. $_LANG['act_download'] .'</a> ';
    $info['acts'].= '<a href="javascript:void(0)" onclick="deal_dbbackup_import(\'file='. $fname;
    $info['acts'].= '&init=1\',\''. f(preg_replace('/\.sql\.php$/','.sql',$fname), 'html') .'\')">'. $_LANG['act_import'] .'</a>';

    return $info;
}
function all_sqlfile_format_vols( $fname, $vol )
{
    global $_CFG;

    /* 备份文件的头信息 */
    $header = DumpSql::getHeader($_CFG['DIR_DB_DUMPSQL'].$fname);

    /* 基本信息 */
    $info['vol']  = 1;
    $info['file'] = $fname;
    $info['type'] = 'volumes';
    $info['date'] = $header['date'];
    $info['size'] = filesize($_CFG['DIR_DB_DUMPSQL'].$fname);

    /* 基本信息 - 显示的文件名 */
    $info['name'] = '<span style="display:none"></span><a style="color:#999;margin-left:16px;" target="_blank" ';
    $info['name'].= 'href="modules/db/db_backup.php?act=view&file='. $fname;
    $info['name'].= '">'. f( preg_replace('/\.sql\.php$/','.sql',$fname), 'html' ) .'</a>';

    return $info;
}
function all_sqlfile_format_volsi( $fname, $files )
{
    global $_LANG;

    /* 基本信息 */
    $info['vol']  = 0;
    $info['size'] = 0;
    $info['file'] = $fname;
    $info['type'] = 'volumesindex';
    $info['date'] = $files['0001']['date'];

    /* 基本信息 - 显示的文件名 */
    $info['name'] = '<span class="plus" style="cursor:pointer;margin-left:0em" ';
    $info['name'].= 'onclick="tabletree_click(this)"></span><a href="javascript:void(0)" onclick="tabletree_click(this.previousSibling)"';
    $info['name'].= '">'. f( preg_replace('/\.sql\.php$/','.sql',$fname), 'html' ) .'</a>';

    /* 基本信息 - 文件操作链接 */
    $info['acts'] = '<a href="javascript:void(0)" onclick="deal_dbbackup_download(\''. $fname;
    $info['acts'].= '\',\''. f(preg_replace('/\.sql\.php$/','.sql',$fname), 'html') .'\')">'. $_LANG['act_download'] .'</a> ';
    $info['acts'].= '<a href="javascript:void(0)" onclick="deal_dbbackup_import(\'file='. $fname;
    $info['acts'].= '&init=1\',\''. f(preg_replace('/\.sql\.php$/','.sql',$fname), 'html') .'\')">'. $_LANG['act_import'] .'</a>';

    /* 重构信息 */
    foreach( $files AS $vol=>$file ){
        $info['vol']  += 1;
        $info['size'] += $file['size'];
    }

    return $info;
}

/**
 * 输出SQL文件数据
 *
 * @params str  $fname    SQL文件名
 * @params str  $oencode  输出的编码，'HTML'或者''
 */
function echo_sqlfile( $fname, $oencode = '' )
{
    global $_CFG, $_LANG;

    /* 构建SQL文件路径 */
    $fpath = $_CFG['DIR_DB_DUMPSQL'].$fname;

    /* 无效SQL文件名 */
    if( substr($fname,-8) != '.sql.php' || !is_file($fpath) ){
        sys_msg($_LANG['lawless_submit']);
    }

    /* 根据编码输出SQL文件 */
    if( $oencode == 'html' ){
        $str = file_get_contents($fpath);
        $str = str_replace(' ', '&nbsp;', $str);
        $str = str_replace(array("\r\n","\r","\n"), '<br />', $str);
        $str = substr($str, strpos($str,'<br />')+6);
    }else{
        $arr = file($fpath); array_shift($arr);
        $str = implode('', $arr);
    }

    echo $str;
}

/**
 * 导入文件SQL到数据库
 *
 * @params str  $fpath  文件绝对路径
 *
 * @return bol  true表示导入成功，false表示导入失败
 */
function import_sqlfile( $fpath )
{
    /* 初始化SQL数组 */
    $sqls = array_filter(file($fpath), 'remove_sqlfile_comment');
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
function remove_sqlfile_comment( $str )
{
    return (substr(trim($str), 0, 2) != '--');
}
?>