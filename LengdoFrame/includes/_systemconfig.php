<?php
// +----------------------------------------------------------------------
// | LengdoFrame - 整站公用配置库
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://lengdo.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Yangfan Dai <dmlk31@163.com>
// +----------------------------------------------------------------------
// $Id$


/* ----------------------------------------------------------------------- */
// - 数据库登陆信息
/* ----------------------------------------------------------------------- */
$_CFG['dbhost'] = 'localhost';
$_CFG['dbname'] = '';
$_CFG['dbuser'] = '';
$_CFG['dbpass'] = '';
$_CFG['tblpre'] = '';


/* ----------------------------------------------------------------------- */
// - 自定义目录路径信息(保留末尾斜干)
/* ----------------------------------------------------------------------- */

/* 开放权限文件夹路径 - 上传的图片 */
define('DIR_UPLOAD_IMG'  , DIR_ROOT . 'upload/images/');
define('URL_UPLOAD_IMG'  , URL_ROOT . 'upload/images/');

/* 开放权限文件夹路径 - XXXX的图片 */
define('DIR_XXXX_IMG' , DIR_ROOT . 'upload/images/xxxx/');
define('URL_XXXX_IMG' , URL_ROOT . 'upload/images/xxxx/');

/* 编辑器控件基本路径信息 */
define('DIR_JSEDITOR_UPLOAD' , DIR_ROOT . 'upload/jseditor/');
define('URL_JSEDITOR_UPLOAD' , URL_ROOT . 'upload/jseditor/');
define('URL_JSEDITOR_FOLDER' , URL_ROOT . 'js/fckeditor/');
?>