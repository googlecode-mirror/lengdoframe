<?php
// +----------------------------------------------------------------------
// | LengdoFrame - 后台公用语言库
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://lengdo.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Yangfan Dai <dmlk31@163.com>
// +----------------------------------------------------------------------
// $Id$


/* 初始化 */
$_LANG = array();


/* ------------------------------------------------------ */
// - 系统信息
/* ------------------------------------------------------ */
$_LANG['sys_title']                   = 'LengdoFrame';
$_LANG['sys_kernel']                  = 'LengdoFrame - 20100111';


/* ------------------------------------------------------ */
// - 字符消息
/* ------------------------------------------------------ */
$_LANG['str_login']                   = '登陆系统';
$_LANG['str_logout']                  = '退出系统';

$_LANG['str_role_no']                 = '无角色';
$_LANG['str_role_nopriv']             = '指定的角色无权限';


/* ------------------------------------------------------ */
// - 常规消息
/* ------------------------------------------------------ */
$_LANG['msg_ok']                      = '成功！';
$_LANG['msg_fail']                    = '失败！';

$_LANG['warn_role_dels']              = '警告！删除该角色将导致下级角色的删除！';
$_LANG['warn_module_dels']            = '请先删除子模块！';


/* ------------------------------------------------------ */
// - 非法消息
/* ------------------------------------------------------ */
$_LANG['lawless_act']                 = '非法进入，您不具有该模块的操作权限!';
$_LANG['lawless_submit']              = '非法提交，参数无效!';


/* ------------------------------------------------------ */
// - 成功消息
/* ------------------------------------------------------ */
$_LANG['ok_logout']                   = '注销成功！';

$_LANG['ok_dbbackup']                 = '备份完成！';
$_LANG['ok_dbbackup_import']          = '数据导入成功！';
$_LANG['ok_dbbackup_importing']       = '数据导入中...';

$_LANG['ok_myaccount_upassword']      = '您的密码已更新成功！';


/* ------------------------------------------------------ */
// - 失败消息
/* ------------------------------------------------------ */
$_LANG['fail_del']                    = '删除失败！';
$_LANG['fail_login']                  = '您的用户名或者密码错误，请重新填写！';

$_LANG['fail_pfile_create']           = '无法创建权限文件，请检查权限文件(夹)权限！';

$_LANG['fail_dbbackup_fdno']          = '备份文件夹不存在, 请创建...';
$_LANG['fail_dbbackup_fdpriv']        = '备份文件夹权限夹不足！';
$_LANG['fail_dbbackup_import']        = 'SQL导入失败！';
$_LANG['fail_dbbackup_write']         = '备份失败：文件写入失败！';
$_LANG['fail_dbbackup_position']      = '备份失败：数据表位置文件读取失败！';

$_LANG['fail_plugin_pfile_unfind']    = '无法找到插件文件';
$_LANG['fail_plugin_pfile_unwrite']   = '无权读取插件文件';
$_LANG['fail_plugin_ifile_unfind']    = '无法找到安装文件';
$_LANG['fail_plugin_ifile_unallow']   = '不允许的安装文件';
$_LANG['fail_plugin_ifile_unwrite']   = '无权读写安装文件';
$_LANG['fail_plugin_config_unfind']   = '无法找到插件的安装配置';


/* ------------------------------------------------------ */
// - 必填消息
/* ------------------------------------------------------ */
$_LANG['fill_login_usr']              = '请填写用户名！';
$_LANG['fill_login_pwd']              = '请填写密码！';

$_LANG['fill_admin_usr']              = '请填写管理员帐号！';
$_LANG['fill_admin_pwd']              = '请填写管理员密码！';
$_LANG['fill_admin_name']             = '请填写管理员姓名！';
$_LANG['fill_admin_role']             = '您无可分配的角色！';
$_LANG['fill_admin_exist']            = '管理员帐号已经存在！';

$_LANG['fill_role_name']              = '请填写角色名称！';

$_LANG['fill_module_name']            = '请填写模块名称！';
$_LANG['fill_module_file']            = '请填写模块处理文件！';
$_LANG['fill_module_exist']           = '模块重复，请重先填写模块处理文件！';

$_LANG['fill_privilege_name']         = '请填写权限名称！';
$_LANG['fill_privilege_aname']        = '请填写操作名称！';
$_LANG['fill_privilege_acode']        = '请填写操作代码！';
$_LANG['fill_privilege_exist']        = '权限重复，请重先填写操作代码！';
$_LANG['fill_privilege_module']       = '请选择模块！';

$_LANG['fill_myaccount_repwd']        = '重复密码错误，请重新填写！';

$_LANG['fill_dbbackup_sqlfile']       = '请上传SQL文件！';


/* ------------------------------------------------------ */
// - 动态消息
/* ------------------------------------------------------ */
$_LANG['spr_confirm_del']             = '确认删除 "%s" ？';

$_LANG['spr_dbbackup_ok']             = '备份完成, 共 %d 卷！';
$_LANG['spr_dbbackup_ok_part']        = '数据文件 #%d 创建成功，程序将自动备份下一卷...';
$_LANG['spr_dbbackup_import_part']    = '正在导入第 <font style="color:#f00">%d</font> / %d 卷数据...';


/* ------------------------------------------------------ */
// - 操作消息
/* ------------------------------------------------------ */
$_LANG['act_priv']                    = '权限';
$_LANG['act_umove']                   = '上移';
$_LANG['act_dmove']                   = '下移';
$_LANG['act_import']                  = '导入';
$_LANG['act_export']                  = '导出';
$_LANG['act_download']                = '下载';
$_LANG['act_export_all']              = '导出全部记录';
$_LANG['act_export_page']             = '导出当页记录';
$_LANG['act_export_choice']           = '导出选中记录';


/* ------------------------------------------------------ */
// - 下拉列表消息
/* ------------------------------------------------------ */
$_LANG['ddl_sel']                     = '请选择..';
$_LANG['ddl_all_module']              = '所有模块';
$_LANG['ddl_top_module']              = '顶级模块';


/* ------------------------------------------------------ */
// - 其他消息
/* ------------------------------------------------------ */
$_LANG['file_unread']                 = '不可读';
$_LANG['file_unedit']                 = '不可改';
$_LANG['file_unwrite']                = '不可写';

$_LANG['file_del_ok']                 = '文件删除成功';
$_LANG['file_mov_fail']               = '文件移动失败';
$_LANG['file_ext_error']              = '文件格式错误';
?>