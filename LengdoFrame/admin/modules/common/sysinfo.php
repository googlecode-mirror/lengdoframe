<?php
// +----------------------------------------------------------------------
// | LengdoFrame - 系统信息模块
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


/* 运行环境 */
$tpl['env']['php']    = phpversion();
$tpl['env']['mysql']  = $db->version();
$tpl['env']['kernel'] = $_LANG['sys_kernel'];

/* 目录权限 */
$tpl['dir']['path_img']      = '<span style="color:#333">'. URL_UPLOAD_IMG      .'</span>';
$tpl['dir']['path_sql']      = '<span style="color:#333">'. URL_DB_DUMPSQL      .'</span>';
$tpl['dir']['path_dbd']      = '<span style="color:#333">'. URL_DB_DATA         .'</span>';
$tpl['dir']['path_dbc']      = '<span style="color:#333">'. URL_DB_CACHESQL     .'</span>';
$tpl['dir']['path_pfile']    = '<span style="color:#333">'. URL_ADMIN_PFILE     .'</span>';
$tpl['dir']['path_jseditor'] = '<span style="color:#333">'. URL_JSEDITOR_UPLOAD .'</span>';

$tpl['dir']['priv_img']      = file_privilege(DIR_UPLOAD_IMG)      >= 3 ? '<span class="yes"></span>' : '<span class="no"></span>';
$tpl['dir']['priv_sql']      = file_privilege(DIR_DB_DUMPSQL)      >= 3 ? '<span class="yes"></span>' : '<span class="no"></span>';
$tpl['dir']['priv_dbd']      = file_privilege(DIR_DB_DATA)         >= 3 ? '<span class="yes"></span>' : '<span class="no"></span>';
$tpl['dir']['priv_dbc']      = file_privilege(DIR_DB_CACHESQL)     >= 3 ? '<span class="yes"></span>' : '<span class="no"></span>';
$tpl['dir']['priv_pfile']    = file_privilege(DIR_ADMIN_PFILE)     >= 3 ? '<span class="yes"></span>' : '<span class="no"></span>';
$tpl['dir']['priv_jseditor'] = file_privilege(DIR_JSEDITOR_UPLOAD) >= 3 ? '<span class="yes"></span>' : '<span class="no"></span>';


/* 加载视图 */
include(DIR_ADMIN_TPL.'sysinfo.html');
?>