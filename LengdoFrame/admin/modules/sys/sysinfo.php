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


/* 权限检查 */
if( admin_id() != 1 ) sys_msg($_LANG['lawless_act']);

/* 运行环境 */
$tpl['env']['php']    = phpversion();
$tpl['env']['mysql']  = $db->version();
$tpl['env']['kernel'] = $_LANG['sys_kernel'];

/* 目录权限 */
$tpl['dir']['path_sql']      = '<span style="color:#333">'. $_CFG['URL_ADMIN_DUMPSQL']  .'</span>';
$tpl['dir']['path_dbc']      = '<span style="color:#333">'. $_CFG['URL_ADMIN_CACHESQL'] .'</span>';
$tpl['dir']['path_pfile']    = '<span style="color:#333">'. $_CFG['URL_ADMIN_PFILE']    .'</span>';

$tpl['dir']['priv_sql']      = file_privilege($_CFG['DIR_ADMIN_DUMPSQL'])  >= 3 ? '<span class="yes"></span>' : '<span class="no"></span>';
$tpl['dir']['priv_dbc']      = file_privilege($_CFG['DIR_ADMIN_CACHESQL']) >= 3 ? '<span class="yes"></span>' : '<span class="no"></span>';
$tpl['dir']['priv_pfile']    = file_privilege($_CFG['DIR_ADMIN_PFILE'])    >= 3 ? '<span class="yes"></span>' : '<span class="no"></span>';


/* 加载视图 */
include($_CFG['DIR_ADMIN_TPL'] . 'sysinfo.html');
?>