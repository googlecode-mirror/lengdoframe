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
    echo_sqlfile($_GET['fname'], 'html'); exit();
}


/* ------------------------------------------------------ */
// - 下载备份文件
/* ------------------------------------------------------ */
elseif( $_REQUEST['act'] == 'download' ){
    /* 权限检查 */
    admin_privilege_valid('db_backup.php', 'backup');

    /* 输出文件下载头 */
    http_download_header( preg_replace('/\.sql\.php$/', '.sql', $_GET['findex']) );

    /* 根据索引文件获取所有文件 */
    $fnames = all_sqlfile( array('findex'=>$_GET['findex']) );

    /* 输出文件数据 */
    foreach( $fnames AS $i=>$fname ){
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
    $tpl['sqlfname'] = DumpSql::getRandName().'.sql';

    /* 生成所有表 */
    $tables  = $db->getCol("SHOW TABLES");
    $onclick = "Formc.cbgSyncCb(Formc.cbgByContainer('wfm-dbbackup-customtable'),'wfm-dbbackup-selall')";

    foreach( $tables AS $table ){
        $items[] = array('value'=>$table, 'text'=>$table, 'onclick'=>$onclick, 'class'=>'checkbox');
    }

    /* HTML 控件 */
    $formc = new Formc();
    $tpl['cbg_custom_table'] = $formc->cbg('custom_tables[]', $items);

    /* 初始化页面信息 */
    $tpl['_body'] = 'backup';
}
/* ------------------------------------------------------ */
// - 异步 - 导出SQL
/* ------------------------------------------------------ */
elseif( $_REQUEST['act'] == 'dumpsql' ){
    /* 权限检查 */
    admin_privilege_valid('db_backup.php', 'backup');

    /* 初始化常量 */
    $vol      = intval($_POST['vol']) > 0 ? intval($_POST['vol']) : 1;
    $volsize  = intval($_POST['volsize']) > 0 ? intval($_POST['volsize']) : 1536; //分卷文件大小( KB为单位 )

    $columns  = intval($_POST['columns']);
    $extended = intval($_POST['extended']);

    /* 初始化常量 - 备份文件名(去除扩展名) */
    $sqlfname = substr($_POST['sqlfname'],-4) != '.sql' ? DumpSql::getRandName() : trim($_POST['sqlfname']);
    $sqlfname = substr($sqlfname, 0, -4);

    /* 创建 DumpSql 对象 */
    $dump = new DumpSql($db);

    /* 设置 DumpSql 对象属性 - 显示字段，扩展插入 */
    $dump->bColumns  = $columns;
    $dump->bExtended = $extended;

    /* 设置 DumpSql 对象属性 - 卷大小限制 */
    $dump->iMaxSize = $volsize * 1024;

    /* 文件路径 */
    $logpath  = $_CFG['DIR_ADMIN_DUMPSQL'] . 'run.log';

    /* 取得要备份的表 */
    $tables = array();

    /* 全部表备份 */
    if( trim($_POST['backup_type']) == 'full' ){ 
        $temp = $db->getCol('SHOW TABLES'); 

        foreach( $temp AS $table ){
            $tables[$table] = -1;
        }

        $dump->putTablesList($logpath, $tables);
    }

    /* 自定义备份 */
    else if( trim($_POST['backup_type']) == 'custom' ){
        $temp = is_array($_POST['custom_tables']) ? $_POST['custom_tables'] : array();  

        foreach( $temp AS $table ){
            $tables[$table] = -1;
        }

        $dump->putTablesList($logpath, $tables);
    }

    /* 备份表 - 根据数据表位置文件 */
    $tables = $dump->dumpTables($logpath, $vol);

    /* 备份失败 - 数据表位文件不可读 */
    if( $tables === false ){
        make_json_fail($_LANG['fail_dbbackup_position']);
    }

    /* 单卷备份或者多卷的最后一卷备份 */
    if( empty($tables) ){
        /* 初始化备份文件名 */
        $fname = $vol > 1 ? "{$sqlfname}_{$vol}.sql.php" : "{$sqlfname}.sql.php";

        /* 初始化备份成功消息 */
        $_LANG['ok_dbbackup'] = $vol > 1 ? sprintf($_LANG['spr_dbbackup_ok'],$vol) : $_LANG['ok_dbbackup'];

        /* 写入SQL到文件 */
        if( write_sqlfile($fname,$dump->sDumpSql) === false ){
            make_json_fail($_LANG['fail_dbbackup_write']);
        }else{
            make_json_ok($_LANG['ok_dbbackup']);
        }
    }

    /* 多卷的非最后一卷备份 */
    else{
        /* 写入SQL到文件 */
        if( write_sqlfile("{$sqlfname}_{$vol}.sql.php",$dump->sDumpSql) === false ){
            make_json_fail($_LANG['fail_dbbackup_write']);
        }

        /* 构建下个文件备份参数 */
        $params = 'sqlfname='. $sqlfname .'.sql&vol='. ($vol+1);
        $params.= '&volsize='. $volsize .'&columns='. $columns .'&extended='. $extended;

        /* 沉睡1秒再返回 */
        sleep(1); make_json_response('-1', sprintf($_LANG['spr_dbbackup_ok_part'],$vol), $params);
    }
}


/* ------------------------------------------------------ */
// - 异步 - 导入备份文件 - 服务器SQL文件导入
/* ------------------------------------------------------ */
elseif( $_REQUEST['act'] == 'import' ){
    /* 权限检查 */
    admin_privilege_valid('db_backup.php', 'backup');

    /* 导入文件SQL到数据库 */
    if( import_sqlfile($_POST['fname']) === false ){
        make_json_fail($_LANG['fail_dbbackup_import']);
    }
    
    /* 导入完成 */
    if( $_POST['vol'] == $_POST['total'] ){
        make_json_ok($_LANG['ok_dbbackup_import']);
    }

    /* 下一卷的备份文件名 */
    $fname = preg_replace('/_[0-9]+\.sql\.php$/', '_'. ($_POST['vol']+1) .'.sql.php', $_POST['fname']);

    /* 构建返回参数 */
    $params = 'fname='. $fname .'&vol='. ($_POST['vol']+1) .'&total='. $_POST['total'];

    /* 构建返回消息 */
    $_LANG['ok_dbbackup_importing'] = sprintf($_LANG['spr_dbbackup_import_part'], ($_POST['vol']+1), $_POST['total']);

    /* 返回消息 */
    make_json_response(-1, $_LANG['ok_dbbackup_importing'], $params);
}
elseif( $_REQUEST['act'] == 'importinit' ){
    /* 根据索引文件获取所有文件 */
    $fnames = all_sqlfile( array('findex'=>$_POST['findex']) );

    /* 文件总卷 */
    $total = count($fnames);

    /* 构建返回参数 */
    $params = 'fname='. $fnames[0] .'&vol=1&total='. $total;

    /* 构建返回消息 */
    if( $total > 1 ) $_LANG['ok_dbbackup_importing'] = sprintf($_LANG['spr_dbbackup_import_part'], 1, $total);

    /* 返回消息 */
    make_json_response(-1, $_LANG['ok_dbbackup_importing'], $params);
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
    $fname = 'upload_sqlfile_temp.sql.php';
    $fpath = $_CFG['DIR_ADMIN_DUMPSQL'].$fname;

    /* 将文件移动到备份文件夹下 */
    if( !move_uploaded_file($_FILES['file']['tmp_name'],$fpath) ){
        make_json_fail($_LANG['file_mov_fail']);
    }

    /* 导入SQL文件 */
    if( import_sqlfile($fname) === false ){
        @unlink($fpath); make_json_ok($_LANG['fail_dbbackup_import']);
    }else{
        @unlink($fpath); make_json_ok($_LANG['ok_dbbackup_import']);
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
            @unlink($_CFG['DIR_ADMIN_DUMPSQL'].$fname);
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
        /* HTML控件 */
        $attribs = array();
        $attribs['upload']  = array('onclick'=>"filecbox_upload(this,{'confirm':'确定上传SQL文件？'});");
        $attribs['overlay'] = array('style'=>"border-right-width:0px;");
        $attribs['filebox'] = array('onchange'=>"filecbox_change(this,filecbox_change_ext('sql','请上传SQL文件！'));");
        $tpl['cbox_file'] = filecbox('file', $attribs);

        /* 初始化页面信息 */
        $tpl['_header'] = 'title';

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

    $mask = file_privilege($_CFG['DIR_ADMIN_DUMPSQL']);
 
    if( $mask === false ){
        $msg = $_LANG['fail_dbbackup_fdno'] .'<br />'. $_CFG['DIR_ADMIN_DUMPSQL'];
    }
    elseif( $mask < 7 ){
        $msg = $_LANG['fail_dbbackup_fdpriv'];
        if( ($mask&1) < 1 ) $tpl['error'] .= $_LANG['file_unread']  .', ';
        if( ($mask&2) < 1 ) $tpl['error'] .= $_LANG['file_unwrite'] .', ';
        if( ($mask&4) < 1 ) $tpl['error'] .= $_LANG['file_unedit']  .'.';

        $msg .= '<br />' . $_CFG['DIR_ADMIN_DUMPSQL'];
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

    /* 获取服务器上全部SQL文件 */
    if( empty($filter) ){
        $fgroup = all_sqlfile_group();

        foreach( $fgroup AS $i=>$files ){
            $all = array_merge( $all, array_values($files) );
        }
    }

    /* 根据文件索引获取全部文件 */
    elseif( $findex = trim($filter['findex']) ){
        /* 单卷文件 */
        if( is_file($_CFG['DIR_ADMIN_DUMPSQL'].$findex) ){
            $all[] = $findex;
        }
        /* 多卷文件 */
        else{
            /* 初始化卷和SQL文件名 */
            $vol = 1;
            $fname = preg_replace('/\.sql\.php$/', '_1.sql.php', $findex);
            
            /* 根据索引遍历文件组 */
            while( is_file($_CFG['DIR_ADMIN_DUMPSQL'].$fname) ){
                $all[] = $fname;
                $fname = preg_replace('/_'.$vol++.'\.sql\.php$/', '_'.$vol.'.sql.php', $fname);
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
    $folder = @opendir($_CFG['DIR_ADMIN_DUMPSQL']);

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
    $header = DumpSql::getHeader($_CFG['DIR_ADMIN_DUMPSQL'].$fname);

    /* 基本信息 */
    $info['vol']  = 1;
    $info['file'] = $fname;
    $info['type'] = 'volume';
    $info['date'] = $header['date'];
    $info['size'] = filesize($_CFG['DIR_ADMIN_DUMPSQL'].$fname);

    /* 基本信息 - 显示的文件名 */
    $info['name'] = '<a style="margin-left:16px;" target="_blank" ';
    $info['name'].= 'href="modules/db/db_backup.php?act=view&fname='. $fname;
    $info['name'].= '">'. f( preg_replace('/\.sql\.php$/','.sql',$fname), 'html' ) .'</a>';

    /* 基本信息 - 文件操作链接 */
    $info['acts'] = '<a href="javascript:void(0)" onclick="deal_dbbackup_download(\''. $fname;
    $info['acts'].= '\',\''. f(preg_replace('/\.sql\.php$/','.sql',$fname), 'html') .'\')">'. $_LANG['act_download'] .'</a> ';
    $info['acts'].= '<a href="javascript:void(0)" onclick="deal_dbbackup_import_init(\''. $fname;
    $info['acts'].= '\',\''. f(preg_replace('/\.sql\.php$/','.sql',$fname), 'html') .'\')">'. $_LANG['act_import'] .'</a>';

    return $info;
}
function all_sqlfile_format_vols( $fname, $vol )
{
    global $_CFG;

    /* 备份文件的头信息 */
    $header = DumpSql::getHeader($_CFG['DIR_ADMIN_DUMPSQL'].$fname);

    /* 基本信息 */
    $info['vol']  = $vol;
    $info['file'] = $fname;
    $info['type'] = 'volumes';
    $info['date'] = $header['date'];
    $info['size'] = filesize($_CFG['DIR_ADMIN_DUMPSQL'].$fname);

    /* 基本信息 - 显示的文件名 */
    $info['name'] = '<span style="display:none"></span><a style="color:#999;margin-left:16px;" target="_blank" ';
    $info['name'].= 'href="modules/db/db_backup.php?act=view&fname='. $fname;
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
    $info['acts'].= '<a href="javascript:void(0)" onclick="deal_dbbackup_import_init(\''. $fname;
    $info['acts'].= '\',\''. f(preg_replace('/\.sql\.php$/','.sql',$fname), 'html') .'\')">'. $_LANG['act_import'] .'</a>';

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
    $fpath = $_CFG['DIR_ADMIN_DUMPSQL'].$fname;

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
 * 写入SQL到文件
 */
function write_sqlfile( $fname, $sql )
{
    global $_CFG;

    /* 构建SQL文件的路径 */
    $fpath = $_CFG['DIR_ADMIN_DUMPSQL'].$fname;

    /* 写入到文件 */
    return file_put_contents($fpath, "-- <?php exit(); ?>\r\n".$sql);
}

/**
 * 导入文件SQL到数据库
 *
 * @params str  $fname  SQL文件名
 *
 * @return bol  true表示导入成功，false表示导入失败
 */
function import_sqlfile( $fname )
{
    global $_CFG;

    /* 构建SQL文件的路径 */
    $fpath = $_CFG['DIR_ADMIN_DUMPSQL'].$fname;

    /* 无效参数 */
    if( !is_file($fpath) ) return false;

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