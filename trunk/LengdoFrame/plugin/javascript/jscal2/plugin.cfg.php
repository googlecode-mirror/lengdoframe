<?php
/* 初始插件配置 */
$_PLUGIN_CFG = array();


/* 设置插件配置 - 插件版本 */
$_PLUGIN_CFG['ver']      = 'v1.7.0';

/* 设置插件配置 - 插件标题 */
$_PLUGIN_CFG['title']    = 'JSCal2 时间选择器';

/* 设置插件配置 - 插件安装 */
$_PLUGIN_CFG['install']   = array();
$_PLUGIN_CFG['install'][] = array('fpath'=>$_CFG['DIR_ADMIN'].'script/system.js', 'src'=>'js/jscal2.js' , 'type'=>'JS LOAD JS');
$_PLUGIN_CFG['install'][] = array('fpath'=>$_CFG['DIR_ADMIN'].'script/system.js', 'src'=>'js/lang/cn.js' , 'type'=>'JS LOAD JS');
$_PLUGIN_CFG['install'][] = array('fpath'=>$_CFG['DIR_ADMIN'].'script/system.js', 'src'=>'css/jscal2.css' , 'type'=>'JS LOAD CSS');


/* 返回插件配置 */
return $_PLUGIN_CFG;
?>
功能说明：
    JSCal2时间选择器


使用说明：
    1. 拷贝 所有文件 到 /plugin/javascript/jscal2/ 文件夹下并安装
    2. 在程序中调用 Calendar 对象的成员函数使用