<?php
// +----------------------------------------------------------------------
// | LengdoFrame - 系统配置模块
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
admin_privilege_valid('sysconfig.php', 'set');


/* 初始化页面信息 */
$tpl['title'] = admin_privilege_name_fk('sysconfig.php', 'set'); //权限名称


/* 加载视图 */
include(DIR_ADMIN_TPL.'sysconfig.html');
?>