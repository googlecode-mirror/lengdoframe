<?php
// +----------------------------------------------------------------------
// | LengdoFrame - 整站公用配置
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://lengdo.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Yangfan Dai <dmlk31@163.com>
// +----------------------------------------------------------------------
// $Id$


/* ----------------------------------------------------------------------- */
// - 预定义常量
/* ----------------------------------------------------------------------- */

/* 管理员 SESSION 的下标 */
define('SN_ADMIN', md5(__FILE__) );

/* 根路径的相对路径和绝对路径(保留末尾斜杠) */
define('DIR_ROOT', str_ireplace( 'includes/config.php', '', str_replace("\\",'/',__FILE__) ) );
define('URL_ROOT', str_ireplace( rtrim(str_replace("\\",'/',$_SERVER['DOCUMENT_ROOT']),'/'), '', DIR_ROOT) );


/* ----------------------------------------------------------------------- */
// - 初始化配置
/* ----------------------------------------------------------------------- */

$_CFG = array();


/* ----------------------------------------------------------------------- */
// - 环境配置
/* ----------------------------------------------------------------------- */

/* 时区 */
$_CFG['timezone'] = 'PRC';


/* ----------------------------------------------------------------------- */
// - 根级路径配置
/* ----------------------------------------------------------------------- */
$_CFG['DIR_ROOT']  = DIR_ROOT;
$_CFG['URL_ROOT']  = URL_ROOT;

$_CFG['DIR_ADMIN'] = DIR_ROOT.'admin/';
$_CFG['URL_ADMIN'] = URL_ROOT.'admin/';


/* ----------------------------------------------------------------------- */
// - 扩展配置
/* ----------------------------------------------------------------------- */
@include_once($_CFG['DIR_ROOT'] . 'includes/systemconfig.php');


/* ----------------------------------------------------------------------- */
// - 子级路径配置
/* ----------------------------------------------------------------------- */

/* 公用文件夹路径 */
$_CFG['DIR_CLS'] = isset($_CFG['DIR_CLS']) ? $_CFG['DIR_CLS'] : $_CFG['DIR_ROOT'].'class/';
$_CFG['DIR_INC'] = isset($_CFG['DIR_INC']) ? $_CFG['DIR_INC'] : $_CFG['DIR_ROOT'].'includes/';

/* 公用文件夹路径 - 组件文件夹 */
$_CFG['DIR_PLUGIN'] =  isset($_CFG['DIR_PLUGIN']) ? $_CFG['DIR_PLUGIN'] : $_CFG['DIR_ROOT'].'plugin/';
$_CFG['URL_PLUGIN'] =  isset($_CFG['URL_PLUGIN']) ? $_CFG['URL_PLUGIN'] : $_CFG['URL_ROOT'].'plugin/';


/* 前台文件夹路径 */
$_CFG['DIR_TPL'] = isset($_CFG['DIR_TPL']) ? $_CFG['DIR_TPL'] : $_CFG['DIR_ROOT'].'tpl/';
$_CFG['DIR_LNG'] = isset($_CFG['DIR_LNG']) ? $_CFG['DIR_LNG'] : $_CFG['DIR_ROOT'].'lang/';


/* 后台文件夹路径 */
$_CFG['DIR_ADMIN_TPL']   = isset($_CFG['DIR_ADMIN_TPL'])   ? $_CFG['DIR_ADMIN_TPL']   : $_CFG['DIR_ADMIN'].'tpl/';
$_CFG['DIR_ADMIN_LNG']   = isset($_CFG['DIR_ADMIN_LNG'])   ? $_CFG['DIR_ADMIN_LNG']   : $_CFG['DIR_ADMIN'].'lang/';
$_CFG['DIR_ADMIN_INC']   = isset($_CFG['DIR_ADMIN_INC'])   ? $_CFG['DIR_ADMIN_INC']   : $_CFG['DIR_ADMIN'].'includes/';

/* 后台文件夹路径 - 自定义数据缓存 */
$_CFG['DIR_DB_DATA']     = isset($_CFG['DIR_DB_DATA'])     ? $_CFG['DIR_DB_DATA']     : $_CFG['DIR_ADMIN'].'data/dbd/';
$_CFG['URL_DB_DATA']     = isset($_CFG['URL_DB_DATA'])     ? $_CFG['URL_DB_DATA']     : $_CFG['URL_ADMIN'].'data/dbd/';

/* 后台文件夹路径 - 数据库备份 */
$_CFG['DIR_DB_DUMPSQL']  = isset($_CFG['DIR_DB_DUMPSQL'])  ? $_CFG['DIR_DB_DUMPSQL']  : $_CFG['DIR_ADMIN'].'data/dumpsql/';
$_CFG['URL_DB_DUMPSQL']  = isset($_CFG['URL_DB_DUMPSQL'])  ? $_CFG['URL_DB_DUMPSQL']  : $_CFG['URL_ADMIN'].'data/dumpsql/';

/* 后台文件夹路径 - 数据库SQL缓存 */
$_CFG['DIR_DB_CACHESQL'] = isset($_CFG['DIR_DB_CACHESQL']) ? $_CFG['DIR_DB_CACHESQL'] : $_CFG['DIR_ADMIN'].'data/cachesql/';
$_CFG['URL_DB_CACHESQL'] = isset($_CFG['URL_DB_CACHESQL']) ? $_CFG['URL_DB_CACHESQL'] : $_CFG['URL_ADMIN'].'data/cachesql/';

/* 后台文件夹路径 - 管理员权限文件 */
$_CFG['DIR_ADMIN_PFILE'] = isset($_CFG['DIR_ADMIN_PFILE']) ? $_CFG['DIR_ADMIN_PFILE'] : $_CFG['DIR_ADMIN'].'data/pfile/';
$_CFG['URL_ADMIN_PFILE'] = isset($_CFG['URL_ADMIN_PFILE']) ? $_CFG['URL_ADMIN_PFILE'] : $_CFG['URL_ADMIN'].'data/pfile/';
?>